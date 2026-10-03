<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtapeTraitement extends Model
{
    use HasFactory;

    protected $fillable = [
        'lot_textile_id',
        'type',
        'date_debut',
        'date_fin',
        'resultat',
        'poids_sortant_kg',
    ];

    protected function casts(): array
    {
        return [
            'date_debut'       => 'datetime',
            'date_fin'         => 'datetime',
            'poids_sortant_kg' => 'float',
        ];
    }

    /*
    |------------------------------------------------------------------
    | Ordre des étapes (pour affichage progressif)
    |------------------------------------------------------------------
    */
    const ORDRE = [
        'RECEPTION'        => 1,
        'TRI'              => 2,
        'NETTOYAGE'        => 3,
        'TRAITEMENT'       => 4,
        'CONTROLE_QUALITE' => 5,
        'EXPEDITION'       => 6,
    ];

    const LABELS = [
        'RECEPTION'        => 'Réception',
        'TRI'              => 'Tri',
        'NETTOYAGE'        => 'Nettoyage',
        'TRAITEMENT'       => 'Traitement',
        'CONTROLE_QUALITE' => 'Contrôle qualité',
        'EXPEDITION'       => 'Expédition',
    ];

    const ICONS = [
        'RECEPTION'        => 'fa-box-open',
        'TRI'              => 'fa-sort-amount-down',
        'NETTOYAGE'        => 'fa-soap',
        'TRAITEMENT'       => 'fa-cogs',
        'CONTROLE_QUALITE' => 'fa-clipboard-check',
        'EXPEDITION'       => 'fa-truck',
    ];

    /*
    |------------------------------------------------------------------
    | Relations
    |------------------------------------------------------------------
    */

    public function lotTextile(): BelongsTo
    {
        return $this->belongsTo(LotTextile::class);
    }

    /*
    |------------------------------------------------------------------
    | Helpers
    |------------------------------------------------------------------
    */

    /** Étape terminée si date_fin renseignée */
    public function getEstTermineeAttribute(): bool
    {
        return $this->date_fin !== null;
    }

    /** Durée en heures */
    public function getDureeHeuresAttribute(): ?float
    {
        if (!$this->date_fin) return null;
        return round($this->date_debut->diffInMinutes($this->date_fin) / 60, 1);
    }

    /** Label lisible */
    public function getLabelAttribute(): string
    {
        return self::LABELS[$this->type] ?? $this->type;
    }

    /** Icône */
    public function getIconeAttribute(): string
    {
        return self::ICONS[$this->type] ?? 'fa-circle';
    }
}
