<?php

namespace App\Providers;

use App\Models\Avis;
use App\Models\Commande;
use App\Models\Panier;
use App\Models\PanierLigne;
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
        // Policies
        // -------------------------------------------------------
        Gate::policy(PanierLigne::class, PanierLignePolicy::class);
        Gate::policy(Commande::class, CommandePolicy::class);
        Gate::policy(Avis::class, AvisPolicy::class);

        // -------------------------------------------------------
        // View Composer — Compteur panier partagé dans toutes les vues
        // -------------------------------------------------------
        // Injecte $panierCount (nb d'articles) dans toutes les vues
        // pour afficher le badge du panier dans la navbar.
        View::composer('*', function ($view) {
            if (auth()->check()) {
                $panier = Panier::where('user_id', auth()->id())
                    ->withCount('lignes')
                    ->first();

                $count = 0;
                if ($panier) {
                    // Somme réelle des quantités
                    $count = (int) Panier::where('user_id', auth()->id())
                        ->with('lignes')
                        ->first()
                        ?->lignes
                        ?->sum('quantite') ?? 0;
                }

                $view->with('panierCount', $count);
            } else {
                $view->with('panierCount', 0);
            }
        });
    }
}
