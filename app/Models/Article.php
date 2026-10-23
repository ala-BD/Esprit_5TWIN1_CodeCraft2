<?php

namespace App\Models;

use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
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
 * @property string|null $taille
 * @property string|null $matiere
 * @property string|null $etat
 * @property string|null $couleur
 * @property float|null $co2_economise_kg
 * @property array|null $images
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\DonVetement|null $donVetement
 */
class Article extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'Vetements Homme',
        'Vetements Femme',
        'Vetements Enfant',
        'Accessoires & Sacs',
        'Linge de maison',
        'Textile Upcycle',
        'Chaussures',
        'Autre',
    ];

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

    public const TAILLES  = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'Unique'];
    public const MATIERES = ['Coton', 'Lin', 'Polyester', 'Laine', 'Soie', 'Synthetique', 'Mixte', 'Autre'];
    public const ETATS    = ['Neuf', 'Tres bon etat', 'Bon etat', 'Usage'];
    public const COULEURS = ['Noir', 'Blanc', 'Gris', 'Bleu', 'Rouge', 'Vert', 'Jaune', 'Orange', 'Rose', 'Violet', 'Marron', 'Beige', 'Multicolore'];

    protected $fillable = [
        'user_id', 'don_vetement_id', 'titre', 'description', 'prix',
        'prix_estime_ia', 'stock', 'categorie', 'statut',
        'taille', 'matiere', 'etat', 'couleur', 'co2_economise_kg', 'images',
    ];

    protected function casts(): array
    {
        return [
            'prix'             => 'decimal:2',
            'prix_estime_ia'   => 'decimal:2',
            'co2_economise_kg' => 'decimal:3',
            'stock'            => 'integer',
            'images'           => 'array',
        ];
    }

    // Relations
    public function user(): BelongsTo         { return $this->belongsTo(User::class); }
    public function donVetement(): BelongsTo  { return $this->belongsTo(DonVetement::class, 'don_vetement_id'); }
    public function lignesCommande(): HasMany  { return $this->hasMany(LigneCommande::class); }
    public function panierLignes(): HasMany    { return $this->hasMany(PanierLigne::class); }
    public function avis(): HasMany            { return $this->hasMany(Avis::class); }
    public function favoris(): HasMany         { return $this->hasMany(Favori::class); }

    /**
     * Vérifie si cet article est dans les favoris d'un utilisateur donné.
     */
    public function isFavoriDe(?int $userId): bool
    {
        if (!$userId) return false;
        return $this->favoris()->where('user_id', $userId)->exists();
    }

    // Query Scopes
    public function scopeRecherche(Builder $q, string $terme): Builder
    {
        return $q->where(fn($s) => $s->where('titre','like',"%{$terme}%")->orWhere('description','like',"%{$terme}%"));
    }
    public function scopeCategorie(Builder $q, string $v): Builder   { return $q->where('categorie', $v); }
    public function scopeTaille(Builder $q, string $v): Builder      { return $q->where('taille', $v); }
    public function scopeMatiere(Builder $q, string $v): Builder     { return $q->where('matiere', $v); }
    public function scopeEtat(Builder $q, string $v): Builder        { return $q->where('etat', $v); }
    public function scopeCouleur(Builder $q, string $v): Builder     { return $q->where('couleur', $v); }
    public function scopePrixEntre(Builder $q, ?float $min, ?float $max): Builder
    {
        if ($min !== null) $q->where('prix', '>=', $min);
        if ($max !== null) $q->where('prix', '<=', $max);
        return $q;
    }
    public function scopeDonsGratuits(Builder $q): Builder  { return $q->where('prix', 0); }
    public function scopeVendeurRole(Builder $q, string $role): Builder
    {
        return $q->whereHas('user', fn($u) => $u->whereHas('roles', fn($r) => $r->where('name', $role)));
    }
    public function scopeEnStock(Builder $q): Builder   { return $q->where('statut', self::STATUT_DISPONIBLE)->where('stock', '>', 0); }
    public function scopeDisponible(Builder $q): Builder { return $q->where('statut', self::STATUT_DISPONIBLE)->where('stock', '>', 0); }
    
    /**
     * Filtrer les articles ayant au moins une note moyenne donnée
     */
    public function scopeNoteMin(Builder $q, float $noteMin): Builder
    {
        $min = (float) $noteMin;
        return $q->whereIn('articles.id', function ($sub) use ($min) {
            $sub->select('article_id')
                ->from('avis')
                ->groupBy('article_id')
                ->havingRaw("AVG(note_globale) >= {$min}");
        });
    }

    /**
     * Trier par note moyenne décroissante
     */
    public function scopeMieuxNotes(Builder $q): Builder
    {
        return $q->withAvg('avis', 'note_globale')
                 ->orderByDesc('avis_avg_note_globale');
    }

    // Helpers
    public function isDisponible(): bool { return $this->statut === self::STATUT_DISPONIBLE && $this->stock > 0; }

    /**
     * Note moyenne globale de l'article (1 décimale, ex: 4.8)
     */
    public function noteMoyenne(): float
    {
        if (isset($this->avis_avg_note_globale)) {
            return round((float) $this->avis_avg_note_globale, 1);
        }
        $avg = $this->avis()->avg('note_globale');
        return $avg !== null ? round((float) $avg, 1) : 0.0;
    }

    /**
     * Nombre d'avis reçus
     */
    public function nbAvis(): int
    {
        if (isset($this->avis_count)) {
            return (int) $this->avis_count;
        }
        return $this->avis()->count();
    }

    /**
     * Détail des notes moyennes par critère
     */
    public function noteMoyenneParCritere(): array
    {
        return [
            'livraison' => round((float) ($this->avis()->avg('note_livraison') ?? 0), 1),
            'article'   => round((float) ($this->avis()->avg('note_article') ?? 0), 1),
            'clarte'    => round((float) ($this->avis()->avg('note_clarte') ?? 0), 1),
            'service'   => round((float) ($this->avis()->avg('note_service') ?? 0), 1),
        ];
    }

    public function getFirstImageUrl(): ?string
    {
        if (!empty($this->images) && is_array($this->images) && count($this->images) > 0) {
            $path = $this->images[0];
            return str_starts_with($path, 'http') ? $path : Storage::url($path);
        }
        return null;
    }

    public function getImageUrls(): array
    {
        if (empty($this->images) || !is_array($this->images)) return [];
        return array_map(fn($p) => str_starts_with($p, 'http') ? $p : Storage::url($p), $this->images);
    }
}
