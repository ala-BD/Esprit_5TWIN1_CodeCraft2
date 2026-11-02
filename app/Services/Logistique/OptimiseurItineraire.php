<?php

namespace App\Services\Logistique;

use App\Models\Mission;
use App\Models\Tournee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Optimisation de l'ordre de passage d'une tournée.
 *
 * Problème du voyageur de commerce à départ fixé : on construit un premier trajet
 * en allant toujours à l'arrêt le plus proche (plus proche voisin), puis on
 * l'améliore par 2-opt — on inverse des portions du trajet tant que cela le
 * raccourcit, ce qui supprime les croisements. Rapide, déterministe, sans service
 * externe, et très proche de l'optimum pour une tournée de quelques dizaines d'arrêts.
 *
 * Seuls les arrêts encore à faire sont réordonnés ; les arrêts terminés gardent
 * leur place et le trajet repart du dernier endroit où le collecteur est passé.
 */
class OptimiseurItineraire
{
    private const RAYON_TERRE_KM = 6371.0;

    /**
     * @return array{
     *   depart: array{nom: string, latitude: float, longitude: float},
     *   retour: ?array{nom: string, latitude: float, longitude: float},
     *   heure_depart: Carbon,
     *   figees: Collection, etapes: Collection, non_localisees: Collection,
     *   distance_avant: float, distance_apres: float, gain_km: float, gain_pct: int,
     *   duree_minutes: int, empreinte: string, optimisable: bool
     * }
     */
    public function proposer(Tournee $tournee, ?Carbon $maintenant = null): array
    {
        $maintenant ??= now();
        $missions = $tournee->missions()->get();

        $figees    = $missions->whereIn('statut', [Mission::STATUT_TERMINEE, Mission::STATUT_ECHOUEE])->values();
        $restantes = $missions->whereIn('statut', [Mission::STATUT_A_FAIRE, Mission::STATUT_EN_COURS]);
        $localisees    = $restantes->filter->estLocalisee()->values();
        $nonLocalisees = $restantes->reject->estLocalisee()->values();

        $depart = $this->pointDeDepart($figees);
        $retour = config('logistique.depot.retour') ? $this->depot() : null;

        // Mission en cours : le collecteur y est déjà engagé, elle reste en tête
        $enCours = $localisees->where('statut', Mission::STATUT_EN_COURS)->values();
        $libres  = $localisees->where('statut', Mission::STATUT_A_FAIRE)->values();

        $ordre = $enCours->concat($this->ordonner($libres, $enCours->last() ? $this->point($enCours->last()) : $depart, $retour));

        $avant = $this->longueur($depart, $localisees->sortBy('ordre')->values(), $retour);
        $apres = $this->longueur($depart, $ordre, $retour);

        $heureDepart = $this->heureDeDepart($tournee, $figees, $restantes, $maintenant);
        $etapes = $this->horaires($depart, $ordre, $heureDepart);

        return [
            'depart'         => $depart,
            'retour'         => $retour,
            'heure_depart'   => $heureDepart,
            'figees'         => $figees,
            'etapes'         => $etapes,
            'non_localisees' => $nonLocalisees,
            'distance_avant' => round($avant, 1),
            'distance_apres' => round($apres, 1),
            'gain_km'        => round($avant - $apres, 1),
            'gain_pct'       => $avant > 0 ? (int) round(($avant - $apres) / $avant * 100) : 0,
            'duree_minutes'  => $etapes->isEmpty() ? 0 : (int) $heureDepart->diffInMinutes($etapes->last()['fin']),
            'empreinte'      => $this->empreinte($missions),
            'optimisable'    => $localisees->count() >= 1,
        ];
    }

    /**
     * Enregistre la proposition : nouvel ordre et nouvelles heures des arrêts restants,
     * distance de la tournée et résumé de l'itinéraire.
     */
    public function appliquer(Tournee $tournee, array $proposition, ?string $explication = null): void
    {
        DB::transaction(function () use ($tournee, $proposition, $explication) {
            $position = $proposition['figees']->count();

            foreach ($proposition['etapes'] as $etape) {
                $etape['mission']->update(['ordre' => ++$position, 'heure_prevue' => $etape['arrivee']->format('H:i')]);
            }

            // Arrêts sans position : à la fin, dans leur ordre actuel
            foreach ($proposition['non_localisees']->sortBy('ordre') as $mission) {
                $mission->update(['ordre' => ++$position]);
            }

            $tournee->update([
                'distance_km'   => $this->distanceTotale($proposition),
                'itineraire_ia' => json_encode([
                    'methode'        => 'plus proche voisin + 2-opt',
                    'calcule_le'     => now()->toIso8601String(),
                    'distance_avant' => $proposition['distance_avant'],
                    'distance_apres' => $proposition['distance_apres'],
                    'gain_pct'       => $proposition['gain_pct'],
                    'ordre'          => $proposition['etapes']->map(fn ($e) => $e['mission']->id)->all(),
                    'explication'    => $explication,
                ], JSON_UNESCAPED_UNICODE),
            ]);
        });
    }

    /** Distance de la tournée entière : depuis le dépôt, arrêts faits puis restants, retour éventuel */
    private function distanceTotale(array $proposition): float
    {
        $faits = $proposition['figees']->filter->estLocalisee()->values();

        return round(
            $this->longueur($this->depot(), $faits, null)
            + $this->longueur($proposition['depart'], $proposition['etapes']->pluck('mission'), $proposition['retour']),
            1
        );
    }

    /** Signature de l'état de la tournée : détecte une modification entre l'aperçu et l'application */
    public function empreinte(Collection $missions): string
    {
        return md5($missions->sortBy('id')->map(fn (Mission $m) => implode('|', [
            $m->id, $m->statut, $m->latitude, $m->longitude, $m->ordre,
        ]))->join(';'));
    }

    /*
    |------------------------------------------------------------------
    | Algorithme
    |------------------------------------------------------------------
    */

    /** Plus proche voisin depuis $depart, puis amélioration 2-opt */
    private function ordonner(Collection $arrets, array $depart, ?array $retour): Collection
    {
        if ($arrets->count() <= 1) {
            return $arrets->values();
        }

        $points = $arrets->map(fn (Mission $m) => $this->point($m))->values()->all();
        $n = count($points);

        // Matrice des distances, le départ en position $n et le retour en $n + 1
        $tous = [...$points, $depart, $retour ?? $depart];
        $d = [];
        foreach ($tous as $i => $a) {
            foreach ($tous as $j => $b) {
                $d[$i][$j] = $i === $j ? 0.0 : $this->distance($a, $b);
            }
        }
        $sansRetour = $retour === null;

        // 1. Plus proche voisin
        $chemin = [];
        $restants = range(0, $n - 1);
        $courant = $n;
        while ($restants) {
            usort($restants, fn ($x, $y) => $d[$courant][$x] <=> $d[$courant][$y] ?: $x <=> $y);
            $courant = array_shift($restants);
            $chemin[] = $courant;
        }

        // 2. 2-opt : inverser le segment [i..k] si cela raccourcit le trajet
        do {
            $ameliore = false;
            for ($i = 0; $i < $n - 1; $i++) {
                for ($k = $i + 1; $k < $n; $k++) {
                    $a = $i === 0 ? $n : $chemin[$i - 1];                                   // avant le segment (ou le départ)
                    $b = $chemin[$i];
                    $c = $chemin[$k];
                    $e = $k === $n - 1 ? ($sansRetour ? null : $n + 1) : $chemin[$k + 1];   // après le segment (ou le retour)

                    $avant = $d[$a][$b] + ($e === null ? 0 : $d[$c][$e]);
                    $apres = $d[$a][$c] + ($e === null ? 0 : $d[$b][$e]);

                    if ($apres < $avant - 1e-9) {
                        array_splice($chemin, $i, $k - $i + 1, array_reverse(array_slice($chemin, $i, $k - $i + 1)));
                        $ameliore = true;
                    }
                }
            }
        } while ($ameliore);

        return collect($chemin)->map(fn (int $i) => $arrets->values()[$i]);
    }

    /** Longueur d'un trajet : départ → arrêts dans l'ordre → retour éventuel */
    public function longueur(array $depart, Collection $missions, ?array $retour): float
    {
        $total = 0.0;
        $precedent = $depart;

        foreach ($missions as $mission) {
            $point = $this->point($mission);
            $total += $this->distance($precedent, $point);
            $precedent = $point;
        }

        return $retour ? $total + $this->distance($precedent, $retour) : $total;
    }

    /** Heures d'arrivée et de fin estimées pour chaque arrêt */
    private function horaires(array $depart, Collection $ordre, Carbon $heureDepart): Collection
    {
        $vitesse = (float) config('logistique.itineraire.vitesse_kmh');
        $arret   = (int) config('logistique.itineraire.minutes_par_arret');
        $horloge = $heureDepart->copy();
        $precedent = $depart;

        return $ordre->map(function (Mission $mission) use (&$horloge, &$precedent, $vitesse, $arret) {
            $point = $this->point($mission);
            $km = $this->distance($precedent, $point);
            $arrivee = $this->arrondir($horloge->copy()->addMinutes((int) ceil($km / $vitesse * 60)));
            $horloge = $arrivee->copy()->addMinutes($arret);
            $precedent = $point;

            return ['mission' => $mission, 'km' => round($km, 1), 'arrivee' => $arrivee, 'fin' => $horloge->copy()];
        })->values();
    }

    /** Arrondi aux 5 minutes supérieures : un horaire lisible pour le client */
    private function arrondir(Carbon $heure): Carbon
    {
        $reste = $heure->minute % 5;

        return $reste === 0 ? $heure->second(0) : $heure->addMinutes(5 - $reste)->second(0);
    }

    /*
    |------------------------------------------------------------------
    | Points et distances
    |------------------------------------------------------------------
    */

    /** Le trajet repart du dernier arrêt fait (localisé), sinon du dépôt */
    private function pointDeDepart(Collection $figees): array
    {
        $dernier = $figees->filter->estLocalisee()->sortBy([['terminee_le', 'asc'], ['ordre', 'asc']])->last();

        return $dernier
            ? ['nom' => 'Dernier arrêt : ' . $dernier->adresse, ...$this->point($dernier)]
            : $this->depot();
    }

    public function depot(): array
    {
        return [
            'nom'       => config('logistique.depot.nom'),
            'latitude'  => (float) config('logistique.depot.latitude'),
            'longitude' => (float) config('logistique.depot.longitude'),
        ];
    }

    /** Tournée du jour déjà commencée : on part de maintenant. Sinon de l'heure prévue du premier arrêt. */
    private function heureDeDepart(Tournee $tournee, Collection $figees, Collection $restantes, Carbon $maintenant): Carbon
    {
        $jour = $tournee->date->copy();

        if ($jour->isSameDay($maintenant) && $figees->isNotEmpty()) {
            return $this->arrondir($maintenant->copy());
        }

        $premiere = $restantes->sortBy('heure_prevue')->first()?->heure;
        $heure = $premiere ?: config('logistique.itineraire.heure_depart');

        // On part un peu avant le premier rendez-vous pour avoir le temps d'y arriver
        return $jour->setTimeFromTimeString($heure)->subMinutes($premiere ? 15 : 0);
    }

    private function point(Mission $mission): array
    {
        return ['latitude' => (float) $mission->latitude, 'longitude' => (float) $mission->longitude];
    }

    /** Distance routière estimée en km : formule de haversine × facteur de détour */
    public function distance(array $a, array $b): float
    {
        $lat1 = deg2rad($a['latitude']);
        $lat2 = deg2rad($b['latitude']);
        $dLat = $lat2 - $lat1;
        $dLng = deg2rad($b['longitude'] - $a['longitude']);

        $h = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;

        return 2 * self::RAYON_TERRE_KM * asin(min(1, sqrt($h))) * (float) config('logistique.itineraire.facteur_detour');
    }
}
