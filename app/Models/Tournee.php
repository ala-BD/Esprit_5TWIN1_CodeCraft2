<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'zone',
        'vehicule',
        'distance_km',
        'itineraire_ia',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date'        => 'date',
            'distance_km' => 'float',
        ];
    }

    /*
    |------------------------------------------------------------------
    | Constantes statut
    |------------------------------------------------------------------
    */
    const STATUT_PLANIFIEE = 'PLANIFIEE';
    const STATUT_EN_COURS  = 'EN_COURS';
    const STATUT_TERMINEE  = 'TERMINEE';
    const STATUT_ANNULEE   = 'ANNULEE';

    /** Statut => [libellé, fond, texte] */
    const STATUTS = [
        self::STATUT_PLANIFIEE => ['label' => 'Planifiée', 'bg' => '#e2ecfb', 'text' => '#1d4fa3'],
        self::STATUT_EN_COURS  => ['label' => 'En cours',  'bg' => '#faefd2', 'text' => '#7a5200'],
        self::STATUT_TERMINEE  => ['label' => 'Terminée',  'bg' => '#e1f3e6', 'text' => '#17663a'],
        self::STATUT_ANNULEE   => ['label' => 'Annulée',   'bg' => '#eceef3', 'text' => '#3a4558'],
    ];

    /*
    |------------------------------------------------------------------
    | Relations
    |------------------------------------------------------------------
    */

    /** Collecteur qui effectue la tournée */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Une tournée contient plusieurs missions, dans l'ordre de passage */
    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class)->orderBy('ordre')->orderBy('heure_prevue');
    }

    /*
    |------------------------------------------------------------------
    | Helpers
    |------------------------------------------------------------------
    */

    /** Retourne le badge couleur selon le statut */
    public function getStatutBadgeAttribute(): array
    {
        return self::STATUTS[$this->statut] ?? ['label' => $this->statut, 'bg' => '#eceef3', 'text' => '#3a4558'];
    }

    /** Libellés des statuts, pour les listes déroulantes */
    public static function statutOptions(): array
    {
        return array_map(fn (array $statut) => $statut['label'], self::STATUTS);
    }
}
