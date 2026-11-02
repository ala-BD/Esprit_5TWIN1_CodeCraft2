<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\Avis;
use App\Models\Commande;
use App\Models\User;

class AvisPolicy
{
    /**
     * Un utilisateur peut laisser un avis uniquement s'il a acheté cet article,
     * que la commande est livrée, que ce n'est pas son propre article,
     * et qu'il n'a pas encore laissé d'avis pour cet article dans cette commande.
     */
    public function create(User $user, Commande $commande, Article $article): bool
    {
        // 1. L'utilisateur doit être l'acheteur de la commande
        if ($commande->user_id !== $user->id) {
            return false;
        }

        // 2. La commande doit impérativement être livrée
        if ($commande->statut !== Commande::STATUT_LIVREE) {
            return false;
        }

        // 3. L'article doit appartenir aux lignes de cette commande
        $articleInOrder = $commande->lignes()->where('article_id', $article->id)->exists();
        if (! $articleInOrder) {
            return false;
        }

        // 4. L'utilisateur ne peut pas évaluer son propre article
        if ($article->user_id === $user->id) {
            return false;
        }

        // 5. Un seul avis par ligne d'article par commande
        $alreadyReviewed = Avis::where('commande_id', $commande->id)
            ->where('article_id', $article->id)
            ->where('user_id', $user->id)
            ->exists();

        return ! $alreadyReviewed;
    }

    /**
     * Seul l'auteur de l'avis peut le modifier.
     */
    public function update(User $user, Avis $avis): bool
    {
        return $avis->user_id === $user->id;
    }

    /**
     * L'auteur ou un Administrateur peut supprimer un avis (modération).
     */
    public function delete(User $user, Avis $avis): bool
    {
        return $avis->user_id === $user->id || $user->isAdmin();
    }

    /**
     * N'importe quel utilisateur connecté sauf l'auteur peut voter (like / dislike).
     */
    public function vote(User $user, Avis $avis): bool
    {
        return $avis->user_id !== $user->id;
    }
}
