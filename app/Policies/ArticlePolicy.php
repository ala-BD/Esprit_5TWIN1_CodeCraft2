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
     * Tous les utilisateurs connectés peuvent publier un nouvel article.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Seul le propriétaire ou l'administrateur peut modifier l'article.
     */
    public function update(User $user, Article $article): bool
    {
        return $article->user_id === $user->id || $user->isAdmin();
    }

    /**
     * Seul le propriétaire ou l'administrateur peut supprimer l'article.
     */
    public function delete(User $user, Article $article): bool
    {
        return $article->user_id === $user->id || $user->isAdmin();
    }
}
