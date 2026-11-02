<?php

namespace App\Policies;

use App\Models\Commande;
use App\Models\User;

class CommandePolicy
{
    /**
     * Any logged-in user can see their own orders list.
     * Admin sees all orders.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * A user can only view their own orders. Admin can view all.
     */
    public function view(User $user, Commande $commande): bool
    {
        return $commande->user_id === $user->id
            || $user->isAdmin()
            || $commande->lignes()->whereHas('article', fn($q) => $q->where('user_id', $user->id))->exists();
    }

    /**
     * Any logged-in user can place an order EXCEPT Atelier (vendor role).
     * Atelier is a seller, not a buyer.
     */
    public function create(User $user): bool
    {
        return $user->role !== User::ROLE_ATELIER;
    }

    /**
     * Only the owner can update (while modifiable), Admin always can.
     */
    public function update(User $user, Commande $commande): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $commande->user_id === $user->id && $commande->estModifiable();
    }

    /**
     * Only the owner can cancel or delete, Admin always can.
     */
    public function delete(User $user, Commande $commande): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $commande->user_id === $user->id;
    }

    /**
     * Admin can update any status.
     * Atelier can confirm/prepare/ship orders that contain their articles.
     */
    public function updateStatut(User $user, Commande $commande): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Atelier can manage orders that contain at least one of their articles
        if ($user->role === User::ROLE_ATELIER) {
            return $commande->lignes()
                ->whereHas('article', fn($q) => $q->where('user_id', $user->id))
                ->exists();
        }

        return false;
    }

    /**
     * An owner can pay their own order via Stripe as long as it has not already been paid
     * and is not cancelled.
     */
    public function payer(User $user, Commande $commande): bool
    {
        return $commande->user_id === $user->id
            && ! $commande->estPaye()
            && $commande->statut !== Commande::STATUT_ANNULEE;
    }
}
