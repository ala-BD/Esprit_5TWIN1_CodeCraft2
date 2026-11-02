<?php

namespace Tests\Feature\Logistique;

use App\Http\Controllers\Admin\LogistiqueController;
use App\Models\Adresse;
use App\Models\Commande;
use App\Models\DonVetement;
use App\Models\Mission;
use App\Models\Tournee;
use App\Models\User;
use App\Notifications\MissionsAffecteesNotification;
use App\Services\Logistique\AffectationAutomatique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AffectationAutomatiqueTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::preventStrayRequests();
        config([
            'assistant.api_key' => null,
            'logistique.depot'  => ['nom' => 'Dépôt', 'latitude' => 36.80, 'longitude' => 10.20, 'retour' => true],
        ]);

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    private function collecteur(string $prenom): User
    {
        return User::factory()->create(['prenom' => $prenom, 'role' => User::ROLE_COLLECTEUR]);
    }

    private function commande(float $lat, float $lng, string $ville = 'Tunis'): Commande
    {
        $client = User::factory()->create(['role' => User::ROLE_CLIENT]);
        $adresse = Adresse::create(['user_id' => $client->id, 'libelle' => 'Domicile', 'rue' => "Rue {$lat}", 'code_postal' => '1000',
            'ville' => $ville, 'gouvernorat' => 'Tunis', 'latitude' => $lat, 'longitude' => $lng, 'par_defaut' => true]);

        return Commande::create(['user_id' => $client->id, 'adresse_id' => $adresse->id, 'numero' => 'TC-' . uniqid(), 'statut' => Commande::STATUT_CONFIRMEE]);
    }

    private function don(?float $lat, ?float $lng): DonVetement
    {
        $donateur = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        if ($lat !== null) {
            Adresse::create(['user_id' => $donateur->id, 'libelle' => 'Domicile', 'rue' => 'Rue', 'code_postal' => '2000',
                'ville' => 'Ariana', 'gouvernorat' => 'Ariana', 'latitude' => $lat, 'longitude' => $lng, 'par_defaut' => true]);
        }

        return DonVetement::create(['user_id' => $donateur->id, 'type' => 'Veste', 'matiere' => 'Laine', 'taille' => 'M',
            'etat' => 'BON', 'statut' => 'DEPOSE', 'date_depot' => today()]);
    }

    private function proposer(?string $date = null): array
    {
        return app(AffectationAutomatique::class)->proposer(\Illuminate\Support\Carbon::parse($date ?? today()->addDay()));
    }

    public function test_a_job_on_the_way_of_an_existing_tour_completes_it(): void
    {
        $sud  = $this->collecteur('Sami');
        $nord = $this->collecteur('Nour');

        // Sami a déjà une tournée demain au sud : deux arrêts à 36.70 / 36.72
        $tournee = Tournee::create(['user_id' => $sud->id, 'date' => today()->addDay(), 'zone' => 'Sud', 'vehicule' => 'Fourgon', 'statut' => Tournee::STATUT_PLANIFIEE]);
        foreach ([36.70, 36.72] as $i => $lat) {
            $tournee->missions()->create(['type' => 'LIVRAISON', 'adresse' => "Sud {$i}", 'ordre' => $i + 1, 'heure_prevue' => '09:00',
                'statut' => 'A_FAIRE', 'latitude' => $lat, 'longitude' => 10.20]);
        }

        $surLeChemin = $this->commande(36.71, 10.20);

        $proposition = $this->proposer();

        $this->assertSame(1, $proposition['total']);
        $plan = $proposition['plans']->first();
        $this->assertTrue($plan['collecteur']->is($sud));
        $this->assertFalse($plan['nouvelle']);
        $this->assertSame('commande-' . $surLeChemin->id, $plan['ajouts']->first()['cle']);
        $this->assertLessThan(0.5, $plan['km_ajoutes']);   // déjà sur sa route : presque aucun détour
    }

    public function test_distant_clusters_go_to_different_collecteurs_and_new_tours_are_named_after_their_towns(): void
    {
        $this->collecteur('Sami');
        $this->collecteur('Nour');
        foreach ([36.95, 36.96, 36.97] as $lat) {
            $this->commande($lat, 10.20, 'Gammarth');
        }
        foreach ([36.60, 36.61, 36.62] as $lat) {
            $this->commande($lat, 10.20, 'Hammam Lif');
        }

        $proposition = $this->proposer();

        $this->assertSame(6, $proposition['total']);
        $this->assertCount(2, $proposition['plans']);
        $this->assertEqualsCanonicalizing(['Gammarth', 'Hammam Lif'], $proposition['plans']->pluck('zone')->all());
        $proposition['plans']->each(fn ($plan) => $this->assertCount(1, $plan['ajouts']->pluck('ville')->unique()));
    }

    public function test_tour_capacity_is_respected_and_what_cannot_be_placed_is_explained(): void
    {
        config(['logistique.affectation.arrets_max' => 2]);
        $this->collecteur('Seul');
        foreach ([36.81, 36.82, 36.83] as $lat) {
            $this->commande($lat, 10.20);
        }
        $this->don(null, null);   // donateur sans adresse

        $proposition = $this->proposer();

        $this->assertSame(2, $proposition['total']);
        $raisons = $proposition['non_affectes']->pluck('raison');
        $this->assertCount(2, $raisons);
        $this->assertTrue($raisons->contains(fn ($r) => str_contains($r, 'pleines')));
        $this->assertTrue($raisons->contains(fn ($r) => str_contains($r, 'sans position')));
    }

    public function test_without_collecteurs_nothing_is_assigned(): void
    {
        $this->commande(36.81, 10.20);

        $proposition = $this->proposer();

        $this->assertSame(0, $proposition['total']);
        $this->assertSame('Aucun collecteur actif', $proposition['non_affectes']->first()['raison']);
    }

    public function test_applying_creates_located_optimised_tours_and_notifies_the_collecteurs(): void
    {
        $collecteur = $this->collecteur('Sami');
        $loin   = $this->commande(36.86, 10.20);
        $proche = $this->commande(36.82, 10.20);
        $don    = $this->don(36.84, 10.20);

        $this->actingAs($this->admin)->get('/admin/logistique/affectation')->assertOk()->assertSee('Répartition automatique');

        $empreinte = $this->proposer()['empreinte'];
        $this->actingAs($this->admin)
            ->post('/admin/logistique/affectation', ['empreinte' => $empreinte, 'date' => today()->addDay()->format('Y-m-d')])
            ->assertRedirect()
            ->assertSessionHas('success');

        $tournee = Tournee::where('user_id', $collecteur->id)->firstOrFail();
        $this->assertSame(today()->addDay()->format('Y-m-d'), $tournee->date->format('Y-m-d'));
        $this->assertSame(3, $tournee->missions()->count());

        // Ordre optimisé depuis le dépôt (36.80) : le plus proche d'abord
        $ordre = $tournee->missions()->get()->map(fn (Mission $m) => $m->commande_id ?? 'don-' . $m->don_vetement_id)->all();
        $this->assertSame([$proche->id, 'don-' . $don->id, $loin->id], $ordre);
        $this->assertTrue($tournee->missions->every->estLocalisee());
        $this->assertNotNull($tournee->itineraire_ia);

        // Les travaux ne sont plus à planifier, et le collecteur est prévenu
        $restant = LogistiqueController::aPlanifier();
        $this->assertCount(0, $restant['commandes']);
        $this->assertCount(0, $restant['dons']);
        Notification::assertSentTo($collecteur, MissionsAffecteesNotification::class);
    }

    public function test_a_stale_proposal_is_not_applied(): void
    {
        $this->collecteur('Sami');
        $this->commande(36.82, 10.20);
        $empreinte = $this->proposer()['empreinte'];

        $this->commande(36.83, 10.20);   // un nouveau travail arrive entre l'aperçu et le clic

        $this->actingAs($this->admin)->post('/admin/logistique/affectation', ['empreinte' => $empreinte])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame(0, Tournee::count());
    }

    public function test_only_the_admin_can_assign(): void
    {
        $collecteur = $this->collecteur('Sami');

        $this->actingAs($collecteur)->get('/admin/logistique/affectation')->assertForbidden();
        $this->actingAs($collecteur)->post('/admin/logistique/affectation', ['empreinte' => str_repeat('a', 32)])->assertForbidden();
    }
}
