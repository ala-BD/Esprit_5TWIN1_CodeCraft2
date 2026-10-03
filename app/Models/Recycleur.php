<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recycleur extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom',
        'agrement',
        'capacite_kg',
        'localisation',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'capacite_kg' => 'float',
            'actif'       => 'boolean',
        ];
    }

    /*
    |------------------------------------------------------------------
    | Relations
    |------------------------------------------------------------------
    */

    /** Recycleur appartient à un User */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Un recycleur traite plusieurs lots */
    public function lotTextiles(): HasMany
    {
        return $this->hasMany(LotTextile::class);
    }

    /*
    |------------------------------------------------------------------
    | Helpers
    |------------------------------------------------------------------
    */

    /** Nombre total de lots traités */
    public function getNbLotsAttribute(): int
    {
        return $this->lotTextiles()->count();
    }

    /** Total kg traités */
    public function getTotalKgAttribute(): float
    {
        return $this->lotTextiles()->sum('poids_kg');
    }
}
