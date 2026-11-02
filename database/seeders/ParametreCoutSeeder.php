<?php

namespace Database\Seeders;

use App\Models\ParametreCout;
use Illuminate\Database\Seeder;

class ParametreCoutSeeder extends Seeder
{
    /**
     * Initialise les barèmes de calcul des coûts de recyclage et seuils de rentabilité.
     */
    public function run(): void
    {
        $baremes = [
            [
                'matiere'                   => 'defaut',
                'cout_recyclage_par_kg'     => 2.00,
                'cout_tri_fixe'             => 0.50,
                'cout_transport_par_kg'     => 0.30,
                'majoration_mixte_pourcent' => 25.00,
                'tarif_horaire_defaut'      => 15.00,
                'marge_cible_pourcent'      => 20.00,
                'marge_minimale_pourcent'   => 5.00,
                'frais_plateforme_pourcent' => 5.00,
                'notes'                     => 'Barème général par défaut',
            ],
            [
                'matiere'                   => 'Coton',
                'cout_recyclage_par_kg'     => 1.80,
                'cout_tri_fixe'             => 0.50,
                'cout_transport_par_kg'     => 0.30,
                'majoration_mixte_pourcent' => 0.00,
                'tarif_horaire_defaut'      => 15.00,
                'marge_cible_pourcent'      => 20.00,
                'marge_minimale_pourcent'   => 5.00,
                'frais_plateforme_pourcent' => 5.00,
                'notes'                     => 'Fibre naturelle pure, effilochage standard et re-filature',
            ],
            [
                'matiere'                   => 'Lin',
                'cout_recyclage_par_kg'     => 2.10,
                'cout_tri_fixe'             => 0.50,
                'cout_transport_par_kg'     => 0.30,
                'majoration_mixte_pourcent' => 0.00,
                'tarif_horaire_defaut'      => 15.00,
                'marge_cible_pourcent'      => 20.00,
                'marge_minimale_pourcent'   => 5.00,
                'frais_plateforme_pourcent' => 5.00,
                'notes'                     => 'Fibre longue noble, excellente valeur d upcycling',
            ],
            [
                'matiere'                   => 'Polyester',
                'cout_recyclage_par_kg'     => 2.20,
                'cout_tri_fixe'             => 0.50,
                'cout_transport_par_kg'     => 0.30,
                'majoration_mixte_pourcent' => 0.00,
                'tarif_horaire_defaut'      => 15.00,
                'marge_cible_pourcent'      => 20.00,
                'marge_minimale_pourcent'   => 5.00,
                'frais_plateforme_pourcent' => 5.00,
                'notes'                     => 'Thermoplastique recyclable en nouveaux granulés (rPET)',
            ],
            [
                'matiere'                   => 'Laine',
                'cout_recyclage_par_kg'     => 2.60,
                'cout_tri_fixe'             => 0.60,
                'cout_transport_par_kg'     => 0.30,
                'majoration_mixte_pourcent' => 0.00,
                'tarif_horaire_defaut'      => 18.00,
                'marge_cible_pourcent'      => 25.00,
                'marge_minimale_pourcent'   => 8.00,
                'frais_plateforme_pourcent' => 5.00,
                'notes'                     => 'Laine cardée ou régénérée, forte valeur marchande',
            ],
            [
                'matiere'                   => 'Soie',
                'cout_recyclage_par_kg'     => 3.20,
                'cout_tri_fixe'             => 0.70,
                'cout_transport_par_kg'     => 0.30,
                'majoration_mixte_pourcent' => 0.00,
                'tarif_horaire_defaut'      => 20.00,
                'marge_cible_pourcent'      => 25.00,
                'marge_minimale_pourcent'   => 8.00,
                'frais_plateforme_pourcent' => 5.00,
                'notes'                     => 'Matière délicate, travail d atelier minutieux privilégié',
            ],
            [
                'matiere'                   => 'Synthetique',
                'cout_recyclage_par_kg'     => 2.40,
                'cout_tri_fixe'             => 0.50,
                'cout_transport_par_kg'     => 0.30,
                'majoration_mixte_pourcent' => 15.00,
                'tarif_horaire_defaut'      => 15.00,
                'marge_cible_pourcent'      => 20.00,
                'marge_minimale_pourcent'   => 5.00,
                'frais_plateforme_pourcent' => 5.00,
                'notes'                     => 'Nylon, acrylique, élasthanne - recyclage chimique ou mécanique',
            ],
            [
                'matiere'                   => 'Mixte',
                'cout_recyclage_par_kg'     => 2.80,
                'cout_tri_fixe'             => 0.70,
                'cout_transport_par_kg'     => 0.30,
                'majoration_mixte_pourcent' => 30.00,
                'tarif_horaire_defaut'      => 15.00,
                'marge_cible_pourcent'      => 20.00,
                'marge_minimale_pourcent'   => 5.00,
                'frais_plateforme_pourcent' => 5.00,
                'notes'                     => 'Poly-coton et multi-fibres, séparation difficile requérant majoration',
            ],
            [
                'matiere'                   => 'Autre',
                'cout_recyclage_par_kg'     => 2.00,
                'cout_tri_fixe'             => 0.50,
                'cout_transport_par_kg'     => 0.30,
                'majoration_mixte_pourcent' => 20.00,
                'tarif_horaire_defaut'      => 15.00,
                'marge_cible_pourcent'      => 20.00,
                'marge_minimale_pourcent'   => 5.00,
                'frais_plateforme_pourcent' => 5.00,
                'notes'                     => 'Fibres atypiques ou compositions indéterminées',
            ],
        ];

        foreach ($baremes as $item) {
            ParametreCout::updateOrCreate(
                ['matiere' => $item['matiere']],
                $item
            );
        }
    }
}

