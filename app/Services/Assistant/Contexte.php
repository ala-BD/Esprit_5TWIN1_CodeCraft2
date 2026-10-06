<?php

namespace App\Services\Assistant;

use App\Models\Mission;
use App\Models\Tournee;
use App\Models\User;
use App\Services\StatistiquesUtilisateurs;
use Illuminate\Support\Carbon;

/**
 * Ce que l'assistant doit savoir pour comprendre une commande sans poser de questions inutiles :
 * les données existantes (annuaire des utilisateurs, tournées du collecteur) et la page affichée.
 */
class Contexte
{
    // Volontairement courts : le quota gratuit de l'IA se compte en jetons par minute
    private const MAX_UTILISATEURS = 40;
    private const MAX_TOURNEES = 8;

    public static function pour(User $user, ?string $page, Carbon $aujourdhui): string
    {
        return $user->isAdmin()
            ? self::administration($user, $page)
            : self::logistique($user, $page, $aujourdhui);
    }

    /*
    |------------------------------------------------------------------
    | Administration
    |------------------------------------------------------------------
    */
    private static function administration(User $user, ?string $page): string
    {
        $total = User::count();
        $utilisateurs = User::latest('id')->limit(self::MAX_UTILISATEURS)->get();

        $lignes = $utilisateurs->map(fn (User $u) => sprintf(
            '#%d | %s | %s | %s | %s%s',
            $u->id,
            $u->full_name,
            $u->email,
            $u->role,
            $u->actif ? 'active' : 'deactivated',
            $u->is($user) ? ' | THIS IS THE PERSON SPEAKING' : '',
        ))->join("\n");

        $texte = "USER DIRECTORY ({$utilisateurs->count()} of {$total} accounts; format: #id | full name | email | role | state)\n{$lignes}";

        if ($total > $utilisateurs->count()) {
            $texte .= "\n(The directory is partial. For a user not listed, pass what was said in `cible`.)";
        }

        $affiche = null;
        if ($page && preg_match('#/admin/users/(\d+)#', $page, $m) && ($vu = User::find((int) $m[1]))) {
            $affiche = "the profile of user #{$vu->id} ({$vu->full_name}). Words like \"this user\", \"him\", \"her\", \"ce compte\", \"lui\" refer to this user.";
        } elseif ($page && str_contains($page, '/admin/statistiques')) {
            $affiche = 'the statistics page.';
        } elseif ($page && str_contains($page, '/admin/users')) {
            $affiche = 'the list of users.';
        }

        return $texte . "\n\n" . self::statistiquesComptes() . ($affiche ? "\n\nON SCREEN RIGHT NOW: {$affiche}" : '');
    }

    /** Les chiffres de la page Statistiques, pour que l'assistant puisse les lire et les commenter */
    private static function statistiquesComptes(): string
    {
        ['chiffres' => $c, 'parRole' => $parRole, 'parSemaine' => $parSemaine, 'derniers' => $derniers] = app(StatistiquesUtilisateurs::class)->calculer();

        $pourcent = fn (int $n) => $c['total'] > 0 ? round($n / $c['total'] * 100) : 0;
        $roles    = $parRole->map(fn (array $r) => "{$r['role']} {$r['nombre']}")->join(', ');
        $semaines = $parSemaine->map(fn (array $s) => $s['debut']->format('d/m') . ':' . $s['nombre'])->join(' ');
        $recents  = $derniers->map(fn (User $u) => "{$u->full_name} ({$u->role}, {$u->created_at->format('Y-m-d')})")->join('; ');

        return <<<TXT
        STATISTICS (all accounts, computed now)
        - Accounts: {$c['total']} in total, {$c['actifs']} active ({$pourcent($c['actifs'])}%), {$c['inactifs']} deactivated.
        - Verified emails: {$c['verifies']} ({$pourcent($c['verifies'])}%).
        - New accounts in the last 30 days: {$c['nouveaux_30']} ({$c['evolution_30']} compared with the 30 days before; positive means growth).
        - Accounts per role: {$roles}.
        - Sign-ups per week, last 12 weeks (week start:count): {$semaines}.
        - Most recent sign-ups: {$recents}.
        TXT;
    }

    /*
    |------------------------------------------------------------------
    | Logistique
    |------------------------------------------------------------------
    */
    private static function logistique(User $user, ?string $page, Carbon $aujourdhui): string
    {
        // Les tournées récentes et à venir, les plus utiles pour une commande orale
        $tournees = Tournee::where('user_id', $user->id)
            ->whereDate('date', '>=', $aujourdhui->copy()->subDays(3))
            ->with('missions')
            ->orderBy('date')
            ->limit(self::MAX_TOURNEES)
            ->get();

        if ($tournees->isEmpty()) {
            $texte = 'ROUNDS: this collector has no recent or upcoming round. A mission can only be added after a round is created.';
        } else {
            $lignes = $tournees->map(function (Tournee $t) use ($aujourdhui) {
                $jour = match (true) {
                    $t->date->isSameDay($aujourdhui)                    => ' (TODAY)',
                    $t->date->isSameDay($aujourdhui->copy()->addDay())  => ' (tomorrow)',
                    $t->date->isSameDay($aujourdhui->copy()->subDay())  => ' (yesterday)',
                    default                                             => '',
                };

                $missions = $t->missions->map(fn (Mission $m) => sprintf(
                    '    stop %d | %s | %s | %s | %s',
                    $m->ordre, $m->type, $m->heure, $m->adresse, $m->statut,
                ))->join("\n");

                return sprintf('#%d | %s%s | zone: %s | %s | vehicle: %s', $t->id, $t->date->format('Y-m-d'), $jour, $t->zone, $t->statut, $t->vehicule)
                    . ($missions ? "\n{$missions}" : "\n    (no mission yet)");
            })->join("\n");

            $missions = $tournees->flatMap->missions;
            $duJour   = $tournees->filter(fn (Tournee $t) => $t->date->isSameDay($aujourdhui))->flatMap->missions;
            $parStatut = fn ($liste) => collect(Mission::STATUTS)->keys()
                ->map(fn (string $statut) => $statut . ' ' . $liste->where('statut', $statut)->count())->join(', ');

            $resume = "SUMMARY: {$tournees->count()} rounds listed ("
                . $tournees->countBy('statut')->map(fn (int $n, string $statut) => "{$statut} {$n}")->join(', ') . "), "
                . "{$missions->count()} missions in total ({$parStatut($missions)}), "
                . "{$missions->where('type', Mission::TYPE_COLLECTE)->count()} pickups and {$missions->where('type', Mission::TYPE_LIVRAISON)->count()} deliveries, "
                . round((float) $tournees->sum('distance_km'), 1) . ' km planned. '
                . "Today: {$duJour->count()} missions ({$parStatut($duJour)}).";

            $texte = $resume . "\n\nROUNDS OF THIS COLLECTOR (format: #id | date | zone | status | vehicle, then its missions: stop number | type | time | address | status)\n{$lignes}";
        }

        $affiche = null;
        if ($page && preg_match('#/logistique/tournees/(\d+)#', $page, $m) && ($vue = Tournee::where('user_id', $user->id)->find((int) $m[1]))) {
            $affiche = "round #{$vue->id} ({$vue->zone}, {$vue->date->format('Y-m-d')}). \"This round\", \"here\", \"cette tournée\", \"ici\" refer to it, and a mission with no round named belongs to it.";
        } elseif ($page && preg_match('#/logistique/missions/(\d+)#', $page, $m) && ($mission = Mission::with('tournee')->find((int) $m[1])) && $mission->tournee->user_id === $user->id) {
            $affiche = "stop {$mission->ordre} of round #{$mission->tournee_id} ({$mission->tournee->zone}). \"This mission\" refers to it.";
        } elseif ($page && str_contains($page, '/logistique/tournees')) {
            $affiche = 'the list of rounds.';
        }

        return $texte . ($affiche ? "\n\nON SCREEN RIGHT NOW: {$affiche}" : '');
    }
}
