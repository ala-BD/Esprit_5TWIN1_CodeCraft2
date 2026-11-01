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

    /**
     * Conversation en texte libre (assistant styliste).
     *
     * @param array $tours liste de ['role' => 'user'|'model', 'texte' => '...']
     */
    public function genererTexte(string $instruction, array $tours): string
    {
        set_time_limit(120);

        $contents = array_map(fn ($tour) => [
            'role'  => $tour['role'],
            'parts' => [['text' => $tour['texte']]],
        ], $tours);

        $reponse = Http::withHeaders(['x-goog-api-key' => config('services.gemini.key')])
            ->timeout((int) config('services.gemini.timeout'))
            ->post(sprintf(self::URL, config('services.gemini.model')), [
                'systemInstruction' => ['parts' => [['text' => $instruction]]],
                'contents'          => $contents,
                'generationConfig'  => [
                    'temperature'    => 0.7,
                    'thinkingConfig' => ['thinkingLevel' => 'minimal'],
                ],
            ]);

        if ($reponse->failed()) {
            throw new RuntimeException('Gemini HTTP ' . $reponse->status() . ' : ' . $reponse->json('error.message', $reponse->body()));
        }

        $texte = trim(collect($reponse->json('candidates.0.content.parts', []))->pluck('text')->filter()->implode(''));
        if ($texte === '') {
            throw new RuntimeException('Réponse Gemini vide (finishReason : ' . $reponse->json('candidates.0.finishReason', '?') . ').');
        }

        return $texte;
    }

    /**
     * Vecteurs d'embedding (gemini-embedding-001) pour une liste de textes.
     *
     * @param  array<int, string> $textes
     * @return array<int, array<int, float>>  un vecteur par texte, dans le même ordre
     */
    public function embeddings(array $textes, int $dimensions = 256): array
    {
        if (count($textes) === 0) return [];

        $modele = 'models/' . config('services.gemini.embedding_model', 'gemini-embedding-001');

        $reponse = Http::withHeaders(['x-goog-api-key' => config('services.gemini.key')])
            ->timeout((int) config('services.gemini.timeout'))
            ->post('https://generativelanguage.googleapis.com/v1beta/' . $modele . ':batchEmbedContents', [
                'requests' => array_map(fn ($texte) => [
                    'model'                => $modele,
                    'content'              => ['parts' => [['text' => $texte]]],
                    'outputDimensionality' => $dimensions,
                ], array_values($textes)),
            ]);

        if ($reponse->failed()) {
            throw new RuntimeException('Gemini embeddings HTTP ' . $reponse->status() . ' : ' . $reponse->json('error.message', $reponse->body()));
        }

        $vecteurs = array_map(fn ($e) => $e['values'] ?? [], $reponse->json('embeddings', []));
        if (count($vecteurs) !== count($textes)) {
            throw new RuntimeException('Gemini embeddings : nombre de vecteurs inattendu.');
        }

        return $vecteurs;
    }

    /** Similarité cosinus entre deux vecteurs (0 = sans rapport, 1 = identiques) */
    public static function cosinus(array $a, array $b): float
    {
        $produit = $normeA = $normeB = 0.0;
        foreach ($a as $i => $valeur) {
            $produit += $valeur * ($b[$i] ?? 0);
            $normeA  += $valeur ** 2;
        }
        foreach ($b as $valeur) {
            $normeB += $valeur ** 2;
        }

        return ($normeA && $normeB) ? $produit / (sqrt($normeA) * sqrt($normeB)) : 0.0;
    }
}
