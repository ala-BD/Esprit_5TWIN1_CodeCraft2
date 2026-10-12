<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property int         $user_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PanierLigne> $lignes
 */
class Panier extends Model
{
    protected $fillable = ['user_id'];

    // -------------------------------------------------------
    // Relations
    // -------------------------------------------------------

    /** Le panier appartient à un utilisateur. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Le panier contient plusieurs lignes. */
    public function lignes(): HasMany
    {
        return $this->hasMany(PanierLigne::class);
    }

    // -------------------------------------------------------
    // Calculs — toujours faits côté serveur
    // -------------------------------------------------------

    /**
     * Nombre total d'articles dans le panier (somme des quantités).
     */
    public function nombreArticles(): int
    {
        return (int) $this->lignes->sum('quantite');
    }

    /**
     * Indique si l'utilisateur bénéficie d'une remise de 10 %.
     * Collecteur, Recycleur et Atelier uniquement.
     */
    private function userGetsRemise(): bool
    {
        return $this->user ? in_array($this->user->role, Commande::ROLES_AVEC_REMISE) : false;
    }

    /**
     * Calcule le sous-total (prix × quantité, sans remise).
     */
    public function sousTotal(): float
    {
        $total = 0.0;
        foreach ($this->lignes as $ligne) {
            if ($ligne->article) {
                $total += (float) $ligne->article->prix * $ligne->quantite;
            }
        }
        return round($total, 2);
    }

    /**
     * Calcule la remise applicable.
     * La remise de 10 % ne s'applique qu'aux articles vendus par un Atelier
     * et uniquement pour les rôles Collecteur / Recycleur / Atelier.
     */
    public function remise(): float
    {
        if (! $this->userGetsRemise()) {
            return 0.0;
        }

        $remise = 0.0;
        foreach ($this->lignes as $ligne) {
            if ($ligne->article && $ligne->article->user && $ligne->article->user->role === User::ROLE_ATELIER) {
                $remise += (float) $ligne->article->prix * $ligne->quantite * Commande::TAUX_REMISE;
            }
        }
        return round($remise, 2);
    }

    /**
     * Frais de livraison standards (DT).
     * Gratuit si le panier est vide.
     */
    public function fraisLivraison(): float
    {
        return $this->lignes->isNotEmpty() ? Commande::FRAIS_LIVRAISON : 0.0;
    }

    /**
     * Total final = sous-total − remise + frais de livraison.
     */
    public function total(): float
    {
        return round($this->sousTotal() - $this->remise() + $this->fraisLivraison(), 2);
    }

    /**
     * Retourne le résumé complet des calculs sous forme de tableau.
     * Pratique pour les réponses JSON et la vue.
     */
    public function resume(): array
    {
        return [
            'sous_total'      => $this->sousTotal(),
            'remise'          => $this->remise(),
            'frais_livraison' => $this->fraisLivraison(),
            'total'           => $this->total(),
            'nb_articles'     => $this->nombreArticles(),
        ];
    }

    // -------------------------------------------------------
    // Helper : synchronisation du panier avant affichage
    // -------------------------------------------------------

    /**
     * Parcourt les lignes et détecte/corrige les incohérences :
     * — article supprimé     → ligne supprimée
     * — article non disponible ou stock épuisé → ligne supprimée, avertissement
     * — quantité > stock disponible → quantité réduite, avertissement
     * — prix changé              → avertissement (prix stocké dans l'article, toujours frais)
     *
     * Retourne un tableau de messages d'avertissement à afficher à l'utilisateur.
     *
     * @return string[]
     */
    public function synchroniser(): array
    {
        $avertissements = [];

        foreach ($this->lignes as $ligne) {
            // Article supprimé
            if (! $ligne->article) {
                $avertissements[] = "Un article de votre panier n'existe plus et a été retiré.";
                $ligne->delete();
                continue;
            }

            $article = $ligne->article;

            // Article non disponible ou rupture de stock
            if (! $article->isDisponible()) {
                $avertissements[] = "« {$article->titre} » n'est plus disponible et a été retiré de votre panier.";
                $ligne->delete();
                continue;
            }

            // Quantité supérieure au stock actuel
            if ($ligne->quantite > $article->stock) {
                $ancienne = $ligne->quantite;
                $ligne->update(['quantite' => $article->stock]);
                $avertissements[] = "La quantité de « {$article->titre} » a été ajustée de {$ancienne} à {$article->stock} (stock disponible).";
            }
        }

        // Recharger les lignes après modifications
        $this->load(['lignes.article.user']);

        return $avertissements;
    }
}
