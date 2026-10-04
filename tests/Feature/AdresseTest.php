<?php

namespace Tests\Feature;

use App\Models\Adresse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdresseTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/adresses');
        $response->assertRedirect('/login');

        $response = $this->get('/adresses/create');
        $response->assertRedirect('/login');

        $response = $this->post('/adresses', []);
        $response->assertRedirect('/login');
    }

    public function test_user_can_view_address_list(): void
    {
        $user = User::factory()->create();
        $adresse = Adresse::factory()->create([
            'user_id' => $user->id,
            'libelle' => 'Maison Test',
            'rue'     => 'Rue de la Liberté',
            'ville'   => 'Tunis',
        ]);

        $response = $this->actingAs($user)->get('/adresses');

        $response->assertOk();
        $response->assertSee('Maison Test');
        $response->assertSee('Rue de la Liberté');
        $response->assertSee('Tunis');
    }

    public function test_user_only_sees_their_own_addresses(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $addr1 = Adresse::factory()->create([
            'user_id' => $user1->id,
            'libelle' => 'Adresse User 1',
        ]);

        $addr2 = Adresse::factory()->create([
            'user_id' => $user2->id,
            'libelle' => 'Adresse User 2 Secrète',
        ]);

        $response = $this->actingAs($user1)->get('/adresses');

        $response->assertOk();
        $response->assertSee('Adresse User 1');
        $response->assertDontSee('Adresse User 2 Secrète');
    }

    public function test_user_can_view_create_address_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/adresses/create');

        $response->assertOk();
        $response->assertSee('Ajouter une adresse');
    }

    public function test_user_can_create_address(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/adresses', [
            'libelle'     => 'Mon Atelier',
            'rue'         => '12 Rue de l\'Artisanat',
            'ville'       => 'Sousse',
            'code_postal' => '4000',
            'latitude'    => 35.8256,
            'longitude'   => 10.6084,
            'par_defaut'  => 1,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/adresses');

        $this->assertDatabaseHas('adresses', [
            'user_id'     => $user->id,
            'libelle'     => 'Mon Atelier',
            'rue'         => '12 Rue de l\'Artisanat',
            'ville'       => 'Sousse',
            'code_postal' => '4000',
            'par_defaut'  => 1,
        ]);
    }

    public function test_first_created_address_is_automatically_default(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/adresses', [
            'libelle'     => 'Maison',
            'rue'         => 'Avenue Habib Bourguiba',
            'ville'       => 'Tunis',
            'code_postal' => '1000',
            'par_defaut'  => 0, // Même si coché à 0, la première adresse devient par défaut
        ]);

        $response->assertSessionHasNoErrors();

        $adresse = $user->adresses()->first();
        $this->assertNotNull($adresse);
        $this->assertTrue($adresse->par_defaut);
    }

    public function test_mandatory_fields_are_validated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/adresses', [
            'libelle'     => 'Test',
            'rue'         => '',
            'ville'       => '',
            'code_postal' => '',
        ]);

        $response->assertSessionHasErrors(['rue', 'ville', 'code_postal']);
    }

    public function test_user_can_view_edit_address_form(): void
    {
        $user = User::factory()->create();
        $adresse = Adresse::factory()->create([
            'user_id' => $user->id,
            'libelle' => 'Boutique',
        ]);

        $response = $this->actingAs($user)->get("/adresses/{$adresse->id}/edit");

        $response->assertOk();
        $response->assertSee('Boutique');
        $response->assertSee('Modifier votre adresse');
    }

    public function test_user_cannot_access_edit_form_of_another_user(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $adresse = Adresse::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($stranger)->get("/adresses/{$adresse->id}/edit");

        $response->assertForbidden();
    }

    public function test_user_can_update_their_address(): void
    {
        $user = User::factory()->create();
        $adresse = Adresse::factory()->create([
            'user_id' => $user->id,
            'rue'     => 'Ancienne Rue',
            'ville'   => 'Tunis',
        ]);

        $response = $this->actingAs($user)->put("/adresses/{$adresse->id}", [
            'libelle'     => 'Nouvelle Maison',
            'rue'         => 'Nouvelle Rue Modifiée',
            'ville'       => 'La Marsa',
            'code_postal' => '2078',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/adresses');

        $adresse->refresh();
        $this->assertSame('Nouvelle Rue Modifiée', $adresse->rue);
        $this->assertSame('La Marsa', $adresse->ville);
    }

    public function test_user_cannot_update_another_users_address(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $adresse = Adresse::factory()->create([
            'user_id' => $owner->id,
            'rue'     => 'Rue Sécurisée',
        ]);

        $response = $this->actingAs($stranger)->put("/adresses/{$adresse->id}", [
            'rue'         => 'Attaque Hack',
            'ville'       => 'Ville',
            'code_postal' => '1000',
        ]);

        $response->assertForbidden();
        $this->assertSame('Rue Sécurisée', $adresse->fresh()->rue);
    }

    public function test_user_can_delete_their_address(): void
    {
        $user = User::factory()->create();
        $adresse = Adresse::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete("/adresses/{$adresse->id}");

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/adresses');

        $this->assertNull(Adresse::find($adresse->id));
    }

    public function test_user_cannot_delete_another_users_address(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $adresse = Adresse::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($stranger)->delete("/adresses/{$adresse->id}");

        $response->assertForbidden();
        $this->assertNotNull(Adresse::find($adresse->id));
    }

    public function test_user_can_set_address_as_default(): void
    {
        $user = User::factory()->create();
        $adresse1 = Adresse::factory()->create([
            'user_id'    => $user->id,
            'par_defaut' => true,
        ]);
        $adresse2 = Adresse::factory()->create([
            'user_id'    => $user->id,
            'par_defaut' => false,
        ]);

        $response = $this->actingAs($user)->patch("/adresses/{$adresse2->id}/defaut");

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/adresses');

        $this->assertFalse($adresse1->fresh()->par_defaut);
        $this->assertTrue($adresse2->fresh()->par_defaut);
    }

    public function test_reverse_geocode_requires_coordinates(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/adresses/reverse-geocode');

        $response->assertStatus(422)
            ->assertJson(['error' => 'Coordonnées requises']);
    }

    public function test_reverse_geocode_handles_request(): void
    {
        $user = User::factory()->create();

        \Illuminate\Support\Facades\Http::fake([
            'nominatim.openstreetmap.org/*' => \Illuminate\Support\Facades\Http::response([
                'address' => [
                    'house_number' => '10',
                    'road'         => 'Avenue Habib Bourguiba',
                    'city'         => 'Tunis',
                    'postcode'     => '1000',
                ],
                'display_name' => '10, Avenue Habib Bourguiba, Tunis, 1000',
            ], 200),
        ]);

        $response = $this->actingAs($user)->getJson('/adresses/reverse-geocode?lat=36.8000&lng=10.1800');

        $response->assertOk()
            ->assertJsonPath('address.road', 'Avenue Habib Bourguiba')
            ->assertJsonPath('address.house_number', '10');
    }
}
