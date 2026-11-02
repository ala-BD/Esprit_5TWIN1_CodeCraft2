<?php

namespace App\Services;

use App\Services\Upcycling\GeminiClient;
use Illuminate\Support\Str;

class DonVetementAiService
{
    public function __construct(private GeminiClient $gemini) {}

    public function estConfigure(): bool
    {
        return $this->gemini->estConfigure();
    }

    /**
     * @return array{est_vetement: bool, type: string, matiere: string, couleur: string, taille: string, etat: string}
     */
    public function suggererDepuisPhoto(string $chemin): array
    {
        $data = $this->gemini->genererJson(
            "Tu aides un donateur de TextileCycle à décrire un vêtement à partir d'une photo. "
            . "Réponds en français. La taille doit être estimée visuellement à partir de la coupe et des proportions "
            . "du vêtement, même si aucune étiquette n'est visible. Cette estimation n'est pas une mesure fiable "
            . "et sera vérifiée par le donateur.",
            [
                $this->gemini->image($chemin),
                ['text' => "Indique si la photo montre un vêtement. Suggère son type, sa matière probable, "
                    . "sa couleur principale visible, une taille visuellement estimée parmi XS, S, M, L, XL ou XXL "
                    . "(tu peux proposer une taille numérique si elle est lisible sur une étiquette), "
                    . "et son état visuel parmi Neuf, Très bon, Bon ou À réparer. "
                    . "En cas d'incertitude sur l'état, choisis Bon."],
            ],
            [
                'type' => 'OBJECT',
                'properties' => [
                    'est_vetement' => ['type' => 'BOOLEAN'],
                    'type' => ['type' => 'STRING'],
                    'matiere' => ['type' => 'STRING'],
                    'couleur' => ['type' => 'STRING'],
                    'taille' => ['type' => 'STRING'],
                    'etat' => ['type' => 'STRING', 'enum' => ['Neuf', 'Très bon', 'Bon', 'À réparer']],
                ],
                'required' => ['est_vetement', 'type', 'matiere', 'couleur', 'taille', 'etat'],
            ]
        );

        return [
            'est_vetement' => (bool) ($data['est_vetement'] ?? false),
            'type' => Str::ucfirst(trim((string) ($data['type'] ?? ''))),
            'matiere' => Str::ucfirst(trim((string) ($data['matiere'] ?? ''))),
            'couleur' => Str::ucfirst(trim((string) ($data['couleur'] ?? ''))),
            'taille' => trim((string) ($data['taille'] ?? '')),
            'etat' => in_array($data['etat'] ?? '', ['Neuf', 'Très bon', 'Bon', 'À réparer'], true)
                ? $data['etat']
                : 'Bon',
        ];
    }
}
