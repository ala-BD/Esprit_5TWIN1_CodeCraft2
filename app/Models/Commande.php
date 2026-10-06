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
 * @property \Carbon\Carbon|null $date_livraison_estimee
 * @property \Carbon\Carbon $date_commande
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
    const STATUT_EN_ATTENTE    = 'EN_ATTENTE';
    const STATUT_CONFIRMEE     = 'CONFIRMEE';
    const STATUT_EN_PREPARATION = 'EN_PREPARATION';
    const STATUT_EXPEDIEE      = 'EXPEDIEE';
    const STATUT_LIVREE        = 'LIVREE';
    const STATUT_ANNULEE       = 'ANNULEE';

    const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_CONFIRMEE,
        self::STATUT_EN_PREPARATION,
        self::STATUT_EXPEDIEE,
        self::STATUT_LIVREE,
        self::STATUT_ANNULEE,
    ];

    // Statuts that allow order modification
    const STATUTS_MODIFIABLES = [
        self::STATUT_EN_ATTENTE,
    ];

    // Statuts that allow cancellation
    const STATUTS_ANNULABLES = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_CONFIRMEE,
        self::STATUT_EN_PREPARATION,
    ];

    // -------------------------------------------------------
    // Modes de paiement
    // -------------------------------------------------------
    const MODES_PAIEMENT = [
        'CARTE'           => 'Carte bancaire',
        'VIREMENT'        => 'Virement bancaire',
        'A_LA_LIVRAISON'  => 'Paiement à la livraison',
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

    /** Whether the order can still be modified */
    public function estModifiable(): bool
    {
        return in_array($this->statut, self::STATUTS_MODIFIABLES);
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
            self::STATUT_EN_ATTENTE     => 'En attente',
            self::STATUT_CONFIRMEE      => 'Confirmée',
            self::STATUT_EN_PREPARATION => 'En préparation',
            self::STATUT_EXPEDIEE       => 'Expédiée',
            self::STATUT_LIVREE         => 'Livrée',
            self::STATUT_ANNULEE        => 'Annulée',
            default                     => $this->statut,
        };
    }

    /** CSS color class for the current status badge */
    public function statutColor(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE     => 'amber',
            self::STATUT_CONFIRMEE      => 'blue',
            self::STATUT_EN_PREPARATION => 'indigo',
            self::STATUT_EXPEDIEE       => 'teal',
            self::STATUT_LIVREE         => 'green',
            self::STATUT_ANNULEE        => 'red',
            default                     => 'slate',
        };
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
