<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Module M5 Logistique : réservé aux utilisateurs COLLECTEUR.
 */
class EnsureCollecteur
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->hasRole(User::ROLE_COLLECTEUR), 403, 'Accès réservé aux collecteurs.');

        return $next($request);
    }
}
