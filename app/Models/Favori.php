<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $article_id
 * @property float|null $prix_au_moment_ajout
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Article $article
 */
class Favori extends Model
{
    protected $table = 'favoris';

    protected $fillable = [
        'user_id',
        'article_id',
        'prix_au_moment_ajout',
    ];

    protected function casts(): array
    {
        return [
            'prix_au_moment_ajout' => 'decimal:2',
        ];
    }

    // Relations
    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
    public function article(): BelongsTo { return $this->belongsTo(Article::class); }
}
