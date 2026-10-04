<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Atelier extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom',
        'specialite',
        'description',
        'portfolio_url',
        'tarif_horaire',
        'localisation',
        'note_moyenne',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'tarif_horaire' => 'float',
            'note_moyenne'  => 'float',
            'actif'         => 'boolean',
        ];
    }

    /*
    |------------------------------------------------------------------
    | Spécialités (partagées avec ProjetUpcycling::categorie_produit)
    |------------------------------------------------------------------
    */
    const SPECIALITES = [
        'VETEMENT'   => 'Vêtements',
        'SAC'        => 'Sacs & pochettes',
        'ACCESSOIRE' => 'Accessoires',
        'DECORATION' => 'Décoration',
        'PATCHWORK'  => 'Patchwork & textile maison',
    ];

    const ICONS = [
        'VETEMENT'   => 'fa-tshirt',
        'SAC'        => 'fa-shopping-bag',
        'ACCESSOIRE' => 'fa-hat-cowboy',
        'DECORATION' => 'fa-couch',
        'PATCHWORK'  => 'fa-th',
    ];

    /*
    |------------------------------------------------------------------
    | Relations
    |------------------------------------------------------------------
    */

    /** Profil atelier d'un User (rôle ATELIER) */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Un atelier réalise plusieurs projets d'upcycling */
    public function projetUpcyclings(): HasMany
    {
        return $this->hasMany(ProjetUpcycling::class);
    }

    /*
    |------------------------------------------------------------------
    | Helpers
    |------------------------------------------------------------------
    */

    public function getSpecialiteLabelAttribute(): string
    {
        return self::SPECIALITES[$this->specialite] ?? $this->specialite;
    }

    public function getIconeAttribute(): string
    {
        return self::ICONS[$this->specialite] ?? 'fa-cut';
    }

    /** Nombre de projets en cours de réalisation (charge de travail) */
    public function getChargeAttribute(): int
    {
        return $this->projetUpcyclings()
            ->whereIn('statut', ProjetUpcycling::STATUTS_EN_COURS)
            ->count();
    }

    /** Recalcule la note moyenne à partir des notes laissées par les clients */
    public function recalculerNote(): void
    {
        $moyenne = $this->projetUpcyclings()->whereNotNull('note_client')->avg('note_client');
        $this->update(['note_moyenne' => round((float) $moyenne, 1)]);
    }
}
