<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointCollecte extends Model
{
    /** @use HasFactory<\Database\Factories\PointCollecteFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom',
        'adresse',
        'ville',
        'latitude',
        'longitude',
        'capacite_max_kg',
        'telephone',
        'statut',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'latitude'        => 'float',
            'longitude'       => 'float',
            'capacite_max_kg' => 'float',
        ];
    }

    public function gestionnaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getStatutBadgeAttribute(): array
    {
        return match ($this->statut) {
            'ACTIF' => ['label' => 'Actif', 'class' => 'bg-emerald-100 text-emerald-700'],
            'INACTIF' => ['label' => 'Inactif', 'class' => 'bg-gray-100 text-gray-700'],
            'PLEIN' => ['label' => 'Plein', 'class' => 'bg-amber-100 text-amber-700'],
            default => ['label' => $this->statut, 'class' => 'bg-slate-100 text-slate-700'],
        };
    }
}
