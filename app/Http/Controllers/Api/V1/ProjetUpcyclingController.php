<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Upcycling\ActionProjetRequest;
use App\Http\Requests\Upcycling\ProjetUpcyclingRequest;
use App\Http\Resources\Upcycling\ClassementAtelierResource;
use App\Http\Resources\Upcycling\ProjetUpcyclingResource;
use App\Models\Atelier;
use App\Models\ProjetUpcycling;
use App\Models\User;
use App\Services\Upcycling\GestionProjetService;
use App\Services\Upcycling\IdeeUpcyclingService;
use App\Services\Upcycling\MatchingAtelierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Throwable;

/**
 * Projets d'upcycling (API v1). Même logique métier que le site web (GestionProjetService).
 */
class ProjetUpcyclingController extends Controller
{
    public function __construct(
        private GestionProjetService $gestion,
        private IdeeUpcyclingService $ideeService,
        private MatchingAtelierService $matching,
    ) {}

    /*
    |------------------------------------------------------------------
    | GET /api/v1/projets?statut=TERMINE — Projets du client ou de l'atelier
    |------------------------------------------------------------------
    */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'statut'   => ['nullable', 'in:' . implode(',', array_merge(array_keys(ProjetUpcycling::ETAPES), ['ANNULE']))],
            'par_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $user = $request->user();
        $projets = ProjetUpcycling::query()
            ->when($user->role === User::ROLE_ATELIER,
                fn ($q) => $q->whereIn('atelier_id', Atelier::where('user_id', $user->id)->select('id')),
                fn ($q) => $user->isAdmin() ? $q : $q->where('client_id', $user->id))
            ->when($request->statut, fn ($q, $statut) => $q->where('statut', $statut))
            ->with(['client', 'atelier', 'devis'])
            ->withCount('messages')
            ->latest()
            ->paginate($request->integer('par_page', 10))
            ->withQueryString();

        return ProjetUpcyclingResource::collection($projets);
    }

    /*
    |------------------------------------------------------------------
    | POST /api/v1/projets — Nouvelle demande (multipart si photo)
    |------------------------------------------------------------------
    */
    public function store(ProjetUpcyclingRequest $request): JsonResponse
    {
        Gate::authorize('create', ProjetUpcycling::class);

        $projet = $this->gestion->creer($request->user(), $request->validated(), $request->file('photo'));

        return (new ProjetUpcyclingResource($projet->load(['client'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ProjetUpcycling $projet): ProjetUpcyclingResource
    {
        Gate::authorize('view', $projet);

        return new ProjetUpcyclingResource($projet->load(['client', 'atelier', 'devis'])->loadCount('messages'));
    }

    /** PATCH /api/v1/projets/{projet} — mise à jour partielle */
    public function update(ProjetUpcyclingRequest $request, ProjetUpcycling $projet): ProjetUpcyclingResource
    {
        Gate::authorize('gererClient', $projet);

        $this->gestion->modifier($projet, $request->validated(), $request->file('photo'));

        return new ProjetUpcyclingResource($projet->fresh(['client', 'atelier', 'devis']));
    }

    public function destroy(ProjetUpcycling $projet): Response
    {
        Gate::authorize('gererClient', $projet);
        $this->gestion->supprimer($projet);

        return response()->noContent();
    }

    /*
    |------------------------------------------------------------------
    | IA et choix
    |------------------------------------------------------------------
    */

    /** POST /api/v1/analyse-photo — l'IA reconnaît le vêtement */
    public function analyserPhoto(ActionProjetRequest $request): JsonResponse
    {
        Gate::authorize('create', ProjetUpcycling::class);

        if (!$this->ideeService->estConfigure()) {
            return response()->json(['message' => "L'analyse IA n'est pas configurée sur ce serveur."], 503);
        }

        try {
            $analyse = $this->ideeService->analyserPhoto($request->file('photo')->getRealPath());
        } catch (Throwable) {
            return response()->json(['message' => "L'IA n'a pas pu analyser la photo."], 502);
        }

        return $analyse['est_vetement']
            ? response()->json(['data' => $analyse])
            : response()->json(['message' => "L'IA ne reconnaît pas de vêtement sur cette photo."], 422);
    }

    /** POST /api/v1/projets/{projet}/idees — nouvelles idées (consigne facultative) */
    public function regenererIdees(ActionProjetRequest $request, ProjetUpcycling $projet): ProjetUpcyclingResource
    {
        Gate::authorize('gererClient', $projet);
        $this->gestion->regenererIdees($projet, $request->validated('consigne'));

        return new ProjetUpcyclingResource($projet->fresh());
    }

    /** PATCH /api/v1/projets/{projet}/idee — {"index": 0} */
    public function choisirIdee(ActionProjetRequest $request, ProjetUpcycling $projet): ProjetUpcyclingResource
    {
        Gate::authorize('gererClient', $projet);
        $this->gestion->choisirIdee($projet, (int) $request->validated('index'));

        return new ProjetUpcyclingResource($projet->fresh());
    }

    /** GET /api/v1/projets/{projet}/matching — ateliers classés pour ce projet */
    public function matching(ProjetUpcycling $projet): AnonymousResourceCollection
    {
        Gate::authorize('gererClient', $projet);

        $classement = $this->matching->classer($projet)
            ->values()
            ->map(fn ($ligne, $i) => $ligne + ['rang' => $i + 1]);

        return ClassementAtelierResource::collection($classement)->additional([
            'meta' => [
                'methode_affinite' => $this->matching->methodeAffinite,
                'ponderation'      => [
                    'specialite'    => MatchingAtelierService::POIDS_SPECIALITE,
                    'affinite'      => MatchingAtelierService::POIDS_AFFINITE,
                    'note'          => MatchingAtelierService::POIDS_NOTE,
                    'tarif'         => MatchingAtelierService::POIDS_TARIF,
                    'disponibilite' => MatchingAtelierService::POIDS_DISPONIBILITE,
                ],
            ],
        ]);
    }

    /** PATCH /api/v1/projets/{projet}/atelier — {"atelier_id": 3} */
    public function choisirAtelier(ActionProjetRequest $request, ProjetUpcycling $projet): ProjetUpcyclingResource
    {
        Gate::authorize('gererClient', $projet);
        $this->gestion->choisirAtelier($projet, Atelier::findOrFail($request->validated('atelier_id')));

        return new ProjetUpcyclingResource($projet->fresh(['atelier']));
    }

    /*
    |------------------------------------------------------------------
    | Suivi
    |------------------------------------------------------------------
    */

    /** POST /api/v1/projets/{projet}/avancer — étape suivante (atelier) */
    public function avancer(ActionProjetRequest $request, ProjetUpcycling $projet): ProjetUpcyclingResource
    {
        Gate::authorize('gererAtelier', $projet);
        $this->gestion->avancer($projet, $request->file('photo_resultat'));

        return new ProjetUpcyclingResource($projet->fresh());
    }

    /** POST /api/v1/projets/{projet}/annuler */
    public function annuler(ProjetUpcycling $projet): ProjetUpcyclingResource
    {
        Gate::authorize('gererClient', $projet);
        $this->gestion->annuler($projet);

        return new ProjetUpcyclingResource($projet->fresh());
    }

    /** POST /api/v1/projets/{projet}/noter — {"note_client": 5, "commentaire_client": "..."} */
    public function noter(ActionProjetRequest $request, ProjetUpcycling $projet): ProjetUpcyclingResource
    {
        Gate::authorize('gererClient', $projet);
        $this->gestion->noter($projet, (int) $request->validated('note_client'), $request->validated('commentaire_client'));

        return new ProjetUpcyclingResource($projet->fresh());
    }
}
