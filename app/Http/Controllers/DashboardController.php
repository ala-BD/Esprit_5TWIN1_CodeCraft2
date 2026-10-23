<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Commande;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Route générique /dashboard → redirige vers le dashboard du rôle courant.
     */
    public function index(Request $request): RedirectResponse
    {
        $role = $request->user()->role;

        $routes = [
            User::ROLE_ADMIN      => 'dashboard.admin',
            User::ROLE_ATELIER    => 'dashboard.atelier',
            User::ROLE_CLIENT     => 'dashboard.client',
            User::ROLE_DONATEUR   => 'dashboard.donateur',
            User::ROLE_COLLECTEUR => 'dashboard.collecteur',
            User::ROLE_RECYCLEUR  => 'dashboard.recycleur',
        ];

        return redirect()->route($routes[$role] ?? 'home');
    }

    // ─────────────────────────────────────────────────────────────────
    // CLIENT
    // ─────────────────────────────────────────────────────────────────
    public function client(Request $request): View
    {
        $user = $request->user();

        $commandes = Commande::with(['lignes.article'])
            ->where('user_id', $user->id)
            ->latest('date_commande')
            ->take(5)
            ->get();

        $allQ = Commande::where('user_id', $user->id);

        $stats = [
            'total'    => (clone $allQ)->count(),
            'en_cours' => (clone $allQ)->whereIn('statut', [
                Commande::STATUT_EN_ATTENTE, Commande::STATUT_CONFIRMEE,
                Commande::STATUT_EN_PREPARATION, Commande::STATUT_EXPEDIEE,
            ])->count(),
            'livrees'  => (clone $allQ)->where('statut', Commande::STATUT_LIVREE)->count(),
            'favoris'  => $user->favoris()->count(),
        ];

        $commandeAConfirmer = Commande::where('user_id', $user->id)
            ->where('statut', Commande::STATUT_EXPEDIEE)
            ->latest('date_commande')
            ->first();

        return view('dashboard.client', compact('commandes', 'stats', 'commandeAConfirmer'));
    }

    // ─────────────────────────────────────────────────────────────────
    // ATELIER
    // ─────────────────────────────────────────────────────────────────
    public function atelier(Request $request): View
    {
        $user = $request->user();

        $articles = $user->articles()->latest()->get();

        $baseQuery = Commande::with(['lignes.article', 'user'])
            ->whereHas('lignes.article', fn($q) => $q->where('user_id', $user->id))
            ->latest('date_commande');

        $commandesEnAttente  = (clone $baseQuery)->where('statut', Commande::STATUT_EN_ATTENTE)->get();
        $commandesConfirmees = (clone $baseQuery)
            ->whereIn('statut', [Commande::STATUT_CONFIRMEE, Commande::STATUT_EN_PREPARATION])
            ->get();

        $stats = [
            'total_articles'   => $articles->count(),
            'en_attente'       => $commandesEnAttente->count(),
            'en_preparation'   => $commandesConfirmees->count(),
            'chiffre_affaires' => Commande::whereHas('lignes.article', fn($q) => $q->where('user_id', $user->id))
                ->whereNotIn('statut', [Commande::STATUT_ANNULEE, Commande::STATUT_EN_ATTENTE_PAIEMENT])
                ->sum('montant_total'),
        ];

        return view('dashboard.atelier', compact('articles', 'commandesEnAttente', 'commandesConfirmees', 'stats'));
    }

    // ─────────────────────────────────────────────────────────────────
    // DONATEUR
    // ─────────────────────────────────────────────────────────────────
    public function donateur(Request $request): View
    {
        $user = $request->user();

        $commandes = Commande::with(['lignes.article'])
            ->where('user_id', $user->id)
            ->latest('date_commande')
            ->take(5)
            ->get();

        $allQ = Commande::where('user_id', $user->id);

        $stats = [
            'total_commandes' => (clone $allQ)->count(),
            'total_dons'      => $user->donVetements()->count(),
            'favoris'         => $user->favoris()->count(),
            'livrees'         => (clone $allQ)->where('statut', Commande::STATUT_LIVREE)->count(),
        ];

        $commandeAConfirmer = Commande::where('user_id', $user->id)
            ->where('statut', Commande::STATUT_EXPEDIEE)
            ->latest()->first();

        return view('dashboard.donateur', compact('commandes', 'stats', 'commandeAConfirmer'));
    }

    // ─────────────────────────────────────────────────────────────────
    // COLLECTEUR
    // ─────────────────────────────────────────────────────────────────
    public function collecteur(Request $request): View
    {
        $user = $request->user();

        $commandes = Commande::with(['lignes.article'])
            ->where('user_id', $user->id)
            ->latest('date_commande')
            ->take(5)
            ->get();

        $allQ  = Commande::where('user_id', $user->id);
        $stats = [
            'total_commandes' => (clone $allQ)->count(),
            'en_cours'        => (clone $allQ)->whereIn('statut', [
                Commande::STATUT_EN_ATTENTE, Commande::STATUT_CONFIRMEE,
                Commande::STATUT_EN_PREPARATION, Commande::STATUT_EXPEDIEE,
            ])->count(),
            'livrees'         => (clone $allQ)->where('statut', Commande::STATUT_LIVREE)->count(),
            'favoris'         => $user->favoris()->count(),
        ];

        $commandeAConfirmer = Commande::where('user_id', $user->id)
            ->where('statut', Commande::STATUT_EXPEDIEE)
            ->latest()->first();

        return view('dashboard.collecteur', compact('commandes', 'stats', 'commandeAConfirmer'));
    }

    // ─────────────────────────────────────────────────────────────────
    // RECYCLEUR
    // ─────────────────────────────────────────────────────────────────
    public function recycleur(Request $request): View
    {
        $user = $request->user();

        // Achats
        $commandes = Commande::with(['lignes.article'])
            ->where('user_id', $user->id)
            ->latest('date_commande')
            ->take(5)
            ->get();

        $allQ  = Commande::where('user_id', $user->id);
        $stats = [
            'total_commandes' => (clone $allQ)->count(),
            'livrees'         => (clone $allQ)->where('statut', Commande::STATUT_LIVREE)->count(),
            'favoris'         => $user->favoris()->count(),
        ];

        $commandeAConfirmer = Commande::where('user_id', $user->id)
            ->where('statut', Commande::STATUT_EXPEDIEE)
            ->latest()->first();

        return view('dashboard.recycleur', compact('commandes', 'stats', 'commandeAConfirmer'));
    }

    // ─────────────────────────────────────────────────────────────────
    // ADMIN
    // ─────────────────────────────────────────────────────────────────
    public function admin(Request $request): View
    {
        $stats = [
            'total_users'     => User::count(),
            'by_role'         => User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role')->toArray(),
            'total_articles'  => Article::count(),
            'total_commandes' => Commande::count(),
            'chiffre_affaires'=> Commande::whereNotIn('statut', [Commande::STATUT_ANNULEE, Commande::STATUT_EN_ATTENTE_PAIEMENT])->sum('montant_total'),
            'en_attente'      => Commande::where('statut', Commande::STATUT_EN_ATTENTE)->count(),
        ];

        $recentCommandes = Commande::with(['user', 'lignes'])->latest('date_commande')->take(8)->get();
        $recentUsers     = User::latest()->take(6)->get();

        return view('dashboard.admin', compact('stats', 'recentCommandes', 'recentUsers'));
    }
}
