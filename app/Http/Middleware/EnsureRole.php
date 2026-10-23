<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de protection par rôle.
 *
 * Usage dans les routes :
 *   ->middleware('role:ADMIN')
 *   ->middleware('role:CLIENT,DONATEUR,COLLECTEUR,RECYCLEUR')
 *   ->middleware('role:ACHETEUR')   // alias pour les 4 rôles acheteurs
 *
 * Si l'utilisateur n'a pas le bon rôle, il reçoit une 403 avec un message clair.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Expand alias ACHETEUR
        $expanded = [];
        foreach ($roles as $role) {
            if (strtoupper($role) === 'ACHETEUR') {
                array_push($expanded,
                    User::ROLE_CLIENT,
                    User::ROLE_DONATEUR,
                    User::ROLE_COLLECTEUR,
                    User::ROLE_RECYCLEUR,
                );
            } else {
                $expanded[] = strtoupper($role);
            }
        }

        if (! in_array($user->role, $expanded, true)) {
            abort(403, "Accès réservé aux rôles : " . implode(', ', $expanded) . ". Votre rôle est : {$user->role}.");
        }

        return $next($request);
    }
}
