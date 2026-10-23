<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Avis;
use App\Models\Commande;
use App\Models\Favori;
use App\Models\Panier;
use App\Models\PanierLigne;
use App\Observers\ArticleObserver;
use App\Policies\AvisPolicy;
use App\Policies\CommandePolicy;
use App\Policies\PanierLignePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // -------------------------------------------------------
        // Observers
        // -------------------------------------------------------
        Article::observe(ArticleObserver::class);

        // -------------------------------------------------------
        // Policies
        // -------------------------------------------------------
        Gate::policy(PanierLigne::class, PanierLignePolicy::class);
        Gate::policy(Commande::class, CommandePolicy::class);
        Gate::policy(Avis::class, AvisPolicy::class);

        // -------------------------------------------------------
        // View Composer — Compteurs panier & favoris partagés dans toutes les vues
        // -------------------------------------------------------
        View::composer('*', function ($view) {
            if (auth()->check()) {
                $userId = auth()->id();

                // Panier count
                $panier = Panier::where('user_id', $userId)
                    ->with('lignes')
                    ->first();
                $panierCount = $panier ? (int) $panier->lignes->sum('quantite') : 0;

                // Favoris count
                $favorisCount = Favori::where('user_id', $userId)->count();

                $view->with([
                    'panierCount'  => $panierCount,
                    'favorisCount' => $favorisCount,
                ]);
            } else {
                $view->with([
                    'panierCount'  => 0,
                    'favorisCount' => 0,
                ]);
            }
        });
    }
}
