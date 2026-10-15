<?php

namespace App\Http\Controllers;

use App\Http\Requests\PanierRequest;
use App\Models\Article;
use App\Models\Panier;
use App\Models\PanierLigne;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PanierController extends Controller
{
    use AuthorizesRequests;

    // -------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------

    /**
     * Récupère ou crée le panier de l'utilisateur connecté.
     * Charge toujours les lignes avec article + vendeur (eager loading).
     */
    private function getPanier(): Panier
    {
        $panier = Panier::firstOrCreate(
            ['user_id' => auth()->id()]
        );

        $panier->load(['user', 'lignes.article.user']);

        return $panier;
    }

    /**
     * Résumé calculé du panier pour les réponses JSON (Alpine.js / Axios).
     */
    private function jsonResume(Panier $panier): array
    {
        // Rechargement propre après toute modification
        $panier->load(['user', 'lignes.article.user']);

        $lignesData = $panier->lignes->map(function (PanierLigne $ligne) {
            $article = $ligne->article;
            return [
                'id'             => $ligne->id,
                'article_id'     => $ligne->article_id,
                'titre'          => $article?->titre,
                'prix_unitaire'  => (float) ($article?->prix ?? 0),
                'a_remise'       => $ligne->aRemise(),
                'montant_remise' => $ligne->montantRemise(),
                'quantite'       => $ligne->quantite,
                'stock'          => $article?->stock ?? 0,
                'sous_total'     => $ligne->sousTotal(),
                'total_ligne'    => $ligne->totalLigne(),
            ];
        });

        return array_merge($panier->resume(), ['lignes' => $lignesData]);
    }

    // -------------------------------------------------------
    // index — Afficher le panier
    // -------------------------------------------------------

    public function index(Request $request): View
    {
        $panier = $this->getPanier();

        // Synchronisation : suppression d'articles obsolètes / ajustement stock
        $avertissements = $panier->synchroniser();

        $lignesData = $panier->lignes->map(function ($ligne) {
            $article = $ligne->article;
            $photo = null;
            if ($article && !empty($article->images)) {
                $photo = is_array($article->images) ? ($article->images[0] ?? null) : $article->images;
            }
            return [
                'id'             => $ligne->id,
                'article_id'     => $ligne->article_id,
                'titre'          => $article?->titre ?? 'Article introuvable',
                'url'            => $article ? route('articles.show', $article) : '#',
                'photo'          => $photo ? (str_starts_with($photo, 'http') ? $photo : asset('storage/' . $photo)) : null,
                'vendeur'        => $article?->user?->name ?? 'Vendeur inconnu',
                'vendeur_role'   => $article?->user?->role ?? '',
                'prix_unitaire'  => (float) ($article?->prix ?? 0),
                'a_remise'       => $ligne->aRemise(),
                'montant_remise' => $ligne->montantRemise(),
                'quantite'       => (int) $ligne->quantite,
                'stock'          => (int) ($article?->stock ?? 0),
                'total_ligne'    => $ligne->totalLigne(),
                'isUpdating'     => false,
            ];
        });

        return view('panier.index', [
            'panier'         => $panier,
            'lignesData'     => $lignesData,
            'resume'         => $panier->resume(),
            'avertissements' => $avertissements,
        ]);
    }

    // -------------------------------------------------------
    // ajouter — Ajouter un article au panier
    // -------------------------------------------------------

    public function ajouter(PanierRequest $request, Article $article): JsonResponse|RedirectResponse
    {
        $quantite = (int) $request->validated()['quantite'];
        $user     = auth()->user();

        // Règle métier 1 : un utilisateur ne peut pas acheter son propre article
        if ($article->user_id === $user->id) {
            $message = 'Vous ne pouvez pas ajouter votre propre article au panier.';
            return $request->expectsJson()
                ? response()->json(['error' => $message], 403)
                : back()->with('error', $message);
        }

        // Règle métier 2 : l'article doit être disponible (publié et en stock)
        if (! $article->isDisponible()) {
            $message = 'Cet article n\'est pas disponible à l\'achat.';
            return $request->expectsJson()
                ? response()->json(['error' => $message], 422)
                : back()->with('error', $message);
        }

        // Récupère ou crée le panier
        $panier = Panier::firstOrCreate(['user_id' => $user->id]);

        // Vérifie si l'article est déjà dans le panier
        /** @var PanierLigne|null $ligne */
        $ligne = $panier->lignes()->where('article_id', $article->id)->first();

        if ($ligne) {
            // Augmenter la quantité existante
            $nouvelleQte = $ligne->quantite + $quantite;
        } else {
            $nouvelleQte = $quantite;
        }

        // Règle métier 3 : la quantité totale ne peut pas dépasser le stock
        if ($nouvelleQte > $article->stock) {
            $message = "Stock insuffisant. Maximum disponible : {$article->stock}.";
            return $request->expectsJson()
                ? response()->json(['error' => $message], 422)
                : back()->with('error', $message);
        }

        if ($ligne) {
            $ligne->update(['quantite' => $nouvelleQte]);
        } else {
            $panier->lignes()->create([
                'article_id' => $article->id,
                'quantite'   => $nouvelleQte,
            ]);
        }

        $panier->load(['user', 'lignes.article.user']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "« {$article->titre} » ajouté au panier.",
                'resume'  => $this->jsonResume($panier),
            ]);
        }

        return redirect()
            ->route('panier.index')
            ->with('success', "« {$article->titre} » a été ajouté à votre panier !");
    }

    // -------------------------------------------------------
    // modifier — Changer la quantité d'une ligne
    // -------------------------------------------------------

    public function modifier(PanierRequest $request, PanierLigne $ligne): JsonResponse|RedirectResponse
    {
        // Vérification Policy : la ligne doit appartenir au panier de l'utilisateur
        $this->authorize('update', $ligne);

        $quantite = (int) $request->validated()['quantite'];
        $article  = $ligne->article;

        if (! $article) {
            $message = 'L\'article associé à cette ligne n\'existe plus.';
            return $request->expectsJson()
                ? response()->json(['error' => $message], 422)
                : back()->with('error', $message);
        }

        // Règle métier : quantité ne peut pas dépasser le stock
        if ($quantite > $article->stock) {
            $message = "Stock insuffisant. Maximum disponible : {$article->stock}.";
            return $request->expectsJson()
                ? response()->json(['error' => $message], 422)
                : back()->with('error', $message);
        }

        $ligne->update(['quantite' => $quantite]);

        $panier = $this->getPanier();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'resume'  => $this->jsonResume($panier),
            ]);
        }

        return back()->with('success', 'Quantité mise à jour.');
    }

    // -------------------------------------------------------
    // retirer — Supprimer une ligne du panier
    // -------------------------------------------------------

    public function retirer(Request $request, PanierLigne $ligne): JsonResponse|RedirectResponse
    {
        // Vérification Policy : la ligne doit appartenir au panier de l'utilisateur
        $this->authorize('delete', $ligne);

        $titre = $ligne->article?->titre ?? 'L\'article';
        $ligne->delete();

        $panier = $this->getPanier();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "« {$titre} » retiré du panier.",
                'resume'  => $this->jsonResume($panier),
            ]);
        }

        return back()->with('success', "« {$titre} » a été retiré de votre panier.");
    }

    // -------------------------------------------------------
    // vider — Vider complètement le panier
    // -------------------------------------------------------

    public function vider(Request $request): JsonResponse|RedirectResponse
    {
        $panier = Panier::where('user_id', auth()->id())->first();

        if ($panier) {
            $panier->lignes()->delete();
            $panier->load(['user', 'lignes.article.user']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Votre panier a été vidé.',
                'resume'  => $panier ? $this->jsonResume($panier) : [
                    'sous_total' => 0, 'remise' => 0, 'frais_livraison' => 0,
                    'total' => 0, 'nb_articles' => 0, 'lignes' => [],
                ],
            ]);
        }

        return redirect()
            ->route('panier.index')
            ->with('success', 'Votre panier a été vidé.');
    }
}
