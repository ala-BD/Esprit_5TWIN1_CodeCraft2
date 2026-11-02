<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle ParametreCout : Barèmes configurables de recyclage et de transformation.
 * Permet à l'Administrateur d'ajuster les coûts au kilo, frais de tri, transport,
 * tarif horaire atelier et seuils de marge sans modifier le code source.
 */
class ParametreCout extends Model
{
    use HasFactory;

    protected $table = 'parametres_cout';

    protected $fillable = [
        'matiere',
        'cout_recyclage_par_kg',
        'cout_tri_fixe',
        'cout_transport_par_kg',
        'majoration_mixte_pourcent',
        'tarif_horaire_defaut',
        'marge_cible_pourcent',
        'marge_minimale_pourcent',
        'frais_plateforme_pourcent',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'cout_recyclage_par_kg'     => 'decimal:2',
            'cout_tri_fixe'             => 'decimal:2',
            'cout_transport_par_kg'     => 'decimal:2',
            'majoration_mixte_pourcent' => 'decimal:2',
            'tarif_horaire_defaut'      => 'decimal:2',
            'marge_cible_pourcent'      => 'decimal:2',
            'marge_minimale_pourcent'   => 'decimal:2',
            'frais_plateforme_pourcent' => 'decimal:2',
        ];
    }

    /**
     * Récupère le barème pour une matière spécifique ou le barème par défaut.
     */
    public static function pourMatiere(?string $matiere): self
    {
        $normalized = $matiere ? trim($matiere) : 'defaut';

        $param = self::where('matiere', $normalized)->first();
        if ($param) {
            return $param;
        }

        // Tenter recherche insensible à la casse
        $param = self::whereRaw('LOWER(matiere) = ?', [strtolower($normalized)])->first();
        if ($param) {
            return $param;
        }

        // Fallback sur 'defaut' ou première ligne
        return self::where('matiere', 'defaut')->first()
            ?? self::first()
            ?? new self([
                'matiere'                   => 'defaut',
                'cout_recyclage_par_kg'     => 2.00,
                'cout_tri_fixe'             => 0.50,
                'cout_transport_par_kg'     => 0.30,
                'majoration_mixte_pourcent' => 25.00,
                'tarif_horaire_defaut'      => 15.00,
                'marge_cible_pourcent'      => 20.00,
                'marge_minimale_pourcent'   => 5.00,
                'frais_plateforme_pourcent' => 5.00,
            ]);
    }
}

