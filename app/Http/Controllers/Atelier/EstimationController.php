<?php

namespace App\Http\Controllers\Atelier;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalculerEstimationRequest;
use App\Models\Article;
use App\Models\Estimation;
use App\Models\ParametreCout;
use App\Services\EstimationService;
use App\Services\RentabiliteService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EstimationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected EstimationService $estimationService,
        protected RentabiliteService $rentabiliteService
    ) {}

    /**
     * Endpoint API JSON : Exécute l'estimation complète (coûts, fourchette de prix, IA et alerte de rentabilité).
     */
    public function calculer(CalculerEstimationRequest $request): JsonResponse
    {
        $user = $request->user();

        $article = null;
        if ($request->filled('article_id')) {
            $article = Article::find($request->input('article_id'));
            if ($article && !$user->isAdmin() && $article->user_id !== $user->id) {
                // Un atelier a le droit d'estimer un don gratuit (prix = 0 ou don_vetement_id) pour évaluer son potentiel d'upcycling
                $isDonGratuit = ((float)$article->prix === 0.0 || $article->don_vetement_id !== null);
                if (!$isDonGratuit) {
                    return response()->json([
                        'error' => 'Non autorisé à estimer un article payant appartenant à un autre utilisateur.',
                    ], 403);
                }
            }
        }

        $shouldSave = $request->boolean('save', false);

        $resultat = $this->estimationService->estimer(
            $request->validated(),
            $user,
            $article,
            $shouldSave
        );

        return response()->json([
            'success' => true,
            'data'    => $resultat,
        ]);
    }

    /**
     * Endpoint API JSON : Recalcul en temps réel de la rentabilité lorsque le prix ou la quantité est modifié.
     */
    public function recalculerMarge(Request $request): JsonResponse
    {
        $request->validate([
            'prix'           => ['required', 'numeric', 'min:0'],
            'cout_revient'   => ['required', 'numeric', 'min:0'],
            'cout_recyclage' => ['nullable', 'numeric', 'min:0'],
            'matiere'        => ['nullable', 'string'],
            'quantite'       => ['nullable', 'integer', 'min:1', 'max:5000'],
            'heures_travail' => ['nullable', 'numeric', 'min:0'],
            'poids_kg'       => ['nullable', 'numeric', 'min:0'],
        ]);

        $parametre = ParametreCout::pourMatiere($request->input('matiere'));
        $quantite  = max(1, (int) $request->input('quantite', 1));
        $heures    = (float) $request->input('heures_travail', 1.0);
        $poids     = (float) $request->input('poids_kg', 0.5);

        $analyse = $this->rentabiliteService->analyserRevenusQuantite(
            (float) $request->input('prix'),
            (float) $request->input('cout_revient'),
            $quantite,
            (float) ($request->input('cout_recyclage') ?? 0.0),
            $heures,
            $poids,
            $parametre
        );

        return response()->json([
            'success'  => true,
            'data'     => $analyse['unitaire'],
            'quantite' => $analyse['quantite'],
        ]);
    }

    /**
     * Catalogue dédié à l'Atelier : Consultation des dons textiles gratuits disponibles
     * avec filtres et indicateurs d'aide à la décision IA.
     */
    public function donsDisponibles(Request $request): View
    {
        $user = $request->user();

        $query = Article::with(['user', 'donVetement', 'derniereEstimation'])
            ->where('prix', 0)
            ->where('statut', Article::STATUT_DISPONIBLE)
            ->where('stock', '>', 0)
            ->latest();

        if ($request->filled('matiere')) {
            $query->where('matiere', $request->input('matiere'));
        }
        if ($request->filled('etat')) {
            $query->where('etat', $request->input('etat'));
        }
        if ($request->filled('categorie')) {
            $query->where('categorie', $request->input('categorie'));
        }
        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(fn($sub) => $sub->where('titre', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%"));
        }

        $dons = $query->paginate(12)->withQueryString();

        $statsDons = [
            'total_lots'        => Article::where('prix', 0)->where('statut', Article::STATUT_DISPONIBLE)->where('stock', '>', 0)->count(),
            'total_pieces'      => Article::where('prix', 0)->where('statut', Article::STATUT_DISPONIBLE)->where('stock', '>', 0)->sum('stock'),
            'total_poids_kg'    => round(Article::where('prix', 0)->where('statut', Article::STATUT_DISPONIBLE)->where('stock', '>', 0)->sum('poids_kg'), 2),
        ];

        return view('atelier.dons.index', compact('dons', 'statsDons'));
    }

    /**
     * Affiche l'historique complet des estimations de l'atelier avec statistiques.
     */
    public function historique(Request $request): View
    {
        $this->authorize('viewAny', Estimation::class);

        $user = $request->user();

        $query = Estimation::with(['article', 'user'])
            ->latest();

        // Si l'utilisateur n'est pas Admin, restreindre strictement à ses estimations
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        // Filtre niveau d'alerte
        if ($request->filled('niveau')) {
            $query->where('niveau_alerte', $request->input('niveau'));
        }

        // Filtre source (ia vs regles)
        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        $estimations = $query->paginate(15)->withQueryString();

        // Statistiques globales de l'atelier
        $baseStatsQuery = Estimation::query();
        if (!$user->isAdmin()) {
            $baseStatsQuery->where('user_id', $user->id);
        }

        $stats = [
            'total'         => (clone $baseStatsQuery)->count(),
            'gagnants'      => (clone $baseStatsQuery)->where('niveau_alerte', Estimation::ALERTE_VERT)->count(),
            'a_surveiller'  => (clone $baseStatsQuery)->where('niveau_alerte', Estimation::ALERTE_ORANGE)->count(),
            'perdants'      => (clone $baseStatsQuery)->where('niveau_alerte', Estimation::ALERTE_ROUGE)->count(),
            'marge_moyenne' => round((float) ((clone $baseStatsQuery)->avg('marge_pourcent') ?? 0.0), 1),
        ];

        return view('atelier.estimations.index', [
            'estimations' => $estimations,
            'stats'       => $stats,
            'currentNiveau' => $request->input('niveau', ''),
            'currentSource' => $request->input('source', ''),
        ]);
    }

    /**
     * Supprime une estimation de l'historique.
     */
    public function destroy(Estimation $estimation): RedirectResponse
    {
        $this->authorize('delete', $estimation);

        $estimation->delete();

        return back()->with('success', 'L\'estimation a été supprimée de votre historique.');
    }
}

