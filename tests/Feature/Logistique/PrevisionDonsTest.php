<?php

namespace Tests\Feature\Logistique;

use App\Models\Adresse;
use App\Models\User;
use App\Services\Logistique\PrevisionDons;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PrevisionDonsTest extends TestCase
{
    use RefreshDatabase;

    /** Lundi de la semaine « en cours » des tests */
    private Carbon $lundi;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        config(['assistant.api_key' => null]);
        Carbon::setTestNow('2026-11-04 10:00:00');   // un mercredi
        $this->lundi = Carbon::parse('2026-11-02');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function donateur(?string $gouvernorat): User
    {
        $user = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        if ($gouvernorat) {
            Adresse::create(['user_id' => $user->id, 'libelle' => 'Domicile', 'rue' => 'Rue', 'code_postal' => '1000',
                'ville' => $gouvernorat, 'gouvernorat' => $gouvernorat, 'latitude' => 36.8, 'longitude' => 10.1, 'par_defaut' => true]);
        }

        return $user;
    }

    /** Insère, pour chaque semaine passée (la plus ancienne d'abord), le nombre de dons donné */
    private function historique(array $parSemaine, ?User $donateur = null): void
    {
        $donateur ??= $this->donateur('Tunis');
        $lignes = [];
        $n = count($parSemaine);

        foreach ($parSemaine as $i => $nombre) {
            $jour = $this->lundi->copy()->subWeeks($n - $i)->addDays(2);
            for ($k = 0; $k < $nombre; $k++) {
                $lignes[] = ['user_id' => $donateur->id, 'type' => 'Veste', 'matiere' => 'Laine', 'taille' => 'M', 'etat' => 'BON',
                    'statut' => 'RECYCLE', 'date_depot' => $jour->format('Y-m-d'), 'created_at' => $jour, 'updated_at' => $jour];
            }
        }

        foreach (array_chunk($lignes, 300) as $paquet) {
            DB::table('don_vetements')->insert($paquet);
        }
    }

    private function prevoir(): array
    {
        return app(PrevisionDons::class)->prevoir(8);
    }

    public function test_a_flat_history_gives_a_flat_forecast(): void
    {
        $this->historique(array_fill(0, 20, 10));

        $p = $this->prevoir();

        $this->assertSame(20, $p['semaines_historique']);
        $this->assertFalse($p['saisonnalite']);
        $this->assertEqualsWithDelta(10, $p['total']['prevision'][0]['valeur'], 0.5);
        $this->assertEqualsWithDelta(10, $p['total']['prevision'][7]['valeur'], 0.5);
        $this->assertSame(0, $p['total']['tendance_pct']);
        $this->assertSame(0, (int) $p['total']['erreur_pct']);   // série parfaite : aucune erreur au backtest
    }

    public function test_steady_growth_is_continued(): void
    {
        $this->historique(range(5, 43, 2));   // +2 dons par semaine pendant 20 semaines

        $prevision = $this->prevoir()['total']['prevision'];

        $this->assertGreaterThan(43, $prevision[0]['valeur']);
        $this->assertGreaterThan($prevision[0]['valeur'] + 10, $prevision[7]['valeur']);
    }

    public function test_last_years_peak_is_expected_again(): void
    {
        // Deux ans d'historique, stables à 20 dons, sauf un pic à 40 il y a un an tout juste (semaines 45 et 46)
        $semaines = [];
        for ($i = 0; $i < 104; $i++) {
            $semaine = $this->lundi->copy()->subWeeks(104 - $i);
            $semaines[] = in_array($semaine->isoWeek(), [45, 46], true) ? 40 : 20;
        }
        $this->historique($semaines);

        $p = $this->prevoir();
        $parSemaine = collect($p['total']['prevision'])->keyBy(fn ($x) => Carbon::parse($x['semaine'])->isoWeek());

        $this->assertTrue($p['saisonnalite']);
        // Semaine 45 (celle du pic l'an dernier) nettement au-dessus de la semaine 49, ordinaire
        $this->assertGreaterThan($parSemaine[49]['valeur'] * 1.3, $parSemaine[45]['valeur']);
    }

    public function test_the_uncertainty_band_widens_with_the_horizon_and_stays_positive(): void
    {
        $this->historique([3, 9, 2, 8, 4, 10, 1, 7, 5, 9, 2, 8, 3, 9, 4, 8]);

        $prevision = $this->prevoir()['total']['prevision'];

        $this->assertGreaterThan($prevision[0]['haut'] - $prevision[0]['bas'], $prevision[7]['haut'] - $prevision[7]['bas']);
        foreach ($prevision as $point) {
            $this->assertGreaterThanOrEqual(0, $point['bas']);
            $this->assertGreaterThanOrEqual($point['valeur'], $point['haut']);
        }
    }

    public function test_donations_are_grouped_by_the_donors_governorate_and_this_week_is_left_out(): void
    {
        $this->historique(array_fill(0, 6, 4), $this->donateur('Ariana'));
        $this->historique(array_fill(0, 6, 2), $this->donateur('Manouba'));
        $this->historique(array_fill(0, 6, 1), $this->donateur(null));
        // Un don de la semaine en cours : pas encore compté
        DB::table('don_vetements')->insert(['user_id' => User::first()->id, 'type' => 'X', 'matiere' => 'X', 'taille' => 'M', 'etat' => 'BON',
            'statut' => 'DEPOSE', 'date_depot' => '2026-11-03', 'created_at' => now(), 'updated_at' => now()]);

        $p = $this->prevoir();
        $zones = $p['zones']->keyBy('zone');

        $this->assertEqualsCanonicalizing(['Ariana', 'Manouba', 'Non renseigné'], $zones->keys()->all());
        $this->assertSame(4.0, $zones['Ariana']['moyenne_4']);
        $this->assertSame(7.0, $p['total']['moyenne_4']);
        $this->assertSame('2026-11-02', $p['debut_prevision']->format('Y-m-d'));
        $this->assertNull($zones['Ariana']['erreur_pct']);   // 6 semaines : trop court pour un backtest
    }

    public function test_the_page_shows_the_forecast_and_a_computed_briefing_without_ai(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        User::factory()->count(2)->create(['role' => User::ROLE_COLLECTEUR]);
        $this->historique(array_fill(0, 20, 24));

        $this->actingAs($admin)->get('/admin/logistique/previsions')
            ->assertOk()
            ->assertSee('24 dons')
            ->assertSee('2 journées')           // 24 arrêts / 12 par journée
            ->assertSee('sur 10 disponibles')   // 2 collecteurs × 5 jours
            ->assertSee('Environ 24 dons attendus cette semaine');
    }

    public function test_the_ai_writes_the_briefing_from_the_numbers_when_configured(): void
    {
        config(['assistant.api_key' => 'cle', 'assistant.base_url' => 'https://ia.test/v1']);
        Http::fake(['ia.test/*' => Http::response(['choices' => [['message' => ['content' => 'Semaine calme, 24 dons attendus.']]]])]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->historique(array_fill(0, 20, 24));

        $this->actingAs($admin)->get('/admin/logistique/previsions')->assertSee('Semaine calme, 24 dons attendus.');

        Http::assertSent(fn ($requete) => str_contains($requete['messages'][1]['content'], 'Hypothèse haute')
            && str_contains($requete['messages'][0]['content'], "n'invente aucune cause"));
    }

    public function test_without_history_the_page_explains_why_and_only_admins_can_see_it(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get('/admin/logistique/previsions')->assertOk()->assertSee("Pas encore assez d'historique", false);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_COLLECTEUR]))->get('/admin/logistique/previsions')->assertForbidden();
    }
}
