<?php

namespace App\Services\Logistique;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Prévision du volume de dons, par semaine et par gouvernorat.
 *
 * Modèle volontairement simple et explicable :
 *  1. saisonnalité annuelle : chaque semaine de l'année reçoit un coefficient, appris sur la dernière
 *     année après avoir retiré la croissance (sinon l'an dernier paraît « creux » simplement parce que la
 *     plateforme était plus petite). Estimée une fois sur le total et partagée par toutes les zones, ce qui
 *     évite qu'une petite zone bruitée invente ses propres saisons. Seulement avec ≥ 52 semaines d'historique ;
 *  2. tendance : lissage exponentiel double de Holt sur la série désaisonnalisée ;
 *  3. incertitude : écart-type des erreurs de prévision à une semaine, élargi avec l'horizon (intervalle à 80 %) ;
 *  4. fiabilité : on cache les 8 dernières semaines, on les prévoit, et on mesure l'erreur (backtest).
 */
class PrevisionDons
{
    private const ALPHA = 0.3;            // poids des dernières semaines dans le niveau
    private const BETA  = 0.1;            // poids des dernières semaines dans la tendance
    private const Z_80  = 1.2816;         // quantile de la loi normale pour un intervalle à 80 %
    private const SEMAINES_TEST = 8;

    /** Série totale (toutes zones) : sert à estimer la saisonnalité commune */
    private array $total = [];

    /**
     * @return array{
     *   debut_prevision: Carbon, horizon: int,
     *   total: array, zones: Collection,
     *   semaines_historique: int, saisonnalite: bool
     * }
     */
    public function prevoir(int $horizon = 8, ?Carbon $maintenant = null): array
    {
        $maintenant ??= now();
        // La semaine en cours n'est pas finie : l'historique s'arrête à la semaine précédente
        $debutPrevision = $maintenant->copy()->startOfWeek();

        $parZone = $this->historique($debutPrevision);
        $semaines = $parZone->isEmpty() ? collect() : collect(array_keys($parZone->first()));

        $total = $semaines->mapWithKeys(fn (string $s) => [$s => $parZone->sum(fn (array $serie) => $serie[$s])])->all();
        $this->total = $total;

        $zones = $parZone->map(fn (array $serie, string $zone) => ['zone' => $zone] + $this->modeliser($serie, $debutPrevision, $horizon))
            ->sortByDesc(fn (array $z) => $z['prevision'][0]['valeur'] ?? 0)
            ->values();

        return [
            'debut_prevision'     => $debutPrevision,
            'horizon'             => $horizon,
            'total'               => $this->modeliser($total, $debutPrevision, $horizon),
            'zones'               => $zones,
            'semaines_historique' => count($total),
            'saisonnalite'        => count($total) >= 52,
        ];
    }

    /**
     * Dons par gouvernorat et par semaine (lundi), de la première semaine connue à la dernière semaine complète.
     *
     * @return Collection<string, array<string, int>> zone => [semaine (Y-m-d) => nombre]
     */
    public function historique(Carbon $finExclue): Collection
    {
        $lignes = DB::table('don_vetements')
            ->leftJoin('adresses', fn ($j) => $j->on('adresses.user_id', '=', 'don_vetements.user_id')->where('adresses.par_defaut', true))
            ->where('don_vetements.date_depot', '<', $finExclue->format('Y-m-d'))
            ->get(['don_vetements.date_depot as date', 'adresses.gouvernorat as zone']);

        if ($lignes->isEmpty()) {
            return collect();
        }

        $semaine = fn (string $date) => Carbon::parse($date)->startOfWeek()->format('Y-m-d');
        $premiere = Carbon::parse($lignes->min('date'))->startOfWeek();

        // Toutes les semaines, même vides : une semaine sans don compte pour zéro
        $grille = [];
        for ($s = $premiere->copy(); $s->lt($finExclue); $s->addWeek()) {
            $grille[$s->format('Y-m-d')] = 0;
        }

        return $lignes->groupBy(fn ($l) => $l->zone ?: 'Non renseigné')
            ->map(function (Collection $dons) use ($grille, $semaine) {
                $serie = $grille;
                foreach ($dons as $don) {
                    $serie[$semaine($don->date)]++;
                }

                return $serie;
            })
            ->sortKeys();
    }

    /**
     * @param  array<string, int>  $serie  semaine => nombre
     */
    private function modeliser(array $serie, Carbon $debutPrevision, int $horizon): array
    {
        $dates = array_keys($serie);
        $valeurs = array_values(array_map('floatval', $serie));
        $n = count($valeurs);

        if ($n < 4) {
            return ['historique' => $this->points($dates, $valeurs), 'prevision' => [], 'tendance_pct' => 0, 'erreur_pct' => null, 'moyenne_4' => array_sum(array_slice($valeurs, -4)) / max(1, min(4, $n))];
        }

        $ajustement = $this->ajuster($dates, $valeurs, $this->saisonsDuTotal(count($this->total)));
        $prevision = $this->projeter($ajustement, $debutPrevision, $horizon);

        // Fiabilité mesurée : on prévoit les dernières semaines sans les avoir vues
        $erreur = null;
        if ($n >= self::SEMAINES_TEST + 12) {
            // Backtest honnête : saisonnalité et tendance apprises sans les semaines testées
            $test = $this->ajuster(array_slice($dates, 0, -self::SEMAINES_TEST), array_slice($valeurs, 0, -self::SEMAINES_TEST),
                $this->saisonsDuTotal(count($this->total) - self::SEMAINES_TEST));
            $prevu = $this->projeter($test, Carbon::parse($dates[$n - self::SEMAINES_TEST]), self::SEMAINES_TEST);
            $reels = array_slice($valeurs, -self::SEMAINES_TEST);
            $somme = array_sum($reels);
            // Erreur relative globale (somme des écarts / somme des dons) : stable même pour de petites zones
            $erreur = $somme > 0
                ? round(array_sum(array_map(fn ($p, $r) => abs($p['valeur'] - $r), $prevu, $reels)) / $somme * 100)
                : null;
        }

        $moyenne4 = array_sum(array_slice($valeurs, -4)) / 4;
        $prochaine = $prevision[0]['valeur'] ?? 0;

        return [
            'historique'   => $this->points($dates, $valeurs),
            'prevision'    => $prevision,
            // Évolution de la semaine prochaine par rapport à la moyenne des 4 dernières semaines
            'tendance_pct' => $moyenne4 > 0 ? (int) round(($prochaine - $moyenne4) / $moyenne4 * 100) : 0,
            'erreur_pct'   => $erreur,
            'moyenne_4'    => round($moyenne4, 1),
        ];
    }

    /** Saisonnalité + lissage de Holt ; renvoie l'état final et l'erreur type */
    private function ajuster(array $dates, array $valeurs, array $saisons): array
    {
        $coef = fn (string $date) => $saisons[$this->semaineDeLAnnee(Carbon::parse($date))] ?? 1.0;

        $desais = array_map(fn ($v, $d) => $v / max($coef($d), 0.2), $valeurs, $dates);

        $niveau = $desais[0];
        $tendance = count($desais) > 4 ? ($desais[3] - $desais[0]) / 3 : 0.0;
        $erreurs = [];

        for ($t = 1; $t < count($desais); $t++) {
            $prevu = $niveau + $tendance;
            $erreurs[] = $desais[$t] - $prevu;
            $ancien = $niveau;
            $niveau = self::ALPHA * $desais[$t] + (1 - self::ALPHA) * ($niveau + $tendance);
            $tendance = self::BETA * ($niveau - $ancien) + (1 - self::BETA) * $tendance;
        }

        // Écart-type des erreurs à une semaine, sur la dernière année au plus
        $recentes = array_slice($erreurs, -52);
        $moyenne = array_sum($recentes) / max(1, count($recentes));
        $ecart = sqrt(array_sum(array_map(fn ($e) => ($e - $moyenne) ** 2, $recentes)) / max(1, count($recentes) - 1));

        return compact('niveau', 'tendance', 'ecart', 'saisons');
    }

    private function projeter(array $ajustement, Carbon $debut, int $horizon): array
    {
        $points = [];
        for ($h = 1; $h <= $horizon; $h++) {
            $semaine = $debut->copy()->addWeeks($h - 1);
            $coef = $ajustement['saisons'][$this->semaineDeLAnnee($semaine)] ?? 1.0;
            $centre = max(0, ($ajustement['niveau'] + $h * $ajustement['tendance']) * $coef);
            $marge = self::Z_80 * $ajustement['ecart'] * sqrt($h) * $coef;

            $points[] = [
                'semaine' => $semaine->format('Y-m-d'),
                'valeur'  => round($centre, 1),
                'bas'     => round(max(0, $centre - $marge), 1),
                'haut'    => round($centre + $marge, 1),
            ];
        }

        return $points;
    }

    /** Saisonnalité estimée sur les $n premières semaines de la série totale */
    private function saisonsDuTotal(int $n): array
    {
        return $this->coefficients(array_slice(array_keys($this->total), 0, $n), array_slice(array_values($this->total), 0, $n));
    }

    /**
     * Coefficient saisonnier de chaque semaine de l'année (1 = semaine moyenne).
     *
     * 1. on retire la croissance : droite de régression sur tout l'historique ;
     * 2. sur la dernière année, chaque semaine vaut « dons réels / dons attendus par la tendance » ;
     * 3. on lisse sur cinq semaines (le bruit d'une semaine isolée n'est pas une saison) et on recentre sur 1.
     * Moins d'un an d'historique : pas de saisonnalité.
     *
     * @return array<int, float> semaine de l'année (1-52) => coefficient
     */
    private function coefficients(array $dates, array $valeurs): array
    {
        $n = count($valeurs);

        if ($n < 52 || array_sum($valeurs) <= 0) {
            return [];
        }

        // 1. Tendance linéaire (moindres carrés)
        $mx = ($n - 1) / 2;
        $my = array_sum($valeurs) / $n;
        $cov = $var = 0.0;
        foreach ($valeurs as $x => $y) {
            $cov += ($x - $mx) * ($y - $my);
            $var += ($x - $mx) ** 2;
        }
        $pente = $var > 0 ? $cov / $var : 0.0;

        // 2. Rapport à la tendance sur la dernière année
        $rapports = [];
        for ($x = $n - 52; $x < $n; $x++) {
            $attendu = $my + $pente * ($x - $mx);
            $rapports[] = $attendu > 0.5 ? $valeurs[$x] / $attendu : 1.0;
        }

        // 3. Lissage sur cinq semaines, puis moyenne ramenée à 1
        $lisses = [];
        foreach ($rapports as $i => $r) {
            $fenetre = array_slice($rapports, max(0, $i - 2), min(5, $i + 3, 52 - max(0, $i - 2)));
            $lisses[$i] = array_sum($fenetre) / count($fenetre);
        }
        $moyenne = array_sum($lisses) / 52;

        $coefficients = [];
        foreach (array_slice($dates, -52) as $i => $date) {
            $coefficients[$this->semaineDeLAnnee(Carbon::parse($date))] = round($lisses[$i] / $moyenne, 3);
        }

        return $coefficients;
    }

    /** Semaine ISO ramenée à 1-52 (la rare semaine 53 est traitée comme la 52) */
    private function semaineDeLAnnee(Carbon $date): int
    {
        return min(52, $date->isoWeek());
    }

    private function points(array $dates, array $valeurs): array
    {
        return array_map(fn ($d, $v) => ['semaine' => $d, 'valeur' => $v], $dates, $valeurs);
    }
}
