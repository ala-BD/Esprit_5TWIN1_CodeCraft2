<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $nom
 * @property array $filtres
 * @property \Carbon\Carbon|null $derniere_notification_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Models\User $user
 */
class RechercheSauvegardee extends Model
{
    protected $table = 'recherches_sauvegardees';

    protected $fillable = [
        'user_id',
        'nom',
        'filtres',
        'derniere_notification_at',
    ];

    protected function casts(): array
    {
        return [
            'filtres'                   => 'array',
            'derniere_notification_at'  => 'datetime',
        ];
    }

    // Relations
    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    /**
     * Construit une requête Article à partir des filtres sauvegardés.
     * Réutilise les mêmes scopes que ArticleController@index.
     */
    public function buildArticleQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $f = $this->filtres ?? [];

        $query = Article::query()->where('statut', Article::STATUT_DISPONIBLE);

        if (!empty($f['q']))           $query->recherche($f['q']);
        if (!empty($f['categorie']))   $query->categorie($f['categorie']);
        if (!empty($f['taille']))      $query->taille($f['taille']);
        if (!empty($f['matiere']))     $query->matiere($f['matiere']);
        if (!empty($f['etat']))        $query->etat($f['etat']);
        if (!empty($f['couleur']))     $query->couleur($f['couleur']);
        if (!empty($f['vendeur_role'])) $query->vendeurRole($f['vendeur_role']);
        if (!empty($f['note_min']))    $query->noteMin((float) $f['note_min']);

        $prixMin = isset($f['prix_min']) && $f['prix_min'] !== '' ? (float) $f['prix_min'] : null;
        $prixMax = isset($f['prix_max']) && $f['prix_max'] !== '' ? (float) $f['prix_max'] : null;
        if ($prixMin !== null || $prixMax !== null) {
            $query->prixEntre($prixMin, $prixMax);
        }

        if (!empty($f['gratuits']))  $query->donsGratuits();
        if (!empty($f['en_stock']))  $query->enStock();

        return $query;
    }

    /**
     * Résumé lisible des filtres pour l'affichage dans l'UI.
     */
    public function resumeFiltres(): string
    {
        $f = $this->filtres ?? [];
        $parts = [];

        if (!empty($f['q']))           $parts[] = "«&nbsp;{$f['q']}&nbsp;»";
        if (!empty($f['categorie']))   $parts[] = $f['categorie'];
        if (!empty($f['taille']))      $parts[] = "T: {$f['taille']}";
        if (!empty($f['etat']))        $parts[] = $f['etat'];
        if (!empty($f['couleur']))     $parts[] = $f['couleur'];
        if (!empty($f['matiere']))     $parts[] = $f['matiere'];

        $prixMin = $f['prix_min'] ?? null;
        $prixMax = $f['prix_max'] ?? null;
        if ($prixMin !== '' && $prixMin !== null && $prixMax !== '' && $prixMax !== null) {
            $parts[] = "{$prixMin}–{$prixMax} DT";
        } elseif ($prixMin !== '' && $prixMin !== null) {
            $parts[] = "≥ {$prixMin} DT";
        } elseif ($prixMax !== '' && $prixMax !== null) {
            $parts[] = "≤ {$prixMax} DT";
        }

        if (!empty($f['en_stock']))  $parts[] = 'en stock';
        if (!empty($f['gratuits'])) $parts[] = 'gratuits';

        return implode(' · ', $parts) ?: 'Tous les articles';
    }
}
