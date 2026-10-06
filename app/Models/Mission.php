<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mission extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournee_id',
        'don_vetement_id',
        'commande_id',
        'type',
        'adresse',
        'ordre',
        'heure_prevue',
        'statut',
        'preuve_livraison',
    ];

    /*
    |------------------------------------------------------------------
    | Constantes type / statut
    |------------------------------------------------------------------
    */
    const TYPE_COLLECTE  = 'COLLECTE';
    const TYPE_LIVRAISON = 'LIVRAISON';

    const TYPES = [
        self::TYPE_COLLECTE  => 'Collecte',
        self::TYPE_LIVRAISON => 'Livraison',
    ];

    const STATUT_A_FAIRE  = 'A_FAIRE';
    const STATUT_EN_COURS = 'EN_COURS';
    const STATUT_TERMINEE = 'TERMINEE';
    const STATUT_ECHOUEE  = 'ECHOUEE';

    /** Statut => [libellé, fond, texte] */
    const STATUTS = [
        self::STATUT_A_FAIRE  => ['label' => 'À faire',  'bg' => '#e2ecfb', 'text' => '#1d4fa3'],
        self::STATUT_EN_COURS => ['label' => 'En cours', 'bg' => '#faefd2', 'text' => '#7a5200'],
        self::STATUT_TERMINEE => ['label' => 'Terminée', 'bg' => '#e1f3e6', 'text' => '#17663a'],
        self::STATUT_ECHOUEE  => ['label' => 'Échouée',  'bg' => '#fde4e1', 'text' => '#a3261c'],
    ];

    /*
    |------------------------------------------------------------------
    | Relations
    |------------------------------------------------------------------
    */

    /** Tournée à laquelle appartient la mission */
    public function tournee(): BelongsTo
    {
        return $this->belongsTo(Tournee::class);
    }

    /** Don collecté (optionnel) */
    public function donVetement(): BelongsTo
    {
        return $this->belongsTo(DonVetement::class);
    }

    /*
    |------------------------------------------------------------------
    | Helpers
    |------------------------------------------------------------------
    */

    /** Retourne le badge couleur selon le statut */
    public function getStatutBadgeAttribute(): array
    {
        return self::STATUTS[$this->statut] ?? ['label' => $this->statut, 'bg' => '#eceef3', 'text' => '#3a4558'];
    }

    /** Libellé du type */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** Heure prévue au format HH:MM (la base peut renvoyer HH:MM:SS) */
    public function getHeureAttribute(): string
    {
        return substr((string) $this->heure_prevue, 0, 5);
    }

    /** Libellés des statuts, pour les listes déroulantes */
    public static function statutOptions(): array
    {
        return array_map(fn (array $statut) => $statut['label'], self::STATUTS);
    }
}
