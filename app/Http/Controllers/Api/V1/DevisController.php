<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Upcycling\DevisRequest;
use App\Http\Resources\Upcycling\DevisResource;
use App\Models\Devis;
use App\Models\ProjetUpcycling;
use App\Services\Upcycling\GestionProjetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Devis d'un projet (API v1).
 */
class DevisController extends Controller
{
    public function __construct(private GestionProjetService $gestion) {}

    /** GET /api/v1/projets/{projet}/devis */
    public function index(ProjetUpcycling $projet): AnonymousResourceCollection
    {
        Gate::authorize('view', $projet);

        return DevisResource::collection($projet->devis);
    }

    /** POST /api/v1/projets/{projet}/devis — l'atelier propose un devis */
    public function store(DevisRequest $request, ProjetUpcycling $projet): JsonResponse
    {
        Gate::authorize('gererAtelier', $projet);
        $devis = $this->gestion->envoyerDevis($projet, $request->validated());

        return (new DevisResource($devis))->response()->setStatusCode(201);
    }

    /** POST /api/v1/devis/{devis}/acceptation */
    public function accepter(Devis $devis): DevisResource
    {
        Gate::authorize('gererClient', $devis->projetUpcycling);
        $this->gestion->accepterDevis($devis);

        return new DevisResource($devis->fresh());
    }

    /** POST /api/v1/devis/{devis}/refus */
    public function refuser(Devis $devis): DevisResource
    {
        Gate::authorize('gererClient', $devis->projetUpcycling);
        $this->gestion->refuserDevis($devis);

        return new DevisResource($devis->fresh());
    }
}
