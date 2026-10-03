<?php

namespace App\Http\Controllers\Recyclage;

use App\Http\Controllers\Controller;
use App\Models\LotTextile;
use App\Models\Recycleur;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class LotTextileController extends Controller
{
    /*
    |------------------------------------------------------------------
    | GET /recyclage — Dashboard recycleur
    |------------------------------------------------------------------
    */
    public function dashboard(): View
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->first();

        if (!$recycleur) {
            return view('recyclage.dashboard', [
                'recycleur' => null,
                'stats'     => [],
                'lots'      => collect(),
            ]);
        }

        $stats = [
            'total'         => LotTextile::where('recycleur_id', $recycleur->id)->count(),
            'en_attente'    => LotTextile::where('recycleur_id', $recycleur->id)->where('statut', 'EN_ATTENTE')->count(),
            'en_traitement' => LotTextile::where('recycleur_id', $recycleur->id)->where('statut', 'EN_TRAITEMENT')->count(),
            'certifies'     => LotTextile::where('recycleur_id', $recycleur->id)->where('statut', 'CERTIFIE')->count(),
            'total_kg'      => LotTextile::where('recycleur_id', $recycleur->id)->sum('poids_kg'),
        ];

        $lots = LotTextile::where('recycleur_id', $recycleur->id)
            ->latest()->take(5)->get();

        return view('recyclage.dashboard', compact('recycleur', 'stats', 'lots'));
    }

    /*
    |------------------------------------------------------------------
    | GET /recyclage/lots — Liste des lots du recycleur connecté
    |------------------------------------------------------------------
    */
    public function index(): View
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();

        $lots = LotTextile::where('recycleur_id', $recycleur->id)
            ->with(['etapeTraitements', 'passeportNumerique'])
            ->latest()
            ->paginate(10);

        // Stats globales
        $stats = [
            'total'        => LotTextile::where('recycleur_id', $recycleur->id)->count(),
            'en_attente'   => LotTextile::where('recycleur_id', $recycleur->id)->where('statut', 'EN_ATTENTE')->count(),
            'en_traitement'=> LotTextile::where('recycleur_id', $recycleur->id)->where('statut', 'EN_TRAITEMENT')->count(),
            'certifies'    => LotTextile::where('recycleur_id', $recycleur->id)->where('statut', 'CERTIFIE')->count(),
            'total_kg'     => LotTextile::where('recycleur_id', $recycleur->id)->sum('poids_kg'),
        ];

        return view('recyclage.lots.index', compact('lots', 'recycleur', 'stats'));
    }

    /*
    |------------------------------------------------------------------
    | GET /recyclage/lots/create — Formulaire création
    |------------------------------------------------------------------
    */
    public function create(): View
    {
        return view('recyclage.lots.create');
    }

    /*
    |------------------------------------------------------------------
    | POST /recyclage/lots — Enregistrer un lot
    |------------------------------------------------------------------
    */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'reference'   => ['required', 'string', 'max:50', 'unique:lot_textiles,reference'],
            'poids_kg'    => ['required', 'numeric', 'min:0.1'],
            'composition' => ['required', 'string', 'max:255'],
            'origine'     => ['required', 'string', 'max:255'],
        ], [
            'reference.required'   => 'La référence du lot est obligatoire.',
            'reference.unique'     => 'Cette référence existe déjà.',
            'poids_kg.required'    => 'Le poids est obligatoire.',
            'poids_kg.numeric'     => 'Le poids doit être un nombre.',
            'poids_kg.min'         => 'Le poids minimum est 0.1 kg.',
            'composition.required' => 'La composition est obligatoire.',
            'origine.required'     => "L'origine est obligatoire.",
        ]);

        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();

        // Recommandation IA simulée
        $filiereIA = LotTextile::recommanderFiliereIA(
            $request->composition,
            (float) $request->poids_kg
        );

        $lot = LotTextile::create([
            'recycleur_id'          => $recycleur->id,
            'reference'             => strtoupper($request->reference),
            'poids_kg'              => $request->poids_kg,
            'composition'           => $request->composition,
            'origine'               => $request->origine,
            'filiere_recommandee_ia'=> $filiereIA,
            'statut'                => 'EN_ATTENTE',
        ]);

        return redirect()
            ->route('recyclage.lots.show', $lot)
            ->with('success', "Lot {$lot->reference} enregistré. Filière recommandée : {$filiereIA}");
    }

    /*
    |------------------------------------------------------------------
    | GET /recyclage/lots/{lot} — Détail d'un lot
    |------------------------------------------------------------------
    */
    public function show(LotTextile $lot): View
    {
        // S'assurer que le recycleur connecté est propriétaire
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403, 'Accès non autorisé.');

        $lot->load(['etapeTraitements', 'passeportNumerique', 'donVetement']);

        // Étapes déjà enregistrées
        $etapesEnregistrees = $lot->etapeTraitements->pluck('type')->toArray();

        // Prochaine étape disponible
        $toutesEtapes = array_keys(\App\Models\EtapeTraitement::ORDRE);
        $prochaineEtape = collect($toutesEtapes)
            ->first(fn($e) => !in_array($e, $etapesEnregistrees));

        return view('recyclage.lots.show', compact('lot', 'etapesEnregistrees', 'prochaineEtape'));
    }

    /*
    |------------------------------------------------------------------
    | GET /recyclage/lots/{lot}/edit — Formulaire édition
    |------------------------------------------------------------------
    */
    public function edit(LotTextile $lot): View
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403);

        return view('recyclage.lots.edit', compact('lot'));
    }

    /*
    |------------------------------------------------------------------
    | PUT /recyclage/lots/{lot} — Mettre à jour un lot
    |------------------------------------------------------------------
    */
    public function update(Request $request, LotTextile $lot): RedirectResponse
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403);

        $request->validate([
            'composition' => ['required', 'string', 'max:255'],
            'origine'     => ['required', 'string', 'max:255'],
            'statut'      => ['required', 'in:EN_ATTENTE,EN_TRAITEMENT,TRAITE,CERTIFIE'],
        ]);

        $lot->update($request->only(['composition', 'origine', 'statut']));

        return redirect()
            ->route('recyclage.lots.show', $lot)
            ->with('success', 'Lot mis à jour avec succès.');
    }

    /*
    |------------------------------------------------------------------
    | DELETE /recyclage/lots/{lot} — Supprimer un lot
    |------------------------------------------------------------------
    */
    public function destroy(LotTextile $lot): RedirectResponse
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403);
        abort_if($lot->statut === 'CERTIFIE', 422, 'Impossible de supprimer un lot certifié.');

        $ref = $lot->reference;
        $lot->delete();

        return redirect()
            ->route('recyclage.lots.index')
            ->with('success', "Lot {$ref} supprimé.");
    }
}
