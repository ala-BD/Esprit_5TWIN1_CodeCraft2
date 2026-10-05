<?php

namespace App\Http\Controllers\Logistique;

use App\Http\Controllers\Controller;
use App\Models\Tournee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TourneeController extends Controller
{
    /*
    |------------------------------------------------------------------
    | GET /logistique/tournees — Liste des tournées du collecteur connecté
    |------------------------------------------------------------------
    */
    public function index(Request $request): View
    {
        $tournees = Tournee::where('user_id', Auth::id())
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = '%' . $request->q . '%';
                $query->where(fn ($sub) => $sub->where('zone', 'like', $q)->orWhere('vehicule', 'like', $q));
            })
            ->when(array_key_exists((string) $request->statut, Tournee::STATUTS), fn ($query) => $query->where('statut', $request->statut))
            ->withCount('missions')
            ->orderByDesc('date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        // Stats globales
        $stats = [
            'total'      => Tournee::where('user_id', Auth::id())->count(),
            'planifiees' => Tournee::where('user_id', Auth::id())->where('statut', Tournee::STATUT_PLANIFIEE)->count(),
            'en_cours'   => Tournee::where('user_id', Auth::id())->where('statut', Tournee::STATUT_EN_COURS)->count(),
        ];

        return view('logistique.tournees.index', compact('tournees', 'stats'));
    }

    /*
    |------------------------------------------------------------------
    | GET /logistique/tournees/create — Formulaire création
    |------------------------------------------------------------------
    */
    public function create(): View
    {
        return view('logistique.tournees.create', [
            'tournee' => new Tournee(['date' => today(), 'statut' => Tournee::STATUT_PLANIFIEE]),
        ]);
    }

    /*
    |------------------------------------------------------------------
    | POST /logistique/tournees — Enregistrer une tournée
    |------------------------------------------------------------------
    */
    public function store(Request $request): RedirectResponse
    {
        $tournee = Tournee::create([...$this->valider($request), 'user_id' => Auth::id()]);

        return redirect()
            ->route('logistique.tournees.show', $tournee)
            ->with('success', 'Tournée enregistrée. Ajoutez maintenant ses missions.');
    }

    /*
    |------------------------------------------------------------------
    | GET /logistique/tournees/{tournee} — Détail d'une tournée et ses missions
    |------------------------------------------------------------------
    */
    public function show(Tournee $tournee): View
    {
        $this->autoriser($tournee);

        $tournee->load('missions.donVetement');

        return view('logistique.tournees.show', compact('tournee'));
    }

    /*
    |------------------------------------------------------------------
    | GET /logistique/tournees/{tournee}/edit — Formulaire édition
    |------------------------------------------------------------------
    */
    public function edit(Tournee $tournee): View
    {
        $this->autoriser($tournee);

        return view('logistique.tournees.edit', compact('tournee'));
    }

    /*
    |------------------------------------------------------------------
    | PUT /logistique/tournees/{tournee} — Mettre à jour une tournée
    |------------------------------------------------------------------
    */
    public function update(Request $request, Tournee $tournee): RedirectResponse
    {
        $this->autoriser($tournee);

        $tournee->update($this->valider($request));

        return redirect()
            ->route('logistique.tournees.show', $tournee)
            ->with('success', 'Tournée mise à jour avec succès.');
    }

    /*
    |------------------------------------------------------------------
    | DELETE /logistique/tournees/{tournee} — Supprimer une tournée (et ses missions)
    |------------------------------------------------------------------
    */
    public function destroy(Tournee $tournee): RedirectResponse
    {
        $this->autoriser($tournee);

        $tournee->delete();

        return redirect()
            ->route('logistique.tournees.index')
            ->with('success', 'Tournée supprimée.');
    }

    /** S'assurer que le collecteur connecté est propriétaire de la tournée */
    private function autoriser(Tournee $tournee): void
    {
        abort_if($tournee->user_id !== Auth::id(), 403, 'Accès non autorisé.');
    }

    /*
    |------------------------------------------------------------------
    | Validation commune création / édition
    |------------------------------------------------------------------
    */
    private function valider(Request $request): array
    {
        return $request->validate([
            'date'        => ['required', 'date'],
            'zone'        => ['required', 'string', 'max:255'],
            'vehicule'    => ['required', 'string', 'max:255'],
            'distance_km' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'statut'      => ['required', Rule::in(array_keys(Tournee::STATUTS))],
        ], [
            'date.required'       => 'La date de la tournée est obligatoire.',
            'date.date'           => "La date n'est pas valide.",
            'zone.required'       => 'La zone est obligatoire.',
            'vehicule.required'   => 'Le véhicule est obligatoire.',
            'distance_km.numeric' => 'La distance doit être un nombre.',
            'distance_km.min'     => 'La distance ne peut pas être négative.',
            'distance_km.max'     => 'La distance ne peut pas dépasser :max km.',
            'statut.required'     => 'Le statut est obligatoire.',
            'statut.in'           => 'Le statut sélectionné est invalide.',
        ]);
    }
}
