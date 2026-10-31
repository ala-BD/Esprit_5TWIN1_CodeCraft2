<?php

namespace App\Http\Controllers\Recyclage;

use App\Http\Controllers\Controller;
use App\Models\EtapeTraitement;
use App\Models\LotHistorique;
use App\Models\LotTextile;
use App\Models\Recycleur;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EtapeTraitementController extends Controller
{
    /*
    |------------------------------------------------------------------
    | POST /recyclage/lots/{lot}/etapes — Ajouter une étape
    |------------------------------------------------------------------
    | Règles métier :
    |  - les étapes suivent le circuit de la filière, dans l'ordre ;
    |  - une étape ne commence qu'une fois la précédente terminée ;
    |  - une étape ne commence pas avant la fin de la précédente ;
    |  - aucune date dans le futur ; aucun ajout sur un lot certifié.
    */
    public function store(Request $request, LotTextile $lot): RedirectResponse
    {
        $this->autoriser($lot);
        $lot->load('etapeTraitements');

        if ($lot->statut === LotTextile::STATUT_CERTIFIE) {
            return back()->withErrors(['type' => 'Ce lot est certifié : ses étapes ne peuvent plus être modifiées.']);
        }
        if ($enCours = $lot->etape_en_cours) {
            return back()->withErrors(['type' => "Terminez d'abord l'étape « {$enCours->label} » avant d'en commencer une nouvelle."])->withInput();
        }

        $attendue  = $lot->prochaine_etape;
        $debutMin  = $lot->debut_minimum_prochaine_etape;
        $maintenant = now()->format('Y-m-d H:i:s');

        $request->validate([
            'type'             => ['required', Rule::in(array_keys(EtapeTraitement::ORDRE))],
            'date_debut'       => array_filter(['required', 'date', 'before_or_equal:' . $maintenant,
                                                $debutMin ? 'after_or_equal:' . $debutMin->format('Y-m-d H:i:s') : null]),
            'date_fin'         => ['nullable', 'date', 'after_or_equal:date_debut', 'before_or_equal:' . $maintenant],
            'resultat'         => ['nullable', 'string', 'max:500'],
            'poids_sortant_kg' => ['nullable', 'numeric', 'min:0'],
        ], [
            'type.required'              => "Le type d'étape est obligatoire.",
            'type.in'                    => "Type d'étape invalide.",
            'date_debut.required'        => 'La date de début est obligatoire.',
            'date_debut.before_or_equal' => 'La date de début ne peut pas être dans le futur.',
            'date_debut.after_or_equal'  => 'L\'étape ne peut pas commencer avant la fin de l\'étape précédente ('
                                            . $debutMin?->format('d/m/Y H:i') . ').',
            'date_fin.after_or_equal'    => 'La date de fin doit être après la date de début.',
            'date_fin.before_or_equal'   => 'La date de fin ne peut pas être dans le futur.',
            'poids_sortant_kg.numeric'   => 'Le poids sortant doit être un nombre.',
        ]);

        if ($request->type !== $attendue) {
            $message = $lot->etapeTraitements->contains('type', $request->type)
                ? 'Cette étape existe déjà pour ce lot.'
                : ($attendue
                    ? 'Les étapes doivent suivre l\'ordre du circuit : prochaine étape attendue « ' . EtapeTraitement::LABELS[$attendue] . ' ».'
                    : 'Toutes les étapes du circuit sont déjà enregistrées.');

            return back()->withErrors(['type' => $message])->withInput();
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
        $lot->actualiserStatut();

        $label = EtapeTraitement::LABELS[$request->type];

        LotHistorique::enregistrer(
            $lot,
            LotHistorique::ETAPE_AJOUTEE,
            "Étape « {$label} » démarrée le " . Carbon::parse($request->date_debut)->format('d/m/Y H:i')
        );

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
        $this->autoriser($lot);

        if ($lot->statut === LotTextile::STATUT_CERTIFIE) {
            return back()->withErrors(['date_fin' => 'Ce lot est certifié : ses étapes ne peuvent plus être modifiées.']);
        }
        if ($etape->est_terminee) {
            return back()->withErrors(['date_fin' => "L'étape « {$etape->label} » est déjà terminée."]);
        }

        $request->validate([
            'date_fin'         => ['required', 'date',
                                   'after_or_equal:' . $etape->date_debut->format('Y-m-d H:i:s'),
                                   'before_or_equal:' . now()->format('Y-m-d H:i:s')],
            'resultat'         => ['nullable', 'string', 'max:500'],
            'poids_sortant_kg' => ['nullable', 'numeric', 'min:0'],
        ], [
            'date_fin.required'        => 'La date de fin est obligatoire.',
            'date_fin.after_or_equal'  => 'La date de fin doit être après le début de l\'étape ('
                                          . $etape->date_debut->format('d/m/Y H:i') . ').',
            'date_fin.before_or_equal' => 'La date de fin ne peut pas être dans le futur.',
        ]);

        $etape->update([
            'date_fin'         => $request->date_fin,
            'resultat'         => $request->resultat,
            'poids_sortant_kg' => $request->poids_sortant_kg,
        ]);

        $lot->actualiserStatut();

        LotHistorique::enregistrer(
            $lot,
            LotHistorique::ETAPE_TERMINEE,
            "Étape « {$etape->label} » terminée le " . Carbon::parse($request->date_fin)->format('d/m/Y H:i')
            . ($request->poids_sortant_kg ? " — {$request->poids_sortant_kg} kg sortants" : '')
        );

        return redirect()
            ->route('recyclage.lots.show', $lot)
            ->with('success', 'Étape marquée comme terminée.');
    }

    private function autoriser(LotTextile $lot): void
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403);
    }
}
