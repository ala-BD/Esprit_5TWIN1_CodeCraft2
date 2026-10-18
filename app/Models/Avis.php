<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $commande_id
 * @property int $user_id
 * @property int $article_id
 * @property int $note_livraison
 * @property int $note_article
 * @property int $note_clarte
 * @property int $note_service
 * @property float $note_globale
 * @property string|null $commentaire
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Article $article
 * @property-read \App\Models\Commande $commande
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AvisVote> $votes
 */
class Avis extends Model
{
    use HasFactory;

    protected $table = 'avis';

    protected $fillable = [
        'commande_id',
        'user_id',
        'article_id',
        'note_livraison',
        'note_article',
        'note_clarte',
        'note_service',
        'note_globale',
        'commentaire',
    ];

    protected function casts(): array
    {
        return [
            'note_livraison' => 'integer',
            'note_article'   => 'integer',
            'note_clarte'    => 'integer',
            'note_service'   => 'integer',
            'note_globale'   => 'decimal:2',
        ];
    }

    // -------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(AvisVote::class);
    }

    // -------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------

    /**
     * Calcule la note globale comme la moyenne exacte des 4 critères.
     */
    public static function calculerNoteGlobale(int $livraison, int $article, int $clarte, int $service): float
    {
        return round(($livraison + $article + $clarte + $service) / 4, 2);
    }

    /**
     * Nombre de likes.
     */
    public function likesCount(): int
    {
        return $this->votes()->where('type', 'like')->count();
    }

    /**
     * Nombre de dislikes.
     */
    public function dislikesCount(): int
    {
        return $this->votes()->where('type', 'dislike')->count();
    }

    /**
     * Récupère le type de vote de l'utilisateur donné ('like', 'dislike', ou null).
     */
    public function getUserVote(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        return $this->votes()->where('user_id', $user->id)->value('type');
    }

    public function isLikedBy(?User $user): bool
    {
        return $this->getUserVote($user) === 'like';
    }

    public function isDislikedBy(?User $user): bool
    {
        return $this->getUserVote($user) === 'dislike';
    }
}
