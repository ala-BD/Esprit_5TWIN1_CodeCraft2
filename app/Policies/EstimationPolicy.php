<?php

namespace App\Policies;

use App\Models\Estimation;
use App\Models\User;

class EstimationPolicy
{
    /**
     * Seuls l'Atelier et l'Admin peuvent accéder à la liste des estimations.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->aLeRole(User::ROLE_ATELIER);
    }

    /**
     * Un atelier ne peut consulter que ses propres estimations (l'admin peut tout voir).
     */
    public function view(User $user, Estimation $estimation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->aLeRole(User::ROLE_ATELIER) && $estimation->user_id === $user->id;
    }

    /**
     * Seuls l'Atelier et l'Admin peuvent lancer une estimation.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->aLeRole(User::ROLE_ATELIER);
    }

    /**
     * Suppression d'une estimation de son historique.
     */
    public function delete(User $user, Estimation $estimation): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->aLeRole(User::ROLE_ATELIER) && $estimation->user_id === $user->id;
    }
}
