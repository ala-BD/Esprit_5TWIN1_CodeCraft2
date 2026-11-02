<?php

namespace App\Http\Controllers\Logistique;

use App\Http\Controllers\Controller;
use App\Models\Tournee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Feuille de route imprimable d'une tournée : arrêts dans l'ordre, heures, contacts,
 * et une case de signature par arrêt — utile sans téléphone ou sans réseau.
 */
class FeuilleDeRouteController extends Controller
{
    /** GET /logistique/tournees/{tournee}/feuille-de-route — le collecteur, pour sa tournée */
    public function collecteur(Tournee $tournee): Response
    {
        abort_if($tournee->user_id !== Auth::id(), 403, 'Accès non autorisé.');

        return $this->pdf($tournee);
    }

    /** GET /admin/logistique/tournees/{tournee}/feuille-de-route — l'administrateur, pour toute tournée */
    public function admin(Tournee $tournee): Response
    {
        return $this->pdf($tournee);
    }

    private function pdf(Tournee $tournee): Response
    {
        $tournee->load(['user', 'missions.commande.user', 'missions.commande.lignes', 'missions.donVetement.user']);

        return Pdf::loadView('logistique.tournees.feuille-de-route', [
            'tournee' => $tournee,
            'depot'   => config('logistique.depot'),
        ])
            ->setPaper('a4')
            ->download('feuille-de-route-' . $tournee->date->format('Y-m-d') . '-' . \Illuminate\Support\Str::slug($tournee->zone) . '.pdf');
    }
}
