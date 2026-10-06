<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LotTextile extends Model
{
    use HasFactory;

    protected $fillable = [
        'recycleur_id',
        'don_vetement_id',
        'reference',
        'poids_kg',
        'composition',
        'origine',
        'filiere_recommandee_ia',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'poids_kg' => 'float',
        ];
    }

    /*
    |------------------------------------------------------------------
    | Constantes statut
    |------------------------------------------------------------------
    */
    const STATUT_EN_ATTENTE   = 'EN_ATTENTE';
    const STATUT_EN_TRAITEMENT = 'EN_TRAITEMENT';
    const STATUT_TRAITE       = 'TRAITE';
    const STATUT_CERTIFIE     = 'CERTIFIE';

    /*
    |------------------------------------------------------------------
    | Relations
    |------------------------------------------------------------------
    */

    /** Appartient à un Recycleur */
    public function recycleur(): BelongsTo
    {
        return $this->belongsTo(Recycleur::class);
    }

    /** Provient d'un DonVetement (optionnel) */
    public function donVetement(): BelongsTo
    {
        return $this->belongsTo(DonVetement::class);
    }

    /** Un lot comporte plusieurs étapes de traitement */
    public function etapeTraitements(): HasMany
    {
        return $this->hasMany(EtapeTraitement::class)->orderBy('date_debut');
    }

    /** Un lot possède au plus un passeport numérique */
    public function passeportNumerique(): HasOne
    {
        return $this->hasOne(PasseportNumerique::class);
    }

    /*
    |------------------------------------------------------------------
    | Helpers
    |------------------------------------------------------------------
    */

    /** Retourne le badge couleur selon le statut */
    public function getStatutBadgeAttribute(): array
    {
        return match($this->statut) {
            self::STATUT_EN_ATTENTE    => ['label' => 'En attente',    'class' => 'bg-yellow-100 text-yellow-700'],
            self::STATUT_EN_TRAITEMENT => ['label' => 'En traitement', 'class' => 'bg-blue-100 text-blue-700'],
            self::STATUT_TRAITE        => ['label' => 'Traité',        'class' => 'bg-green-100 text-green-700'],
            self::STATUT_CERTIFIE      => ['label' => 'Certifié',      'class' => 'bg-purple-100 text-purple-700'],
            default                    => ['label' => $this->statut,   'class' => 'bg-gray-100 text-gray-700'],
        };
    }

    /** Retourne le badge couleur de la filière IA */
    public function getFiliereIaBadgeAttribute(): array
    {
        return match($this->filiere_recommandee_ia) {
            'REVENTE'            => ['label' => 'Revente',            'class' => 'bg-emerald-100 text-emerald-700', 'icon' => 'fa-store'],
            'UPCYCLING'          => ['label' => 'Upcycling',          'class' => 'bg-amber-100 text-amber-700',    'icon' => 'fa-cut'],
            'RECYCLAGE_FIBRE'    => ['label' => 'Recyclage fibre',    'class' => 'bg-blue-100 text-blue-700',      'icon' => 'fa-recycle'],
            'RECYCLAGE_ENERGIE'  => ['label' => 'Recyclage énergie',  'class' => 'bg-red-100 text-red-700',        'icon' => 'fa-bolt'],
            default              => ['label' => $this->filiere_recommandee_ia, 'class' => 'bg-gray-100 text-gray-700', 'icon' => 'fa-question'],
        };
    }

    /** Progression du traitement en % */
    public function getProgressionAttribute(): int
    {
        $etapes = $this->etapeTraitements()->count();
        if ($etapes === 0) return 0;
        $terminees = $this->etapeTraitements()->whereNotNull('date_fin')->count();
        return (int) round(($terminees / 6) * 100); // 6 étapes max
    }

    /**
     * Recommandation IA de la filière selon la composition et le poids.
     *
     * Règles métier :
     *  1. Fibres synthétiques (polyester, nylon, acrylique, élasthanne)
     *       → toujours RECYCLAGE_FIBRE (non recyclable autrement)
     *  2. Fibres mélangées (synthétique + naturel)
     *       → RECYCLAGE_FIBRE (le mélange empêche la revente/upcycling)
     *  3. Fibres nobles pures (laine, lin, soie, cachemire) et lot <= 100 kg
     *       → UPCYCLING (transformation artisanale possible)
     *  4. Fibres nobles en grand lot (> 100 kg)
     *       → RECYCLAGE_FIBRE (trop volumineux pour upcycling)
     *  5. Coton pur et lot <= 50 kg
     *       → REVENTE (peut être revendu directement)
     *  6. Coton pur et lot > 50 kg
     *       → RECYCLAGE_FIBRE (trop lourd pour revente)
     *  7. Composition inconnue ou grand lot
     *       → RECYCLAGE_ENERGIE (dernier recours)
     */
    public static function recommanderFiliereIA(string $composition, float $poidsKg): string
    {
        $c = strtolower($composition);

        // Détection des types de fibres
        $aSynthetique = str_contains($c, 'polyester')
                     || str_contains($c, 'nylon')
                     || str_contains($c, 'acrylique')
                     || str_contains($c, 'élasthanne')
                     || str_contains($c, 'elasthanne')
                     || str_contains($c, 'viscose');

        $aFibreNoble  = str_contains($c, 'laine')
                     || str_contains($c, 'lin')
                     || str_contains($c, 'soie')
                     || str_contains($c, 'cachemire');

        $aCoton       = str_contains($c, 'coton');

        // Règle 1 & 2 : présence de synthétique → recyclage fibre
        if ($aSynthetique) {
            return 'RECYCLAGE_FIBRE';
        }

        // Règle 3 & 4 : fibre noble pure (sans synthétique)
        if ($aFibreNoble) {
            return $poidsKg <= 100 ? 'UPCYCLING' : 'RECYCLAGE_FIBRE';
        }

        // Règle 5 & 6 : coton pur
        if ($aCoton) {
            return $poidsKg <= 50 ? 'REVENTE' : 'RECYCLAGE_FIBRE';
        }

        // Règle 7 : composition inconnue ou grand lot
        return $poidsKg > 50 ? 'RECYCLAGE_FIBRE' : 'RECYCLAGE_ENERGIE';
    }
}
