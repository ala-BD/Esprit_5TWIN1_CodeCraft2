<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Favori;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriController extends Controller
{
    /**
     * Affiche la liste de souhaits de l'utilisateur connecté.
     */
    public function index(Request $request): View
    {
        $favoris = Favori::where('user_id', $request->user()->id)
            ->with(['article' => fn($q) => $q
                ->with('user')
                ->withAvg('avis', 'note_globale')
                ->withCount('avis'),
            ])
            ->latest()
            ->get();

        return view('favoris.index', [
            'favoris' => $favoris,
        ]);
    }

    /**
     * Bascule le favori (toggle) pour un article donné.
     * Retourne du JSON pour les appels AJAX (icône cœur).
     */
    public function toggle(Request $request, Article $article): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $favori = Favori::where('user_id', $user->id)
            ->where('article_id', $article->id)
            ->first();

        if ($favori) {
            $favori->delete();
            $isFavori = false;
        } else {
            Favori::create([
                'user_id'              => $user->id,
                'article_id'           => $article->id,
                'prix_au_moment_ajout' => $article->prix,
            ]);
            $isFavori = true;
        }

        $count = Favori::where('user_id', $user->id)->count();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'is_favori'    => $isFavori,
                'favori_count' => $count,
                'message'      => $isFavori
                    ? 'Article ajouté à votre liste de souhaits.'
                    : 'Article retiré de votre liste de souhaits.',
            ]);
        }

        return redirect()->back()->with(
            $isFavori ? 'success' : 'info',
            $isFavori
                ? "«&nbsp;{$article->titre}&nbsp;» ajouté à vos favoris."
                : "«&nbsp;{$article->titre}&nbsp;» retiré de vos favoris."
        );
    }
}
