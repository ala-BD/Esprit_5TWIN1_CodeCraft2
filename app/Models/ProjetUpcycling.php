<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjetUpcycling extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'atelier_id',
        'don_vetement_id',
        'type_vetement',
        'matiere',
        'etat',
        'description',
        'budget_max',
        'produit_final',
        'categorie_produit',
        'idee_generee_ia',
        'source_ia',
        'statut',
        'date_debut',
        'date_fin',
        'note_client',
        'commentaire_client',
    ];

    protected function casts(): array
    {
        return [
            'budget_max'      => 'float',
            'idee_generee_ia' => 'array',
            'date_debut'      => 'date',
            'date_fin'        => 'date',
            'note_client'     => 'integer',
        ];
    }

    /*
    |------------------------------------------------------------------
    | Constantes statut — l'ordre sert au suivi par étapes
    |------------------------------------------------------------------
    */
    const STATUT_DEMANDE        = 'DEMANDE';
    const STATUT_ATELIER_CHOISI = 'ATELIER_CHOISI';
    const STATUT_DEVIS_ACCEPTE  = 'DEVIS_ACCEPTE';
    const STATUT_CONCEPTION     = 'CONCEPTION';
    const STATUT_CONFECTION     = 'CONFECTION';
    const STATUT_FINITION       = 'FINITION';
    const STATUT_TERMINE        = 'TERMINE';
    const STATUT_ANNULE         = 'ANNULE';

    const ETAPES = [
        'DEMANDE'        => ['label' => 'Demande',         'icon' => 'fa-paper-plane'],
        'ATELIER_CHOISI' => ['label' => 'Atelier choisi',  'icon' => 'fa-store'],
        'DEVIS_ACCEPTE'  => ['label' => 'Devis accepté',   'icon' => 'fa-file-signature'],
        'CONCEPTION'     => ['label' => 'Conception',      'icon' => 'fa-pencil-ruler'],
        'CONFECTION'     => ['label' => 'Confection',      'icon' => 'fa-cut'],
        'FINITION'       => ['label' => 'Finition',        'icon' => 'fa-magic'],
        'TERMINE'        => ['label' => 'Terminé',         'icon' => 'fa-check-double'],
    ];

    /** Étapes que l'atelier fait avancer lui-même */
    const ETAPES_ATELIER = ['DEVIS_ACCEPTE', 'CONCEPTION', 'CONFECTION', 'FINITION'];

    /** Statuts comptés dans la charge de travail d'un atelier */
    const STATUTS_EN_COURS = ['DEVIS_ACCEPTE', 'CONCEPTION', 'CONFECTION', 'FINITION'];

    const ETATS = [
        'BON'   => 'Bon état',
        'USE'   => 'Usé',
        'ABIME' => 'Abîmé / taché',
    ];

    /*
    |------------------------------------------------------------------
    | Relations
    |------------------------------------------------------------------
    */

    /** Client qui demande la transformation */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /** Atelier qui réalise le projet (choisi après matching) */
    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    /** Don d'origine (optionnel) — un don peut être transformé en projet */
    public function donVetement(): BelongsTo
    {
        return $this->belongsTo(DonVetement::class);
    }

    /** Un projet reçoit plusieurs devis */
    public function devis(): HasMany
    {
        return $this->hasMany(Devis::class)->latest('id');
    }

    /*
    |------------------------------------------------------------------
    | Helpers
    |------------------------------------------------------------------
    */

    public function getStatutBadgeAttribute(): array
    {
        return match($this->statut) {
            self::STATUT_DEMANDE        => ['label' => 'Demande',        'class' => 'bg-yellow-100 text-yellow-700'],
            self::STATUT_ATELIER_CHOISI => ['label' => 'Atelier choisi', 'class' => 'bg-sky-100 text-sky-700'],
            self::STATUT_DEVIS_ACCEPTE  => ['label' => 'Devis accepté',  'class' => 'bg-indigo-100 text-indigo-700'],
            self::STATUT_CONCEPTION,
            self::STATUT_CONFECTION,
            self::STATUT_FINITION       => ['label' => self::ETAPES[$this->statut]['label'], 'class' => 'bg-blue-100 text-blue-700'],
            self::STATUT_TERMINE        => ['label' => 'Terminé',        'class' => 'bg-green-100 text-green-700'],
            self::STATUT_ANNULE         => ['label' => 'Annulé',         'class' => 'bg-gray-200 text-gray-600'],
            default                     => ['label' => $this->statut,    'class' => 'bg-gray-100 text-gray-700'],
        };
    }

    /** Position de l'étape courante (0 à 6) */
    public function getIndexEtapeAttribute(): int
    {
        $index = array_search($this->statut, array_keys(self::ETAPES), true);
        return $index === false ? 0 : $index;
    }

    /** Progression du projet en % */
    public function getProgressionAttribute(): int
    {
        if ($this->statut === self::STATUT_ANNULE) return 0;
        return (int) round($this->index_etape / (count(self::ETAPES) - 1) * 100);
    }

    /** Étape suivante que l'atelier peut valider, ou null */
    public function getEtapeSuivanteAttribute(): ?string
    {
        if (!in_array($this->statut, self::ETAPES_ATELIER, true)) return null;
        return array_keys(self::ETAPES)[$this->index_etape + 1] ?? null;
    }

    /** Devis en attente de réponse du client */
    public function getDevisEnAttenteAttribute(): ?Devis
    {
        return $this->devis->firstWhere('statut', Devis::STATUT_EN_ATTENTE);
    }

    /** Devis retenu */
    public function getDevisAccepteAttribute(): ?Devis
    {
        return $this->devis->firstWhere('statut', Devis::STATUT_ACCEPTE);
    }

    /** Le client peut encore modifier sa demande */
    public function estModifiable(): bool
    {
        return in_array($this->statut, [self::STATUT_DEMANDE, self::STATUT_ATELIER_CHOISI], true);
    }

    /** Le client peut encore annuler (avant le début des travaux) */
    public function estAnnulable(): bool
    {
        return in_array($this->statut, [self::STATUT_DEMANDE, self::STATUT_ATELIER_CHOISI, self::STATUT_DEVIS_ACCEPTE], true);
    }
}
