<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use App\Models\DonVetement;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ArticleController extends Controller
{
    use AuthorizesRequests;

    /**
     * Affiche la liste des articles avec filtres avancés et pagination.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Article::class);

        $query = Article::with(['user', 'donVetement'])->latest();

        // Filtre "Mes articles uniquement"
        $filterMine = $request->boolean('mine') || $request->get('filter') === 'mine';
        if ($filterMine) {
            $query->where('user_id', $request->user()->id);
        }

        // Scopes conditionnels
        $query
            ->when($request->filled('q'),        fn($q) => $q->recherche($request->q))
            ->when($request->filled('categorie'), fn($q) => $q->categorie($request->categorie))
            ->when($request->filled('taille'),    fn($q) => $q->taille($request->taille))
            ->when($request->filled('matiere'),   fn($q) => $q->matiere($request->matiere))
            ->when($request->filled('etat'),      fn($q) => $q->etat($request->etat))
            ->when($request->filled('couleur'),   fn($q) => $q->couleur($request->couleur))
            ->when($request->filled('statut'),    fn($q) => $q->where('statut', $request->statut))
            ->when($request->filled('prix_min') || $request->filled('prix_max'),
                fn($q) => $q->prixEntre(
                    $request->filled('prix_min') ? (float) $request->prix_min : null,
                    $request->filled('prix_max') ? (float) $request->prix_max : null
                ))
            ->when($request->boolean('gratuits'), fn($q) => $q->donsGratuits())
            ->when($request->boolean('en_stock'), fn($q) => $q->enStock())
            ->when($request->filled('vendeur_role'), fn($q) => $q->vendeurRole($request->vendeur_role));

        // Tri
        $sort = $request->get('tri', 'recent');
        match ($sort) {
            'prix_asc'  => $query->reorder('prix', 'asc'),
            'prix_desc' => $query->reorder('prix', 'desc'),
            default     => $query->reorder('created_at', 'desc'),
        };

        $articles = $query->paginate(12)->withQueryString();

        // Statistiques globales
        $stats = [
            'total'       => Article::count(),
            'disponibles' => Article::where('statut', Article::STATUT_DISPONIBLE)->count(),
            'mesArticles' => Article::where('user_id', $request->user()->id)->count(),
        ];

        // Comptage des filtres actifs pour le badge
        $activeFilters = array_filter([
            $request->filled('q'),
            $request->filled('categorie'),
            $request->filled('taille'),
            $request->filled('matiere'),
            $request->filled('etat'),
            $request->filled('couleur'),
            $request->filled('statut'),
            $request->filled('prix_min') || $request->filled('prix_max'),
            $request->boolean('gratuits'),
            $request->boolean('en_stock'),
            $request->filled('vendeur_role'),
        ]);

        return view('articles.index', [
            'articles'      => $articles,
            'categories'    => Article::CATEGORIES,
            'statuts'       => Article::STATUTS,
            'tailles'       => Article::TAILLES,
            'matieres'      => Article::MATIERES,
            'etats'         => Article::ETATS,
            'couleurs'      => Article::COULEURS,
            'stats'         => $stats,
            'filterMine'    => $filterMine,
            'activeFilters' => count($activeFilters),
            'currentQ'      => $request->get('q', ''),
            'currentCat'    => $request->get('categorie', ''),
            'currentStat'   => $request->get('statut', ''),
            'currentTri'    => $sort,
        ]);
    }

    /**
     * Autocomplete endpoint pour la barre de recherche.
     */
    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }
        $suggestions = Article::where('statut', Article::STATUT_DISPONIBLE)
            ->where('titre', 'like', "%{$q}%")
            ->orderByDesc('created_at')
            ->limit(8)
            ->pluck('titre');
        return response()->json($suggestions);
    }

    /**
     * Affiche la page de detail d un article.
     */
    public function show(Article $article): View
    {
        $this->authorize('view', $article);

        $article->load(['user', 'donVetement']);

        $relatedArticles = Article::with('user')
            ->where('id', '!=', $article->id)
            ->where('categorie', $article->categorie)
            ->where('statut', Article::STATUT_DISPONIBLE)
            ->take(3)
            ->get();

        return view('articles.show', [
            'article'         => $article,
            'relatedArticles' => $relatedArticles,
        ]);
    }

    /**
     * Affiche le formulaire de creation d un article.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Article::class);

        $article = new Article([
            'stock'  => 1,
            'statut' => Article::STATUT_DISPONIBLE,
        ]);

        $userDons = $request->user()->donVetements()->latest()->get();

        return view('articles.form', [
            'article'    => $article,
            'isEdit'     => false,
            'categories' => Article::CATEGORIES,
            'statuts'    => Article::STATUTS,
            'tailles'    => Article::TAILLES,
            'matieres'   => Article::MATIERES,
            'etats'      => Article::ETATS,
            'couleurs'   => Article::COULEURS,
            'userDons'   => $userDons,
        ]);
    }

    /**
     * Enregistre un nouvel article.
     */
    public function store(ArticleRequest $request): RedirectResponse
    {
        $this->authorize('create', Article::class);

        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;
        $validated['statut']  = Article::STATUT_DISPONIBLE;

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('articles', 'public');
            }
        }
        $validated['images'] = $imagePaths;

        $article = Article::create($validated);

        return redirect()
            ->route('articles.show', $article)
            ->with('success', 'Votre article a ete publie avec succes sur la marketplace !');
    }

    /**
     * Affiche le formulaire de modification d un article.
     */
    public function edit(Request $request, Article $article): View
    {
        $this->authorize('update', $article);

        $userDons = $request->user()->isAdmin()
            ? DonVetement::latest()->take(30)->get()
            : $request->user()->donVetements()->latest()->get();

        return view('articles.form', [
            'article'    => $article,
            'isEdit'     => true,
            'categories' => Article::CATEGORIES,
            'statuts'    => Article::STATUTS,
            'tailles'    => Article::TAILLES,
            'matieres'   => Article::MATIERES,
            'etats'      => Article::ETATS,
            'couleurs'   => Article::COULEURS,
            'userDons'   => $userDons,
        ]);
    }

    /**
     * Met a jour un article existant.
     */
    public function update(ArticleRequest $request, Article $article): RedirectResponse
    {
        $this->authorize('update', $article);

        $validated = $request->validated();
        unset($validated['user_id']);

        $currentImages = $article->images ?? [];

        if ($request->filled('remove_images')) {
            $toRemove = (array) $request->input('remove_images');
            foreach ($toRemove as $path) {
                Storage::disk('public')->delete($path);
            }
            $currentImages = array_values(array_diff($currentImages, $toRemove));
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $currentImages[] = $image->store('articles', 'public');
            }
        }

        $validated['images'] = $currentImages;
        $article->update($validated);

        return redirect()
            ->route('articles.show', $article)
            ->with('success', "L'article a ete mis a jour avec succes !");
    }

    /**
     * Supprime un article.
     */
    public function destroy(Article $article): RedirectResponse
    {
        $this->authorize('delete', $article);

        if (!empty($article->images)) {
            foreach ($article->images as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $titre = $article->titre;
        $article->delete();

        return redirect()
            ->route('articles.index')
            ->with('success', "L'article {$titre} a ete supprime de la marketplace.");
    }
}
