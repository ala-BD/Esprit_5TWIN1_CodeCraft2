<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\TokenRequest;
use App\Http\Resources\Upcycling\UtilisateurResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Authentification de l'API par token Sanctum.
 */
class AuthTokenController extends Controller
{
    /*
    |------------------------------------------------------------------
    | POST /api/v1/auth/token — Échanger e-mail + mot de passe contre un token
    |------------------------------------------------------------------
    */
    public function store(TokenRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (!$user || !Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => 'Identifiants incorrects.']);
        }
        if (!$user->actif) {
            throw ValidationException::withMessages(['email' => 'Ce compte est désactivé.']);
        }

        $token = $user->createToken($request->validated('nom_appareil'), $request->abilities(), now()->addDays(30));

        return response()->json([
            'token'        => $token->plainTextToken,
            'type'         => 'Bearer',
            'abilities'    => $token->accessToken->abilities,
            'expire_le'    => $token->accessToken->expires_at?->toIso8601String(),
            'utilisateur'  => (new UtilisateurResource($user))->avecEmail(),
        ], 201);
    }

    /*
    |------------------------------------------------------------------
    | GET /api/v1/auth/moi — Utilisateur du token
    |------------------------------------------------------------------
    */
    public function moi(Request $request): UtilisateurResource
    {
        return (new UtilisateurResource($request->user()))->additional([
            'token' => [
                'abilities' => $request->user()->currentAccessToken()?->abilities,
            ],
        ]);
    }

    /*
    |------------------------------------------------------------------
    | DELETE /api/v1/auth/token — Révoquer le token utilisé
    |------------------------------------------------------------------
    */
    public function destroy(Request $request): Response
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }
}
