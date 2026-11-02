<?php

namespace Tests\Feature\Logistique;

use App\Http\Controllers\Admin\LogistiqueController;
use App\Models\Adresse;
use App\Models\Commande;
use App\Models\DonVetement;
use App\Models\Mission;
use App\Models\Tournee;
use App\Models\User;
use App\Notifications\CommandeStatutNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LocalisationEtSuiviTest extends TestCase
{
    use RefreshDatabase;

    private User $collecteur;

    private Tournee $tournee;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Http::preventStrayRequests();

        $this->collecteur = User::factory()->create(['prenom' => 'Youssef', 'name' => 'Trabelsi', 'role' => User::ROLE_COLLECTEUR]);
        $this->tournee = Tournee::create([
            'user_id' => $this->collecteur->id, 'date' => today(), 'zone' => 'Tunis Centre',
            'vehicule' => 'Fourgon', 'statut' => Tournee::STATUT_EN_COURS,
        ]);
    }

    private function adresse(User $user, float $lat = 36.8105, float $lng = 10.1830): Adresse
    {
        return Adresse::create([
            'user_id' => $user->id, 'libelle' => 'Domicile', 'rue' => '30 Avenue de la Liberté',
            'code_postal' => '1002', 'ville' => 'Lafayette', 'gouvernorat' => 'Tunis',
            'latitude' => $lat, 'longitude' => $lng, 'par_defaut' => true,
        ]);
    }

    private function commande(string $statut = Commande::STATUT_CONFIRMEE): Commande
    {
        $client = User::factory()->create(['role' => User::ROLE_CLIENT]);

        return Commande::create([
            'user_id' => $client->id, 'adresse_id' => $this->adresse($client)->id,
            'numero' => 'TC-TEST-' . uniqid(), 'statut' => $statut, 'montant_total' => 32,
        ]);
    }

    private function don(): DonVetement
    {
        $donateur = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $this->adresse($donateur, 36.8625, 10.1956);

        return DonVetement::create([
            'user_id' => $donateur->id, 'type' => 'Veste', 'matiere' => 'Laine', 'taille' => 'M',
            'etat' => 'BON', 'statut' => 'DEPOSE', 'date_depot' => today(),
        ]);
    }

    private function mission(array $attributs): Mission
    {
        return $this->tournee->missions()->create(array_merge([
            'type' => Mission::TYPE_LIVRAISON, 'ordre' => 1, 'heure_prevue' => '09:00', 'statut' => Mission::STATUT_A_FAIRE,
        ], $attributs));
    }

    /*
    |------------------------------------------------------------------
    | Localisation
    |------------------------------------------------------------------
    */

    public function test_a_delivery_takes_the_address_and_position_of_its_order(): void
    {
        $commande = $this->commande();

        $mission = $this->mission(['commande_id' => $commande->id]);

        $this->assertSame('30 Avenue de la Liberté, 1002 Lafayette', $mission->adresse);
        $this->assertSame([36.8105, 10.183], [$mission->latitude, $mission->longitude]);
        $this->assertSame('COMMANDE', $mission->source_localisation);
    }

    public function test_a_pickup_takes_the_donors_default_address(): void
    {
        $mission = $this->mission(['type' => Mission::TYPE_COLLECTE, 'don_vetement_id' => $this->don()->id]);

        $this->assertSame([36.8625, 10.1956], [$mission->latitude, $mission->longitude]);
        $this->assertSame('DONATEUR', $mission->source_localisation);
    }

    public function test_a_written_address_is_geocoded_once_then_cached(): void
    {
        config(['logistique.geocodage.actif' => true]);
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([['lat' => '36.8008', 'lon' => '10.1815']])]);

        $premiere = $this->mission(['adresse' => '12 Rue de Marseille, Tunis']);
        $seconde  = $this->mission(['adresse' => '12  rue de marseille, Tunis', 'ordre' => 2]);

        $this->assertSame([36.8008, 10.1815], [$premiere->latitude, $premiere->longitude]);
        $this->assertSame('GEOCODAGE', $premiere->source_localisation);
        $this->assertSame([36.8008, 10.1815], [$seconde->latitude, $seconde->longitude]);

        Http::assertSentCount(1);
        Http::assertSent(fn ($requete) => $requete['countrycodes'] === 'tn' && $requete->hasHeader('User-Agent'));
    }

    public function test_an_address_that_cannot_be_found_leaves_the_stop_unlocated(): void
    {
        config(['logistique.geocodage.actif' => true]);
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

        $mission = $this->mission(['adresse' => 'Quelque part']);

        $this->assertFalse($mission->estLocalisee());
        $this->assertNull($mission->source_localisation);
    }

    public function test_explicit_coordinates_are_kept_and_changing_the_address_relocates(): void
    {
        $mission = $this->mission(['adresse' => 'Atelier Fil Vert', 'latitude' => 36.809, 'longitude' => 10.17]);
        $this->assertSame('MANUELLE', $mission->source_localisation);

        // Le statut change : la position n'est pas recalculée
        $mission->update(['statut' => Mission::STATUT_EN_COURS]);
        $this->assertSame(36.809, $mission->fresh()->latitude);

        // L'adresse change (géocodage désactivé ici) : l'ancienne position ne vaut plus
        $mission->update(['adresse' => 'Une autre adresse']);
        $this->assertFalse($mission->fresh()->estLocalisee());
    }

    /*
    |------------------------------------------------------------------
    | Synchronisation avec les commandes et les dons
    |------------------------------------------------------------------
    */

    public function test_a_delivery_ships_then_delivers_the_order_and_tells_the_client(): void
    {
        $commande = $this->commande();
        $mission  = $this->mission(['commande_id' => $commande->id]);

        $mission->update(['statut' => Mission::STATUT_EN_COURS]);
        $this->assertSame(Commande::STATUT_EXPEDIEE, $commande->fresh()->statut);

        $mission->update(['statut' => Mission::STATUT_TERMINEE, 'preuve_livraison' => 'Signé par le client']);
        $commande->refresh();

        $this->assertSame(Commande::STATUT_LIVREE, $commande->statut);
        $this->assertStringContainsString('Signé par le client', collect($commande->historique_statuts)->last()['commentaire']);
        $this->assertNotNull($mission->fresh()->terminee_le);
        Notification::assertSentToTimes($commande->user, CommandeStatutNotification::class, 2);
    }

    public function test_a_failed_delivery_is_noted_and_the_order_goes_back_to_planning(): void
    {
        $commande = $this->commande();
        $mission  = $this->mission(['commande_id' => $commande->id]);

        $this->assertFalse(LogistiqueController::aPlanifier()['commandes']->contains($commande));

        $mission->update(['statut' => Mission::STATUT_ECHOUEE, 'preuve_livraison' => 'Client absent']);
        $commande->refresh();

        $this->assertSame(Commande::STATUT_CONFIRMEE, $commande->statut);
        $this->assertStringContainsString('Client absent', collect($commande->historique_statuts)->last()['commentaire']);
        $this->assertTrue(LogistiqueController::aPlanifier()['commandes']->contains($commande));
    }

    public function test_a_delivered_or_cancelled_order_is_never_changed_back(): void
    {
        $commande = $this->commande(Commande::STATUT_ANNULEE);

        $this->mission(['commande_id' => $commande->id, 'statut' => Mission::STATUT_TERMINEE]);

        $this->assertSame(Commande::STATUT_ANNULEE, $commande->fresh()->statut);
        Notification::assertNothingSent();
    }

    public function test_a_completed_pickup_sends_the_donation_to_sorting(): void
    {
        $don     = $this->don();
        $mission = $this->mission(['type' => Mission::TYPE_COLLECTE, 'don_vetement_id' => $don->id]);

        $this->assertSame('DEPOSE', $don->fresh()->statut);

        $mission->update(['statut' => Mission::STATUT_TERMINEE]);

        $this->assertSame('EN_TRI', $don->fresh()->statut);
    }

    public function test_reopening_a_stop_clears_its_finish_time(): void
    {
        $mission = $this->mission(['adresse' => 'X', 'statut' => Mission::STATUT_TERMINEE]);
        $this->assertNotNull($mission->terminee_le);

        $mission->update(['statut' => Mission::STATUT_A_FAIRE]);

        $this->assertNull($mission->fresh()->terminee_le);
    }

    /*
    |------------------------------------------------------------------
    | Formulaire et validation
    |------------------------------------------------------------------
    */

    private function poster(array $donnees)
    {
        return $this->actingAs($this->collecteur)->post("/logistique/tournees/{$this->tournee->id}/missions", array_merge([
            'type' => Mission::TYPE_LIVRAISON, 'ordre' => 1, 'heure_prevue' => '09:00', 'statut' => Mission::STATUT_A_FAIRE,
        ], $donnees));
    }

    public function test_an_order_can_be_linked_without_typing_its_address(): void
    {
        $commande = $this->commande();

        $this->poster(['commande_id' => $commande->id, 'adresse' => ''])->assertSessionHasNoErrors();

        $this->assertSame($commande->id, Mission::firstOrFail()->commande_id);
        $this->assertSame('COMMANDE', Mission::firstOrFail()->source_localisation);
    }

    public function test_an_order_goes_on_a_delivery_and_a_donation_on_a_pickup(): void
    {
        $this->poster(['type' => Mission::TYPE_COLLECTE, 'commande_id' => $this->commande()->id])->assertSessionHasErrors('commande_id');
        $this->poster(['type' => Mission::TYPE_LIVRAISON, 'don_vetement_id' => $this->don()->id])->assertSessionHasErrors('don_vetement_id');

        $this->assertSame(0, Mission::count());
    }

    public function test_an_order_cannot_be_in_two_active_missions(): void
    {
        $commande = $this->commande();
        $premiere = $this->mission(['commande_id' => $commande->id]);

        $this->poster(['commande_id' => $commande->id])->assertSessionHasErrors('commande_id');

        // Après un échec, une nouvelle tentative peut être planifiée
        $premiere->update(['statut' => Mission::STATUT_ECHOUEE]);
        $this->poster(['commande_id' => $commande->id])->assertSessionHasNoErrors();
        $this->assertSame(2, Mission::count());
    }

    public function test_the_form_only_offers_orders_and_donations_still_to_plan(): void
    {
        $libre = $this->commande();
        $prise = $this->commande();
        $this->mission(['commande_id' => $prise->id]);
        $don = $this->don();

        $this->actingAs($this->collecteur)->get("/logistique/tournees/{$this->tournee->id}/missions/create")
            ->assertOk()
            ->assertViewHas('commandes', fn (array $options) => isset($options[$libre->id]) && !isset($options[$prise->id]))
            ->assertViewHas('dons', fn (array $options) => isset($options[$don->id]));
    }

    /*
    |------------------------------------------------------------------
    | Supervision par l'administrateur
    |------------------------------------------------------------------
    */

    public function test_admin_sees_every_tournee_and_what_is_left_to_plan(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $nonPlanifiee = $this->commande();
        $this->mission(['adresse' => 'Rue sans position']);

        $this->actingAs($admin)->get('/admin/logistique')
            ->assertOk()
            ->assertSee('Tunis Centre')
            ->assertSee($nonPlanifiee->numero)
            ->assertSee('sans position sur la carte');

        $this->actingAs($admin)->get("/admin/logistique/tournees/{$this->tournee->id}")
            ->assertOk()
            ->assertSee('Rue sans position');
    }

    public function test_only_the_admin_can_supervise(): void
    {
        $this->get('/admin/logistique')->assertRedirect('/login');
        $this->actingAs($this->collecteur)->get('/admin/logistique')->assertForbidden();
    }
}
