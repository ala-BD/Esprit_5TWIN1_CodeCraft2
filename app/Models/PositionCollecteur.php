<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Position GPS d'un collecteur, envoyée par son téléphone pendant une tournée.
 */
class PositionCollecteur extends Model
{
    protected $table = 'positions_collecteurs';

    public $timestamps = false;

    /** Au-delà, la position n'est plus considérée comme « en direct » */
    public const MINUTES_EN_DIRECT = 5;

    protected $fillable = [
        'user_id',
        'tournee_id',
        'latitude',
        'longitude',
        'precision_m',
        'enregistree_le',
    ];

    protected function casts(): array
    {
        return [
            'latitude'       => 'float',
            'longitude'      => 'float',
            'enregistree_le' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tournee(): BelongsTo
    {
        return $this->belongsTo(Tournee::class);
    }

    public function estEnDirect(): bool
    {
        return $this->enregistree_le->gt(now()->subMinutes(self::MINUTES_EN_DIRECT));
    }
}
