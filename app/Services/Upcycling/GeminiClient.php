<?php

namespace App\Services\Upcycling;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client minimal pour l'API Google Gemini (generateContent).
 *
 * La réponse est contrainte par un schéma JSON (responseSchema),
 * ce qui garantit un résultat directement exploitable.
 */
class GeminiClient
{
    const URL = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    public function estConfigure(): bool
    {
        return filled(config('services.gemini.key'));
    }

    /**
     * Construit une partie "image" à partir d'un fichier local.
     */
    public function image(string $chemin): array
    {
        $mime = mime_content_type($chemin) ?: 'image/jpeg';

        return ['inline_data' => [
            'mime_type' => $mime,
            'data'      => base64_encode(file_get_contents($chemin)),
        ]];
    }

    /**
     * Envoie un prompt (texte + images éventuelles) et renvoie le JSON décodé.
     *
     * @param array $parts   parties du message : ['text' => '...'] ou $this->image(...)
     * @param array $schema  schéma de la réponse (format OpenAPI de Gemini)
     */
    public function genererJson(string $instruction, array $parts, array $schema): array
    {
        set_time_limit(120);

        $reponse = Http::withHeaders(['x-goog-api-key' => config('services.gemini.key')])
            ->timeout((int) config('services.gemini.timeout'))
            ->post(sprintf(self::URL, config('services.gemini.model')), [
                'systemInstruction' => ['parts' => [['text' => $instruction]]],
                'contents'          => [['role' => 'user', 'parts' => $parts]],
                'generationConfig'  => [
                    'responseMimeType' => 'application/json',
                    'responseSchema'   => $schema,
                    'temperature'      => 0.9,
                    'thinkingConfig'   => ['thinkingLevel' => 'minimal'],
                ],
            ]);

        if ($reponse->failed()) {
            throw new RuntimeException('Gemini HTTP ' . $reponse->status() . ' : ' . $reponse->json('error.message', $reponse->body()));
        }

        $texte = collect($reponse->json('candidates.0.content.parts', []))
            ->pluck('text')
            ->filter()
            ->implode('');

        $data = json_decode($texte, true);
        if (!is_array($data)) {
            throw new RuntimeException('Réponse Gemini illisible (finishReason : ' . $reponse->json('candidates.0.finishReason', '?') . ').');
        }

        return $data;
    }
}
