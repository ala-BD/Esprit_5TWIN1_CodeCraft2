<?php

namespace App\Http\Controllers\Recyclage;

use App\Http\Controllers\Controller;
use App\Models\EtapeTraitement;
use App\Models\LotTextile;
use App\Models\Recycleur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EtapeTraitementController extends Controller
{
    /*
    |------------------------------------------------------------------
    | POST /recyclage/lots/{lot}/etapes — Ajouter une étape
    |------------------------------------------------------------------
    */
    public function store(Request $request, LotTextile $lot): RedirectResponse
    {
        // Vérification propriétaire
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403);

        $request->validate([
            'type'             => ['required', 'in:RECEPTION,TRI,NETTOYAGE,TRAITEMENT,CONTROLE_QUALITE,EXPEDITION'],
            'date_debut'       => ['required', 'date'],
            'date_fin'         => ['nullable', 'date', 'after_or_equal:date_debut'],
            'resultat'         => ['nullable', 'string', 'max:500'],
            'poids_sortant_kg' => ['nullable', 'numeric', 'min:0'],
        ], [
            'type.required'       => "Le type d'étape est obligatoire.",
            'type.in'             => "Type d'étape invalide.",
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_fin.after_or_equal' => 'La date de fin doit être après la date de début.',
            'poids_sortant_kg.numeric' => 'Le poids sortant doit être un nombre.',
        ]);

        // Empêcher la duplication d'une étape
        $dejaExiste = $lot->etapeTraitements()->where('type', $request->type)->exists();
        if ($dejaExiste) {
            return back()->withErrors(['type' => 'Cette étape existe déjà pour ce lot.'])->withInput();
        }

        EtapeTraitement::create([
            'lot_textile_id'   => $lot->id,
            'type'             => $request->type,
            'date_debut'       => $request->date_debut,
            'date_fin'         => $request->date_fin,
            'resultat'         => $request->resultat,
            'poids_sortant_kg' => $request->poids_sortant_kg,
        ]);

        // Mettre à jour le statut du lot automatiquement
        $this->mettreAJourStatutLot($lot);

        $label = EtapeTraitement::LABELS[$request->type];
        return redirect()
            ->route('recyclage.lots.show', $lot)
            ->with('success', "Étape « {$label} » ajoutée avec succès.");
    }

    /*
    |------------------------------------------------------------------
    | PATCH /recyclage/etapes/{etape}/terminer — Terminer une étape
    |------------------------------------------------------------------
    */
    public function terminer(Request $request, EtapeTraitement $etape): RedirectResponse
    {
        $lot = $etape->lotTextile;
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403);

        $request->validate([
            'date_fin'         => ['required', 'date', 'after_or_equal:' . $etape->date_debut->format('Y-m-d')],
            'resultat'         => ['nullable', 'string', 'max:500'],
            'poids_sortant_kg' => ['nullable', 'numeric', 'min:0'],
        ]);

        $etape->update([
            'date_fin'         => $request->date_fin,
            'resultat'         => $request->resultat,
            'poids_sortant_kg' => $request->poids_sortant_kg,
        ]);

        $this->mettreAJourStatutLot($lot);

        return redirect()
            ->route('recyclage.lots.show', $lot)
            ->with('success', 'Étape marquée comme terminée.');
    }

    /*
    |------------------------------------------------------------------
    | Mise à jour automatique du statut du lot
    |------------------------------------------------------------------
    */
    private function mettreAJourStatutLot(LotTextile $lot): void
    {
        $etapes = $lot->etapeTraitements()->get();
        $nbTerminees = $etapes->whereNotNull('date_fin')->count();

        if ($etapes->where('type', 'EXPEDITION')->whereNotNull('date_fin')->count() > 0) {
            $lot->update(['statut' => 'TRAITE']);
        } elseif ($etapes->count() > 0) {
            $lot->update(['statut' => 'EN_TRAITEMENT']);
        }
    }
}
