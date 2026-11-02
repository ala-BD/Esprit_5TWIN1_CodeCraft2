<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesEtPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Rôles et permissions Spatie, synchronisés avec la colonne users.role.
 */
class RolesEtPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_seeder_cree_les_six_roles_et_les_permissions(): void
    {
        $this->assertEqualsCanonicalizing(User::ROLES, Role::pluck('name')->all());
        $this->assertEqualsCanonicalizing(
            ['gerer utilisateurs', 'voir statistiques', 'gerer projets upcycling', 'traiter lots', 'gerer tournees', 'publier articles'],
            Permission::pluck('name')->all()
        );

        $this->assertTrue(Role::findByName(User::ROLE_ADMIN)->hasPermissionTo('gerer utilisateurs'));
        $this->assertTrue(Role::findByName(User::ROLE_ATELIER)->hasPermissionTo('gerer projets upcycling'));
        $this->assertFalse(Role::findByName(User::ROLE_CLIENT)->hasPermissionTo('gerer utilisateurs'));
    }

    public function test_le_seeder_est_idempotent_et_aligne_les_comptes_existants(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_RECYCLEUR]);
        $user->syncRoles([]); // compte créé avant Spatie : aucun rôle

        $this->seed(RolesEtPermissionsSeeder::class);
        $this->seed(RolesEtPermissionsSeeder::class);

        $this->assertSame(6, Role::count());
        $this->assertSame(6, Permission::count());
        $this->assertTrue($user->fresh()->hasRole(User::ROLE_RECYCLEUR));
    }

    public function test_la_migration_aligne_une_base_existante_sans_roles_spatie(): void
    {
        // Base seedée avant Spatie : comptes sans rôle Spatie, admin bloqué (403)
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $admin->syncRoles([]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($admin->fresh())->get('/admin/users')->assertForbidden();

        $migration = require database_path('migrations/2026_11_02_120000_creer_roles_et_permissions.php');
        $migration->up();

        $this->assertTrue($admin->fresh()->hasRole(User::ROLE_ADMIN));
        $this->actingAs($admin->fresh())->get('/admin/users')->assertOk();
    }

    public function test_la_fabrique_produit_des_utilisateurs_avec_le_role_spatie(): void
    {
        $donateur = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertSame([User::ROLE_DONATEUR], $donateur->getRoleNames()->all());
        $this->assertSame([User::ROLE_ADMIN], $admin->getRoleNames()->all());
        $this->assertTrue($admin->can('gerer utilisateurs'));
        $this->assertFalse($donateur->can('gerer utilisateurs'));
    }

    public function test_changer_la_colonne_role_synchronise_le_role_spatie(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENT]);

        $user->update(['role' => User::ROLE_ATELIER]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = $user->fresh();

        // Un seul rôle Spatie, toujours égal à la colonne
        $this->assertSame([User::ROLE_ATELIER], $user->getRoleNames()->all());
        $this->assertTrue($user->hasRole(User::ROLE_ATELIER));
        $this->assertFalse($user->hasRole(User::ROLE_CLIENT));
        $this->assertTrue($user->can('gerer projets upcycling'));
    }

    public function test_le_changement_de_role_depuis_le_profil_est_synchronise(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENT]);

        $this->actingAs($user)->patch('/profile/role', ['role' => User::ROLE_RECYCLEUR])
            ->assertRedirect('/profile');

        $this->assertSame([User::ROLE_RECYCLEUR], $user->fresh()->getRoleNames()->all());
    }

    public function test_un_admin_cree_par_l_administration_recoit_le_role_spatie(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->post('/admin/users', [
            'name'                  => 'Mansouri',
            'prenom'                => 'Karim',
            'email'                 => 'karim@example.com',
            'role'                  => User::ROLE_COLLECTEUR,
            'actif'                 => '1',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasNoErrors();

        $cree = User::where('email', 'karim@example.com')->firstOrFail();
        $this->assertTrue($cree->hasRole(User::ROLE_COLLECTEUR));
        $this->assertTrue($cree->can('gerer tournees'));
    }

    public function test_l_admin_a_la_permission_et_accede_a_la_gestion(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/statistiques')->assertOk();
    }

    public function test_un_client_est_refuse_403(): void
    {
        $client = User::factory()->create(['role' => User::ROLE_CLIENT]);

        $this->assertFalse($client->can('gerer utilisateurs'));
        $this->actingAs($client)->get('/admin/users')->assertForbidden();
        $this->actingAs($client)->get('/admin/statistiques')->assertForbidden();
    }

    public function test_un_admin_prive_de_la_permission_est_refuse(): void
    {
        // La colonne dit ADMIN, mais la permission Spatie a été retirée du rôle :
        // le contrôle de permission côté serveur bloque l'accès.
        Role::findByName(User::ROLE_ADMIN)->revokePermissionTo('gerer utilisateurs');
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)->get('/admin/users')->assertForbidden();
        $this->actingAs($admin)->delete('/admin/users/'.User::factory()->create()->id)->assertForbidden();
        $this->actingAs($admin)->get('/admin/statistiques')->assertOk(); // autre permission, toujours accordée
    }

    public function test_la_policy_utilisateur_s_appuie_sur_la_permission(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $atelier = User::factory()->create(['role' => User::ROLE_ATELIER]);

        $this->assertTrue($admin->can('create', User::class));
        $this->assertTrue($admin->can('delete', $atelier));
        $this->assertFalse($atelier->can('create', User::class));
        $this->assertFalse($atelier->can('update', $admin));
    }

    public function test_l_alias_role_reste_ensure_role_et_les_alias_spatie_sont_enregistres(): void
    {
        $aliases = Route::getMiddleware();

        $this->assertSame(\App\Http\Middleware\EnsureRole::class, $aliases['role']);
        $this->assertSame(\Spatie\Permission\Middleware\PermissionMiddleware::class, $aliases['permission']);
        $this->assertSame(\Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class, $aliases['role_or_permission']);
    }

    public function test_a_le_role_lit_la_colonne(): void
    {
        $user = User::factory()->make(['role' => User::ROLE_COLLECTEUR]);

        $this->assertTrue($user->aLeRole('collecteur'));
        $this->assertFalse($user->aLeRole(User::ROLE_ADMIN));
    }
}
