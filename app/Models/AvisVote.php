<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $avis_id
 * @property int $user_id
 * @property string $type
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Avis $avis
 * @property-read \App\Models\User $user
 */
class AvisVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'avis_id',
        'user_id',
        'type',
    ];

    public function avis(): BelongsTo
    {
        return $this->belongsTo(Avis::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
