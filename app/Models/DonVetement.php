<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DonVetement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'point_collecte_id',
        'type',
        'matiere',
        'taille',
        'etat',
        'photo_url',
        'qr_code',
        'statut',
        'date_depot',
        'categorie_ia',
        'score_confiance_ia',
    ];

    protected function casts(): array
    {
        return [
            'date_depot'         => 'date',
            'score_confiance_ia' => 'float',
        ];
    }

    /*
    |------------------------------------------------------------------
    | Relations
    |------------------------------------------------------------------
    */

    /** Donateur */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pointCollecte(): BelongsTo
    {
        return $this->belongsTo(PointCollecte::class);
    }

    /** Un don peut devenir un LotTextile */
    public function lotTextile(): HasOne
    {
        return $this->hasOne(LotTextile::class);
    }

    public function getStatutBadgeAttribute(): array
    {
        return match ($this->statut) {
            'DEPOSE' => ['label' => 'Déposé', 'class' => 'bg-blue-100 text-blue-700'],
            'EN_TRI' => ['label' => 'En tri', 'class' => 'bg-amber-100 text-amber-700'],
            'VENDU' => ['label' => 'Vendu', 'class' => 'bg-emerald-100 text-emerald-700'],
            'UPCYCLING' => ['label' => 'Upcycling', 'class' => 'bg-purple-100 text-purple-700'],
            'RECYCLE' => ['label' => 'Recyclé', 'class' => 'bg-slate-100 text-slate-700'],
            default => ['label' => $this->statut, 'class' => 'bg-gray-100 text-gray-700'],
        };
    }
}
