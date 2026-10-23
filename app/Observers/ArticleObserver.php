<?php

namespace App\Observers;

use App\Models\Article;
use App\Models\Favori;
use App\Models\RechercheSauvegardee;
use App\Notifications\FavoriBientotEnRuptureNotification;
use App\Notifications\FavoriPrixBaisseNotification;
use App\Notifications\FavoriRetourStockNotification;
use App\Notifications\NouvelArticleRechercheNotification;

/**
 * Observer Eloquent sur le modèle Article.
 * Déclenche des notifications in-app et e-mail quand :
 *  - un article favori baisse de prix
 *  - un article favori revient en stock (stock 0 → > 0)
 *  - un nouvel article correspond à une recherche sauvegardée
 */
class ArticleObserver
{
    /**
     * Appelé après la création d'un article.
     * Cherche les recherches sauvegardées dont les critères correspondent.
     */
    public function created(Article $article): void
    {
        if ($article->statut !== Article::STATUT_DISPONIBLE) {
            return;
        }

        RechercheSauvegardee::with('user')->chunk(50, function ($recherches) use ($article) {
            /** @var RechercheSauvegardee $recherche */
            foreach ($recherches as $recherche) {
                // Ne pas notifier le vendeur de son propre article
                if ($recherche->user_id === $article->user_id) {
                    continue;
                }

                // Vérifie que l'article correspond aux filtres sauvegardés
                $match = $recherche->buildArticleQuery()
                    ->where('id', $article->id)
                    ->exists();

                if ($match) {
                    $recherche->user->notify(new NouvelArticleRechercheNotification($article, $recherche));

                    // Met à jour derniere_notification_at pour éviter le spam
                    $recherche->update(['derniere_notification_at' => now()]);
                }
            }
        });
    }

    /**
     * Appelé après la mise à jour d'un article.
     * Détecte les baisses de prix et les retours en stock.
     */
    public function updated(Article $article): void
    {
        // ---- Baisse de prix ----
        if ($article->wasChanged('prix') && $article->prix < $article->getOriginal('prix')) {
            $ancienPrix = (float) $article->getOriginal('prix');
            $nouveauPrix = (float) $article->prix;

            Favori::with('user')
                ->where('article_id', $article->id)
                ->chunk(50, function ($favoris) use ($article, $ancienPrix, $nouveauPrix) {
                    /** @var Favori $favori */
                    foreach ($favoris as $favori) {
                        // Ne pas notifier le vendeur
                        if ($favori->user_id === $article->user_id) {
                            continue;
                        }
                        $favori->user->notify(new FavoriPrixBaisseNotification($article, $ancienPrix, $nouveauPrix));
                        // Mémorise le nouveau prix de référence
                        $favori->update(['prix_au_moment_ajout' => $nouveauPrix]);
                    }
                });
        }

        // ---- Retour en stock ----
        if ($article->wasChanged('stock')
            && (int) $article->getOriginal('stock') <= 0
            && (int) $article->stock > 0
        ) {
            Favori::with('user')
                ->where('article_id', $article->id)
                ->chunk(50, function ($favoris) use ($article) {
                    /** @var Favori $favori */
                    foreach ($favoris as $favori) {
                        if ($favori->user_id === $article->user_id) {
                            continue;
                        }
                        $favori->user->notify(new FavoriRetourStockNotification($article));
                    }
                });
        }

        // ---- Bientôt en rupture (stock tombe à 2 ou 1) ----
        if ($article->wasChanged('stock')
            && (int) $article->getOriginal('stock') > 2
            && (int) $article->stock <= 2
            && (int) $article->stock > 0
        ) {
            Favori::with('user')
                ->where('article_id', $article->id)
                ->chunk(50, function ($favoris) use ($article) {
                    /** @var Favori $favori */
                    foreach ($favoris as $favori) {
                        if ($favori->user_id === $article->user_id) {
                            continue;
                        }
                        $favori->user->notify(new FavoriBientotEnRuptureNotification($article, (int) $article->stock));
                    }
                });
        }

        // ---- Nouvel article devenu DISPONIBLE (ex-ARCHIVE remis en vente) ----
        if ($article->wasChanged('statut')
            && $article->statut === Article::STATUT_DISPONIBLE
        ) {
            RechercheSauvegardee::with('user')->chunk(50, function ($recherches) use ($article) {
                /** @var RechercheSauvegardee $recherche */
                foreach ($recherches as $recherche) {
                    if ($recherche->user_id === $article->user_id) {
                        continue;
                    }
                    $match = $recherche->buildArticleQuery()
                        ->where('id', $article->id)
                        ->exists();
                    if ($match) {
                        $recherche->user->notify(new NouvelArticleRechercheNotification($article, $recherche));
                        $recherche->update(['derniere_notification_at' => now()]);
                    }
                }
            });
        }
    }
}
