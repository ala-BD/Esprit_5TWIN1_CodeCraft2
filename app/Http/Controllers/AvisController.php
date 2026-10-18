<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvisRequest;
use App\Models\Article;
use App\Models\Avis;
use App\Models\AvisVote;
use App\Models\Commande;
use App\Notifications\NouveauAvisNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AvisController extends Controller
{
    use AuthorizesRequests;

    /**
     * Affiche le formulaire de dépôt d'avis pour un article d'une commande livrée.
     */
    public function create(Commande $commande, Article $article): View
    {
        $this->authorize('create', [Avis::class, $commande, $article]);

        $article->loadMissing('user');

        return view('avis.create', [
            'commande' => $commande,
            'article'  => $article,
        ]);
    }

    /**
     * Enregistre un nouvel avis après validation des critères.
     */
    public function store(AvisRequest $request, Commande $commande, Article $article): RedirectResponse
    {
        $this->authorize('create', [Avis::class, $commande, $article]);

        // Calcul automatique de la note globale sur le serveur (moyenne des 4 critères)
        $noteGlobale = Avis::calculerNoteGlobale(
            (int) $request->input('note_livraison'),
            (int) $request->input('note_article'),
            (int) $request->input('note_clarte'),
            (int) $request->input('note_service')
        );

        $avis = DB::transaction(function () use ($request, $commande, $article, $noteGlobale) {
            return Avis::create([
                'commande_id'    => $commande->id,
                'user_id'        => auth()->id(),
                'article_id'     => $article->id,
                'note_livraison' => (int) $request->input('note_livraison'),
                'note_article'   => (int) $request->input('note_article'),
                'note_clarte'    => (int) $request->input('note_clarte'),
                'note_service'   => (int) $request->input('note_service'),
                'note_globale'   => $noteGlobale,
                'commentaire'    => $request->filled('commentaire') ? trim($request->input('commentaire')) : null,
            ]);
        });

        // Notification envoyée au vendeur de l'article (Atelier / Donateur)
        try {
            $article->loadMissing('user');
            if ($article->user && $article->user->id !== auth()->id()) {
                $article->user->notify(new NouveauAvisNotification($avis));
            }
        } catch (\Throwable $e) {
            Log::warning('[Avis] Erreur lors de l\'envoi de la notification: ' . $e->getMessage());
        }

        return redirect()
            ->route('commandes.show', $commande)
            ->with('success', "Merci pour votre avis ! Votre évaluation (⭐ {$noteGlobale}/5) a bien été enregistrée.");
    }

    /**
     * Affiche le formulaire de modification d'un avis existant.
     */
    public function edit(Avis $avis): View
    {
        $this->authorize('update', $avis);

        $avis->loadMissing(['article.user', 'commande']);

        return view('avis.edit', [
            'avis'     => $avis,
            'article'  => $avis->article,
            'commande' => $avis->commande,
        ]);
    }

    /**
     * Met à jour un avis existant.
     */
    public function update(AvisRequest $request, Avis $avis): RedirectResponse
    {
        $this->authorize('update', $avis);

        $noteGlobale = Avis::calculerNoteGlobale(
            (int) $request->input('note_livraison'),
            (int) $request->input('note_article'),
            (int) $request->input('note_clarte'),
            (int) $request->input('note_service')
        );

        $avis->update([
            'note_livraison' => (int) $request->input('note_livraison'),
            'note_article'   => (int) $request->input('note_article'),
            'note_clarte'    => (int) $request->input('note_clarte'),
            'note_service'   => (int) $request->input('note_service'),
            'note_globale'   => $noteGlobale,
            'commentaire'    => $request->filled('commentaire') ? trim($request->input('commentaire')) : null,
        ]);

        return redirect()
            ->route('articles.show', $avis->article_id)
            ->with('success', "Votre avis a été modifié avec succès (nouvelle note : ⭐ {$noteGlobale}/5).");
    }

    /**
     * Supprime un avis (auteur ou Administrateur).
     */
    public function destroy(Avis $avis): RedirectResponse
    {
        $this->authorize('delete', $avis);

        $articleId = $avis->article_id;
        $avis->delete();

        return redirect()
            ->route('articles.show', $articleId)
            ->with('success', "L'avis a été supprimé avec succès.");
    }

    /**
     * Action AJAX : Voter pour un avis (like / dislike).
     */
    public function vote(Request $request, Avis $avis): JsonResponse
    {
        $this->authorize('vote', $avis);

        $request->validate([
            'type' => ['required', 'string', 'in:like,dislike'],
        ]);

        $userId   = auth()->id();
        $type     = $request->input('type');
        $userVote = null;

        $existing = AvisVote::where('avis_id', $avis->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            if ($existing->type === $type) {
                // Même type de vote : annulation du vote (toggle off)
                $existing->delete();
                $userVote = null;
            } else {
                // Inversion du vote (ex: like -> dislike)
                $existing->update(['type' => $type]);
                $userVote = $type;
            }
        } else {
            // Nouveau vote
            AvisVote::create([
                'avis_id' => $avis->id,
                'user_id' => $userId,
                'type'    => $type,
            ]);
            $userVote = $type;
        }

        $likesCount = $avis->likesCount();
        $dislikesCount = $avis->dislikesCount();

        return response()->json([
            'success'        => true,
            'likes'          => $likesCount,
            'dislikes'       => $dislikesCount,
            'userVote'       => $userVote,
            'likes_count'    => $likesCount,
            'dislikes_count' => $dislikesCount,
            'user_vote'      => $userVote,
        ]);
    }
}
