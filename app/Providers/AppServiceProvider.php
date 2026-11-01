<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
