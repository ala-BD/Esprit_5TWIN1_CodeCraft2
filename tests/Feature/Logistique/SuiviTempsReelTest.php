<?php

namespace Tests\Feature\Logistique;

use App\Models\Adresse;
use App\Models\Commande;
use App\Models\Mission;
use App\Models\PositionCollecteur;
use App\Models\Tournee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuiviTempsReelTest extends TestCase
{
    use RefreshDatabase;

    private User $collecteur;

    private Tournee $tournee;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        Carbon::setTestNow('2026-11-10 10:30:00');

        $this->collecteur = User::factory()->create(['prenom' => 'Youssef', 'name' => 'Trabelsi', 'role' => User::ROLE_COLLECTEUR]);
        $this->tournee = Tournee::create([
            'user_id' => $this->collecteur->id, 'date' => '2026-11-10', 'zone' => 'Tunis Centre',
            'vehicule' => 'Fourgon', 'statut' => Tournee::STATUT_PLANIFIEE,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function arret(int $ordre, array $extra = []): Mission
    {
        return $this->tournee->missions()->create(array_merge([
            'type' => Mission::TYPE_LIVRAISON, 'adresse' => "Arrêt {$ordre}", 'ordre' => $ordre,
            'heure_prevue' => sprintf('%02d:00', 8 + $ordre), 'statut' => Mission::STATUT_A_FAIRE,
            'latitude' => 36.80 + $ordre / 100, 'longitude' => 10.18,
        ], $extra));
    }

    private function avancer(Mission $mission, array $donnees, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->collecteur)->postJson("/logistique/missions/{$mission->id}/avancer", $donnees);
    }

    /*
    |------------------------------------------------------------------
    | Mode tournée
    |------------------------------------------------------------------
    */

    public function test_tour_mode_shows_the_next_stop_with_its_contact(): void
    {
        $client = User::factory()->create(['prenom' => 'Rania', 'name' => 'Toumi', 'role' => User::ROLE_CLIENT, 'telephone' => '21655200000']);
        $adresse = Adresse::create(['user_id' => $client->id, 'libelle' => 'Domicile', 'rue' => '30 Avenue de la Liberté', 'code_postal' => '1002',
            'ville' => 'Lafayette', 'gouvernorat' => 'Tunis', 'latitude' => 36.81, 'longitude' => 10.18, 'par_defaut' => true]);
        $commande = Commande::create(['user_id' => $client->id, 'adresse_id' => $adresse->id, 'numero' => 'TC-TEST-1', 'statut' => Commande::STATUT_CONFIRMEE]);

        $this->arret(1, ['statut' => Mission::STATUT_TERMINEE]);
        $this->arret(2, ['commande_id' => $commande->id, 'adresse' => null, 'latitude' => null, 'longitude' => null]);

        $this->actingAs($this->collecteur)->get("/logistique/tournees/{$this->tournee->id}/conduite")
            ->assertOk()
            ->assertSee('30 Avenue de la Liberté, 1002 Lafayette')
            ->assertSee('Rania Toumi')
            ->assertSee('tel:21655200000', false)
            ->assertSee('google.com/maps/dir', false)
            ->assertSee('1/2 arrêts');
    }

    public function test_a_finished_tour_says_so(): void
    {
        $this->arret(1, ['statut' => Mission::STATUT_TERMINEE]);

        $this->actingAs($this->collecteur)->get("/logistique/tournees/{$this->tournee->id}/conduite")
            ->assertOk()
            ->assertSee('Tournée terminée');
    }

    public function test_starting_a_stop_starts_the_tour_and_ships_the_order(): void
    {
        $client = User::factory()->create(['role' => User::ROLE_CLIENT]);
        $commande = Commande::create(['user_id' => $client->id, 'numero' => 'TC-TEST-2', 'statut' => Commande::STATUT_CONFIRMEE, 'adresse_snapshot' => 'Rue X']);
        $mission = $this->arret(1, ['commande_id' => $commande->id]);
        $this->arret(2);

        $this->avancer($mission, ['action' => 'demarrer'])
            ->assertOk()
            ->assertJson(['termine' => false, 'prochain' => $mission->id]);

        $this->assertSame(Mission::STATUT_EN_COURS, $mission->fresh()->statut);
        $this->assertSame(Tournee::STATUT_EN_COURS, $this->tournee->fresh()->statut);
        $this->assertSame(Commande::STATUT_EXPEDIEE, $commande->fresh()->statut);
    }

    public function test_finishing_with_a_photo_stores_the_proof_and_closing_the_last_stop_ends_the_tour(): void
    {
        Storage::fake('public');
        $premier = $this->arret(1, ['statut' => Mission::STATUT_EN_COURS]);
        $second  = $this->arret(2);

        $this->avancer($premier, ['action' => 'terminer', 'preuve' => 'Remis en main propre', 'photo' => UploadedFile::fake()->image('preuve.jpg')])
            ->assertOk()
            ->assertJson(['termine' => false, 'prochain' => $second->id]);

        $premier->refresh();
        $this->assertSame(Mission::STATUT_TERMINEE, $premier->statut);
        $this->assertSame('Remis en main propre', $premier->preuve_livraison);
        Storage::disk('public')->assertExists($premier->photo_preuve);

        $this->avancer($second, ['action' => 'echouer', 'preuve' => 'Client absent'])
            ->assertOk()
            ->assertJson(['termine' => true, 'prochain' => null]);

        $this->assertSame(Tournee::STATUT_TERMINEE, $this->tournee->fresh()->statut);
    }

    public function test_a_failure_needs_a_reason_and_the_proof_must_be_an_image(): void
    {
        $mission = $this->arret(1, ['statut' => Mission::STATUT_EN_COURS]);

        $this->avancer($mission, ['action' => 'echouer'])->assertJsonValidationErrors('preuve');
        $this->avancer($mission, ['action' => 'terminer', 'photo' => UploadedFile::fake()->create('note.pdf', 10, 'application/pdf')])
            ->assertJsonValidationErrors('photo');
        $this->avancer($mission, ['action' => 'voler'])->assertJsonValidationErrors('action');

        $this->assertSame(Mission::STATUT_EN_COURS, $mission->fresh()->statut);
    }

    public function test_another_collecteur_cannot_drive_this_tour(): void
    {
        $intrus = User::factory()->create(['role' => User::ROLE_COLLECTEUR]);
        $mission = $this->arret(1);

        $this->actingAs($intrus)->get("/logistique/tournees/{$this->tournee->id}/conduite")->assertForbidden();
        $this->avancer($mission, ['action' => 'terminer'], $intrus)->assertForbidden();

        $this->assertSame(Mission::STATUT_A_FAIRE, $mission->fresh()->statut);
    }

    /*
    |------------------------------------------------------------------
    | Positions GPS
    |------------------------------------------------------------------
    */

    public function test_positions_are_recorded_at_most_every_fifteen_seconds(): void
    {
        $envoyer = fn (float $lat) => $this->actingAs($this->collecteur)->postJson('/logistique/position', [
            'latitude' => $lat, 'longitude' => 10.18, 'precision' => 12.4, 'tournee_id' => $this->tournee->id,
        ]);

        $envoyer(36.80)->assertOk()->assertJson(['enregistree' => true]);
        $envoyer(36.81)->assertOk()->assertJson(['enregistree' => false]);

        Carbon::setTestNow(now()->addSeconds(16));
        $envoyer(36.82)->assertOk()->assertJson(['enregistree' => true]);

        $this->assertSame(2, PositionCollecteur::count());
        $derniere = $this->collecteur->dernierePosition;
        $this->assertSame(36.82, $derniere->latitude);
        $this->assertSame(12, $derniere->precision_m);
        $this->assertSame($this->tournee->id, $derniere->tournee_id);
    }

    public function test_a_position_cannot_be_attached_to_someone_elses_tour_and_must_be_valid(): void
    {
        $autre = Tournee::create(['user_id' => User::factory()->create(['role' => User::ROLE_COLLECTEUR])->id,
            'date' => '2026-11-10', 'zone' => 'X', 'vehicule' => 'Y', 'statut' => Tournee::STATUT_PLANIFIEE]);

        $this->actingAs($this->collecteur)->postJson('/logistique/position', ['latitude' => 36.8, 'longitude' => 10.1, 'tournee_id' => $autre->id])->assertOk();
        $this->assertNull(PositionCollecteur::first()->tournee_id);

        $this->actingAs($this->collecteur)->postJson('/logistique/position', ['latitude' => 120, 'longitude' => 10.1])
            ->assertJsonValidationErrors('latitude');

        $this->actingAs(User::factory()->create(['role' => User::ROLE_CLIENT]))
            ->postJson('/logistique/position', ['latitude' => 36.8, 'longitude' => 10.1])->assertForbidden();
    }

    /*
    |------------------------------------------------------------------
    | Carte en direct (administrateur)
    |------------------------------------------------------------------
    */

    public function test_the_live_map_reports_progress_lateness_and_positions(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->arret(1, ['statut' => Mission::STATUT_TERMINEE]);
        $this->arret(2);                                         // prévu à 10:00, il est 10:30
        $this->arret(3, ['latitude' => null, 'longitude' => null]);
        PositionCollecteur::create(['user_id' => $this->collecteur->id, 'latitude' => 36.805, 'longitude' => 10.18, 'enregistree_le' => now()->subMinutes(2)]);

        $this->actingAs($admin)->get('/admin/logistique/carte')->assertOk()->assertSee('Carte en direct');

        $this->actingAs($admin)->getJson('/admin/logistique/carte/donnees')
            ->assertOk()
            ->assertJsonPath('date', '2026-11-10')
            ->assertJsonCount(1, 'tournees')
            ->assertJsonPath('tournees.0.collecteur.nom', 'Youssef Trabelsi')
            ->assertJsonPath('tournees.0.total', 3)
            ->assertJsonPath('tournees.0.faites', 1)
            ->assertJsonPath('tournees.0.retard', 30)
            ->assertJsonPath('tournees.0.prochain.ordre', 2)
            ->assertJsonPath('tournees.0.position.en_direct', true)
            ->assertJsonCount(2, 'tournees.0.arrets');           // l'arrêt sans position n'est pas sur la carte
    }

    public function test_an_old_position_is_not_live_and_other_days_have_no_lateness(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->arret(1);
        PositionCollecteur::create(['user_id' => $this->collecteur->id, 'latitude' => 36.805, 'longitude' => 10.18, 'enregistree_le' => now()->subMinutes(20)]);

        $this->actingAs($admin)->getJson('/admin/logistique/carte/donnees')
            ->assertJsonPath('tournees.0.position.en_direct', false)
            ->assertJsonPath('tournees.0.position.il_y_a', 20);

        $this->actingAs($admin)->getJson('/admin/logistique/carte/donnees?date=2026-11-11')->assertJsonCount(0, 'tournees');
    }

    public function test_only_the_admin_sees_the_live_map(): void
    {
        $this->actingAs($this->collecteur)->get('/admin/logistique/carte')->assertForbidden();
        $this->actingAs($this->collecteur)->getJson('/admin/logistique/carte/donnees')->assertForbidden();
    }
}
