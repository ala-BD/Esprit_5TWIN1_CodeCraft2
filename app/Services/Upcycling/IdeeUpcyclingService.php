<?php

namespace App\Services\Upcycling;

use App\Models\Atelier;
use App\Models\ProjetUpcycling;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * IA M3 — Upcycling avec Google Gemini (vision + génération).
 *
 *  1. analyserPhoto() : reconnaît le vêtement sur la photo (type, matière, couleur, état, défauts)
 *  2. generer()       : propose 3 idées de transformation adaptées (étapes, prix, matériaux)
 *
 * Sans clé GEMINI_API_KEY (ou en cas d'erreur), un générateur local
 * à base de règles prend le relais pour que l'application reste utilisable.
 */
class IdeeUpcyclingService
{
    const SOURCE_GEMINI = 'GEMINI';
    const SOURCE_LOCAL  = 'LOCAL';

    const DIFFICULTES = ['FACILE', 'MOYEN', 'DIFFICILE'];

    /**
     * Impact moyen de la fabrication d'un vêtement neuf (kg CO2e, litres d'eau),
     * évité lorsqu'on transforme un vêtement existant au lieu d'en acheter un.
     * Ordres de grandeur ADEME / Ellen MacArthur Foundation.
     */
    const IMPACT = [
        'jean'     => [33, 7500],
        'pantalon' => [25, 5000],
        'short'    => [15, 3500],
        'chemise'  => [10, 2700],
        'tshirt'   => [7, 2700],
        'pull'     => [20, 5000],
        'robe'     => [15, 4000],
        'veste'    => [30, 6000],
        'jupe'     => [12, 3000],
    ];

    public function __construct(private GeminiClient $gemini) {}

    public function estConfigure(): bool
    {
        return $this->gemini->estConfigure();
    }

    /*
    |------------------------------------------------------------------
    | 1. Analyse de la photo (pré-remplissage du formulaire)
    |------------------------------------------------------------------
    */

    /**
     * @return array{est_vetement: bool, type_vetement: string, matiere: string, couleur: string, etat: string, description: string, defauts: array<int, string>}
     */
    public function analyserPhoto(string $chemin): array
    {
        $data = $this->gemini->genererJson(
            "Tu es expert textile pour TextileCycle, une plateforme tunisienne d'upcycling. "
            . "Tu identifies un vêtement à partir d'une photo. Réponds en français correct avec les accents, avec des mots simples.",
            [
                $this->gemini->image($chemin),
                ['text' => "Identifie ce vêtement : type (un ou deux mots, ex: jean, chemise, blazer), matière probable, "
                    . "couleur principale, état (BON = comme neuf, USE = usure visible, ABIME = trou ou tache), "
                    . "défauts visibles (liste vide si aucun) et une description d'une phrase. "
                    . "Si la photo ne montre pas un vêtement, mets est_vetement à false."],
            ],
            [
                'type'       => 'OBJECT',
                'properties' => [
                    'est_vetement'  => ['type' => 'BOOLEAN'],
                    'type_vetement' => ['type' => 'STRING'],
                    'matiere'       => ['type' => 'STRING'],
                    'couleur'       => ['type' => 'STRING'],
                    'etat'          => ['type' => 'STRING', 'enum' => array_keys(ProjetUpcycling::ETATS)],
                    'description'   => ['type' => 'STRING'],
                    'defauts'       => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                ],
                'required' => ['est_vetement', 'type_vetement', 'matiere', 'couleur', 'etat', 'description', 'defauts'],
            ]
        );

        return [
            'est_vetement'  => (bool) ($data['est_vetement'] ?? false),
            'type_vetement' => Str::ucfirst(trim($data['type_vetement'] ?? '')),
            'matiere'       => Str::ucfirst(trim($data['matiere'] ?? '')),
            'couleur'       => Str::ucfirst(trim($data['couleur'] ?? '')),
            'etat'          => array_key_exists($data['etat'] ?? '', ProjetUpcycling::ETATS) ? $data['etat'] : 'USE',
            'description'   => trim($data['description'] ?? ''),
            'defauts'       => array_values(array_filter(array_map('strval', $data['defauts'] ?? []))),
        ];
    }

    /*
    |------------------------------------------------------------------
    | 2. Génération des idées de transformation
    |------------------------------------------------------------------
    */

    /**
     * @param array{type_vetement: string, matiere: string, etat: string, couleur?: ?string, description?: ?string, budget_max?: ?float} $vetement
     * @param string|null $photo    chemin absolu de la photo du vêtement
     * @param string|null $consigne demande libre du client ("plus petit", "pour un enfant"...)
     * @return array{source: string, idees: array, defauts: array<int, string>}
     */
    public function generer(array $vetement, ?string $photo = null, ?string $consigne = null): array
    {
        if ($this->estConfigure()) {
            try {
                return $this->genererAvecGemini($vetement, $photo, $consigne);
            } catch (Throwable $e) {
                Log::warning('Upcycling IA : appel Gemini échoué, bascule sur le générateur local.', ['erreur' => $e->getMessage()]);
            }
        }

        return [
            'source'  => self::SOURCE_LOCAL,
            'idees'   => $this->genererLocalement($vetement['type_vetement'], $vetement['matiere'], $vetement['etat']),
            'defauts' => [],
        ];
    }

    private function genererAvecGemini(array $vetement, ?string $photo, ?string $consigne): array
    {
        $etat = ProjetUpcycling::ETATS[$vetement['etat']] ?? $vetement['etat'];
        $prompt = "Vêtement à transformer :\n"
            . "- Type : {$vetement['type_vetement']}\n"
            . "- Matière : {$vetement['matiere']}\n"
            . '- Couleur : ' . ($vetement['couleur'] ?? 'non précisée') . "\n"
            . "- État : {$etat}\n"
            . '- Description du client : ' . ($vetement['description'] ?? 'aucune') . "\n"
            . '- Budget maximum : ' . (!empty($vetement['budget_max']) ? $vetement['budget_max'] . ' DT' : 'non précisé') . "\n";

        if ($consigne) {
            $prompt .= "\nDemande particulière du client pour ces nouvelles idées : « {$consigne} ».\n";
        }

        $prompt .= "\nPropose exactement 3 idées d'upcycling très différentes les unes des autres. "
            . ($photo ? "Appuie-toi sur la photo (coupe, couleur, détails, défauts visibles). " : '')
            . "Pour chaque idée : un titre court, une description de 2 phrases qui donne envie, la catégorie du produit fini, "
            . "la difficulté, le temps de travail en heures, 3 à 5 étapes de fabrication courtes, les matériaux à ajouter, "
            . "et une fourchette de prix réaliste en dinars tunisiens (DT) pour la main-d'œuvre d'un atelier tunisien. "
            . 'Liste aussi les défauts visibles du vêtement (liste vide si aucun).';

        $parts = [];
        if ($photo && is_file($photo)) {
            $parts[] = $this->gemini->image($photo);
        }
        $parts[] = ['text' => $prompt];

        $data = $this->gemini->genererJson(
            "Tu es styliste expert en upcycling pour TextileCycle, une plateforme tunisienne d'économie circulaire du textile. "
            . 'Tes idées sont créatives mais réalisables par un atelier de couture, adaptées à la matière et à l\'état du vêtement. '
            . 'Tu écris en français correct, avec tous les accents (é, è, à, ç...), même si le client écrit sans accents.',
            $parts,
            [
                'type'       => 'OBJECT',
                'properties' => [
                    'defauts' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                    'idees'   => [
                        'type'  => 'ARRAY',
                        'items' => [
                            'type'       => 'OBJECT',
                            'properties' => [
                                'titre'        => ['type' => 'STRING'],
                                'description'  => ['type' => 'STRING'],
                                'categorie'    => ['type' => 'STRING', 'enum' => array_keys(Atelier::SPECIALITES)],
                                'difficulte'   => ['type' => 'STRING', 'enum' => self::DIFFICULTES],
                                'duree_heures' => ['type' => 'INTEGER'],
                                'etapes'       => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                                'materiaux'    => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                                'prix_min'     => ['type' => 'INTEGER'],
                                'prix_max'     => ['type' => 'INTEGER'],
                            ],
                            'required' => ['titre', 'description', 'categorie', 'difficulte', 'duree_heures', 'etapes', 'materiaux', 'prix_min', 'prix_max'],
                        ],
                    ],
                ],
                'required' => ['defauts', 'idees'],
            ]
        );

        $idees = $this->normaliser($data['idees'] ?? []);
        if (count($idees) === 0) {
            throw new \RuntimeException('Gemini n\'a renvoyé aucune idée.');
        }

        return [
            'source'  => self::SOURCE_GEMINI,
            'idees'   => $idees,
            'defauts' => array_values(array_filter(array_map('strval', $data['defauts'] ?? []))),
        ];
    }

    /** Garde au plus 3 idées et sécurise les valeurs */
    private function normaliser(array $idees): array
    {
        return collect($idees)->take(3)->map(function ($idee) {
            $min = max(1, (int) ($idee['prix_min'] ?? 20));
            $max = max($min, (int) ($idee['prix_max'] ?? $min));

            return [
                'titre'        => (string) ($idee['titre'] ?? 'Idée'),
                'description'  => (string) ($idee['description'] ?? ''),
                'categorie'    => array_key_exists($idee['categorie'] ?? '', Atelier::SPECIALITES) ? $idee['categorie'] : 'VETEMENT',
                'difficulte'   => in_array($idee['difficulte'] ?? '', self::DIFFICULTES, true) ? $idee['difficulte'] : 'MOYEN',
                'duree_heures' => max(1, (int) ($idee['duree_heures'] ?? 4)),
                'etapes'       => array_values(array_map('strval', $idee['etapes'] ?? [])),
                'materiaux'    => array_values(array_map('strval', $idee['materiaux'] ?? [])),
                'prix_min'     => $min,
                'prix_max'     => $max,
            ];
        })->values()->all();
    }

    /*
    |------------------------------------------------------------------
    | 3. Impact écologique (calcul local, indépendant de l'IA)
    |------------------------------------------------------------------
    */

    /** @return array{co2_evite_kg: float, eau_economisee_l: int} */
    public function impact(string $type): array
    {
        [$co2, $eau] = self::IMPACT[$this->familleVetement($type)] ?? [12, 3000];

        return ['co2_evite_kg' => (float) $co2, 'eau_economisee_l' => $eau];
    }

    /** Ramène un type saisi librement à une famille connue (jean, chemise, veste...) */
    public function familleVetement(string $type): string
    {
        $type = Str::of($type)->lower()->ascii()->replace(['-', ' '], '')->toString();

        $synonymes = [
            'jean'     => ['jean', 'denim'],
            'short'    => ['short', 'bermuda'],
            'pantalon' => ['pantalon', 'jogging', 'chino'],
            'tshirt'   => ['tshirt', 'teeshirt', 'top', 'debardeur', 'polo'],
            'chemise'  => ['chemise', 'chemisier', 'blouse'],
            'pull'     => ['pull', 'sweat', 'gilet', 'cardigan', 'tricot'],
            'robe'     => ['robe'],
            'jupe'     => ['jupe'],
            'veste'    => ['veste', 'blazer', 'manteau', 'blouson', 'costume'],
        ];

        foreach ($synonymes as $famille => $mots) {
            foreach ($mots as $mot) {
                if (str_contains($type, $mot)) return $famille;
            }
        }

        return 'autre';
    }

    /*
    |------------------------------------------------------------------
    | 4. Générateur local (sans clé API)
    |------------------------------------------------------------------
    */
    public function genererLocalement(string $type, string $matiere, string $etat): array
    {
        // [titre, description, catégorie, difficulté, heures, matériaux, étapes]
        $catalogue = [
            'jean' => [
                ['Sac cabas en denim', 'Le jean devient un grand cabas solide ; les poches arrière sont conservées comme poches extérieures.', 'SAC', 'MOYEN', 6, ['Doublure coton', 'Sangles', 'Fil épais'], ['Découper les jambes à plat', 'Assembler le fond et les côtés', 'Coudre la doublure', 'Poser les anses']],
                ['Short effet vintage', 'Les jambes sont raccourcies et les bords effilochés, avec quelques patchs contrastés.', 'VETEMENT', 'FACILE', 2, ['Patchs thermocollants'], ['Mesurer et couper à la longueur', 'Effilocher les bords', 'Poser les patchs']],
                ['Coussin patchwork denim', 'Des carrés de différentes nuances de bleu forment une housse de coussin graphique.', 'PATCHWORK', 'MOYEN', 4, ['Rembourrage', 'Fermeture éclair'], ['Découper des carrés', 'Assembler le patchwork', 'Poser la fermeture', 'Fermer la housse']],
            ],
            'short' => [
                ['Pochette zippée', 'Le devant du short devient une pochette plate avec sa poche d\'origine.', 'SAC', 'FACILE', 2, ['Fermeture éclair', 'Doublure'], ['Découper le devant', 'Coudre la doublure', 'Poser la fermeture']],
                ['Tablier de jardin', 'Le short ouvert devient un tablier à poches pour le jardinage ou l\'atelier.', 'ACCESSOIRE', 'FACILE', 2, ['Sangle coton'], ['Ouvrir les coutures', 'Ourler les bords', 'Coudre la sangle']],
                ['Coussin de sol', 'Les jambes du short forment les faces d\'un coussin rond rembourré.', 'DECORATION', 'MOYEN', 3, ['Rembourrage', 'Tissu de fond'], ['Découper deux cercles', 'Assembler', 'Rembourrer et fermer']],
            ],
            'pantalon' => [
                ['Sac bandoulière', 'Le haut du pantalon et sa ceinture deviennent un sac bandoulière original.', 'SAC', 'MOYEN', 5, ['Sangle réglable', 'Doublure'], ['Couper sous les poches', 'Fermer le fond', 'Doubler', 'Fixer la bandoulière']],
                ['Jupe droite', 'Les jambes sont ouvertes et réassemblées en jupe droite.', 'VETEMENT', 'MOYEN', 4, ['Fil assorti'], ['Découdre l\'entrejambe', 'Assembler les panneaux', 'Ourler']],
                ['Range-couverts', 'Le tissu est découpé en pochettes à couverts pour la table.', 'DECORATION', 'FACILE', 2, ['Biais'], ['Découper des rectangles', 'Plier et coudre', 'Border de biais']],
            ],
            'chemise' => [
                ['Top cache-cœur', 'La chemise est recoupée et croisée devant pour un top noué à la taille.', 'VETEMENT', 'MOYEN', 4, ['Biais', 'Fil assorti'], ['Retirer le col', 'Recouper le devant', 'Poser le biais', 'Coudre les liens']],
                ['Tote bag léger', 'Le dos de la chemise devient un sac de course pliable, les manches font les anses.', 'SAC', 'FACILE', 3, ['Fil solide'], ['Découper le dos', 'Assembler le sac', 'Coudre les anses']],
                ['Chouchous et bandeaux', 'Les chutes de tissu deviennent des chouchous et bandeaux assortis.', 'ACCESSOIRE', 'FACILE', 1, ['Élastique'], ['Couper des bandes', 'Coudre en tube', 'Glisser l\'élastique']],
            ],
            'tshirt' => [
                ['Tote bag sans couture', 'Le t-shirt est découpé et noué pour former un sac de course lavable.', 'SAC', 'FACILE', 1, [], ['Couper les manches', 'Découper l\'encolure', 'Franger et nouer le bas']],
                ['Fil textile tressé', 'Le t-shirt est découpé en un long fil pour crocheter un panier.', 'DECORATION', 'MOYEN', 4, ['Crochet'], ['Découper en spirale', 'Étirer le fil', 'Crocheter le panier']],
                ['Crop top noué', 'Le t-shirt est raccourci et noué pour une coupe moderne.', 'VETEMENT', 'FACILE', 1, [], ['Couper à la longueur', 'Fendre le devant', 'Nouer']],
            ],
            'pull' => [
                ['Plaid patchwork en maille', 'Des carrés de pulls feutrés sont assemblés en plaid chaud.', 'PATCHWORK', 'DIFFICILE', 10, ['Doublure polaire', 'Fil laine'], ['Feutrer en machine', 'Découper des carrés', 'Assembler', 'Doubler']],
                ['Bonnet et mitaines', 'Le bas et les manches du pull deviennent un bonnet et des mitaines.', 'ACCESSOIRE', 'MOYEN', 3, ['Élastique', 'Pompon'], ['Couper le bas en tube', 'Fermer le bonnet', 'Recouper les manches']],
                ['Housse de coussin tricot', 'Le devant du pull devient une housse de coussin texturée.', 'DECORATION', 'FACILE', 2, ['Boutons', 'Rembourrage'], ['Découper le devant', 'Coudre en housse', 'Poser les boutons']],
            ],
            'robe' => [
                ['Jupe midi', 'Le haut de la robe est retiré et la taille reprise avec une ceinture élastique.', 'VETEMENT', 'FACILE', 3, ['Élastique large'], ['Couper sous le buste', 'Coudre la coulisse', 'Passer l\'élastique']],
                ['Pochette de soirée', 'Le tissu de la robe est doublé et monté en pochette avec fermoir.', 'SAC', 'MOYEN', 4, ['Fermoir métal', 'Entoilage'], ['Découper le patron', 'Entoiler', 'Assembler et doubler', 'Poser le fermoir']],
                ['Guirlande de fanions', 'Le tissu est découpé en triangles pour une guirlande décorative.', 'DECORATION', 'FACILE', 2, ['Biais', 'Cordon'], ['Découper les triangles', 'Assembler sur le biais']],
            ],
            'jupe' => [
                ['Trousse plate', 'Le tissu de la jupe devient une trousse zippée doublée.', 'ACCESSOIRE', 'FACILE', 2, ['Fermeture éclair'], ['Découper', 'Poser la fermeture', 'Assembler']],
                ['Top bandeau', 'La jupe est retournée et ajustée pour devenir un top bandeau.', 'VETEMENT', 'MOYEN', 3, ['Élastique'], ['Ajuster la largeur', 'Coudre la coulisse', 'Ourler']],
                ['Sac seau', 'La jupe froncée devient un sac seau avec cordon de serrage.', 'SAC', 'MOYEN', 4, ['Cordon', 'Œillets'], ['Fermer le fond', 'Poser les œillets', 'Passer le cordon']],
            ],
            'veste' => [
                ['Veste customisée', 'La veste est ajustée et personnalisée avec broderies et empiècements contrastés.', 'VETEMENT', 'DIFFICILE', 8, ['Fil à broder', 'Tissu contrasté'], ['Reprendre les coutures', 'Broder le motif', 'Poser les empiècements']],
                ['Sac à dos', 'Le tissu de la veste devient un petit sac à dos ; les poches sont conservées.', 'SAC', 'DIFFICILE', 8, ['Sangles réglables', 'Doublure'], ['Démonter la veste', 'Couper le patron', 'Assembler et doubler', 'Fixer les sangles']],
                ['Trousse zippée', 'Les chutes servent à confectionner des trousses avec fermeture éclair.', 'ACCESSOIRE', 'FACILE', 2, ['Fermeture éclair'], ['Découper', 'Poser la fermeture', 'Assembler']],
            ],
        ];

        $famille = $this->familleVetement($type);
        if (!isset($catalogue[$famille])) {
            $matiere = Str::lower($matiere);
            $famille = str_contains($matiere, 'denim') ? 'jean' : (str_contains($matiere, 'laine') ? 'pull' : 'chemise');
        }

        // Un vêtement abîmé est plutôt découpé (accessoires, patchwork) que porté
        $idees = $catalogue[$famille];
        if ($etat === 'ABIME') {
            usort($idees, fn ($a, $b) => ($a[2] === 'VETEMENT') <=> ($b[2] === 'VETEMENT'));
        }

        return array_map(fn ($i) => [
            'titre'        => $i[0],
            'description'  => $i[1],
            'categorie'    => $i[2],
            'difficulte'   => $i[3],
            'duree_heures' => $i[4],
            'etapes'       => $i[6],
            'materiaux'    => $i[5],
            'prix_min'     => 10 + $i[4] * 8,
            'prix_max'     => 20 + $i[4] * 14,
        ], $idees);
    }
}
