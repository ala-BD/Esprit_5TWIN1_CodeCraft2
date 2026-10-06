<?php

namespace Tests\Feature;

use App\Models\DonVetement;
use App\Models\PointCollecte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonVetementTest extends TestCase
{
    use RefreshDatabase;

    public function test_donor_can_submit_a_donation_for_an_active_collection_point(): void
    {
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $point = PointCollecte::factory()->create(['statut' => 'ACTIF']);

        $response = $this->actingAs($donor)->post(route('collecte.dons.store'), [
            'point_collecte_id' => $point->id,
            'type' => 'Chemise',
            'matiere' => 'Coton',
            'taille' => 'M',
            'etat' => 'Bon',
        ]);

        $don = DonVetement::firstOrFail();

        $response->assertRedirect(route('collecte.dons.show', ['don' => $don->id]));
        $this->assertDatabaseHas('don_vetements', [
            'id' => $don->id,
            'user_id' => $donor->id,
            'point_collecte_id' => $point->id,
            'statut' => 'DEPOSE',
        ]);
        $this->assertNotEmpty($don->qr_code);
    }

    public function test_donor_cannot_view_another_donors_donation(): void
    {
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $otherDonor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $point = PointCollecte::factory()->create();
        $don = DonVetement::create([
            'user_id' => $otherDonor->id,
            'point_collecte_id' => $point->id,
            'type' => 'Manteau privé',
            'matiere' => 'Laine',
            'taille' => 'L',
            'etat' => 'Bon',
            'statut' => 'DEPOSE',
            'date_depot' => now()->toDateString(),
        ]);

        $this->actingAs($donor)
            ->get(route('collecte.dons.show', ['don' => $don->id]))
            ->assertForbidden();
    }

    public function test_donor_only_sees_their_own_donations_in_the_list(): void
    {
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $otherDonor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $point = PointCollecte::factory()->create();

        foreach ([[$donor, 'Mon don privé'], [$otherDonor, 'Don d’un autre']] as [$owner, $type]) {
            DonVetement::create([
                'user_id' => $owner->id,
                'point_collecte_id' => $point->id,
                'type' => $type,
                'matiere' => 'Coton',
                'taille' => 'M',
                'etat' => 'Bon',
                'statut' => 'DEPOSE',
                'date_depot' => now()->toDateString(),
            ]);
        }

        $this->actingAs($donor)
            ->get(route('collecte.dons.index'))
            ->assertOk()
            ->assertSee('Mon don privé')
            ->assertDontSee('Don d’un autre');
    }

    public function test_donor_cannot_submit_to_an_inactive_collection_point(): void
    {
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $point = PointCollecte::factory()->create(['statut' => 'INACTIF']);

        $this->actingAs($donor)
            ->from(route('collecte.dons.create'))
            ->post(route('collecte.dons.store'), [
                'point_collecte_id' => $point->id,
                'type' => 'Chemise',
                'matiere' => 'Coton',
                'taille' => 'M',
                'etat' => 'Bon',
            ])
            ->assertRedirect(route('collecte.dons.create'))
            ->assertSessionHasErrors('point_collecte_id');
    }

    public function test_collector_can_update_a_donation_status(): void
    {
        $collector = User::factory()->create(['role' => User::ROLE_COLLECTEUR]);
        $donor = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $point = PointCollecte::factory()->create();
        $don = DonVetement::create([
            'user_id' => $donor->id,
            'point_collecte_id' => $point->id,
            'type' => 'Pantalon',
            'matiere' => 'Denim',
            'taille' => '40',
            'etat' => 'Très bon',
            'statut' => 'DEPOSE',
            'date_depot' => now()->toDateString(),
        ]);

        $this->actingAs($collector)
            ->patch(route('collecte.dons.statut', ['don' => $don->id]), ['statut' => 'EN_TRI'])
            ->assertRedirect(route('collecte.dons.show', ['don' => $don->id]));

        $this->assertDatabaseHas('don_vetements', [
            'id' => $don->id,
            'statut' => 'EN_TRI',
        ]);
    }
}