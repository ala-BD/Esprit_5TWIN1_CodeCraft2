<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle Estimation : Historique et résultats des calculs hybrides d'aide à la décision.
 * Stocke les coûts de recyclage, d'upcycling, fourchette de prix, marge nette, niveau d'alerte,
 * et explication textuelle (générée par IA ou par règles métier).
 */
class Estimation extends Model
{
    use HasFactory;

    public const ALERTE_VERT   = 'vert';
    public const ALERTE_ORANGE = 'orange';
    public const ALERTE_ROUGE  = 'rouge';

    public const SOURCE_IA     = 'ia';
    public const SOURCE_REGLES = 'regles';

    public const CONFIANCE_FAIBLE = 'faible';
    public const CONFIANCE_MOYEN  = 'moyen';
    public const CONFIANCE_ELEVE  = 'eleve';

    protected $fillable = [
        'article_id',
        'user_id',
        'cout_recyclage',
        'cout_upcycling',
        'prix_min',
        'prix_conseille',
        'prix_max',
        'marge_nette',
        'marge_pourcent',
        'niveau_alerte',
        'confiance',
        'explication',
        'source',
        'details_calcul',
    ];

    protected function casts(): array
    {
        return [
            'cout_recyclage' => 'decimal:2',
            'cout_upcycling' => 'decimal:2',
            'prix_min'       => 'decimal:2',
            'prix_conseille' => 'decimal:2',
            'prix_max'       => 'decimal:2',
            'marge_nette'    => 'decimal:2',
            'marge_pourcent' => 'decimal:2',
            'details_calcul' => 'array',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Badge d'alerte visuel avec classes Tailwind et icône.
     */
    public function getAlerteBadgeAttribute(): array
    {
        return match ($this->niveau_alerte) {
            self::ALERTE_VERT => [
                'label'   => 'Article gagnant',
                'bg'      => 'bg-emerald-100',
                'text'    => 'text-emerald-800',
                'border'  => 'border-emerald-300',
                'dot'     => 'bg-emerald-500',
                'icon'    => 'fa-circle-check',
                'title'   => 'Rentabilité optimale (≥ 20%)',
            ],
            self::ALERTE_ORANGE => [
                'label'   => 'À surveiller',
                'bg'      => 'bg-amber-100',
                'text'    => 'text-amber-800',
                'border'  => 'border-amber-300',
                'dot'     => 'bg-amber-500',
                'icon'    => 'fa-triangle-exclamation',
                'title'   => 'Marge positive mais faible',
            ],
            self::ALERTE_ROUGE => [
                'label'   => 'Article perdant',
                'bg'      => 'bg-rose-100',
                'text'    => 'text-rose-800',
                'border'  => 'border-rose-300',
                'dot'     => 'bg-rose-500',
                'icon'    => 'fa-circle-xmark',
                'title'   => 'Marge négative ou vente à perte',
            ],
            default => [
                'label'   => 'Indéterminé',
                'bg'      => 'bg-slate-100',
                'text'    => 'text-slate-700',
                'border'  => 'border-slate-300',
                'dot'     => 'bg-slate-400',
                'icon'    => 'fa-circle-question',
                'title'   => 'Non calculé',
            ],
        };
    }

    /**
     * Badge de source (IA vs Règles).
     */
    public function getSourceBadgeAttribute(): array
    {
        $details = is_array($this->details_calcul) ? $this->details_calcul : [];
        $depuisCache = !empty($details['depuis_cache']);
        $raisonLabel = $details['raison_label'] ?? null;

        if ($this->source === self::SOURCE_IA) {
            return [
                'label' => "✨ Estimation par l'IA (Gemini)" . ($depuisCache ? ' (cache)' : ''),
                'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'icon'  => 'fa-sparkles',
            ];
        }

        $label = "⚙️ Règles métier (plan B)" . ($raisonLabel ? " : {$raisonLabel}" : '');
        return [
            'label' => $label,
            'class' => 'bg-slate-100 text-slate-700 border-slate-300',
            'icon'  => 'fa-gears',
        ];
    }
}

