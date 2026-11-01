<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Upcycling\MessageProjetRequest;
use App\Http\Resources\Upcycling\MessageProjetResource;
use App\Models\ProjetUpcycling;
use App\Services\Upcycling\GestionProjetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Discussion d'un projet et styliste IA (API v1).
 */
class MessageProjetController extends Controller
{
    public function __construct(private GestionProjetService $gestion) {}

    /** GET /api/v1/projets/{projet}/messages */
    public function index(ProjetUpcycling $projet): AnonymousResourceCollection
    {
        Gate::authorize('view', $projet);

        return MessageProjetResource::collection($projet->messages()->with(['user', 'projetUpcycling.atelier'])->get());
    }

    /** POST /api/v1/projets/{projet}/messages — message à l'autre partie (notifiée) */
    public function store(MessageProjetRequest $request, ProjetUpcycling $projet): JsonResponse
    {
        Gate::authorize('discuter', $projet);
        $messages = $this->gestion->ecrireMessage($projet, $request->user(), $request->validated('contenu'));

        return MessageProjetResource::collection($messages)->response()->setStatusCode(201);
    }

    /** POST /api/v1/projets/{projet}/styliste — question au styliste IA (question + réponse) */
    public function styliste(MessageProjetRequest $request, ProjetUpcycling $projet): JsonResponse
    {
        Gate::authorize('discuter', $projet);
        $messages = $this->gestion->ecrireMessage($projet, $request->user(), $request->validated('contenu'), demanderIa: true);

        return MessageProjetResource::collection($messages)->response()->setStatusCode(201);
    }
}
