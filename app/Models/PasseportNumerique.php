<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PasseportNumerique extends Model
{
    use HasFactory;

    protected $fillable = [
        'lot_textile_id',
        'qr_code',
        'hash_integrite',
        'co2_evite_kg',
        'eau_economisee_l',
        'date_emission',
    ];

    protected function casts(): array
    {
        return [
            'co2_evite_kg'     => 'float',
            'eau_economisee_l' => 'float',
            'date_emission'    => 'datetime',
        ];
    }

    /*
    |------------------------------------------------------------------
    | Relations
    |------------------------------------------------------------------
    */

    public function lotTextile(): BelongsTo
    {
        return $this->belongsTo(LotTextile::class);
    }

    /*
    |------------------------------------------------------------------
    | Factory helpers
    |------------------------------------------------------------------
    */

    /**
     * Génère un passeport pour un lot donné.
     * Calcul simplifié : 1 kg textile ≈ 2.4 kg CO₂, 1 kg ≈ 3000 L eau
     */
    public static function genererPourLot(LotTextile $lot): self
    {
        $poids      = $lot->poids_kg;
        $co2        = round($poids * 2.4, 2);
        $eau        = round($poids * 3000, 0);
        $qrCode     = strtoupper('PSP-' . $lot->reference . '-' . Str::random(6));
        $hash       = hash('sha256', $lot->id . $lot->reference . $qrCode . now());

        return self::create([
            'lot_textile_id'   => $lot->id,
            'qr_code'          => $qrCode,
            'hash_integrite'   => $hash,
            'co2_evite_kg'     => $co2,
            'eau_economisee_l' => $eau,
            'date_emission'    => now(),
        ]);
    }
}
