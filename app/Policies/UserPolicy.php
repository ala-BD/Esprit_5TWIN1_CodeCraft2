<?php

namespace App\Policies;

use App\Models\User;

/**
 * Gestion des comptes par l'administration : permission Spatie "gerer utilisateurs".
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('gerer utilisateurs');
    }

    public function view(User $user, User $cible): bool
    {
        return $user->can('gerer utilisateurs');
    }

    public function create(User $user): bool
    {
        return $user->can('gerer utilisateurs');
    }

    public function update(User $user, User $cible): bool
    {
        return $user->can('gerer utilisateurs');
    }

    public function delete(User $user, User $cible): bool
    {
        return $user->can('gerer utilisateurs');
    }
}
