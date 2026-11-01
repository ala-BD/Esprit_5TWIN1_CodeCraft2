<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Message du fil de discussion d'un projet d'upcycling
 * (client, atelier ou assistant styliste IA).
 */
class MessageProjet extends Model
{
    use HasFactory;

    protected $fillable = [
        'projet_upcycling_id',
        'user_id',
        'auteur',
        'contenu',
    ];

    const AUTEUR_CLIENT  = 'CLIENT';
    const AUTEUR_ATELIER = 'ATELIER';
    const AUTEUR_IA      = 'IA';

    public function projetUpcycling(): BelongsTo
    {
        return $this->belongsTo(ProjetUpcycling::class);
    }

    /** Auteur humain (null pour l'IA) */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEstIaAttribute(): bool
    {
        return $this->auteur === self::AUTEUR_IA;
    }

    /** Nom affiché dans la discussion */
    public function getNomAuteurAttribute(): string
    {
        return match ($this->auteur) {
            self::AUTEUR_IA      => 'Styliste IA',
            self::AUTEUR_ATELIER => $this->projetUpcycling->atelier->nom ?? 'Atelier',
            default              => $this->user?->full_name ?? 'Client',
        };
    }
}
