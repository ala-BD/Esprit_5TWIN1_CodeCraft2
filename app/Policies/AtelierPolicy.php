<?php

namespace App\Policies;

use App\Models\Atelier;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Autorisations du profil atelier.
 */
class AtelierPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Atelier $atelier): bool
    {
        return true;
    }

    /** Un compte ATELIER crée un seul profil */
    public function create(User $user): Response
    {
        if ($user->role !== User::ROLE_ATELIER) {
            return Response::deny('Réservé aux comptes Atelier.');
        }

        return Atelier::where('user_id', $user->id)->exists()
            ? Response::deny('Vous avez déjà un profil atelier.')
            : Response::allow();
    }

    public function update(User $user, Atelier $atelier): Response
    {
        return $atelier->user_id === $user->id
            ? Response::allow()
            : Response::deny('Seul le propriétaire peut modifier cet atelier.');
    }

    public function delete(User $user, Atelier $atelier): Response
    {
        return $this->update($user, $atelier);
    }
}
