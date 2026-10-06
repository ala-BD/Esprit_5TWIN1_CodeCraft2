<?php

namespace Tests\Feature\Logistique;

use App\Models\Mission;
use App\Models\Tournee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TourneeMissionTest extends TestCase
{
    use RefreshDatabase;

    private function collecteur(): User
    {
        return User::factory()->create(['prenom' => 'Youssef', 'role' => User::ROLE_COLLECTEUR]);
    }

    private function tournee(User $user, array $overrides = []): Tournee
    {
        return Tournee::create(array_merge([
            'user_id'  => $user->id,
            'date'     => '2026-10-10',
            'zone'     => 'Tunis Centre',
            'vehicule' => 'Camionnette 123 TU 4567',
            'statut'   => Tournee::STATUT_PLANIFIEE,
        ], $overrides));
    }

    private function mission(Tournee $tournee, array $overrides = []): Mission
    {
        return $tournee->missions()->create(array_merge($this->missionPayload(), $overrides));
    }

    private function tourneePayload(array $overrides = []): array
    {
        return array_merge([
            'date'        => '2026-10-12',
            'zone'        => 'La Marsa',
            'vehicule'    => 'Fourgon 87 TU 2210',
            'distance_km' => '24.5',
            'statut'      => Tournee::STATUT_PLANIFIEE,
        ], $overrides);
    }

    private function missionPayload(array $overrides = []): array
    {
        return array_merge([
            'type'         => Mission::TYPE_COLLECTE,
            'adresse'      => '12 rue de Marseille, Tunis',
            'ordre'        => 1,
            'heure_prevue' => '09:30',
            'statut'       => Mission::STATUT_A_FAIRE,
        ], $overrides);
    }

    /*
    |------------------------------------------------------------------
    | Accès
    |------------------------------------------------------------------
    */

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/logistique/tournees')->assertRedirect('/login');
    }

    public function test_non_collecteur_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $this->actingAs($user)->get('/logistique/tournees')->assertForbidden();
        $this->actingAs($user)->post('/logistique/tournees', $this->tourneePayload())->assertForbidden();
        $this->assertSame(0, Tournee::count());
    }

    public function test_collecteur_cannot_touch_another_collecteurs_tournee(): void
    {
        $tournee = $this->tournee($this->collecteur());
        $mission = $this->mission($tournee);
        $autre   = $this->collecteur();

        $this->actingAs($autre)->get("/logistique/tournees/{$tournee->id}")->assertForbidden();
        $this->actingAs($autre)->put("/logistique/tournees/{$tournee->id}", $this->tourneePayload())->assertForbidden();
        $this->actingAs($autre)->delete("/logistique/tournees/{$tournee->id}")->assertForbidden();
        $this->actingAs($autre)->post("/logistique/tournees/{$tournee->id}/missions", $this->missionPayload())->assertForbidden();
        $this->actingAs($autre)->put("/logistique/missions/{$mission->id}", $this->missionPayload())->assertForbidden();
        $this->actingAs($autre)->delete("/logistique/missions/{$mission->id}")->assertForbidden();

        $this->assertSame('Tunis Centre', $tournee->refresh()->zone);
        $this->assertSame(1, $tournee->missions()->count());
    }

    /*
    |------------------------------------------------------------------
    | Tournées
    |------------------------------------------------------------------
    */

    public function test_collecteur_lists_only_their_own_tournees_and_can_filter(): void
    {
        $user = $this->collecteur();
        $this->tournee($user, ['zone' => 'Ariana', 'statut' => Tournee::STATUT_TERMINEE]);
        $this->tournee($user, ['zone' => 'La Goulette']);
        $this->tournee($this->collecteur(), ['zone' => 'Sfax Nord']);

        $this->actingAs($user)->get('/logistique/tournees')
            ->assertOk()
            ->assertSee('Ariana')
            ->assertSee('La Goulette')
            ->assertDontSee('Sfax Nord');

        $this->actingAs($user)->get('/logistique/tournees?statut=TERMINEE')
            ->assertSee('Ariana')
            ->assertDontSee('La Goulette');

        $this->actingAs($user)->get('/logistique/tournees?q=goulette')
            ->assertSee('La Goulette')
            ->assertDontSee('Ariana');
    }

    public function test_collecteur_can_view_tournee_pages(): void
    {
        $user    = $this->collecteur();
        $tournee = $this->tournee($user);
        $this->mission($tournee, ['adresse' => '45 avenue de la Liberté']);

        $this->actingAs($user)->get('/logistique/tournees/create')->assertOk();
        $this->actingAs($user)->get("/logistique/tournees/{$tournee->id}/edit")->assertOk()->assertSee('Tunis Centre');
        $this->actingAs($user)->get("/logistique/tournees/{$tournee->id}")
            ->assertOk()
            ->assertSee('45 avenue de la Liberté')
            ->assertSee('09:30');
    }

    public function test_collecteur_can_create_a_tournee(): void
    {
        $user = $this->collecteur();

        $response = $this->actingAs($user)->post('/logistique/tournees', $this->tourneePayload());

        $tournee = Tournee::firstOrFail();
        $response->assertSessionHasNoErrors()->assertRedirect("/logistique/tournees/{$tournee->id}");
        $this->assertSame($user->id, $tournee->user_id);
        $this->assertSame('La Marsa', $tournee->zone);
        $this->assertSame('2026-10-12', $tournee->date->format('Y-m-d'));
        $this->assertSame(24.5, $tournee->distance_km);
    }

    public function test_tournee_validation(): void
    {
        $this->actingAs($this->collecteur())
            ->post('/logistique/tournees', $this->tourneePayload([
                'date' => 'pas-une-date', 'zone' => '', 'vehicule' => '', 'distance_km' => '-3', 'statut' => 'PERDUE',
            ]))
            ->assertSessionHasErrors(['date', 'zone', 'vehicule', 'distance_km', 'statut']);

        $this->assertSame(0, Tournee::count());
    }

    public function test_collecteur_can_update_a_tournee(): void
    {
        $user    = $this->collecteur();
        $tournee = $this->tournee($user);

        $this->actingAs($user)
            ->put("/logistique/tournees/{$tournee->id}", $this->tourneePayload(['statut' => Tournee::STATUT_EN_COURS, 'distance_km' => '']))
            ->assertSessionHasNoErrors()
            ->assertRedirect("/logistique/tournees/{$tournee->id}");

        $tournee->refresh();
        $this->assertSame('La Marsa', $tournee->zone);
        $this->assertSame(Tournee::STATUT_EN_COURS, $tournee->statut);
        $this->assertNull($tournee->distance_km);
    }

    public function test_deleting_a_tournee_deletes_its_missions(): void
    {
        $user    = $this->collecteur();
        $tournee = $this->tournee($user);
        $this->mission($tournee);
        $this->mission($tournee, ['ordre' => 2]);

        $this->actingAs($user)
            ->delete("/logistique/tournees/{$tournee->id}")
            ->assertRedirect('/logistique/tournees');

        $this->assertSame(0, Tournee::count());
        $this->assertSame(0, Mission::count());
    }

    /*
    |------------------------------------------------------------------
    | Missions
    |------------------------------------------------------------------
    */

    public function test_collecteur_can_view_mission_forms(): void
    {
        $user    = $this->collecteur();
        $tournee = $this->tournee($user);
        $mission = $this->mission($tournee, ['ordre' => 3]);

        // Le formulaire de création propose l'ordre suivant
        $this->actingAs($user)->get("/logistique/tournees/{$tournee->id}/missions/create")
            ->assertOk()
            ->assertViewHas('mission', fn (Mission $nouvelle) => $nouvelle->ordre === 4);

        $this->actingAs($user)->get("/logistique/missions/{$mission->id}/edit")
            ->assertOk()
            ->assertSee('12 rue de Marseille, Tunis');
    }

    public function test_collecteur_can_add_a_mission_to_a_tournee(): void
    {
        $user    = $this->collecteur();
        $tournee = $this->tournee($user);

        $this->actingAs($user)
            ->post("/logistique/tournees/{$tournee->id}/missions", $this->missionPayload(['type' => Mission::TYPE_LIVRAISON, 'don_vetement_id' => '']))
            ->assertSessionHasNoErrors()
            ->assertRedirect("/logistique/tournees/{$tournee->id}");

        $mission = $tournee->missions()->firstOrFail();
        $this->assertSame(Mission::TYPE_LIVRAISON, $mission->type);
        $this->assertSame('09:30', $mission->heure);
        $this->assertNull($mission->don_vetement_id);
    }

    public function test_mission_validation(): void
    {
        $user    = $this->collecteur();
        $tournee = $this->tournee($user);

        $this->actingAs($user)
            ->post("/logistique/tournees/{$tournee->id}/missions", $this->missionPayload([
                'type' => 'RAMASSAGE', 'adresse' => '', 'ordre' => 0, 'heure_prevue' => '25h', 'statut' => 'PERDUE', 'don_vetement_id' => 999,
            ]))
            ->assertSessionHasErrors(['type', 'adresse', 'ordre', 'heure_prevue', 'statut', 'don_vetement_id']);

        $this->assertSame(0, Mission::count());
    }

    public function test_collecteur_can_update_a_mission(): void
    {
        $user    = $this->collecteur();
        $tournee = $this->tournee($user);
        $mission = $this->mission($tournee);

        $this->actingAs($user)
            ->put("/logistique/missions/{$mission->id}", $this->missionPayload([
                'statut' => Mission::STATUT_TERMINEE, 'heure_prevue' => '10:15', 'preuve_livraison' => 'Signé par M. Ben Ali',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect("/logistique/tournees/{$tournee->id}");

        $mission->refresh();
        $this->assertSame(Mission::STATUT_TERMINEE, $mission->statut);
        $this->assertSame('10:15', $mission->heure);
        $this->assertSame('Signé par M. Ben Ali', $mission->preuve_livraison);
    }

    public function test_collecteur_can_delete_a_mission(): void
    {
        $user    = $this->collecteur();
        $tournee = $this->tournee($user);
        $mission = $this->mission($tournee);

        $this->actingAs($user)
            ->delete("/logistique/missions/{$mission->id}")
            ->assertRedirect("/logistique/tournees/{$tournee->id}");

        $this->assertSame(0, Mission::count());
        $this->assertSame(1, Tournee::count());
    }

    public function test_missions_are_listed_in_order_of_passage(): void
    {
        $user    = $this->collecteur();
        $tournee = $this->tournee($user);
        $this->mission($tournee, ['ordre' => 2, 'adresse' => 'Deuxième arrêt']);
        $this->mission($tournee, ['ordre' => 1, 'adresse' => 'Premier arrêt']);

        $this->actingAs($user)->get("/logistique/tournees/{$tournee->id}")
            ->assertSeeInOrder(['Premier arrêt', 'Deuxième arrêt']);
    }
}
