<?php

namespace Tests\Feature\Logistique;

use App\Models\Mission;
use App\Models\Tournee;
use App\Models\User;
use App\Notifications\MissionEchoueeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FinitionsLogistiqueTest extends TestCase
{
    use RefreshDatabase;

    private User $collecteur;

    private Tournee $tournee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collecteur = User::factory()->create(['prenom' => 'Youssef', 'name' => 'Trabelsi', 'role' => User::ROLE_COLLECTEUR]);
        $this->tournee = Tournee::create([
            'user_id' => $this->collecteur->id, 'date' => today(), 'zone' => 'Tunis Centre',
            'vehicule' => 'Fourgon', 'statut' => Tournee::STATUT_EN_COURS,
        ]);
        $this->tournee->missions()->create([
            'type' => Mission::TYPE_COLLECTE, 'adresse' => '12 Rue de Marseille, 1000 Tunis', 'ordre' => 1,
            'heure_prevue' => '09:00', 'statut' => Mission::STATUT_A_FAIRE, 'latitude' => 36.80, 'longitude' => 10.18,
        ]);
    }

    public function test_the_collecteur_downloads_the_route_sheet_of_their_round(): void
    {
        $reponse = $this->actingAs($this->collecteur)->get("/logistique/tournees/{$this->tournee->id}/feuille-de-route");

        $reponse->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('feuille-de-route-' . today()->format('Y-m-d') . '-tunis-centre.pdf', $reponse->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $reponse->getContent());
    }

    public function test_only_the_owner_or_the_admin_gets_the_route_sheet(): void
    {
        $intrus = User::factory()->create(['role' => User::ROLE_COLLECTEUR]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($intrus)->get("/logistique/tournees/{$this->tournee->id}/feuille-de-route")->assertForbidden();
        $this->actingAs($intrus)->get("/admin/logistique/tournees/{$this->tournee->id}/feuille-de-route")->assertForbidden();
        $this->actingAs($admin)->get("/admin/logistique/tournees/{$this->tournee->id}/feuille-de-route")
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_a_failed_stop_alerts_the_active_admins_with_the_reason(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $inactif = User::factory()->create(['role' => User::ROLE_ADMIN, 'actif' => false]);

        $this->tournee->missions()->first()->update(['statut' => Mission::STATUT_ECHOUEE, 'preuve_livraison' => 'Client absent']);

        Notification::assertSentTo($admin, MissionEchoueeNotification::class, function ($notification) use ($admin) {
            $donnees = $notification->toArray($admin);

            return str_contains($donnees['message'], 'Client absent')
                && str_contains($donnees['message'], 'Youssef Trabelsi')
                && $donnees['url'] === route('admin.logistique.show', $this->tournee);
        });
        Notification::assertNotSentTo($inactif, MissionEchoueeNotification::class);
        Notification::assertNotSentTo($this->collecteur, MissionEchoueeNotification::class);
    }

    public function test_a_completed_stop_does_not_alert_anyone(): void
    {
        Notification::fake();
        User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->tournee->missions()->first()->update(['statut' => Mission::STATUT_TERMINEE]);

        Notification::assertNothingSent();
    }

    public function test_the_notification_bell_is_shown_in_the_admin_and_logistics_areas(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get('/admin/logistique')->assertOk()->assertSee('id="notif-wrapper"', false);
        $this->actingAs($this->collecteur)->get('/logistique/tournees')->assertOk()->assertSee('id="notif-wrapper"', false);
    }
}
