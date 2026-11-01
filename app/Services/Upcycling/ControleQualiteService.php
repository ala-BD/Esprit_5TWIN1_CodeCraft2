<?php

namespace App\Services\Upcycling;

use App\Models\ProjetUpcycling;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * IA M3 — Contrôle qualité avant / après (vision Gemini).
 *
 * Compare la photo du vêtement d'origine, la photo du produit fini et l'idée retenue :
 * score de conformité, points forts, améliorations et description prête pour le portfolio.
 */
class ControleQualiteService
{
    public function __construct(private GeminiClient $gemini) {}

    /**
     * @return array|null null si l'IA n'est pas configurée, si une photo manque ou en cas d'erreur
     */
    public function verifier(ProjetUpcycling $projet): ?array
    {
        $avant = $this->chemin($projet->photo);
        $apres = $this->chemin($projet->photo_resultat);

        if (!$this->gemini->estConfigure() || !$apres) {
            return null;
        }

        $idee = $projet->idee_retenue;
        $parts = [];
        if ($avant) {
            $parts[] = ['text' => 'Photo 1 — vêtement d\'origine (avant) :'];
            $parts[] = $this->gemini->image($avant);
        }
        $parts[] = ['text' => 'Photo ' . ($avant ? '2' : '1') . ' — produit fini livré par l\'atelier (après) :'];
        $parts[] = $this->gemini->image($apres);
        $parts[] = ['text' => "Produit commandé : « {$projet->produit_final} »"
            . ($idee ? ". Description prévue : {$idee['description']}" : '')
            . ". Évalue si le produit fini correspond à la commande et à la qualité attendue d'un atelier professionnel. "
            . 'Donne un score de conformité de 0 à 100, un verdict en une phrase, 1 à 3 points forts, 0 à 3 améliorations possibles, '
            . 'et une description vendeuse de 2 phrases du produit fini pour le portfolio de l\'atelier.'];

        try {
            $data = $this->gemini->genererJson(
                "Tu es contrôleur qualité textile pour TextileCycle. Tu es exigeant mais juste. Réponds en français correct avec les accents.",
                $parts,
                [
                    'type'       => 'OBJECT',
                    'properties' => [
                        'conforme'              => ['type' => 'BOOLEAN'],
                        'score'                 => ['type' => 'INTEGER'],
                        'verdict'               => ['type' => 'STRING'],
                        'points_forts'          => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                        'ameliorations'         => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                        'description_portfolio' => ['type' => 'STRING'],
                    ],
                    'required' => ['conforme', 'score', 'verdict', 'points_forts', 'ameliorations', 'description_portfolio'],
                ]
            );
        } catch (Throwable $e) {
            Log::warning('Upcycling IA : contrôle qualité échoué.', ['erreur' => $e->getMessage()]);
            return null;
        }

        return [
            'conforme'              => (bool) ($data['conforme'] ?? false),
            'score'                 => max(0, min(100, (int) ($data['score'] ?? 0))),
            'verdict'               => (string) ($data['verdict'] ?? ''),
            'points_forts'          => array_values(array_map('strval', $data['points_forts'] ?? [])),
            'ameliorations'         => array_values(array_map('strval', $data['ameliorations'] ?? [])),
            'description_portfolio' => (string) ($data['description_portfolio'] ?? ''),
            'date'                  => now()->toDateTimeString(),
        ];
    }

    private function chemin(?string $photo): ?string
    {
        if (!$photo) return null;

        $chemin = str_starts_with($photo, 'images/') ? public_path($photo) : Storage::disk('public')->path($photo);

        return is_file($chemin) ? $chemin : null;
    }
}
