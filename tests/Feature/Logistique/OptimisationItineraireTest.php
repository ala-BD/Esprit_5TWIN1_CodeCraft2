<?php

namespace Tests\Feature\Logistique;

use App\Models\Mission;
use App\Models\Tournee;
use App\Models\User;
use App\Services\Logistique\OptimiseurItineraire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OptimisationItineraireTest extends TestCase
{
    use RefreshDatabase;

    private User $collecteur;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config([
            'assistant.api_key' => null,                                   // explication sans IA par défaut
            'logistique.depot'  => ['nom' => 'Dépôt', 'latitude' => 36.80, 'longitude' => 10.00, 'retour' => false],
            'logistique.itineraire.facteur_detour' => 1.0,
        ]);

        $this->collecteur = User::factory()->create(['role' => User::ROLE_COLLECTEUR]);
    }

    private function tournee(string $date = '2026-11-10'): Tournee
    {
        return Tournee::create([
            'user_id' => $this->collecteur->id, 'date' => $date, 'zone' => 'Test',
            'vehicule' => 'Fourgon', 'statut' => Tournee::STATUT_PLANIFIEE,
        ]);
    }

    /** Arrêts alignés vers l'est, à 0,01° d'écart : l'ordre optimal est l'ordre des longitudes */
    private function arret(Tournee $tournee, int $ordre, float $longitude, array $extra = []): Mission
    {
        return $tournee->missions()->create(array_merge([
            'type' => Mission::TYPE_LIVRAISON, 'adresse' => "Arrêt {$longitude}", 'ordre' => $ordre,
            'heure_prevue' => '09:00', 'statut' => Mission::STATUT_A_FAIRE,
            'latitude' => 36.80, 'longitude' => $longitude,
        ], $extra));
    }

    private function optimiseur(): OptimiseurItineraire
    {
        return app(OptimiseurItineraire::class);
    }

    /*
    |------------------------------------------------------------------
    | Algorithme
    |------------------------------------------------------------------
    */

    public function test_stops_entered_in_a_zigzag_are_put_back_in_order(): void
    {
        $tournee = $this->tournee();
        foreach ([10.05, 10.01, 10.04, 10.02, 10.03] as $i => $lng) {
            $this->arret($tournee, $i + 1, $lng);
        }

        $proposition = $this->optimiseur()->proposer($tournee);

        $this->assertSame([10.01, 10.02, 10.03, 10.04, 10.05], $proposition['etapes']->map(fn ($e) => $e['mission']->longitude)->all());
        $this->assertLessThan($proposition['distance_avant'], $proposition['distance_apres']);
        $this->assertGreaterThan(0, $proposition['gain_pct']);
    }

    public function test_two_opt_removes_a_crossing_left_by_nearest_neighbour(): void
    {
        // Carré : le plus proche voisin depuis le coin produit un trajet qui se croise si on le laisse faire
        $tournee = $this->tournee();
        $coins = [[36.80, 10.01], [36.81, 10.02], [36.80, 10.02], [36.81, 10.01]];
        foreach ($coins as $i => [$lat, $lng]) {
            $this->arret($tournee, $i + 1, $lng, ['latitude' => $lat]);
        }

        $proposition = $this->optimiseur()->proposer($tournee);

        // Aucun chemin plus court que le résultat parmi toutes les permutations
        $missions = $proposition['etapes']->pluck('mission');
        $depot = $this->optimiseur()->depot();
        $meilleur = PHP_FLOAT_MAX;
        foreach ($this->permutations($missions->all()) as $perm) {
            $meilleur = min($meilleur, $this->optimiseur()->longueur($depot, collect($perm), null));
        }
        $this->assertEqualsWithDelta($meilleur, $this->optimiseur()->longueur($depot, $missions, null), 0.001);
    }

    public function test_done_stops_stay_put_and_the_route_restarts_from_the_last_one(): void
    {
        $tournee = $this->tournee();
        $fait = $this->arret($tournee, 1, 10.05, ['statut' => Mission::STATUT_TERMINEE]);
        $this->arret($tournee, 2, 10.01);
        $this->arret($tournee, 3, 10.04);

        $proposition = $this->optimiseur()->proposer($tournee);

        $this->assertTrue($proposition['figees']->contains($fait));
        $this->assertSame(10.05, $proposition['depart']['longitude']);
        // Depuis 10.05, on va d'abord au plus proche (10.04)
        $this->assertSame([10.04, 10.01], $proposition['etapes']->map(fn ($e) => $e['mission']->longitude)->all());
    }

    public function test_the_stop_in_progress_stays_first_and_unlocated_stops_go_last(): void
    {
        $tournee = $this->tournee();
        $this->arret($tournee, 1, 10.01);
        $enCours = $this->arret($tournee, 2, 10.09, ['statut' => Mission::STATUT_EN_COURS]);
        $perdue  = $this->arret($tournee, 3, 0, ['adresse' => 'Inconnue', 'latitude' => null, 'longitude' => null]);

        $proposition = $this->optimiseur()->proposer($tournee);

        $this->assertTrue($proposition['etapes']->first()['mission']->is($enCours));
        $this->assertTrue($proposition['non_localisees']->contains($perdue));

        $this->optimiseur()->appliquer($tournee, $proposition);
        $this->assertSame(3, $perdue->fresh()->ordre);
    }

    public function test_arrival_times_follow_distance_and_stop_duration(): void
    {
        config(['logistique.itineraire.vitesse_kmh' => 60, 'logistique.itineraire.minutes_par_arret' => 10]);
        $tournee = $this->tournee();
        $this->arret($tournee, 1, 10.01, ['heure_prevue' => '09:00']);
        $this->arret($tournee, 2, 10.02, ['heure_prevue' => '09:00']);

        $proposition = $this->optimiseur()->proposer($tournee);
        $etapes = $proposition['etapes'];

        // Départ 15 min avant le premier rendez-vous ; 0,01° de longitude ≈ 0,89 km, soit 1 min à 60 km/h
        $this->assertSame('08:45', $proposition['heure_depart']->format('H:i'));
        $this->assertSame('08:50', $etapes[0]['arrivee']->format('H:i'));   // 08:45 + 1 min, arrondi aux 5 min supérieures
        $this->assertSame('09:05', $etapes[1]['arrivee']->format('H:i'));   // 08:50 + 10 min d'arrêt + 1 min, arrondi
    }

    public function test_a_tour_of_today_already_started_departs_now(): void
    {
        Carbon::setTestNow('2026-11-10 13:02:00');
        $tournee = $this->tournee('2026-11-10');
        $this->arret($tournee, 1, 10.01, ['statut' => Mission::STATUT_TERMINEE]);
        $this->arret($tournee, 2, 10.02);

        $this->assertSame('13:05', $this->optimiseur()->proposer($tournee)['heure_depart']->format('H:i'));

        Carbon::setTestNow();
    }

    /*
    |------------------------------------------------------------------
    | Pages et application
    |------------------------------------------------------------------
    */

    public function test_the_preview_changes_nothing_and_applying_saves_order_times_and_distance(): void
    {
        $tournee = $this->tournee();
        $loin  = $this->arret($tournee, 1, 10.05);
        $pres  = $this->arret($tournee, 2, 10.01);

        $this->actingAs($this->collecteur)->get("/logistique/tournees/{$tournee->id}/optimisation")
            ->assertOk()
            ->assertSee('Itinéraire optimisé')
            ->assertSee('raccourcit le trajet');   // pas d'IA configurée : résumé calculé
        $this->assertSame(1, $loin->fresh()->ordre);

        $empreinte = $this->optimiseur()->proposer($tournee)['empreinte'];
        $this->actingAs($this->collecteur)->post("/logistique/tournees/{$tournee->id}/optimisation", ['empreinte' => $empreinte])
            ->assertRedirect("/logistique/tournees/{$tournee->id}")
            ->assertSessionHas('success');

        $this->assertSame(1, $pres->fresh()->ordre);
        $this->assertSame(2, $loin->fresh()->ordre);

        $tournee->refresh();
        $this->assertNotNull($tournee->distance_km);
        $resume = json_decode($tournee->itineraire_ia, true);
        $this->assertSame([$pres->id, $loin->id], $resume['ordre']);
        $this->assertSame('plus proche voisin + 2-opt', $resume['methode']);
    }

    public function test_applying_a_stale_preview_shows_the_new_proposal_instead(): void
    {
        $tournee = $this->tournee();
        $this->arret($tournee, 1, 10.05);
        $this->arret($tournee, 2, 10.01);
        $empreinte = $this->optimiseur()->proposer($tournee)['empreinte'];

        $this->arret($tournee, 3, 10.03);   // la tournée change entre l'aperçu et le clic

        $this->actingAs($this->collecteur)->post("/logistique/tournees/{$tournee->id}/optimisation", ['empreinte' => $empreinte])
            ->assertRedirect("/logistique/tournees/{$tournee->id}/optimisation")
            ->assertSessionHas('error');
        $this->assertNull($tournee->fresh()->itineraire_ia);
    }

    public function test_the_ai_explains_the_route_when_configured(): void
    {
        config(['assistant.api_key' => 'cle', 'assistant.base_url' => 'https://ia.test/v1']);
        Http::fake(['ia.test/*' => Http::response(['choices' => [['message' => ['content' => 'Vous gagnez 40 % en suivant les arrêts d\'ouest en est.']]]])]);

        $tournee = $this->tournee();
        $this->arret($tournee, 1, 10.05);
        $this->arret($tournee, 2, 10.01);

        $this->actingAs($this->collecteur)->get("/logistique/tournees/{$tournee->id}/optimisation")
            ->assertSee('Vous gagnez 40 %', false);

        Http::assertSent(fn ($requete) => str_contains($requete['messages'][1]['content'], 'Plus long trajet')
            && str_contains($requete['messages'][0]['content'], 'Ne décris ni la géographie'));
    }

    public function test_only_the_owner_can_optimise_and_an_unlocated_tour_is_refused(): void
    {
        $tournee = $this->tournee();
        $this->arret($tournee, 1, 0, ['latitude' => null, 'longitude' => null]);

        $this->actingAs(User::factory()->create(['role' => User::ROLE_COLLECTEUR]))
            ->get("/logistique/tournees/{$tournee->id}/optimisation")->assertForbidden();

        $this->actingAs($this->collecteur)->get("/logistique/tournees/{$tournee->id}/optimisation")
            ->assertRedirect("/logistique/tournees/{$tournee->id}")
            ->assertSessionHas('error');
    }

    private function permutations(array $elements): \Generator
    {
        if (count($elements) <= 1) {
            yield $elements;

            return;
        }
        foreach ($elements as $i => $element) {
            $reste = $elements;
            unset($reste[$i]);
            foreach ($this->permutations(array_values($reste)) as $perm) {
                yield [$element, ...$perm];
            }
        }
    }
}
