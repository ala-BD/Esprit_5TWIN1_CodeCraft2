<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Règle métier non respectée (ex. accepter un devis déjà traité).
 *
 * Une seule exception pour le site et l'API :
 *  - API / requête JSON → 422 {"message": "..."}
 *  - site web           → retour à la page précédente avec un message d'erreur
 */
class RegleMetierException extends RuntimeException
{
    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $this->getMessage()], 422);
        }

        return back()->withInput()->with('error', $this->getMessage());
    }
}
