<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Logistique\AffectationAutomatique;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AffectationController extends Controller
{
    public function __construct(private AffectationAutomatique $affectation)
    {
    }

    /*
    |------------------------------------------------------------------
    | GET /admin/logistique/affectation — Proposition de répartition
    |------------------------------------------------------------------
    */
    public function show(Request $request): View
    {
        $date = $this->date($request);

        return view('admin.logistique.affectation', [
            'proposition' => $this->affectation->proposer($date),
        ]);
    }

    /*
    |------------------------------------------------------------------
    | POST /admin/logistique/affectation — Appliquer la répartition
    |------------------------------------------------------------------
    */
    public function store(Request $request): RedirectResponse
    {
        $request->validate(['empreinte' => ['required', 'string', 'size:32']]);
        $date = $this->date($request);

        $proposition = $this->affectation->proposer($date);

        // Commandes, dons ou tournées modifiés depuis l'aperçu : on montre la nouvelle proposition
        if (!hash_equals($proposition['empreinte'], $request->empreinte)) {
            return redirect()
                ->route('admin.logistique.affectation', ['date' => $date->format('Y-m-d')])
                ->with('error', 'Les travaux à planifier ont changé entre-temps : voici la proposition mise à jour.');
        }

        if ($proposition['total'] === 0) {
            return redirect()->route('admin.logistique.index')->with('error', 'Rien à affecter.');
        }

        $tournees = $this->affectation->appliquer($proposition);

        return redirect()
            ->route('admin.logistique.index', ['date' => $date->format('Y-m-d')])
            ->with('success', "{$proposition['total']} travaux répartis sur {$tournees->count()} tournée(s) du {$date->format('d/m/Y')}. Les collecteurs ont été prévenus.");
    }

    /** Date de planification : demain par défaut, jamais dans le passé */
    private function date(Request $request): Carbon
    {
        $date = $request->date('date') ?? today()->addDay();

        return $date->lt(today()) ? today() : $date->startOfDay();
    }
}
