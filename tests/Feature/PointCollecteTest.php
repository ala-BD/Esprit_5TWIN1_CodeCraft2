<?php

namespace Tests\Feature;

use App\Models\User;
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

        $response->assertOk();
    }
}
