<?php

namespace App\Http\Controllers\Recyclage;

use App\Http\Controllers\Controller;
use App\Models\LotTextile;
use App\Models\Recycleur;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StatistiqueController extends Controller
{
    /*
    |------------------------------------------------------------------
    | GET /recyclage/statistiques — Tableau de bord statistiques
    |------------------------------------------------------------------
    */
    public function index(): View
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();

        $lots = LotTextile::where('recycleur_id', $recycleur->id);

        $stats = [
            'total_lots'     => (clone $lots)->count(),
            'en_attente'     => (clone $lots)->where('statut', 'EN_ATTENTE')->count(),
            'en_traitement'  => (clone $lots)->where('statut', 'EN_TRAITEMENT')->count(),
            'traites'        => (clone $lots)->where('statut', 'TRAITE')->count(),
            'certifies'      => (clone $lots)->where('statut', 'CERTIFIE')->count(),
            'total_kg'       => (clone $lots)->sum('poids_kg'),

            // Répartition par filière IA
            'filieres' => [
                'REVENTE'           => (clone $lots)->where('filiere_recommandee_ia', 'REVENTE')->count(),
                'UPCYCLING'         => (clone $lots)->where('filiere_recommandee_ia', 'UPCYCLING')->count(),
                'RECYCLAGE_FIBRE'   => (clone $lots)->where('filiere_recommandee_ia', 'RECYCLAGE_FIBRE')->count(),
                'RECYCLAGE_ENERGIE' => (clone $lots)->where('filiere_recommandee_ia', 'RECYCLAGE_ENERGIE')->count(),
            ],
        ];

        return view('recyclage.statistiques', compact('stats', 'recycleur'));
    }
}
