<?php

namespace App\Services;

use App\Models\Article;
use App\Models\LigneCommande;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EstimationPrixService
{
    /**
     * Prix de base par catégorie (en DT).
     */
    protected const PRIX_BASE_CATEGORIES = [
        'Vetements Homme'    => 35.00,
        'Vetements Femme'    => 40.00,
        'Vetements Enfant'   => 22.00,
        'Accessoires & Sacs' => 28.00,
        'Linge de maison'    => 30.00,
        'Textile Upcycle'    => 48.00,
        'Chaussures'         => 45.00,
        'Autre'              => 25.00,
    ];

    /**
     * Coefficients selon la matière textile.
     */
    protected const COEFF_MATIERES = [
        'Soie'        => 1.45,
        'Laine'       => 1.30,
        'Lin'         => 1.25,
        'Coton'       => 1.10,
        'Mixte'       => 0.95,
        'Polyester'   => 0.92,
        'Synthetique' => 0.90,
        'Autre'       => 1.00,
    ];

    /**
     * Coefficients selon l'état de l'article.
     */
    protected const COEFF_ETATS = [
        'Neuf'          => 1.30,
        'Tres bon etat' => 1.10,
        'Bon etat'      => 0.90,
        'Usage'         => 0.70,
    ];

    /**
     * Calcule la fourchette de prix de vente (min, conseillé, max).
     *
     * @param string|null $categorie
     * @param string|null $matiere
     * @param string|null $etat
     * @param User|null $vendeur
     * @param float $coutRevientUpcycling
     * @return array
     */
    public function estimerFourchette(
        ?string $categorie,
        ?string $matiere,
        ?string $etat,
        ?User $vendeur = null,
        float $coutRevientUpcycling = 0.0
    ): array {
        // 1. Prix de base catégoriel
        $base = self::PRIX_BASE_CATEGORIES[$categorie] ?? 30.00;

        // 2. Ajustement matière & état
        $coeffMatiere = self::COEFF_MATIERES[$matiere] ?? 1.00;
        $coeffEtat    = self::COEFF_ETATS[$etat] ?? 0.90;
        $prixTheorique = $base * $coeffMatiere * $coeffEtat;

        // 3. Ajustement statistique sur les ventes réelles de la marketplace RETISS
        $marcheData = $this->getPrixMarcheSimilaire($categorie, $matiere);
        if ($marcheData['nb_ventes'] >= 2 && $marcheData['prix_median'] > 0) {
            // Pondération 50% modèle théorique + 50% réalité marché observée
            $prixEstime = ($prixTheorique * 0.5) + ($marcheData['prix_median'] * 0.5);
        } else {
            $prixEstime = $prixTheorique;
        }

        // 4. Ajustement réputation du vendeur / atelier (Avis clients)
        $ajustementReputation = 1.0;
        if ($vendeur) {
            $note = $vendeur->noteMoyenne();
            $nbAvis = $vendeur->nbAvisRecus();
            if ($nbAvis >= 3) {
                if ($note >= 4.5) {
                    $ajustementReputation = 1.08; // +8% pour excellente réputation
                } elseif ($note >= 4.0) {
                    $ajustementReputation = 1.04; // +4%
                } elseif ($note < 3.0) {
                    $ajustementReputation = 0.95; // -5%
                }
            }
        }
        $prixEstime *= $ajustementReputation;

        // 5. Ajustement saisonnier
        $coeffSaison = $this->getCoeffSaison($matiere, $categorie);
        $prixEstime *= $coeffSaison;

        // 6. Plancher de rentabilité : le prix conseillé doit idéalement couvrir au moins les frais de revient
        if ($coutRevientUpcycling > 0 && $prixEstime < $coutRevientUpcycling) {
            // Ne pas forcer un prix déraisonnable, mais suggérer un prix aligné au moins avec les charges
            $prixEstime = max($prixEstime, round($coutRevientUpcycling * 1.15, 2));
        }

        $prixConseille = round(max(5.0, $prixEstime), 2);
        $prixMin       = round(max(4.0, $prixConseille * 0.80), 2);
        $prixMax       = round(max($prixConseille, $prixConseille * 1.25), 2);

        return [
            'prix_min'              => $prixMin,
            'prix_conseille'        => $prixConseille,
            'prix_max'              => $prixMax,
            'base_theorique'        => round($prixTheorique, 2),
            'marche_median'         => $marcheData['prix_median'],
            'marche_nb_ventes'      => $marcheData['nb_ventes'],
            'ajustement_reputation' => round(($ajustementReputation - 1) * 100, 1),
            'ajustement_saison'     => round(($coeffSaison - 1) * 100, 1),
        ];
    }

    /**
     * Analyse les données réelles de vente sur les articles similaires vendus.
     */
    protected function getPrixMarcheSimilaire(?string $categorie, ?string $matiere): array
    {
        if (!$categorie) {
            return ['prix_median' => null, 'nb_ventes' => 0];
        }

        try {
            $query = LigneCommande::query()
                ->join('articles', 'ligne_commandes.article_id', '=', 'articles.id')
                ->where('articles.categorie', $categorie)
                ->where('ligne_commandes.prix_unitaire', '>', 0);

            if ($matiere) {
                $query->where('articles.matiere', $matiere);
            }

            $prixList = $query->pluck('ligne_commandes.prix_unitaire')
                ->map(fn($val) => (float) $val)
                ->sort()
                ->values();

            $count = $prixList->count();
            if ($count === 0) {
                // Fallback sans le filtre matière si aucun résultat
                if ($matiere) {
                    return $this->getPrixMarcheSimilaire($categorie, null);
                }
                return ['prix_median' => null, 'nb_ventes' => 0];
            }

            // Calcul de la médiane
            $middle = (int) floor($count / 2);
            $median = ($count % 2 === 0)
                ? ($prixList[$middle - 1] + $prixList[$middle]) / 2
                : $prixList[$middle];

            return [
                'prix_median' => round($median, 2),
                'nb_ventes'   => $count,
            ];
        } catch (\Throwable $e) {
            return ['prix_median' => null, 'nb_ventes' => 0];
        }
    }

    /**
     * Calcule le coefficient de saisonnalité selon le mois courant.
     */
    protected function getCoeffSaison(?string $matiere, ?string $categorie): float
    {
        $mois = Carbon::now()->month;

        // Période automne-hiver (octobre à mars)
        if (in_array($mois, [10, 11, 12, 1, 2, 3])) {
            if ($matiere === 'Laine') {
                return 1.15; // +15% sur la laine en saison froide
            }
            if (in_array($categorie, ['Vetements Homme', 'Vetements Femme']) && in_array($matiere, ['Mixte', 'Coton'])) {
                return 1.05;
            }
        }

        // Période printemps-été (mai à août)
        if (in_array($mois, [5, 6, 7, 8])) {
            if ($matiere === 'Lin' || $matiere === 'Soie') {
                return 1.12; // +12% sur les matières légères et respirantes
            }
            if ($matiere === 'Laine') {
                return 0.90; // -10% sur la laine hors saison
            }
        }

        return 1.00;
    }
}

