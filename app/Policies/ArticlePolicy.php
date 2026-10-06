<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    /**
     * Tous les utilisateurs connectés peuvent voir la liste des articles.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Tous les utilisateurs connectés peuvent voir le détail d'un article.
     */
    public function view(User $user, Article $article): bool
    {
        return true;
    }

    /**
     * Seuls l'Atelier, le Donateur et l'Admin peuvent créer des articles.
     * Le Client, le Collecteur et le Recycleur ne peuvent pas ajouter d'articles.
     */
    public function create(User $user): bool
    {
        return $user->canCreateArticle();
    }

    /**
     * Seul le propriétaire (s'il est Atelier ou Donateur) ou l'Admin peut modifier l'article.
     */
    public function update(User $user, Article $article): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $article->user_id === $user->id && in_array($user->role, [User::ROLE_ATELIER, User::ROLE_DONATEUR]);
    }

    /**
     * Seul le propriétaire (s'il est Atelier ou Donateur) ou l'Admin peut supprimer l'article.
     */
    public function delete(User $user, Article $article): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $article->user_id === $user->id && in_array($user->role, [User::ROLE_ATELIER, User::ROLE_DONATEUR]);
    }

    /**
     * Règle stricte : Un atelier (ou tout vendeur) ne peut jamais acheter son propre article.
     * L'article doit en outre être disponible à la vente (stock > 0 et statut DISPONIBLE).
     */
    public function buy(User $user, Article $article): bool
    {
        return $article->user_id !== $user->id && $article->isDisponible();
    }
}
