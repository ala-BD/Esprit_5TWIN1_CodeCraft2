<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Upcycling\AtelierResource;
use App\Models\Atelier;
use App\Models\ProjetUpcycling;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Catalogue des ateliers (lecture seule).
 */
class AtelierController extends Controller
{
    /*
    |------------------------------------------------------------------
    | GET /api/v1/ateliers?specialite=SAC&q=tunis&par_page=10
    |------------------------------------------------------------------
    */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'specialite' => ['nullable', 'in:' . implode(',', array_keys(Atelier::SPECIALITES))],
            'q'          => ['nullable', 'string', 'max:100'],
            'par_page'   => ['nullable', 'integer', 'between:1,50'],
        ]);

        $ateliers = Atelier::where('actif', true)
            ->when($request->specialite, fn ($q, $specialite) => $q->where('specialite', $specialite))
            ->when($request->q, fn ($q, $texte) => $q->where(fn ($sub) => $sub
                ->where('nom', 'like', "%{$texte}%")->orWhere('localisation', 'like', "%{$texte}%")))
            ->withCount(['projetUpcyclings as projets_termines' => fn ($q) => $q->where('statut', ProjetUpcycling::STATUT_TERMINE)])
            ->orderByDesc('note_moyenne')
            ->paginate($request->integer('par_page', 10))
            ->withQueryString();

        return AtelierResource::collection($ateliers);
    }

    /*
    |------------------------------------------------------------------
    | GET /api/v1/ateliers/{atelier}
    |------------------------------------------------------------------
    */
    public function show(Atelier $atelier): AtelierResource
    {
        $atelier->loadCount(['projetUpcyclings as projets_termines' => fn ($q) => $q->where('statut', ProjetUpcycling::STATUT_TERMINE)]);

        return new AtelierResource($atelier);
    }
}
