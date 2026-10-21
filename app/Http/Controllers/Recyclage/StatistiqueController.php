<?php

namespace App\Http\Controllers\Recyclage;

use App\Http\Controllers\Controller;
use App\Models\LotTextile;
use App\Models\Recycleur;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            'total_lots'    => (clone $lots)->count(),
            'en_attente'    => (clone $lots)->where('statut', 'EN_ATTENTE')->count(),
            'en_traitement' => (clone $lots)->where('statut', 'EN_TRAITEMENT')->count(),
            'traites'       => (clone $lots)->where('statut', 'TRAITE')->count(),
            'certifies'     => (clone $lots)->where('statut', 'CERTIFIE')->count(),
            'total_kg'      => (clone $lots)->sum('poids_kg'),

            // Répartition par filière IA
            'filieres' => [
                'REVENTE'           => (clone $lots)->where('filiere_recommandee_ia', 'REVENTE')->count(),
                'UPCYCLING'         => (clone $lots)->where('filiere_recommandee_ia', 'UPCYCLING')->count(),
                'RECYCLAGE_FIBRE'   => (clone $lots)->where('filiere_recommandee_ia', 'RECYCLAGE_FIBRE')->count(),
                'RECYCLAGE_ENERGIE' => (clone $lots)->where('filiere_recommandee_ia', 'RECYCLAGE_ENERGIE')->count(),
            ],
        ];

        // ----------------------------------------------------------------
        // Données pour graphiques — 12 derniers mois
        // ----------------------------------------------------------------

        // Évolution du nombre de lots par mois
        $evolutionLots = $this->evolutionParMois($recycleur->id, 'COUNT(*)', 'nb_lots');

        // Évolution du poids traité par mois
        $evolutionPoids = $this->evolutionParMois($recycleur->id, 'SUM(poids_kg)', 'poids_total');

        // Labels des 12 derniers mois
        $labelsM = collect();
        for ($i = 11; $i >= 0; $i--) {
            $labelsM->push(now()->subMonths($i)->translatedFormat('M Y'));
        }

        // Construire les séries indexées par mois
        $seriesLots  = $this->buildSerie($evolutionLots,  'nb_lots');
        $seriesPoids = $this->buildSerie($evolutionPoids, 'poids_total');

        // CO₂ cumulé par mois (basé sur poids × 2.4)
        $seriesCo2 = array_map(fn($p) => round($p * 2.4, 1), $seriesPoids);

        // Répartition par statut pour chart donut
        $statutsLabels = ['En attente', 'En traitement', 'Traité', 'Certifié'];
        $statutsData   = [
            $stats['en_attente'],
            $stats['en_traitement'],
            $stats['traites'],
            $stats['certifies'],
        ];

        // Répartition filières pour chart donut
        $filieresLabels = ['Revente', 'Upcycling', 'Recyclage fibre', 'Recyclage énergie'];
        $filieresData   = array_values($stats['filieres']);

        return view('recyclage.statistiques', compact(
            'stats', 'recycleur',
            'labelsM', 'seriesLots', 'seriesPoids', 'seriesCo2',
            'statutsLabels', 'statutsData',
            'filieresLabels', 'filieresData'
        ));
    }

    /*
    |------------------------------------------------------------------
    | Helpers privés
    |------------------------------------------------------------------
    */

    /** Requête groupée par mois sur les 12 derniers mois */
    private function evolutionParMois(int $recycleurId, string $aggregate, string $alias): \Illuminate\Support\Collection
    {
        return DB::table('lot_textiles')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mois, {$aggregate} as {$alias}")
            ->where('recycleur_id', $recycleurId)
            ->where('created_at', '>=', now()->subMonths(12)->startOfMonth())
            ->groupBy('mois')
            ->orderBy('mois')
            ->get()
            ->keyBy('mois');
    }

    /** Construit un tableau de 12 valeurs à partir des résultats groupés */
    private function buildSerie(\Illuminate\Support\Collection $data, string $field): array
    {
        $serie = [];
        for ($i = 11; $i >= 0; $i--) {
            $key   = now()->subMonths($i)->format('Y-m');
            $serie[] = (float) ($data->get($key)?->$field ?? 0);
        }
        return $serie;
    }
}
