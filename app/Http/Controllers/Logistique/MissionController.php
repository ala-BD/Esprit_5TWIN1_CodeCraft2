<?php

namespace App\Http\Controllers\Logistique;

use App\Http\Controllers\Controller;
use App\Models\DonVetement;
use App\Models\Mission;
use App\Models\Tournee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MissionController extends Controller
{
    /*
    |------------------------------------------------------------------
    | GET /logistique/tournees/{tournee}/missions/create — Formulaire création
    |------------------------------------------------------------------
    */
    public function create(Tournee $tournee): View
    {
        $this->autoriser($tournee);

        $mission = new Mission([
            'type'   => Mission::TYPE_COLLECTE,
            'statut' => Mission::STATUT_A_FAIRE,
            'ordre'  => (int) $tournee->missions()->max('ordre') + 1,
        ]);

        return view('logistique.missions.create', [
            'tournee' => $tournee,
            'mission' => $mission,
            'dons'    => $this->donOptions(),
        ]);
    }

    /*
    |------------------------------------------------------------------
    | POST /logistique/tournees/{tournee}/missions — Enregistrer une mission
    |------------------------------------------------------------------
    */
    public function store(Request $request, Tournee $tournee): RedirectResponse
    {
        $this->autoriser($tournee);

        $tournee->missions()->create($this->valider($request));

        return redirect()
            ->route('logistique.tournees.show', $tournee)
            ->with('success', 'Mission ajoutée à la tournée.');
    }

    /*
    |------------------------------------------------------------------
    | GET /logistique/missions/{mission}/edit — Formulaire édition
    |------------------------------------------------------------------
    */
    public function edit(Mission $mission): View
    {
        $this->autoriser($mission->tournee);

        return view('logistique.missions.edit', [
            'tournee' => $mission->tournee,
            'mission' => $mission,
            'dons'    => $this->donOptions(),
        ]);
    }

    /*
    |------------------------------------------------------------------
    | PUT /logistique/missions/{mission} — Mettre à jour une mission
    |------------------------------------------------------------------
    */
    public function update(Request $request, Mission $mission): RedirectResponse
    {
        $this->autoriser($mission->tournee);

        $mission->update($this->valider($request));

        return redirect()
            ->route('logistique.tournees.show', $mission->tournee)
            ->with('success', 'Mission mise à jour avec succès.');
    }

    /*
    |------------------------------------------------------------------
    | DELETE /logistique/missions/{mission} — Supprimer une mission
    |------------------------------------------------------------------
    */
    public function destroy(Mission $mission): RedirectResponse
    {
        $this->autoriser($mission->tournee);

        $tournee = $mission->tournee;
        $mission->delete();

        return redirect()
            ->route('logistique.tournees.show', $tournee)
            ->with('success', 'Mission supprimée.');
    }

    /** S'assurer que le collecteur connecté est propriétaire de la tournée */
    private function autoriser(Tournee $tournee): void
    {
        abort_if($tournee->user_id !== Auth::id(), 403, 'Accès non autorisé.');
    }

    /** Dons proposés dans la liste déroulante : id => libellé */
    private function donOptions(): array
    {
        return ['' => 'Aucun don lié'] + DonVetement::latest()->take(100)->get()
            ->mapWithKeys(fn (DonVetement $don) => [$don->id => "Don n° {$don->id} — {$don->type}, {$don->matiere}"])
            ->all();
    }

    /*
    |------------------------------------------------------------------
    | Validation commune création / édition
    |------------------------------------------------------------------
    */
    private function valider(Request $request): array
    {
        return $request->validate([
            'type'             => ['required', Rule::in(array_keys(Mission::TYPES))],
            'adresse'          => ['required', 'string', 'max:255'],
            'ordre'            => ['required', 'integer', 'min:1', 'max:999'],
            'heure_prevue'     => ['required', 'date_format:H:i'],
            'statut'           => ['required', Rule::in(array_keys(Mission::STATUTS))],
            'preuve_livraison' => ['nullable', 'string', 'max:255'],
            'don_vetement_id'  => ['nullable', 'integer', 'exists:don_vetements,id'],
        ], [
            'type.required'            => 'Le type de mission est obligatoire.',
            'type.in'                  => 'Le type sélectionné est invalide.',
            'adresse.required'         => "L'adresse est obligatoire.",
            'ordre.required'           => "L'ordre de passage est obligatoire.",
            'ordre.integer'            => "L'ordre de passage doit être un nombre entier.",
            'ordre.min'                => "L'ordre de passage commence à 1.",
            'heure_prevue.required'    => "L'heure prévue est obligatoire.",
            'heure_prevue.date_format' => "L'heure prévue doit être au format HH:MM.",
            'statut.required'          => 'Le statut est obligatoire.',
            'statut.in'                => 'Le statut sélectionné est invalide.',
            'don_vetement_id.exists'   => "Le don sélectionné n'existe pas.",
        ]);
    }
}
