<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $commande_id
 * @property int $article_id
 * @property int $quantite
 * @property float $prix_unitaire
 * @property float $remise
 * @property float $total_ligne
 * @property-read \App\Models\Commande $commande
 * @property-read \App\Models\Article $article
 */
class LigneCommande extends Model
{
    protected $table = 'ligne_commandes';

    protected $fillable = [
        'commande_id',
        'article_id',
        'quantite',
        'prix_unitaire',
        'remise',
        'total_ligne',
    ];

    protected function casts(): array
    {
        return [
            'prix_unitaire' => 'decimal:2',
            'remise'        => 'decimal:2',
            'total_ligne'   => 'decimal:2',
            'quantite'      => 'integer',
        ];
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /** Prix après remise */
    public function getPrixFinalAttribute(): float
    {
        return (float) ($this->prix_unitaire - $this->remise);
    }
}
