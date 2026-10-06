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
        return $commande->user_id === $user->id || $user->isAdmin();
    }

    /**
     * Any logged-in user can place an order (business rules enforced in controller).
     */
    public function create(User $user): bool
    {
        return true;
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
     * Only the owner can cancel (before shipping), Admin always can.
     */
    public function delete(User $user, Commande $commande): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $commande->user_id === $user->id && $commande->estAnnulable();
    }

    /**
     * Only admin can update status (advance workflow).
     */
    public function updateStatut(User $user, Commande $commande): bool
    {
        return $user->isAdmin();
    }
}
