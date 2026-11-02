<?php

namespace App\Services;

use App\Models\Estimation;
use App\Models\ParametreCout;

class RentabiliteService
{
    /**
     * Calcule la rentabilité complète, le niveau d'alerte et les conseils d'ajustement.
     *
     * @param float $prixVente
     * @param float $coutRevient
     * @param float $coutRecyclage
     * @param ParametreCout|null $parametre
     * @return array
     */
    public function analyserRentabilite(
        float $prixVente,
        float $coutRevient,
        float $coutRecyclage = 0.0,
        ?ParametreCout $parametre = null
    ): array {
        $param = $parametre ?? ParametreCout::pourMatiere('defaut');

        $prix        = max(0.0, round($prixVente, 2));
        $revient     = max(0.0, round($coutRevient, 2));
        $recyclage   = max(0.0, round($coutRecyclage, 2));

        $fraisPlateformePourcent = (float) $param->frais_plateforme_pourcent;
        $margeCiblePourcent      = (float) $param->marge_cible_pourcent;
        $margeMinimalePourcent   = (float) $param->marge_minimale_pourcent;

        $fraisPlateforme = round($prix * ($fraisPlateformePourcent / 100), 2);
        $margeNette      = round($prix - $revient - $fraisPlateforme, 2);
        $margePourcent   = $prix > 0 ? round(($margeNette / $prix) * 100, 1) : -100.0;

        // Prix requis pour atteindre la marge cible
        $denominateur = 1 - (($fraisPlateformePourcent + $margeCiblePourcent) / 100);
        $prixPourCible = $denominateur > 0 ? round($revient / $denominateur, 2) : round($revient * 1.3, 2);

        // Seuil d'équilibre strict (marge 0%)
        $denomEquilibre = 1 - ($fraisPlateformePourcent / 100);
        $prixEquilibre = $denomEquilibre > 0 ? round($revient / $denomEquilibre, 2) : $revient;

        // Détermination du niveau d'alerte
        if ($margeNette > 0 && $margePourcent >= $margeCiblePourcent) {
            $niveau = Estimation::ALERTE_VERT;
            $titre  = 'Article gagnant';
            $badge  = 'bg-emerald-100 text-emerald-800 border-emerald-300';
            $icon   = 'fa-circle-check';
            $conseil = "Excellente rentabilité ! Votre marge nette de {$margeNette} DT ({$margePourcent}%) dépasse votre objectif cible de {$margeCiblePourcent}%.";
        } elseif ($margeNette > 0 && $margePourcent < $margeCiblePourcent) {
            $niveau = Estimation::ALERTE_ORANGE;
            $titre  = 'À surveiller';
            $badge  = 'bg-amber-100 text-amber-800 border-amber-300';
            $icon   = 'fa-triangle-exclamation';
            $conseil = "Marge positive mais faible ({$margeNette} DT, soit {$margePourcent}%). Augmentez le prix à {$prixPourCible} DT pour atteindre les {$margeCiblePourcent}% de marge cible.";
        } else {
            $niveau = Estimation::ALERTE_ROUGE;
            $titre  = 'Article perdant';
            $badge  = 'bg-rose-100 text-rose-800 border-rose-300';
            $icon   = 'fa-circle-xmark';

            $perteAbs = abs($margeNette);
            if ($revient > $prix && $revient > ($recyclage * 2.5) && $recyclage > 0) {
                $conseil = "Attention, vente à perte (déficit de {$perteAbs} DT). Cet article coûte plus cher à transformer ({$revient} DT) qu'à recycler ({$recyclage} DT) : envisagez de l'orienter vers la filière recyclage ou fixez un prix minimal de {$prixPourCible} DT.";
            } else {
                $conseil = "Attention, marge déficitaire (-{$perteAbs} DT). Pour couvrir vos charges d'atelier et atteindre {$margeCiblePourcent}% de marge, le prix minimal requis est de {$prixPourCible} DT.";
            }
        }

        return [
            'niveau_alerte'          => $niveau,
            'titre_alerte'           => $titre,
            'badge_class'            => $badge,
            'icon'                   => $icon,
            'prix_vente'             => $prix,
            'cout_revient'           => $revient,
            'cout_recyclage'         => $recyclage,
            'frais_plateforme'       => $fraisPlateforme,
            'frais_plateforme_pct'   => $fraisPlateformePourcent,
            'marge_nette'            => $margeNette,
            'marge_pourcent'         => $margePourcent,
            'marge_cible_pct'        => $margeCiblePourcent,
            'marge_minimale_pct'     => $margeMinimalePourcent,
            'prix_pour_cible'        => $prixPourCible,
            'prix_seuil_rentabilite' => $prixEquilibre,
            'conseil'                => $conseil,
        ];
    }

    /**
     * Calcule l'estimation des revenus détaillés par pièce et par quantité pour un don ou lot textile.
     *
     * @param float $prixVenteUnitaire Prix de vente unitaire conseillé ou simulé
     * @param float $coutRevientUnitaire Coût de revient unitaire (achat + travail + matériaux + transport)
     * @param int $quantite Nombre d'articles / pièces dans le lot (min 1)
     * @param float $coutRecyclageUnitaire Coût de recyclage d'une pièce
     * @param float $heuresTravailUnitaire Heures de travail estimées par pièce
     * @param float $poidsKgUnitaire Poids en kg d'une pièce
     * @param ParametreCout|null $parametre
     * @return array
     */
    public function analyserRevenusQuantite(
        float $prixVenteUnitaire,
        float $coutRevientUnitaire,
        int $quantite = 1,
        float $coutRecyclageUnitaire = 0.0,
        float $heuresTravailUnitaire = 1.0,
        float $poidsKgUnitaire = 0.5,
        ?ParametreCout $parametre = null
    ): array {
        $q = max(1, $quantite);
        $unitaire = $this->analyserRentabilite($prixVenteUnitaire, $coutRevientUnitaire, $coutRecyclageUnitaire, $parametre);

        $caTotal            = round($unitaire['prix_vente'] * $q, 2);
        $coutTotal          = round($unitaire['cout_revient'] * $q, 2);
        $fraisPlateformeTot = round($unitaire['frais_plateforme'] * $q, 2);
        $margeTotale        = round($unitaire['marge_nette'] * $q, 2);
        $heuresTotales      = round($heuresTravailUnitaire * $q, 2);
        $poidsTotalKg       = round($poidsKgUnitaire * $q, 3);

        // Rendement horaire net : bénéfice net dégagé par heure de travail atelier
        $gainHoraireNet = $heuresTotales > 0 ? round($margeTotale / $heuresTotales, 2) : 0.0;

        // Conseil spécifique au volume / quantité
        if ($unitaire['niveau_alerte'] === Estimation::ALERTE_VERT) {
            $conseilQuantite = "En transformant ce lot de {$q} pièce(s), votre atelier génère un chiffre d'affaires estimé de {$caTotal} DT pour un bénéfice net total de +{$margeTotale} DT ({$gainHoraireNet} DT/heure de travail net).";
        } elseif ($unitaire['niveau_alerte'] === Estimation::ALERTE_ORANGE) {
            $conseilQuantite = "Ce lot de {$q} pièce(s) dégage une marge totale de +{$margeTotale} DT. Augmenter le prix unitaire ou réduire le temps de confection améliorerait le rendement horaire.";
        } else {
            $perteTotale = abs($margeTotale);
            $conseilQuantite = "Attention : sur un volume de {$q} pièce(s), le déficit cumulé atteindrait -{$perteTotale} DT. La charge de travail ({$heuresTotales} h) n'est pas rentabilisée à ce prix de revente.";
        }

        return [
            'unitaire' => array_merge($unitaire, [
                'heures_travail' => $heuresTravailUnitaire,
                'poids_kg'       => $poidsKgUnitaire,
            ]),
            'quantite' => [
                'nombre_pieces'          => $q,
                'chiffre_affaires_total' => $caTotal,
                'cout_total'             => $coutTotal,
                'frais_plateforme_total' => $fraisPlateformeTot,
                'marge_totale'           => $margeTotale,
                'marge_pourcent'         => $unitaire['marge_pourcent'],
                'heures_totales'         => $heuresTotales,
                'poids_total_kg'         => $poidsTotalKg,
                'gain_horaire_net'       => $gainHoraireNet,
                'conseil_quantite'       => $conseilQuantite,
            ],
        ];
    }
}

