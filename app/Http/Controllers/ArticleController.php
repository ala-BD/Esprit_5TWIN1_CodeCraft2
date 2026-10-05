<?php

namespace App\Http\Controllers;

use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use App\Models\DonVetement;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ArticleController extends Controller
{
    use AuthorizesRequests;

    /**
     * Affiche la liste des articles de la marketplace (avec recherche & filtres).
     * Tous les utilisateurs connectés peuvent voir les articles de tout le monde.
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

        // Recherche par titre ou description
        if ($search = trim((string) $request->get('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filtre par catégorie
        if ($cat = $request->get('categorie')) {
            $query->where('categorie', $cat);
        }

        // Filtre par statut
        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        $articles = $query->paginate(12)->withQueryString();

        // Statistiques globales pour les compteurs d'en-tête
        $stats = [
            'total'       => Article::count(),
            'disponibles' => Article::where('statut', Article::STATUT_DISPONIBLE)->count(),
            'mesArticles' => Article::where('user_id', $request->user()->id)->count(),
        ];

        return view('articles.index', [
            'articles'    => $articles,
            'categories'  => Article::CATEGORIES,
            'statuts'     => Article::STATUTS,
            'stats'       => $stats,
            'filterMine'  => $filterMine,
            'currentQ'    => $search,
            'currentCat'  => $cat,
            'currentStat' => $statut,
        ]);
    }

    /**
     * Affiche la page de détail d'un article.
     */
    public function show(Article $article): View
    {
        $this->authorize('view', $article);

        $article->load(['user', 'donVetement']);

        // Suggestions d'autres articles de la même catégorie
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
     * Affiche le formulaire de création d'un article.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Article::class);

        $article = new Article([
            'stock'  => 1,
            'statut' => Article::STATUT_DISPONIBLE,
        ]);

        // Récupérer les dons de l'utilisateur pour une éventuelle association
        $userDons = $request->user()->donVetements()->latest()->get();

        return view('articles.form', [
            'article'    => $article,
            'isEdit'     => false,
            'categories' => Article::CATEGORIES,
            'statuts'    => Article::STATUTS,
            'userDons'   => $userDons,
        ]);
    }

    /**
     * Enregistre un nouvel article dans la marketplace.
     * Le user_id est TOUJOURS injecté depuis auth()->id(), jamais depuis le formulaire.
     */
    public function store(ArticleRequest $request): RedirectResponse
    {
        $this->authorize('create', Article::class);

        $validated = $request->validated();

        // Sécurité stricte : user_id provient toujours de l'utilisateur authentifié
        $validated['user_id'] = $request->user()->id;

        // Lors de la publication initiale, le statut est toujours "DISPONIBLE"
        $validated['statut'] = Article::STATUT_DISPONIBLE;

        // Traitement des images multiples
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
            ->with('success', 'Votre article a été publié avec succès sur la marketplace !');
    }

    /**
     * Affiche le formulaire de modification d'un article existant.
     * Accessible uniquement par le propriétaire ou un administrateur.
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
            'userDons'   => $userDons,
        ]);
    }

    /**
     * Met à jour un article existant.
     * Accessible uniquement par le propriétaire ou un administrateur.
     */
    public function update(ArticleRequest $request, Article $article): RedirectResponse
    {
        $this->authorize('update', $article);

        $validated = $request->validated();

        // Protection : on ne permet jamais de changer le propriétaire initial
        unset($validated['user_id']);

        $currentImages = $article->images ?? [];

        // Suppression des images cochées
        if ($request->filled('remove_images')) {
            $toRemove = (array) $request->input('remove_images');
            foreach ($toRemove as $path) {
                Storage::disk('public')->delete($path);
            }
            $currentImages = array_values(array_diff($currentImages, $toRemove));
        }

        // Ajout des nouvelles images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $currentImages[] = $image->store('articles', 'public');
            }
        }

        $validated['images'] = $currentImages;

        $article->update($validated);

        return redirect()
            ->route('articles.show', $article)
            ->with('success', 'L\'article a été mis à jour avec succès !');
    }

    /**
     * Supprime un article de la marketplace.
     * Accessible uniquement par le propriétaire ou un administrateur.
     */
    public function destroy(Article $article): RedirectResponse
    {
        $this->authorize('delete', $article);

        // Supprimer les images physiques stockées
        if (!empty($article->images)) {
            foreach ($article->images as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $titre = $article->titre;
        $article->delete();

        return redirect()
            ->route('articles.index')
            ->with('success', "L'article « {$titre} » a été supprimé de la marketplace.");
    }
}
