<?php

namespace App\Services\Upcycling;

use Anthropic\Client;
use App\Models\Atelier;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * IA M3 — Génération d'idées d'upcycling.
 *
 * Utilise Claude (API Anthropic) si ANTHROPIC_API_KEY est configurée,
 * sinon (ou en cas d'erreur) un générateur local à base de règles,
 * pour que l'application reste utilisable sans clé.
 */
class IdeeUpcyclingService
{
    const SOURCE_CLAUDE = 'CLAUDE';
    const SOURCE_LOCAL  = 'LOCAL';

    const DIFFICULTES = ['FACILE', 'MOYEN', 'DIFFICILE'];

    /**
     * @return array{source: string, idees: array<int, array{titre: string, description: string, categorie: string, difficulte: string, duree_heures: int, materiaux: array<int, string>}>}
     */
    public function generer(string $type, string $matiere, string $etat, ?string $description = null): array
    {
        if (config('services.anthropic.key')) {
            try {
                $idees = $this->genererAvecClaude($type, $matiere, $etat, $description);
                if (count($idees) > 0) {
                    return ['source' => self::SOURCE_CLAUDE, 'idees' => $idees];
                }
            } catch (Throwable $e) {
                Log::warning('Upcycling IA : appel Claude échoué, bascule sur le générateur local.', [
                    'erreur' => $e->getMessage(),
                ]);
            }
        }

        return ['source' => self::SOURCE_LOCAL, 'idees' => $this->genererLocalement($type, $matiere, $etat)];
    }

    /*
    |------------------------------------------------------------------
    | Claude (structured outputs → JSON garanti conforme au schéma)
    |------------------------------------------------------------------
    */
    private function genererAvecClaude(string $type, string $matiere, string $etat, ?string $description): array
    {
        set_time_limit(90);

        $client = new Client(apiKey: config('services.anthropic.key'));

        $prompt = "Vêtement à transformer :\n"
            . "- Type : {$type}\n"
            . "- Matière : {$matiere}\n"
            . "- État : {$etat}\n"
            . '- Précisions du client : ' . ($description ?: 'aucune') . "\n\n"
            . 'Propose exactement 3 idées d\'upcycling différentes et réalisables par un atelier de couture tunisien.';

        $message = $client->messages->create(
            model: config('services.anthropic.model'),
            maxTokens: 4000,
            system: 'Tu es un styliste expert en upcycling textile pour la plateforme TextileCycle. '
                . 'Tes idées sont concrètes, adaptées à la matière et à l\'état du vêtement, et rédigées en français. '
                . 'La catégorie doit correspondre au produit fini.',
            messages: [
                ['role' => 'user', 'content' => $prompt],
            ],
            outputConfig: [
                'effort' => 'low',
                'format' => [
                    'type'   => 'json_schema',
                    'schema' => $this->schema(),
                ],
            ],
        );

        if ($message->stopReason === 'refusal') {
            return [];
        }

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $data = json_decode($block->text, true);
                return $this->normaliser($data['idees'] ?? []);
            }
        }

        return [];
    }

    private function schema(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'idees' => [
                    'type'  => 'array',
                    'items' => [
                        'type'       => 'object',
                        'properties' => [
                            'titre'        => ['type' => 'string', 'description' => 'Nom court du produit fini'],
                            'description'  => ['type' => 'string', 'description' => 'Deux ou trois phrases sur la transformation'],
                            'categorie'    => ['type' => 'string', 'enum' => array_keys(Atelier::SPECIALITES)],
                            'difficulte'   => ['type' => 'string', 'enum' => self::DIFFICULTES],
                            'duree_heures' => ['type' => 'integer', 'description' => 'Temps de travail estimé en heures'],
                            'materiaux'    => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                        'required'             => ['titre', 'description', 'categorie', 'difficulte', 'duree_heures', 'materiaux'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required'             => ['idees'],
            'additionalProperties' => false,
        ];
    }

    /** Garde au plus 3 idées et sécurise les valeurs */
    private function normaliser(array $idees): array
    {
        return collect($idees)->take(3)->map(fn ($idee) => [
            'titre'        => (string) ($idee['titre'] ?? 'Idée'),
            'description'  => (string) ($idee['description'] ?? ''),
            'categorie'    => array_key_exists($idee['categorie'] ?? '', Atelier::SPECIALITES) ? $idee['categorie'] : 'VETEMENT',
            'difficulte'   => in_array($idee['difficulte'] ?? '', self::DIFFICULTES, true) ? $idee['difficulte'] : 'MOYEN',
            'duree_heures' => max(1, (int) ($idee['duree_heures'] ?? 4)),
            'materiaux'    => array_values(array_map('strval', $idee['materiaux'] ?? [])),
        ])->values()->all();
    }

    /*
    |------------------------------------------------------------------
    | Générateur local (sans clé API)
    |------------------------------------------------------------------
    */
    public function genererLocalement(string $type, string $matiere, string $etat): array
    {
        $type    = mb_strtolower($type);
        $matiere = mb_strtolower($matiere);

        $catalogue = [
            'jean' => [
                ['Sac cabas en denim', 'Le jean est découpé et cousu en sac cabas solide ; les poches arrière deviennent des poches extérieures.', 'SAC', 'MOYEN', 6, ['doublure coton', 'sangles', 'fil épais']],
                ['Short effet vintage', 'Les jambes sont raccourcies, les bords effilochés et décorés de patchs.', 'VETEMENT', 'FACILE', 2, ['patchs thermocollants']],
                ['Coussin patchwork denim', 'Des carrés de différentes nuances de bleu sont assemblés en housse de coussin.', 'PATCHWORK', 'MOYEN', 4, ['rembourrage', 'fermeture éclair']],
            ],
            'chemise' => [
                ['Top cache-cœur', 'La chemise est recoupée et croisée devant pour créer un top cache-cœur noué à la taille.', 'VETEMENT', 'MOYEN', 4, ['biais', 'fil assorti']],
                ['Tote bag léger', 'Le dos de la chemise devient un sac de course pliable avec les manches en anses.', 'SAC', 'FACILE', 3, ['fil solide']],
                ['Chouchous et bandeaux', 'Les chutes de tissu sont transformées en chouchous et bandeaux assortis.', 'ACCESSOIRE', 'FACILE', 1, ['élastique']],
            ],
            'pull' => [
                ['Plaid patchwork en maille', 'Plusieurs carrés de pull feutrés sont assemblés en plaid chaud.', 'PATCHWORK', 'DIFFICILE', 10, ['doublure polaire', 'fil laine']],
                ['Bonnet et mitaines', 'Le bas et les manches du pull deviennent un bonnet et une paire de mitaines.', 'ACCESSOIRE', 'MOYEN', 3, ['élastique', 'pompon']],
                ['Housse de coussin tricot', 'Le devant du pull devient une housse de coussin texturée.', 'DECORATION', 'FACILE', 2, ['boutons', 'rembourrage']],
            ],
            'robe' => [
                ['Jupe midi', 'Le haut de la robe est retiré et la taille reprise avec une ceinture élastique.', 'VETEMENT', 'FACILE', 3, ['élastique large']],
                ['Pochette de soirée', 'Le tissu de la robe est doublé et monté en pochette avec fermoir.', 'SAC', 'MOYEN', 4, ['fermoir métal', 'entoilage']],
                ['Guirlande de fanions', 'Le tissu est découpé en triangles pour une guirlande décorative.', 'DECORATION', 'FACILE', 2, ['biais', 'cordon']],
            ],
            'veste' => [
                ['Veste customisée', 'La veste est ajustée et personnalisée avec broderies et empiècements contrastés.', 'VETEMENT', 'DIFFICILE', 8, ['fil à broder', 'tissu contrasté']],
                ['Sac à dos', 'Le tissu de la veste devient un petit sac à dos ; les poches sont conservées.', 'SAC', 'DIFFICILE', 8, ['sangles réglables', 'doublure']],
                ['Trousse zippée', 'Les chutes servent à confectionner des trousses avec fermeture éclair.', 'ACCESSOIRE', 'FACILE', 2, ['fermeture éclair']],
            ],
        ];

        $cle = collect(array_keys($catalogue))->first(fn ($mot) => str_contains($type, $mot));
        if (!$cle) {
            $cle = str_contains($matiere, 'denim') ? 'jean' : (str_contains($matiere, 'laine') ? 'pull' : 'chemise');
        }

        // Un vêtement abîmé est plutôt découpé (accessoires, patchwork) que porté
        $idees = $catalogue[$cle];
        if ($etat === 'ABIME') {
            usort($idees, fn ($a, $b) => ($a[2] === 'VETEMENT') <=> ($b[2] === 'VETEMENT'));
        }

        return array_map(fn ($i) => [
            'titre'        => $i[0],
            'description'  => $i[1],
            'categorie'    => $i[2],
            'difficulte'   => $i[3],
            'duree_heures' => $i[4],
            'materiaux'    => $i[5],
        ], $idees);
    }
}
