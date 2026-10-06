<?php

namespace App\Services\Upcycling;

use App\Models\Atelier;
use App\Models\ProjetUpcycling;
use Illuminate\Support\Collection;

/**
 * Matching atelier : classe les ateliers actifs pour un projet donné.
 *
 * Score sur 100 :
 *  - Spécialité correspondant à la catégorie du produit  : 40 pts
 *  - Note moyenne des clients (sur 5)                     : 25 pts
 *  - Tarif horaire (le moins cher obtient le maximum)     : 20 pts
 *  - Disponibilité (moins de projets en cours = mieux)    : 15 pts
 */
class MatchingAtelierService
{
    const POIDS_SPECIALITE     = 40;
    const POIDS_NOTE           = 25;
    const POIDS_TARIF          = 20;
    const POIDS_DISPONIBILITE  = 15;

    /** Note neutre attribuée à un atelier encore jamais noté */
    const NOTE_PAR_DEFAUT = 3.0;

    /** Au-delà de ce nombre de projets en cours, l'atelier n'a plus de points de disponibilité */
    const CHARGE_MAX = 5;

    /**
     * @return Collection<int, array{atelier: Atelier, score: int, details: array<string, int>}>
     */
    public function classer(ProjetUpcycling $projet): Collection
    {
        $ateliers = Atelier::where('actif', true)
            ->withCount(['projetUpcyclings as charge_en_cours' => fn ($q) => $q->whereIn('statut', ProjetUpcycling::STATUTS_EN_COURS)])
            ->get();

        if ($ateliers->isEmpty()) {
            return collect();
        }

        $tarifMin = max(1, $ateliers->min('tarif_horaire'));

        return $ateliers->map(function (Atelier $atelier) use ($projet, $tarifMin) {
            $note = $atelier->note_moyenne > 0 ? $atelier->note_moyenne : self::NOTE_PAR_DEFAUT;

            $details = [
                'specialite'     => $projet->categorie_produit && $atelier->specialite === $projet->categorie_produit
                                        ? self::POIDS_SPECIALITE : 0,
                'note'           => (int) round($note / 5 * self::POIDS_NOTE),
                'tarif'          => (int) round($tarifMin / max(1, $atelier->tarif_horaire) * self::POIDS_TARIF),
                'disponibilite'  => (int) round(max(0, 1 - $atelier->charge_en_cours / self::CHARGE_MAX) * self::POIDS_DISPONIBILITE),
            ];

            return [
                'atelier' => $atelier,
                'score'   => array_sum($details),
                'details' => $details,
            ];
        })->sortByDesc('score')->values();
    }
}
