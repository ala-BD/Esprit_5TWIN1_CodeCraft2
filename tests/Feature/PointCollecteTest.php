<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PointCollecte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointCollecteTest extends TestCase
{
    use RefreshDatabase;

    public function test_collector_can_access_points_collection_index(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_COLLECTEUR,
        ]);

        $response = $this->actingAs($user)
            ->get(route('collecte.points.index'));

        $response->assertOk()
            ->assertSee(route('collecte.dashboard'));
    }

    public function test_collector_can_view_a_collection_point(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_COLLECTEUR,
        ]);
        $point = PointCollecte::factory()->create([
            'user_id' => $user->id,
            'nom' => 'Point de collecte test',
        ]);

        $response = $this->actingAs($user)
            ->get(route('collecte.points.show', ['point' => $point->id]));

        $response->assertOk()
            ->assertSee('Point de collecte test')
            ->assertSee(route('collecte.points.edit', ['point' => $point->id]));
    }
}
