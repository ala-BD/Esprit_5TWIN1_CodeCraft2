<?php

namespace App\Policies;

use App\Models\PanierLigne;
use App\Models\User;

/**
 * Politique d'accès aux lignes de panier.
 *
 * Règle fondamentale : un utilisateur ne peut interagir
 * qu'avec les lignes de SON propre panier.
 */
class PanierLignePolicy
{
    /**
     * L'utilisateur peut modifier une ligne uniquement si
     * elle appartient à son propre panier.
     */
    public function update(User $user, PanierLigne $ligne): bool
    {
        return $ligne->panier->user_id === $user->id;
    }

    /**
     * L'utilisateur peut supprimer une ligne uniquement si
     * elle appartient à son propre panier.
     */
    public function delete(User $user, PanierLigne $ligne): bool
    {
        return $ligne->panier->user_id === $user->id;
    }
}
