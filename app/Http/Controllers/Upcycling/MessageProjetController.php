<?php

namespace App\Http\Controllers\Upcycling;

use App\Http\Controllers\Controller;
use App\Http\Requests\Upcycling\MessageProjetRequest;
use App\Models\MessageProjet;
use App\Models\ProjetUpcycling;
use App\Services\Upcycling\GestionProjetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MessageProjetController extends Controller
{
    public function __construct(private GestionProjetService $gestion) {}

    /*
    |------------------------------------------------------------------
    | GET /upcycling/projets/{projet}/messages?apres={id}  (AJAX)
    | Nouveaux messages depuis le dernier affiché : la discussion se met
    | à jour toute seule, sans recharger la page.
    |------------------------------------------------------------------
    */
    public function index(Request $request, ProjetUpcycling $projet): JsonResponse
    {
        Gate::authorize('view', $projet);

        $messages = $projet->messages()
            ->with('user')
            ->where('id', '>', $request->integer('apres'))
            ->get();

        return $this->reponseJson($projet, $messages);
    }

    /*
    |------------------------------------------------------------------
    | POST /upcycling/projets/{projet}/messages
    | Message au fil du projet ; avec "ia=1", le styliste IA répond.
    | En AJAX, renvoie les bulles à ajouter à la discussion.
    |------------------------------------------------------------------
    */
    public function store(MessageProjetRequest $request, ProjetUpcycling $projet): RedirectResponse|JsonResponse
    {
        Gate::authorize('discuter', $projet);

        $messages = $this->gestion->ecrireMessage($projet, Auth::user(), $request->validated('contenu'), $request->boolean('ia'));

        if ($request->expectsJson()) {
            return $this->reponseJson($projet, $messages, 201);
        }

        return redirect()->to(route('upcycling.projets.show', $projet) . '#discussion');
    }

    /** @param Collection<int, MessageProjet> $messages */
    private function reponseJson(ProjetUpcycling $projet, Collection $messages, int $statut = 200): JsonResponse
    {
        $monRole = $projet->client_id === Auth::id() ? MessageProjet::AUTEUR_CLIENT : MessageProjet::AUTEUR_ATELIER;

        return response()->json([
            'html'      => $messages->map(fn ($message) => view('upcycling.projets.partials.message', [
                'message' => $message,
                'monRole' => $monRole,
                'nouveau' => true,
            ])->render())->implode(''),
            'ids'       => $messages->pluck('id'),
            'dernier_id' => $messages->max('id'),
            'total'     => $projet->messages()->count(),
            'ia'        => $messages->contains(fn ($m) => $m->est_ia),
        ], $statut);
    }
}
