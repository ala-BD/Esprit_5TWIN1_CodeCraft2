<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Estimation;
use App\Models\ParametreCout;
use App\Models\User;

class EstimationService
{
    public function __construct(
        protected EstimationCoutService $coutService,
        protected EstimationPrixService $prixService,
        protected IaClient $iaClient,
        protected RentabiliteService $rentabiliteService
    ) {}

    /**
     * Exécute le pipeline d'estimation complet :
     * 1. Calcul des coûts de recyclage & upcycling (PHP déterministe)
     * 2. Calcul de la fourchette de prix de vente (Règles marché + marketplace)
     * 3. Appel de l'IA (ou repli sur règles) pour affiner la fourchette et expliquer
     * 4. Calcul de rentabilité et niveau d'alerte (Vert, Orange, Rouge)
     * 5. Sauvegarde optionnelle dans la table estimations
     *
     * @param array $data
     * @param User|null $user
     * @param Article|null $article
     * @param bool $save
     * @return array
     */
    public function estimer(array $data, ?User $user = null, ?Article $article = null, bool $save = false): array
    {
        $matiere        = $data['matiere'] ?? ($article?->matiere);
        $categorie      = $data['categorie'] ?? ($article?->categorie);
        $etat           = $data['etat'] ?? ($article?->etat);
        $taille         = $data['taille'] ?? ($article?->taille);
        $couleur        = $data['couleur'] ?? ($article?->couleur);
        $titre          = $data['titre'] ?? ($article?->titre ?? 'Article');
        $poidsKg        = (float) ($data['poids_kg'] ?? ($article?->poids_kg ?? 0.5));
        $coutAchat      = (float) ($data['cout_achat'] ?? ($article?->cout_achat ?? 0.0));
        $heuresTravail  = (float) ($data['heures_travail'] ?? ($article?->heures_travail ?? 1.0));
        $coutMateriaux  = (float) ($data['cout_materiaux'] ?? ($article?->cout_materiaux ?? 0.0));
        $prixManuel     = isset($data['prix']) && is_numeric($data['prix']) ? (float) $data['prix'] : null;
        $quantite       = max(1, (int) ($data['quantite'] ?? ($article?->stock > 0 ? $article->stock : 1)));

        $parametre = ParametreCout::pourMatiere($matiere);

        // 1. Calcul des coûts
        $recyclage = $this->coutService->calculerRecyclage($matiere, $poidsKg, $parametre);
        $upcycling = $this->coutService->calculerUpcycling($coutAchat, $heuresTravail, $coutMateriaux, $poidsKg, $user, $parametre);
        $filiereOrientation = $this->coutService->comparerFilieres($recyclage, $upcycling, $etat);

        // 2. Pré-estimation statistique du prix de vente
        $fourchetteRegles = $this->prixService->estimerFourchette(
            $categorie,
            $matiere,
            $etat,
            $user,
            $upcycling['total']
        );

        // 3. Appel IA (ou fallback)
        $iaInput = [
            'titre'          => $titre,
            'categorie'      => $categorie,
            'matiere'        => $matiere,
            'etat'           => $etat,
            'taille'         => $taille,
            'couleur'        => $couleur,
            'poids_kg'       => $poidsKg,
            'cout_upcycling' => $upcycling['total'],
            'cout_recyclage' => $recyclage['total'],
            'prix_conseille' => $fourchetteRegles['prix_conseille'],
            'prix_min'       => $fourchetteRegles['prix_min'],
            'prix_max'       => $fourchetteRegles['prix_max'],
        ];

        $iaResult = $this->iaClient->estimerArticle($iaInput);

        $prixConseille = $iaResult['prix_conseille'];
        $prixMin       = $iaResult['prix_min'];
        $prixMax       = $iaResult['prix_max'];

        // Prix retenu pour l'analyse de rentabilité (prix manuel choisi par l'atelier ou prix conseillé)
        $prixPourRentabilite = $prixManuel ?? $prixConseille;

        // 4. Calcul de rentabilité unitaire et par quantité
        $revenusComplets = $this->rentabiliteService->analyserRevenusQuantite(
            $prixPourRentabilite,
            $upcycling['total'],
            $quantite,
            $recyclage['total'],
            $heuresTravail,
            $poidsKg,
            $parametre
        );

        $rentabilite = $revenusComplets['unitaire'];
        $quantiteData = $revenusComplets['quantite'];

        $resultat = [
            'article_id'           => $article?->id,
            'titre'                => $titre,
            'categorie'            => $categorie,
            'matiere'              => $matiere,
            'etat'                 => $etat,
            'poids_kg'             => $poidsKg,
            'cout_recyclage'       => $recyclage['total'],
            'cout_upcycling'       => $upcycling['total'],
            'detail_recyclage'     => $recyclage,
            'detail_upcycling'     => $upcycling,
            'filiere_orientation'  => $filiereOrientation,
            'prix_min'             => $prixMin,
            'prix_conseille'       => $prixConseille,
            'prix_max'             => $prixMax,
            'prix_actuel'          => $prixPourRentabilite,
            'rentabilite'          => $rentabilite,
            'quantite'             => $quantiteData,
            'source'               => $iaResult['source'],
            'confiance'            => $iaResult['confiance'],
            'explication'          => $iaResult['explication'],
            'parametres'           => [
                'tarif_horaire_applique'  => $upcycling['tarif_horaire'],
                'marge_cible_pourcent'    => $parametre->marge_cible_pourcent,
                'marge_minimale_pourcent' => $parametre->marge_minimale_pourcent,
                'frais_plateforme_pct'    => $parametre->frais_plateforme_pourcent,
            ],
        ];

        // 5. Sauvegarde en base si demandée et utilisateur présent
        if ($save && $user) {
            $estimation = Estimation::create([
                'article_id'     => $article?->id,
                'user_id'        => $user->id,
                'cout_recyclage' => $recyclage['total'],
                'cout_upcycling' => $upcycling['total'],
                'prix_min'       => $prixMin,
                'prix_conseille' => $prixConseille,
                'prix_max'       => $prixMax,
                'marge_nette'    => $rentabilite['marge_nette'],
                'marge_pourcent' => $rentabilite['marge_pourcent'],
                'niveau_alerte'  => $rentabilite['niveau_alerte'],
                'confiance'      => $iaResult['confiance'],
                'explication'    => $iaResult['explication'],
                'source'         => $iaResult['source'],
                'details_calcul' => [
                    'recyclage'   => $recyclage,
                    'upcycling'   => $upcycling,
                    'rentabilite' => $rentabilite,
                    'quantite'    => $quantiteData,
                    'filiere'     => $filiereOrientation,
                ],
            ]);

            $resultat['estimation_id'] = $estimation->id;

            // Si un article existant est associé, mettre à jour prix_estime_ia
            if ($article) {
                $article->update([
                    'prix_estime_ia' => $prixConseille,
                ]);
            }
        }

        return $resultat;
    }
}

