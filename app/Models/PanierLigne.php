<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int   $id
 * @property int   $panier_id
 * @property int   $article_id
 * @property int   $quantite
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Panier  $panier
 * @property-read \App\Models\Article $article
 */
class PanierLigne extends Model
{
    protected $fillable = ['panier_id', 'article_id', 'quantite'];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
        ];
    }

    // -------------------------------------------------------
    // Relations
    // -------------------------------------------------------

    /** La ligne appartient à un panier. */
    public function panier(): BelongsTo
    {
        return $this->belongsTo(Panier::class);
    }

    /** La ligne référence un article. */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /**
     * Sous-total de la ligne (prix × quantité, sans remise).
     */
    public function sousTotal(): float
    {
        return $this->article ? round((float) $this->article->prix * $this->quantite, 2) : 0.0;
    }

    /**
     * Indique si une remise s'applique à cette ligne.
     * Remise de 10 % pour Collecteur/Recycleur/Atelier sur les articles d'Atelier.
     */
    public function aRemise(): bool
    {
        if (! $this->article || ! $this->panier?->user || ! $this->article->user) {
            return false;
        }

        return in_array($this->panier->user->role, Commande::ROLES_AVEC_REMISE)
            && $this->article->user->role === User::ROLE_ATELIER;
    }

    /**
     * Montant de la remise pour cette ligne.
     */
    public function montantRemise(): float
    {
        return $this->aRemise()
            ? round((float) $this->article->prix * $this->quantite * Commande::TAUX_REMISE, 2)
            : 0.0;
    }

    /**
     * Total de la ligne après remise éventuelle.
     */
    public function totalLigne(): float
    {
        return round($this->sousTotal() - $this->montantRemise(), 2);
    }
}
