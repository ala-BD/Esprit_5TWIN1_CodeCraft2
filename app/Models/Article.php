<?php

namespace App\Models;

use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $don_vetement_id
 * @property string $titre
 * @property string|null $description
 * @property float $prix
 * @property float|null $prix_estime_ia
 * @property int $stock
 * @property string $categorie
 * @property string $statut
 * @property array|null $images
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\DonVetement|null $donVetement
 */
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    /**
     * Catégories courantes de la marketplace textile.
     */
    public const CATEGORIES = [
        'Vêtements Homme',
        'Vêtements Femme',
        'Vêtements Enfant',
        'Accessoires & Sacs',
        'Linge de maison',
        'Textile Upcyclé',
        'Chaussures',
        'Autre',
    ];

    /**
     * Statuts possibles de l'article.
     */
    public const STATUT_DISPONIBLE = 'DISPONIBLE';
    public const STATUT_RESERVE    = 'RESERVE';
    public const STATUT_VENDU      = 'VENDU';
    public const STATUT_ARCHIVE    = 'ARCHIVE';

    public const STATUTS = [
        self::STATUT_DISPONIBLE,
        self::STATUT_RESERVE,
        self::STATUT_VENDU,
        self::STATUT_ARCHIVE,
    ];

    /**
     * Les attributs assignables en masse.
     */
    protected $fillable = [
        'user_id',
        'don_vetement_id',
        'titre',
        'description',
        'prix',
        'prix_estime_ia',
        'stock',
        'categorie',
        'statut',
        'images',
    ];

    /**
     * Les attributs à caster.
     */
    protected function casts(): array
    {
        return [
            'prix'           => 'decimal:2',
            'prix_estime_ia' => 'decimal:2',
            'stock'          => 'integer',
            'images'         => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * L'article appartient à un utilisateur propriétaire.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * L'article peut provenir d'un don de vêtement (optionnel).
     */
    public function donVetement(): BelongsTo
    {
        return $this->belongsTo(DonVetement::class, 'don_vetement_id');
    }

    /**
     * L'article peut être présent dans plusieurs lignes de commande.
     */
    public function lignesCommande(): HasMany
    {
        return $this->hasMany(LigneCommande::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers & Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Vérifie si l'article est disponible à l'achat.
     */
    public function isDisponible(): bool
    {
        return $this->statut === self::STATUT_DISPONIBLE && $this->stock > 0;
    }

    /**
     * Scope pour filtrer les articles disponibles.
     */
    public function scopeDisponible($query)
    {
        return $query->where('statut', self::STATUT_DISPONIBLE)->where('stock', '>', 0);
    }

    /**
     * Retourne l'URL de la première image ou null.
     */
    public function getFirstImageUrl(): ?string
    {
        if (!empty($this->images) && is_array($this->images) && count($this->images) > 0) {
            $path = $this->images[0];
            return str_starts_with($path, 'http') ? $path : Storage::url($path);
        }
        return null;
    }

    /**
     * Retourne toutes les URLs d'images sous forme de tableau.
     */
    public function getImageUrls(): array
    {
        if (empty($this->images) || !is_array($this->images)) {
            return [];
        }

        return array_map(function ($path) {
            return str_starts_with($path, 'http') ? $path : Storage::url($path);
        }, $this->images);
    }
}
