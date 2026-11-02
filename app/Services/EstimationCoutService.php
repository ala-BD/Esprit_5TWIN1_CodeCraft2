<?php

namespace App\Services;

use App\Models\ParametreCout;
use App\Models\User;

class EstimationCoutService
{
    /**
     * Calcule le coût complet de la filière Recyclage.
     *
     * Formule :
     *   Traitement matière = poids_kg * cout_recyclage_par_kg
     *   Majoration mélange = Traitement matière * (majoration_mixte_pourcent / 100) (si Mixte/Synthetique)
     *   Tri fixe unitaire  = cout_tri_fixe
     *   Transport          = poids_kg * cout_transport_par_kg
     *   Total recyclage    = Tri + Traitement + Majoration + Transport
     *
     * @param string|null $matiere
     * @param float $poidsKg
     * @param ParametreCout|null $parametre
     * @return array
     */
    public function calculerRecyclage(?string $matiere, float $poidsKg, ?ParametreCout $parametre = null): array
    {
        $param = $parametre ?? ParametreCout::pourMatiere($matiere);
        $poids = max(0.05, $poidsKg); // minimum 50g

        $coutTri       = round((float) $param->cout_tri_fixe, 2);
        $coutAuKg      = (float) $param->cout_recyclage_par_kg;
        $traitement    = round($poids * $coutAuKg, 2);

        // Majoration si matière mélangée ou complexe
        $estMixte = in_array(strtolower($matiere ?? ''), ['mixte', 'synthetique', 'autre', 'mélange']);
        $majorationPourcent = $estMixte ? (float) $param->majoration_mixte_pourcent : 0.0;
        $majoration    = round($traitement * ($majorationPourcent / 100), 2);

        $coutTransportParKg = (float) $param->cout_transport_par_kg;
        $transport     = round(max(0.20, $poids * $coutTransportParKg), 2);

        $totalRecyclage = round($coutTri + $traitement + $majoration + $transport, 2);

        return [
            'tri'                => $coutTri,
            'traitement_base'    => $traitement,
            'cout_au_kg'         => $coutAuKg,
            'majoration_mixte'   => $majoration,
            'majoration_pourcent'=> $majorationPourcent,
            'transport'          => $transport,
            'total'              => $totalRecyclage,
            'poids_kg'           => $poids,
            'matiere'            => $param->matiere,
        ];
    }

    /**
     * Calcule le coût complet de la filière Upcycling / Transformation / Réparation.
     *
     * Formule :
     *   Achat              = cout_achat (saisi par l'atelier, 0 si don)
     *   Main-d'œuvre       = heures_travail * tarif_horaire (de l'atelier ou barème)
     *   Matériaux          = cout_materiaux (fournitures, boutons, fils, tissus)
     *   Transport/logique  = poids_kg * cout_transport_par_kg
     *   Total upcycling    = Achat + Main-d'œuvre + Matériaux + Transport
     *
     * @param float $coutAchat
     * @param float $heuresTravail
     * @param float $coutMateriaux
     * @param float $poidsKg
     * @param User|null $atelier
     * @param ParametreCout|null $parametre
     * @return array
     */
    public function calculerUpcycling(
        float $coutAchat = 0.0,
        float $heuresTravail = 0.0,
        float $coutMateriaux = 0.0,
        float $poidsKg = 0.0,
        ?User $atelier = null,
        ?ParametreCout $parametre = null
    ): array {
        $param = $parametre ?? ParametreCout::pourMatiere('defaut');

        $achat     = max(0.0, round($coutAchat, 2));
        $heures    = max(0.0, (float) $heuresTravail);
        $materiaux = max(0.0, round($coutMateriaux, 2));
        $poids     = max(0.05, (float) $poidsKg);

        // Tarif horaire : priorité au profil de l'atelier, sinon barème paramétré
        $tarifHoraire = $atelier && $atelier->tarif_horaire !== null && (float) $atelier->tarif_horaire > 0
            ? (float) $atelier->tarif_horaire
            : (float) $param->tarif_horaire_defaut;

        $mainDOeuvre = round($heures * $tarifHoraire, 2);
        $transport   = round(max(0.20, $poids * (float) $param->cout_transport_par_kg), 2);

        $totalUpcycling = round($achat + $mainDOeuvre + $materiaux + $transport, 2);

        return [
            'achat'         => $achat,
            'heures'        => $heures,
            'tarif_horaire' => $tarifHoraire,
            'main_d_oeuvre' => $mainDOeuvre,
            'materiaux'     => $materiaux,
            'transport'     => $transport,
            'total'         => $totalUpcycling,
        ];
    }

    /**
     * Analyse et compare les deux filières pour émettre une recommandation d'orientation.
     *
     * @param array $coutRecyclage
     * @param array $coutUpcycling
     * @param string|null $etat
     * @return array
     */
    public function comparerFilieres(array $coutRecyclage, array $coutUpcycling, ?string $etat = null): array
    {
        $totalRecyclage = $coutRecyclage['total'];
        $totalUpcycling = $coutUpcycling['total'];
        $etatNormalise  = strtolower($etat ?? '');

        // Si l'état est "Usage" ou "Dégradé" et que l'upcycling exige beaucoup de travail (> 4h ou coût > 50 DT)
        if (($etatNormalise === 'usage' && $coutUpcycling['heures'] >= 3.0) || ($totalUpcycling > 60 && $totalRecyclage < 5)) {
            return [
                'filiere_recommandee' => 'RECYCLAGE',
                'label'               => 'Orientation Recyclage recommandée',
                'badge_class'         => 'bg-blue-100 text-blue-700',
                'motif'               => "Le coût de remise en état ({$totalUpcycling} DT) est trop lourd par rapport à l'état de la pièce. La valorisation matière en recyclage ({$totalRecyclage} DT) est plus sobre.",
            ];
        }

        return [
            'filiere_recommandee' => 'UPCYCLING',
            'label'               => 'Filière Upcycling / Transformation',
            'badge_class'         => 'bg-emerald-100 text-emerald-700',
            'motif'               => "La pièce a un potentiel de revente intéressant en upcycling avec un coût de revient maîtrisé ({$totalUpcycling} DT).",
        ];
    }
}

