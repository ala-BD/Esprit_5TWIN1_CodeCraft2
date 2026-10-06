<?php

namespace App\Services\Assistant;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lit un texte anglais avec une voix naturelle (modèle Orpheus, API « audio/speech »).
 * Le navigateur garde sa propre voix pour le français, et en secours si ce service échoue.
 */
class Synthetiseur
{
    /** Longueur maximale d'un texte par requête : le navigateur découpe les réponses plus longues */
    public const LONGUEUR_MAX = 200;

    public function estConfigure(): bool
    {
        return filled(config('assistant.api_key')) && filled(config('assistant.voice_model'));
    }

    /** @return array{audio: string, type: string}|array{erreur: 'reseau'|'cle'|'quota'|'conditions'|'service'} */
    public function lire(string $texte): array
    {
        try {
            $reponse = Http::withToken(config('assistant.api_key'))
                ->timeout(config('assistant.timeout'))
                ->post(config('assistant.base_url') . '/audio/speech', [
                    'model'           => config('assistant.voice_model'),
                    'voice'           => config('assistant.voice'),
                    'input'           => $texte,
                    'response_format' => 'wav',
                ]);
        } catch (ConnectionException $e) {
            return ['erreur' => 'reseau'];
        }

        if ($reponse->failed()) {
            Log::warning('Assistant vocal : erreur de synthèse vocale', ['statut' => $reponse->status(), 'corps' => $reponse->body()]);

            return ['erreur' => match (true) {
                // Le propriétaire du compte doit accepter les conditions du modèle dans la console du fournisseur
                str_contains($reponse->body(), 'model_terms_required') => 'conditions',
                in_array($reponse->status(), [401, 403])               => 'cle',
                $reponse->status() === 429                             => 'quota',
                default                                                => 'service',
            }];
        }

        return ['audio' => $reponse->body(), 'type' => $reponse->header('content-type') ?: 'audio/wav'];
    }
}
