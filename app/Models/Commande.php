<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $adresse_id
 * @property string|null $adresse_snapshot
 * @property string $numero
 * @property string $statut
 * @property float $montant_sous_total
 * @property float $remise
 * @property float $frais_livraison
 * @property float $montant_total
 * @property string $mode_paiement
 * @property string|null $stripe_session_id
 * @property string|null $stripe_payment_intent
 * @property string $statut_paiement
 * @property Carbon|null $date_paiement
 * @property Carbon|null $date_livraison_estimee
 * @property Carbon $date_commande
 * @property array|null $historique_statuts
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Adresse|null $adresse
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LigneCommande> $lignes
 */
class Commande extends Model
{
    use HasFactory;

    // -------------------------------------------------------
    // Statuts
    // -------------------------------------------------------
    const STATUT_EN_ATTENTE_PAIEMENT = 'EN_ATTENTE_PAIEMENT';
    const STATUT_EN_ATTENTE          = 'EN_ATTENTE';
    const STATUT_CONFIRMEE           = 'CONFIRMEE';
    const STATUT_EN_PREPARATION      = 'EN_PREPARATION';
    const STATUT_EXPEDIEE            = 'EXPEDIEE';
    const STATUT_LIVREE              = 'LIVREE';
    const STATUT_ANNULEE             = 'ANNULEE';

    const STATUTS = [
        self::STATUT_EN_ATTENTE_PAIEMENT,
        self::STATUT_EN_ATTENTE,
        self::STATUT_CONFIRMEE,
        self::STATUT_EN_PREPARATION,
        self::STATUT_EXPEDIEE,
        self::STATUT_LIVREE,
        self::STATUT_ANNULEE,
    ];

    // Statuts that allow order modification
    const STATUTS_MODIFIABLES = [
        self::STATUT_EN_ATTENTE_PAIEMENT,
        self::STATUT_EN_ATTENTE,
    ];

    // Statuts that allow cancellation
    const STATUTS_ANNULABLES = [
        self::STATUT_EN_ATTENTE_PAIEMENT,
        self::STATUT_EN_ATTENTE,
        self::STATUT_CONFIRMEE,
        self::STATUT_EN_PREPARATION,
    ];

    // -------------------------------------------------------
    // Statuts de paiement (Stripe)
    // -------------------------------------------------------
    const STATUT_PAIEMENT_EN_ATTENTE = 'EN_ATTENTE';
    const STATUT_PAIEMENT_PAYE       = 'PAYE';
    const STATUT_PAIEMENT_ECHOUE     = 'ECHOUE';
    const STATUT_PAIEMENT_REMBOURSE  = 'REMBOURSE';

    // -------------------------------------------------------
    // Modes de paiement
    // -------------------------------------------------------
    const MODES_PAIEMENT = [
        'STRIPE'          => 'Carte bancaire (Stripe)',
        'CARTE'           => 'Carte bancaire',
        'VIREMENT'        => 'Virement bancaire',
        'A_LA_LIVRAISON'  => 'Paiement à la livraison',
        'GRATUIT'         => 'Don gratuit (aucun paiement)',
    ];

    // Frais de livraison standard (DT)
    const FRAIS_LIVRAISON = 7.00;

    // Taux de remise pour Collecteur / Recycleur / Atelier
    const TAUX_REMISE = 0.10;

    // Rôles bénéficiant d'une remise
    const ROLES_AVEC_REMISE = [
        User::ROLE_COLLECTEUR,
        User::ROLE_RECYCLEUR,
        User::ROLE_ATELIER,
    ];

    protected $fillable = [
        'user_id',
        'adresse_id',
        'adresse_snapshot',
        'numero',
        'statut',
        'montant_sous_total',
        'remise',
        'frais_livraison',
        'montant_total',
        'mode_paiement',
        'stripe_session_id',
        'stripe_payment_intent',
        'statut_paiement',
        'date_paiement',
        'date_livraison_estimee',
        'date_commande',
        'historique_statuts',
    ];

    protected function casts(): array
    {
        return [
            'montant_sous_total'     => 'decimal:2',
            'remise'                 => 'decimal:2',
            'frais_livraison'        => 'decimal:2',
            'montant_total'          => 'decimal:2',
            'date_livraison_estimee' => 'date',
            'date_commande'          => 'datetime',
            'date_paiement'          => 'datetime',
            'historique_statuts'     => 'array',
        ];
    }

    // -------------------------------------------------------
    // Relations
    // -------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adresse(): BelongsTo
    {
        return $this->belongsTo(Adresse::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneCommande::class);
    }

    public function avis(): HasMany
    {
        return $this->hasMany(Avis::class);
    }

    // -------------------------------------------------------
    // Helpers
    // -------------------------------------------------------

    /** Generate a unique order number like TC-2026-000001 */
    public static function genererNumero(): string
    {
        $annee = now()->year;
        $count = static::whereYear('created_at', $annee)->count() + 1;
        return sprintf('TC-%d-%06d', $annee, $count);
    }

    /** Whether the order can still be modified (only EN_ATTENTE_PAIEMENT or EN_ATTENTE) */
    public function estModifiable(): bool
    {
        return in_array($this->statut, self::STATUTS_MODIFIABLES);
    }

    /** Whether the order has been paid via Stripe */
    public function estPaye(): bool
    {
        return $this->statut_paiement === self::STATUT_PAIEMENT_PAYE;
    }

    /** Whether the order is waiting for Stripe payment confirmation */
    public function enAttenteDeStripe(): bool
    {
        return $this->statut === 'EN_ATTENTE_PAIEMENT';
    }

    /** Whether the order can be cancelled */
    public function estAnnulable(): bool
    {
        return in_array($this->statut, self::STATUTS_ANNULABLES);
    }

    /** Whether the order has been shipped (locked for cancel) */
    public function estExpediee(): bool
    {
        return in_array($this->statut, [self::STATUT_EXPEDIEE, self::STATUT_LIVREE]);
    }

    /** Whether the order has been delivered */
    public function estLivree(): bool
    {
        return $this->statut === self::STATUT_LIVREE;
    }

    /** Whether client can confirm delivery (when order is EXPEDIEE) */
    public function peutConfirmerLivraison(): bool
    {
        return $this->statut === self::STATUT_EXPEDIEE;
    }

    /** Check if an article already has a review in this order */
    public function hasAvisForArticle(int $articleId): bool
    {
        return $this->avis()->where('article_id', $articleId)->exists();
    }

    /** Get the review for an article in this order */
    public function getAvisForArticle(int $articleId): ?Avis
    {
        return $this->avis()->where('article_id', $articleId)->first();
    }

    /** Push a new status event with timestamp into the timeline */
    public function pushHistorique(string $statut, ?string $commentaire = null): void
    {
        $historique = $this->historique_statuts ?? [];
        $historique[] = [
            'statut'      => $statut,
            'date'        => now()->toIso8601String(),
            'commentaire' => $commentaire,
        ];
        $this->update(['historique_statuts' => $historique]);
    }

    /** Human-readable label for the current status */
    public function statutLabel(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE_PAIEMENT => 'En attente de paiement',
            self::STATUT_EN_ATTENTE          => 'En attente',
            self::STATUT_CONFIRMEE           => 'Confirmée',
            self::STATUT_EN_PREPARATION      => 'En préparation',
            self::STATUT_EXPEDIEE            => 'Expédiée',
            self::STATUT_LIVREE              => 'Livrée',
            self::STATUT_ANNULEE             => 'Annulée',
            default                          => $this->statut,
        };
    }

    /** CSS color class for the current status badge */
    public function statutColor(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE_PAIEMENT => 'amber',
            self::STATUT_EN_ATTENTE          => 'amber',
            self::STATUT_CONFIRMEE           => 'blue',
            self::STATUT_EN_PREPARATION      => 'indigo',
            self::STATUT_EXPEDIEE            => 'teal',
            self::STATUT_LIVREE              => 'green',
            self::STATUT_ANNULEE             => 'red',
            default                          => 'slate',
        };
    }

    /** Human-readable label for the payment status */
    public function statutPaiementLabel(): string
    {
        return match ($this->statut_paiement) {
            self::STATUT_PAIEMENT_PAYE       => 'Payé',
            self::STATUT_PAIEMENT_EN_ATTENTE => 'En attente de paiement',
            self::STATUT_PAIEMENT_ECHOUE     => 'Paiement échoué',
            self::STATUT_PAIEMENT_REMBOURSE  => 'Remboursé',
            default                          => $this->statut_paiement ?? 'Non payé',
        };
    }

    /** CSS color class for the payment status */
    public function statutPaiementColor(): string
    {
        return match ($this->statut_paiement) {
            self::STATUT_PAIEMENT_PAYE       => 'green',
            self::STATUT_PAIEMENT_EN_ATTENTE => 'amber',
            self::STATUT_PAIEMENT_ECHOUE     => 'red',
            self::STATUT_PAIEMENT_REMBOURSE  => 'purple',
            default                          => 'slate',
        };
    }

    /** Human-readable label for the payment method */
    public function modePaiementLabel(): string
    {
        return self::MODES_PAIEMENT[$this->mode_paiement] ?? $this->mode_paiement;
    }

    /** Ecological impact stats */
    public function impactEcologique(): array
    {
        // Estimated CO2 saved (kg) per second-hand item: ~3.5 kg vs new production
        // Water saved (litres) per item: ~2700 L (equivalent to 1 pair of jeans)
        $nbArticles = $this->lignes->sum('quantite');
        return [
            'co2_kg'        => round($nbArticles * 3.5, 1),
            'eau_litres'    => $nbArticles * 2700,
            'nb_articles'   => $nbArticles,
        ];
    }
}
