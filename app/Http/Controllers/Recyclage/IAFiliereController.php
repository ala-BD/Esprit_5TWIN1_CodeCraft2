<?php

namespace App\Http\Controllers\Recyclage;

use App\Http\Controllers\Controller;
use App\Models\LotHistorique;
use App\Models\LotTextile;
use App\Models\Recycleur;
use App\Services\Recyclage\FiliereIA;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class IAFiliereController extends Controller
{
    /*
    |------------------------------------------------------------------
    | GET /recyclage/ia — Performance du modèle IA
    |------------------------------------------------------------------
    */
    public function index(FiliereIA $ia): View
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();

        $lots = LotTextile::where('recycleur_id', $recycleur->id);
        $valides = (clone $lots)->whereNotNull('filiere_validee')->get();

        // Taux d'accord entre l'IA et les décisions réelles du recycleur
        $accord = [
            'total'      => $valides->count(),
            'confirmees' => $valides->filter(fn ($l) => $l->filiere_validee === $l->filiere_recommandee_ia)->count(),
        ];
        $accord['taux'] = $accord['total'] ? round($accord['confirmees'] / $accord['total'] * 100) : null;

        $sources = [
            'modele' => (clone $lots)->where('source_ia', 'MODELE')->count(),
            'regles' => (clone $lots)->where(fn ($q) => $q->where('source_ia', 'REGLES')->orWhereNull('source_ia'))->count(),
        ];

        $totalDecisions = LotTextile::whereNotNull('filiere_validee')->count();

        return view('recyclage.ia.index', [
            'metriques'      => $ia->metriques(),
            'accord'         => $accord,
            'sources'        => $sources,
            'totalDecisions' => $totalDecisions,
        ]);
    }

    /*
    |------------------------------------------------------------------
    | POST /recyclage/ia/entrainer — Réentraîner avec les décisions réelles
    |------------------------------------------------------------------
    */
    public function entrainer(FiliereIA $ia): RedirectResponse
    {
        Recycleur::where('user_id', Auth::id())->firstOrFail();

        try {
            $resultat = $ia->entrainer();
        } catch (Throwable $e) {
            return back()->with('error', "Réentraînement impossible : le service IA est indisponible.");
        }

        $m = $resultat['metriques'];

        return redirect()
            ->route('recyclage.ia.index')
            ->with('success', "Modèle v{$m['version']} entraîné ({$m['algorithme']}) avec {$resultat['retours_utilises']} décision(s) réelle(s) — précision : "
                . round($m['test']['precision'] * 100, 1) . ' %');
    }

    /*
    |------------------------------------------------------------------
    | POST /recyclage/lots/{lot}/filiere/valider — Décision du recycleur
    |------------------------------------------------------------------
    */
    public function valider(Request $request, LotTextile $lot): RedirectResponse
    {
        $this->autoriser($lot);

        $request->validate([
            'filiere' => ['required', Rule::in(LotTextile::FILIERES)],
        ], [
            'filiere.required' => 'Veuillez choisir une filière.',
            'filiere.in'       => 'Filière invalide.',
        ]);

        $ancienne = $lot->filiere_validee;
        $lot->update([
            'filiere_validee'    => $request->filiere,
            'filiere_validee_le' => now(),
        ]);
        $lot->actualiserStatut();   // le circuit d'étapes dépend de la filière

        $confirme = $request->filiere === $lot->filiere_recommandee_ia;
        LotHistorique::enregistrer(
            $lot,
            LotHistorique::FILIERE_VALIDEE,
            $confirme
                ? 'Recommandation IA confirmée : ' . LotTextile::libelleFiliere($request->filiere)
                : 'Filière corrigée par le recycleur : ' . LotTextile::libelleFiliere($request->filiere)
                  . ' (IA : ' . LotTextile::libelleFiliere($lot->filiere_recommandee_ia) . ')',
            'filiere_validee', $ancienne, $request->filiere
        );

        return redirect()
            ->route('recyclage.lots.show', $lot)
            ->with('success', $confirme
                ? 'Recommandation IA confirmée. Merci, cette décision servira au prochain entraînement.'
                : 'Correction enregistrée. Le modèle apprendra de cette décision au prochain entraînement.');
    }

    /*
    |------------------------------------------------------------------
    | POST /recyclage/lots/{lot}/filiere/recalculer — Nouvelle prédiction
    |------------------------------------------------------------------
    */
    public function recalculer(LotTextile $lot, FiliereIA $ia): RedirectResponse
    {
        $this->autoriser($lot);

        $ancienne = $lot->filiere_recommandee_ia;
        $lot->update($ia->predire($lot));
        $lot->actualiserStatut();

        LotHistorique::enregistrer(
            $lot,
            LotHistorique::IA_PREDICTION,
            'Prédiction IA recalculée : ' . LotTextile::libelleFiliere($lot->filiere_recommandee_ia)
                . ($lot->score_confiance_ia !== null ? ' (' . round($lot->score_confiance_ia * 100) . ' %)' : ' (règles métier)'),
            'filiere_recommandee_ia', $ancienne, $lot->filiere_recommandee_ia
        );

        return redirect()
            ->route('recyclage.lots.show', $lot)
            ->with($lot->source_ia === 'MODELE' ? 'success' : 'error', $lot->source_ia === 'MODELE'
                ? 'Prédiction IA mise à jour.'
                : 'Service IA indisponible : recommandation calculée par les règles métier.');
    }

    private function autoriser(LotTextile $lot): void
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        abort_if($lot->recycleur_id !== $recycleur->id, 403);
    }
}
