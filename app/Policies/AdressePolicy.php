<?php

namespace App\Policies;

use App\Models\Adresse;
use App\Models\User;

class AdressePolicy
{
    /**
     * Détermine si l'utilisateur peut afficher la liste de ses adresses.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Détermine si l'utilisateur peut voir une adresse précise.
     */
    public function view(User $user, Adresse $adresse): bool
    {
        return $user->id === $adresse->user_id;
    }

    /**
     * Détermine si l'utilisateur peut créer une adresse.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Détermine si l'utilisateur peut modifier une adresse.
     */
    public function update(User $user, Adresse $adresse): bool
    {
        return $user->id === $adresse->user_id;
    }

    /**
     * Détermine si l'utilisateur peut supprimer une adresse.
     */
    public function delete(User $user, Adresse $adresse): bool
    {
        return $user->id === $adresse->user_id;
    }
}
