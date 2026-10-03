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

    /** Simule la recommandation IA selon la composition */
    public static function recommanderFiliereIA(string $composition, float $poidsKg): string
    {
        $composition = strtolower($composition);
        if (str_contains($composition, 'coton') && $poidsKg < 10)  return 'REVENTE';
        if (str_contains($composition, 'lin')   || str_contains($composition, 'laine')) return 'UPCYCLING';
        if (str_contains($composition, 'polyester') || $poidsKg > 50) return 'RECYCLAGE_FIBRE';
        return 'RECYCLAGE_ENERGIE';
    }
}
