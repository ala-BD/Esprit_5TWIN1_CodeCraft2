<?php

namespace App\Services\Logistique;

use App\Http\Controllers\Admin\LogistiqueController;
use App\Models\Commande;
use App\Models\DonVetement;
use App\Models\Mission;
use App\Models\Tournee;
use App\Models\User;
use App\Notifications\MissionsAffecteesNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Répartit automatiquement les commandes à livrer et les dons à collecter
 * entre les collecteurs, pour une date donnée.
 *
 * Insertion la moins chère, en parallèle : à chaque tour, parmi tous les couples
 * (travail, collecteur), on retient celui qui allonge le moins le trajet du
 * collecteur, en tenant compte de sa charge et du coût d'ouverture d'une tournée.
 * L'ordre définitif de chaque tournée est ensuite calculé par l'optimiseur d'itinéraire.
 */
class AffectationAutomatique
{
    public function __construct(private OptimiseurItineraire $optimiseur)
    {
    }

    /**
     * @return array{
     *   date: Carbon,
     *   plans: Collection,         // un plan par collecteur qui reçoit au moins un travail
     *   non_affectes: Collection,  // travaux impossibles à placer, avec la raison
     *   total: int, km_ajoutes: float, empreinte: string
     * }
     */
    public function proposer(Carbon $date): array
    {
        $date = $date->copy()->startOfDay();
        $depot = $this->optimiseur->depot();
        $max = (int) config('logistique.affectation.arrets_max');

        ['localises' => $travaux, 'non_localises' => $sansPosition] = $this->travaux();

        // État de départ de chaque collecteur : sa tournée du jour (s'il en a une) et ses arrêts restants
        $collecteurs = User::where('role', User::ROLE_COLLECTEUR)->where('actif', true)->orderBy('id')->get()
            ->map(function (User $collecteur) use ($date) {
                $tournee = Tournee::where('user_id', $collecteur->id)->whereDate('date', $date)
                    ->whereIn('statut', [Tournee::STATUT_PLANIFIEE, Tournee::STATUT_EN_COURS])
                    ->withCount('missions')->orderBy('id')->first();

                $route = $tournee
                    ? $tournee->missions()->whereIn('statut', [Mission::STATUT_A_FAIRE, Mission::STATUT_EN_COURS])->get()
                        ->filter->estLocalisee()->map(fn (Mission $m) => $this->point($m->latitude, $m->longitude))->values()->all()
                    : [];

                return [
                    'collecteur' => $collecteur,
                    'tournee'    => $tournee,
                    'arrets'     => $tournee?->missions_count ?? 0,
                    'route'      => $route,          // points dans l'ordre de passage
                    'ajouts'     => [],
                    'km'         => 0.0,
                ];
            })->values()->all();

        $nonAffectes = $sansPosition->map(fn (array $t) => $t + ['raison' => "Adresse sans position sur la carte"]);

        if (!$collecteurs) {
            $nonAffectes = $nonAffectes->concat($travaux->map(fn (array $t) => $t + ['raison' => 'Aucun collecteur actif']));
            $travaux = collect();
        }

        // Insertion la moins chère, un travail à la fois
        $restants = $travaux->values()->all();
        while ($restants) {
            $meilleur = null;

            foreach ($restants as $i => $travail) {
                foreach ($collecteurs as $c => $etat) {
                    if ($etat['arrets'] >= $max) {
                        continue;
                    }

                    [$surcout, $position] = $this->insertion($etat['route'], $travail['point'], $depot);
                    $penalite = $etat['arrets'] * (float) config('logistique.affectation.cout_par_arret_km')
                        + ($etat['tournee'] === null && !$etat['ajouts'] ? (float) config('logistique.affectation.cout_ouverture_km') : 0);

                    $score = $surcout + $penalite;
                    if ($meilleur === null || $score < $meilleur['score'] - 1e-9) {
                        $meilleur = compact('i', 'c', 'score', 'surcout', 'position');
                    }
                }
            }

            // Plus aucune place chez personne
            if ($meilleur === null) {
                $nonAffectes = $nonAffectes->concat(collect($restants)->map(fn (array $t) => $t + ['raison' => "Toutes les tournées sont pleines ({$max} arrêts)"]));
                break;
            }

            $travail = $restants[$meilleur['i']];
            $etat = &$collecteurs[$meilleur['c']];
            array_splice($etat['route'], $meilleur['position'], 0, [$travail['point']]);
            $etat['ajouts'][] = $travail + ['surcout' => round($meilleur['surcout'], 1)];
            $etat['arrets']++;
            $etat['km'] += $meilleur['surcout'];
            unset($etat);

            array_splice($restants, $meilleur['i'], 1);
        }

        $plans = collect($collecteurs)->filter(fn (array $e) => $e['ajouts'])->map(fn (array $e) => [
            'collecteur' => $e['collecteur'],
            'tournee'    => $e['tournee'],
            'nouvelle'   => $e['tournee'] === null,
            'zone'       => $e['tournee']?->zone ?? $this->nomDeZone($e['ajouts']),
            'ajouts'     => collect($e['ajouts']),
            'arrets'     => $e['arrets'],
            'km_ajoutes' => round($e['km'], 1),
        ])->values();

        return [
            'date'         => $date,
            'plans'        => $plans,
            'non_affectes' => $nonAffectes->values(),
            'total'        => $plans->sum(fn ($p) => $p['ajouts']->count()),
            'km_ajoutes'   => round($plans->sum('km_ajoutes'), 1),
            'empreinte'    => $this->empreinte($date, $travaux->concat($sansPosition)),
        ];
    }

    /**
     * Crée les tournées et les missions proposées, optimise chaque tournée touchée
     * et prévient les collecteurs.
     *
     * @return Collection<int, Tournee> les tournées créées ou complétées
     */
    public function appliquer(array $proposition): Collection
    {
        $tournees = DB::transaction(function () use ($proposition) {
            return $proposition['plans']->map(function (array $plan) use ($proposition) {
                $tournee = $plan['tournee'] ?? Tournee::create([
                    'user_id'  => $plan['collecteur']->id,
                    'date'     => $proposition['date'],
                    'zone'     => $plan['zone'],
                    'vehicule' => Tournee::where('user_id', $plan['collecteur']->id)->latest('date')->value('vehicule') ?? 'À préciser',
                    'statut'   => Tournee::STATUT_PLANIFIEE,
                ]);

                $ordre = (int) $tournee->missions()->max('ordre');

                foreach ($plan['ajouts'] as $travail) {
                    $tournee->missions()->create([
                        'type'            => $travail['type'],
                        'commande_id'     => $travail['commande_id'],
                        'don_vetement_id' => $travail['don_vetement_id'],
                        'ordre'           => ++$ordre,
                        'heure_prevue'    => config('logistique.itineraire.heure_depart'),
                        'statut'          => Mission::STATUT_A_FAIRE,
                    ]);
                }

                // Ordre de passage et heures définitifs
                $this->optimiseur->appliquer($tournee, $this->optimiseur->proposer($tournee));

                return $tournee->fresh();
            });
        });

        foreach ($proposition['plans'] as $i => $plan) {
            try {
                $plan['collecteur']->notify(new MissionsAffecteesNotification($tournees[$i], $plan['ajouts']->count()));
            } catch (\Throwable $e) {
                Log::warning('Logistique : notification d\'affectation non envoyée', ['erreur' => $e->getMessage()]);
            }
        }

        return $tournees;
    }

    /*
    |------------------------------------------------------------------
    | Travaux à planifier
    |------------------------------------------------------------------
    */

    /** @return array{localises: Collection, non_localises: Collection} */
    private function travaux(): array
    {
        $aPlanifier = LogistiqueController::aPlanifier();

        $travaux = $aPlanifier['commandes']->map(fn (Commande $c) => [
            'cle'             => 'commande-' . $c->id,
            'type'            => Mission::TYPE_LIVRAISON,
            'commande_id'     => $c->id,
            'don_vetement_id' => null,
            'libelle'         => "Livraison {$c->numero}",
            'personne'        => $c->user?->full_name,
            'adresse'         => $c->adresse?->formatted_address ?? (string) $c->adresse_snapshot,
            'ville'           => $c->adresse?->ville,
            'point'           => $this->pointAdresse($c->adresse?->latitude, $c->adresse?->longitude),
        ])->concat($aPlanifier['dons']->map(fn (DonVetement $d) => [
            'cle'             => 'don-' . $d->id,
            'type'            => Mission::TYPE_COLLECTE,
            'commande_id'     => null,
            'don_vetement_id' => $d->id,
            'libelle'         => "Collecte du don n° {$d->id} ({$d->type})",
            'personne'        => $d->user?->full_name,
            'adresse'         => (string) $d->user?->adresseParDefaut?->formatted_address,
            'ville'           => $d->user?->adresseParDefaut?->ville,
            'point'           => $this->pointAdresse($d->user?->adresseParDefaut?->latitude, $d->user?->adresseParDefaut?->longitude),
        ]))->values();

        return [
            'localises'     => $travaux->filter(fn (array $t) => $t['point'] !== null)->values(),
            'non_localises' => $travaux->filter(fn (array $t) => $t['point'] === null)->values(),
        ];
    }

    /**
     * Surcoût minimal (km) d'insertion d'un point dans une route qui part du dépôt et y revient,
     * et la position où l'insérer.
     *
     * @return array{0: float, 1: int}
     */
    private function insertion(array $route, array $point, array $depot): array
    {
        $chemin = [$depot, ...$route, $depot];
        $meilleur = [PHP_FLOAT_MAX, 0];

        for ($i = 0; $i < count($chemin) - 1; $i++) {
            $surcout = $this->optimiseur->distance($chemin[$i], $point)
                + $this->optimiseur->distance($point, $chemin[$i + 1])
                - $this->optimiseur->distance($chemin[$i], $chemin[$i + 1]);

            if ($surcout < $meilleur[0]) {
                $meilleur = [$surcout, $i];
            }
        }

        return $meilleur;
    }

    /** Nom d'une nouvelle tournée : les deux villes les plus représentées */
    private function nomDeZone(array $ajouts): string
    {
        $villes = collect($ajouts)->pluck('ville')->filter()->countBy()->sortDesc()->keys()->take(2);

        return $villes->isEmpty() ? 'Tournée automatique' : $villes->join(' — ');
    }

    private function empreinte(Carbon $date, Collection $travaux): string
    {
        return md5($date->format('Y-m-d') . '|' . $travaux->pluck('cle')->sort()->join(',')
            . '|' . Tournee::whereDate('date', $date)->withCount('missions')->get()->map(fn ($t) => $t->id . ':' . $t->missions_count)->join(','));
    }

    private function pointAdresse($latitude, $longitude): ?array
    {
        return $latitude !== null && $longitude !== null ? $this->point($latitude, $longitude) : null;
    }

    private function point($latitude, $longitude): array
    {
        return ['latitude' => (float) $latitude, 'longitude' => (float) $longitude];
    }
}
