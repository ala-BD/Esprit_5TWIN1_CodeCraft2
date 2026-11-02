<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rôles et permissions Spatie.
 *
 * Les 6 rôles reprennent la colonne users.role (source de vérité du code existant) ;
 * les permissions décrivent ce que chaque rôle a le droit de faire.
 * Idempotent : peut être relancé sans créer de doublons.
 */
class RolesEtPermissionsSeeder extends Seeder
{
    /** Permissions accordées à chaque rôle */
    public const PERMISSIONS_PAR_ROLE = [
        User::ROLE_ADMIN => [
            'gerer utilisateurs',
            'voir statistiques',
            'gerer projets upcycling',
            'traiter lots',
            'gerer tournees',
            'publier articles',
        ],
        User::ROLE_ATELIER    => ['gerer projets upcycling', 'publier articles'],
        User::ROLE_DONATEUR   => ['publier articles'],
        User::ROLE_RECYCLEUR  => ['traiter lots'],
        User::ROLE_COLLECTEUR => ['gerer tournees'],
        User::ROLE_CLIENT     => [],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS_PAR_ROLE as $role => $permissions) {
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }

            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }

        // Comptes déjà présents en base : rôle Spatie aligné sur la colonne users.role
        User::query()->each(fn (User $user) => $user->synchroniserRoleSpatie());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
