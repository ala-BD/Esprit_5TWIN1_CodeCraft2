<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['prenom' => 'Ada', 'role' => User::ROLE_ADMIN]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name'                  => 'Mansouri',
            'prenom'                => 'Karim',
            'email'                 => 'karim@example.com',
            'telephone'             => '21655000001',
            'role'                  => User::ROLE_COLLECTEUR,
            'actif'                 => '1',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $this->actingAs($user)->get('/admin/users')->assertForbidden();

        // Désactiver CSRF pour tester uniquement le middleware EnsureAdmin sur POST
        $this->actingAs($user)
            ->from('/admin/users')
            ->post('/admin/users', array_merge($this->payload(), ['_token' => csrf_token()]))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'karim@example.com']);
    }

    public function test_admin_can_list_search_and_filter_users(): void
    {
        $admin = $this->admin();
        User::factory()->create(['name' => 'Zeta', 'email' => 'zeta@example.com', 'role' => User::ROLE_CLIENT]);
        User::factory()->create(['name' => 'Omega', 'email' => 'omega@example.com', 'role' => User::ROLE_ATELIER, 'actif' => false]);

        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()
            ->assertSee('zeta@example.com')
            ->assertSee('omega@example.com');

        $this->actingAs($admin)->get('/admin/users?q=zeta')
            ->assertSee('zeta@example.com')
            ->assertDontSee('omega@example.com');

        $this->actingAs($admin)->get('/admin/users?role=ATELIER')
            ->assertSee('omega@example.com')
            ->assertDontSee('zeta@example.com');

        $this->actingAs($admin)->get('/admin/users?actif=0')
            ->assertSee('omega@example.com')
            ->assertDontSee('zeta@example.com');
    }

    public function test_admin_can_view_create_show_and_edit_pages(): void
    {
        $admin = $this->admin();
        $user  = User::factory()->create(['prenom' => 'Lina']);

        $this->actingAs($admin)->get('/admin/users/create')->assertOk();
        $this->actingAs($admin)->get("/admin/users/{$user->id}")->assertOk()->assertSee($user->email);
        $this->actingAs($admin)->get("/admin/users/{$user->id}/edit")->assertOk()->assertSee($user->email);
    }

    public function test_admin_can_view_statistics(): void
    {
        $admin = $this->admin();
        User::factory()->count(2)->create(['role' => User::ROLE_CLIENT, 'created_at' => now()->subDays(3)]);
        User::factory()->create(['role' => User::ROLE_ATELIER, 'actif' => false, 'created_at' => now()->subDays(45)]);

        $response = $this->actingAs($admin)->get('/admin/statistiques')->assertOk();

        $this->assertSame(4, $response->viewData('chiffres')['total']);
        $this->assertSame(3, $response->viewData('chiffres')['actifs']);
        $this->assertSame(3, $response->viewData('chiffres')['nouveaux_30']);
        $this->assertSame(2, $response->viewData('chiffres')['evolution_30']);
        $this->assertSame(2, $response->viewData('parRole')->firstWhere('role', User::ROLE_CLIENT)['nombre']);
        $this->assertSame(0, $response->viewData('parRole')->firstWhere('role', User::ROLE_COLLECTEUR)['nombre']);
        $this->assertSame(4, $response->viewData('parSemaine')->sum('nombre'));
        $this->assertCount(12, $response->viewData('parSemaine'));
    }

    public function test_non_admin_cannot_view_statistics(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/statistiques')->assertForbidden();
    }

    public function test_admin_can_create_a_user(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/users', $this->payload());

        $user = User::where('email', 'karim@example.com')->firstOrFail();

        $response->assertSessionHasNoErrors()->assertRedirect("/admin/users/{$user->id}");
        $this->assertSame(User::ROLE_COLLECTEUR, $user->role);
        $this->assertTrue($user->actif);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('Password123!', $user->password));
    }

    public function test_create_validates_input(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/users', $this->payload([
                'name'     => '',
                'email'    => $admin->email,
                'role'     => 'SUPERHERO',
                'password' => 'Password123!',
                'password_confirmation' => 'different',
            ]))
            ->assertSessionHasErrors(['name', 'email', 'role', 'password']);

        $this->assertSame(1, User::count());
    }

    public function test_admin_can_update_a_user_without_changing_password(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $oldHash = $user->password;

        $this->actingAs($this->admin())
            ->put("/admin/users/{$user->id}", $this->payload([
                'email'    => $user->email,
                'role'     => User::ROLE_RECYCLEUR,
                'actif'    => '0',
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect("/admin/users/{$user->id}");

        $user->refresh();
        $this->assertSame('Karim', $user->prenom);
        $this->assertSame(User::ROLE_RECYCLEUR, $user->role);
        $this->assertFalse($user->actif);
        $this->assertSame($oldHash, $user->password);
    }

    public function test_admin_can_change_a_user_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())
            ->put("/admin/users/{$user->id}", $this->payload([
                'email'    => $user->email,
                'password' => 'NewPassword456!',
                'password_confirmation' => 'NewPassword456!',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('NewPassword456!', $user->refresh()->password));
    }

    public function test_admin_cannot_demote_or_deactivate_themself(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put("/admin/users/{$admin->id}", $this->payload([
                'email' => $admin->email, 'role' => User::ROLE_CLIENT, 'password' => '', 'password_confirmation' => '',
            ]))
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->put("/admin/users/{$admin->id}", $this->payload([
                'email' => $admin->email, 'role' => User::ROLE_ADMIN, 'actif' => '0', 'password' => '', 'password_confirmation' => '',
            ]))
            ->assertSessionHas('error');

        $admin->refresh();
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertTrue($admin->actif);
    }

    public function test_admin_can_delete_a_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())
            ->delete("/admin/users/{$user->id}")
            ->assertRedirect('/admin/users');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_themself(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete("/admin/users/{$admin->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
