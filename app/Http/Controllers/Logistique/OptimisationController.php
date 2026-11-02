<?php

namespace App\Http\Controllers\Logistique;

use App\Http\Controllers\Controller;
use App\Models\Tournee;
use App\Services\Logistique\ExplicationItineraire;
use App\Services\Logistique\OptimiseurItineraire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OptimisationController extends Controller
{
    public function __construct(
        private OptimiseurItineraire $optimiseur,
        private ExplicationItineraire $explication,
    ) {
    }

    /*
    |------------------------------------------------------------------
    | GET /logistique/tournees/{tournee}/optimisation — Aperçu de l'itinéraire optimisé
    |------------------------------------------------------------------
    */
    public function show(Tournee $tournee): View|RedirectResponse
    {
        $this->autoriser($tournee);

        $proposition = $this->optimiseur->proposer($tournee);

        if (!$proposition['optimisable']) {
            return redirect()
                ->route('logistique.tournees.show', $tournee)
                ->with('error', "Aucun arrêt restant n'a de position sur la carte : rien à optimiser.");
        }

        return view('logistique.tournees.optimisation', [
            'tournee'     => $tournee,
            'proposition' => $proposition,
            'explication' => $this->explication->expliquer($tournee, $proposition),
        ]);
    }

    /*
    |------------------------------------------------------------------
    | POST /logistique/tournees/{tournee}/optimisation — Appliquer le nouvel ordre
    |------------------------------------------------------------------
    */
    public function store(Request $request, Tournee $tournee): RedirectResponse
    {
        $this->autoriser($tournee);

        $request->validate(['empreinte' => ['required', 'string', 'size:32']]);

        $proposition = $this->optimiseur->proposer($tournee);

        // La tournée a bougé depuis l'aperçu (arrêt terminé, ajouté…) : on montre la nouvelle proposition
        if (!hash_equals($proposition['empreinte'], $request->empreinte)) {
            return redirect()
                ->route('logistique.tournees.optimisation', $tournee)
                ->with('error', 'La tournée a changé entre-temps : voici la proposition mise à jour.');
        }

        $this->optimiseur->appliquer($tournee, $proposition, $this->explication->expliquer($tournee, $proposition));

        $message = $proposition['gain_km'] > 0.05
            ? "Itinéraire optimisé : {$proposition['gain_km']} km économisés ({$proposition['gain_pct']} %)."
            : 'Itinéraire recalculé : l\'ordre était déjà le meilleur, les heures de passage ont été mises à jour.';

        return redirect()->route('logistique.tournees.show', $tournee)->with('success', $message);
    }

    private function autoriser(Tournee $tournee): void
    {
        abort_if($tournee->user_id !== Auth::id(), 403, 'Accès non autorisé.');
    }
}
