<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Devis extends Model
{
    use HasFactory;

    protected $table = 'devis';

    protected $fillable = [
        'projet_upcycling_id',
        'montant',
        'delai_jours',
        'message',
        'statut',
        'date_emission',
    ];

    protected function casts(): array
    {
        return [
            'montant'       => 'float',
            'delai_jours'   => 'integer',
            'date_emission' => 'date',
        ];
    }

    const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    const STATUT_ACCEPTE    = 'ACCEPTE';
    const STATUT_REFUSE     = 'REFUSE';

    /*
    |------------------------------------------------------------------
    | Relations
    |------------------------------------------------------------------
    */

    public function projetUpcycling(): BelongsTo
    {
        return $this->belongsTo(ProjetUpcycling::class);
    }

    /*
    |------------------------------------------------------------------
    | Helpers
    |------------------------------------------------------------------
    */

    public function getStatutBadgeAttribute(): array
    {
        return match($this->statut) {
            self::STATUT_EN_ATTENTE => ['label' => 'En attente', 'class' => 'bg-yellow-100 text-yellow-700'],
            self::STATUT_ACCEPTE    => ['label' => 'Accepté',    'class' => 'bg-green-100 text-green-700'],
            self::STATUT_REFUSE     => ['label' => 'Refusé',     'class' => 'bg-red-100 text-red-600'],
            default                 => ['label' => $this->statut, 'class' => 'bg-gray-100 text-gray-700'],
        };
    }

    /** Date de livraison estimée si le devis est accepté aujourd'hui */
    public function getLivraisonEstimeeAttribute(): \Illuminate\Support\Carbon
    {
        return now()->addDays($this->delai_jours);
    }
}
