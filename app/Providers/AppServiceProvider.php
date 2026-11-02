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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->configurerRateLimiting();
    }

    /**
     * Limites d'appels (middleware throttle:...).
     *  - api       : 60 requêtes / minute par utilisateur (ou par IP)
     *  - api-token : 5 tentatives de connexion / minute par email + IP (anti brute force)
     *  - ia        : 10 appels IA / minute par utilisateur (protège le quota Gemini)
     */
    private function configurerRateLimiting(): void
    {
        $reponse429 = fn (string $message) => function (Request $request, array $headers) use ($message) {
            return $request->expectsJson() || $request->is('api/*')
                ? response()->json(['message' => $message, 'reessayer_dans' => (int) ($headers['Retry-After'] ?? 60)], 429, $headers)
                : back()->with('error', $message);
        };

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip())
            ->response($reponse429('Trop de requêtes : 60 par minute maximum.')));

        RateLimiter::for('api-token', fn (Request $request) => Limit::perMinute(5)
            ->by(mb_strtolower((string) $request->input('email')) . '|' . $request->ip())
            ->response($reponse429('Trop de tentatives de connexion. Réessayez dans une minute.')));

        RateLimiter::for('ia', fn (Request $request) => Limit::perMinute(10)
            ->by($request->user()?->id ?: $request->ip())
            ->response($reponse429("Trop de demandes à l'IA : 10 par minute maximum. Patientez un instant.")));
    }
}
