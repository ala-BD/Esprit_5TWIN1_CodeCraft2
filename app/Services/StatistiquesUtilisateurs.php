<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Statistiques des comptes : affichées sur la page d'administration
 * et fournies à l'assistant vocal pour qu'il puisse les commenter.
 */
class StatistiquesUtilisateurs
{
    /** Nombre de semaines du graphique des inscriptions */
    public const SEMAINES = 12;

    /** @return array{chiffres: array, parRole: \Illuminate\Support\Collection, parSemaine: \Illuminate\Support\Collection, derniers: \Illuminate\Support\Collection} */
    public function calculer(): array
    {
        $total = User::count();

        // Chiffres clés
        $nouveaux30   = User::where('created_at', '>=', now()->subDays(30))->count();
        $precedents30 = User::whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count();

        $chiffres = [
            'total'        => $total,
            'actifs'       => User::where('actif', true)->count(),
            'inactifs'     => User::where('actif', false)->count(),
            'verifies'     => User::whereNotNull('email_verified_at')->count(),
            'nouveaux_30'  => $nouveaux30,
            'evolution_30' => $nouveaux30 - $precedents30,
        ];

        // Répartition par rôle (tous les rôles, même à zéro), du plus au moins représenté
        $comptes = User::selectRaw('role, count(*) as nombre')->groupBy('role')->pluck('nombre', 'role');
        $parRole = collect(User::ROLES)
            ->map(fn (string $role) => ['role' => $role, 'nombre' => (int) ($comptes[$role] ?? 0)])
            ->sortByDesc('nombre')
            ->values();

        // Inscriptions par semaine (regroupées en PHP : indépendant du moteur SQL)
        $debut = now()->startOfWeek()->subWeeks(self::SEMAINES - 1);
        $dates = User::where('created_at', '>=', $debut)->pluck('created_at');

        $parSemaine = collect(range(0, self::SEMAINES - 1))->map(function (int $i) use ($debut, $dates) {
            $lundi = $debut->copy()->addWeeks($i);
            $fin   = $lundi->copy()->endOfWeek();

            return [
                'debut'  => $lundi,
                'fin'    => $fin,
                'nombre' => $dates->filter(fn (Carbon $date) => $date->between($lundi, $fin))->count(),
            ];
        });

        $derniers = User::latest()->take(6)->get();

        return compact('chiffres', 'parRole', 'parSemaine', 'derniers');
    }
}
