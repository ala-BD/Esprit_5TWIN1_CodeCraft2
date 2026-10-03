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

    /** Un don peut devenir un LotTextile */
    public function lotTextile(): HasOne
    {
        return $this->hasOne(LotTextile::class);
    }
}
