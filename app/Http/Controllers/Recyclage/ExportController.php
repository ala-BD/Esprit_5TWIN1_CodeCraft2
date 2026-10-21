<?php

namespace App\Http\Controllers\Recyclage;

use App\Http\Controllers\Controller;
use App\Models\LotTextile;
use App\Models\Recycleur;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /*
    |------------------------------------------------------------------
    | GET /recyclage/lots/export — Exporter les lots (Excel stylé ou CSV)
    |------------------------------------------------------------------
    */
    public function exportLots(Request $request): Response|StreamedResponse
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();

        // Appliquer les mêmes filtres que l'index
        $query = LotTextile::where('recycleur_id', $recycleur->id)
            ->with(['etapeTraitements', 'passeportNumerique', 'donVetement']);

        if ($request->filled('q')) {
            $q = '%' . $request->q . '%';
            $query->where(fn($sub) =>
                $sub->where('reference', 'like', $q)
                    ->orWhere('composition', 'like', $q)
                    ->orWhere('origine', 'like', $q)
            );
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('filiere')) {
            $query->where('filiere_recommandee_ia', $request->filiere);
        }
        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }
        if ($request->filled('poids_min')) {
            $query->where('poids_kg', '>=', (float) $request->poids_min);
        }
        if ($request->filled('poids_max')) {
            $query->where('poids_kg', '<=', (float) $request->poids_max);
        }

        $lots = $query->orderByDesc('created_at')->get();

        $format = $request->get('format', 'excel');

        // Logo en base64 pour intégration directe dans Excel
        $logoPath = public_path('images/logo.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

        // Si format CSV brut demandé
        if ($format === 'csv') {
            $filename = 'lots-recyclage-' . now()->format('Y-m-d') . '.csv';
            $headers = [
                'Référence', 'Poids (kg)', 'Composition', 'Origine', 'Filière IA',
                'Statut', 'Progression (%)', 'Nb étapes', 'Don rattaché', 'Passeport QR',
                'CO₂ évité (kg)', 'Eau économisée (L)', 'Date création', 'Dernière MAJ',
            ];

            return response()->stream(function () use ($lots, $headers) {
                $handle = fopen('php://output', 'w');
                fwrite($handle, "\xEF\xBB\xBF");
                fputcsv($handle, $headers, ';');
                foreach ($lots as $lot) {
                    $co2 = round($lot->poids_kg * 2.4, 2);
                    $eau = round($lot->poids_kg * 3000, 0);
                    fputcsv($handle, [
                        $lot->reference,
                        $lot->poids_kg,
                        $lot->composition,
                        $lot->origine,
                        $this->filiereLabel($lot->filiere_recommandee_ia),
                        $this->statutLabel($lot->statut),
                        $lot->progression,
                        $lot->etapeTraitements->count(),
                        $lot->donVetement ? "Don n°{$lot->donVetement->id} ({$lot->donVetement->type})" : '',
                        $lot->passeportNumerique?->qr_code ?? '',
                        $co2,
                        $eau,
                        $lot->created_at->format('d/m/Y H:i'),
                        $lot->updated_at->format('d/m/Y H:i'),
                    ], ';');
                }
                fclose($handle);
            }, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-store, no-cache',
            ]);
        }

        // Par défaut : Template Excel professionnel stylisé (.xls)
        $filename = 'inventaire-lots-retiss-' . now()->format('Y-m-d') . '.xls';

        $content = view('recyclage.exports.lots-excel', compact('lots', 'recycleur', 'logoBase64'))->render();

        return response($content, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
            'Pragma'              => 'public',
        ]);
    }

    /*
    |------------------------------------------------------------------
    | GET /recyclage/statistiques/export — Exporter le bilan stats (Excel)
    |------------------------------------------------------------------
    */
    public function exportStatistiques(Request $request): Response|StreamedResponse
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();

        $base    = fn() => LotTextile::where('recycleur_id', $recycleur->id);
        $totalKg = ($base)()->sum('poids_kg');

        $stats = [
            'total_lots'    => ($base)()->count(),
            'en_attente'    => ($base)()->where('statut', 'EN_ATTENTE')->count(),
            'en_traitement' => ($base)()->where('statut', 'EN_TRAITEMENT')->count(),
            'traites'       => ($base)()->where('statut', 'TRAITE')->count(),
            'certifies'     => ($base)()->where('statut', 'CERTIFIE')->count(),
            'total_kg'      => $totalKg,
            'filieres' => [
                'REVENTE'           => ($base)()->where('filiere_recommandee_ia', 'REVENTE')->count(),
                'UPCYCLING'         => ($base)()->where('filiere_recommandee_ia', 'UPCYCLING')->count(),
                'RECYCLAGE_FIBRE'   => ($base)()->where('filiere_recommandee_ia', 'RECYCLAGE_FIBRE')->count(),
                'RECYCLAGE_ENERGIE' => ($base)()->where('filiere_recommandee_ia', 'RECYCLAGE_ENERGIE')->count(),
            ],
        ];

        // Évolution sur les 12 mois
        $labelsM     = [];
        $seriesLots  = [];
        $seriesPoids = [];
        $seriesCo2   = [];

        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $labelsM[]     = $m->translatedFormat('M Y');
            $lotsM         = LotTextile::where('recycleur_id', $recycleur->id)
                                ->whereYear('created_at', $m->year)
                                ->whereMonth('created_at', $m->month);
            $seriesLots[]  = (clone $lotsM)->count();
            $pds           = (clone $lotsM)->sum('poids_kg');
            $seriesPoids[] = $pds;
            $seriesCo2[]   = round($pds * 2.4, 1);
        }

        $format = $request->get('format', 'excel');

        // Si CSV brut demandé
        if ($format === 'csv') {
            $csvRows = [
                ['Indicateur', 'Valeur'],
                ['=== PROFIL RECYCLEUR ===', ''],
                ['Nom', $recycleur->nom],
                ['Agrément', $recycleur->agrement],
                ['Localisation', $recycleur->localisation],
                ['Capacité (kg)', $recycleur->capacite_kg],
                ['=== STATISTIQUES LOTS ===', ''],
                ['Total lots', $stats['total_lots']],
                ['Total kg traités', $stats['total_kg']],
                ['Lots certifiés', $stats['certifies']],
                ['=== IMPACT ÉCOLOGIQUE ===', ''],
                ['CO₂ évité (kg)', round($totalKg * 2.4, 1)],
                ['Eau économisée (L)', round($totalKg * 3000, 0)],
            ];

            return response()->stream(function () use ($csvRows) {
                $handle = fopen('php://output', 'w');
                fwrite($handle, "\xEF\xBB\xBF");
                foreach ($csvRows as $r) {
                    fputcsv($handle, $r, ';');
                }
                fclose($handle);
            }, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="statistiques-recyclage-' . now()->format('Y-m-d') . '.csv"',
            ]);
        }

        // Template Excel professionnel stylisé
        $logoPath = public_path('images/logo.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

        $filename = 'bilan-activite-retiss-' . now()->format('Y-m-d') . '.xls';

        $content = view('recyclage.exports.statistiques-excel', compact(
            'recycleur', 'stats', 'labelsM', 'seriesLots', 'seriesPoids', 'seriesCo2', 'logoBase64'
        ))->render();

        return response($content, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
            'Pragma'              => 'public',
        ]);
    }

    /*
    |------------------------------------------------------------------
    | GET /recyclage/statistiques/export-pdf — Exporter le bilan stats en PDF
    |------------------------------------------------------------------
    */
    public function exportStatistiquesPdf()
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();

        $base    = fn() => LotTextile::where('recycleur_id', $recycleur->id);
        $totalKg = ($base)()->sum('poids_kg');

        $stats = [
            'total_lots'    => ($base)()->count(),
            'en_attente'    => ($base)()->where('statut', 'EN_ATTENTE')->count(),
            'en_traitement' => ($base)()->where('statut', 'EN_TRAITEMENT')->count(),
            'traites'       => ($base)()->where('statut', 'TRAITE')->count(),
            'certifies'     => ($base)()->where('statut', 'CERTIFIE')->count(),
            'total_kg'      => $totalKg,
            'filieres' => [
                'REVENTE'           => ($base)()->where('filiere_recommandee_ia', 'REVENTE')->count(),
                'UPCYCLING'         => ($base)()->where('filiere_recommandee_ia', 'UPCYCLING')->count(),
                'RECYCLAGE_FIBRE'   => ($base)()->where('filiere_recommandee_ia', 'RECYCLAGE_FIBRE')->count(),
                'RECYCLAGE_ENERGIE' => ($base)()->where('filiere_recommandee_ia', 'RECYCLAGE_ENERGIE')->count(),
            ],
        ];

        // Évolution sur les 12 mois
        $labelsM     = [];
        $seriesLots  = [];
        $seriesPoids = [];
        $seriesCo2   = [];

        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $labelsM[]     = $m->translatedFormat('M Y');
            $lotsM         = LotTextile::where('recycleur_id', $recycleur->id)
                                ->whereYear('created_at', $m->year)
                                ->whereMonth('created_at', $m->month);
            $seriesLots[]  = (clone $lotsM)->count();
            $pds           = (clone $lotsM)->sum('poids_kg');
            $seriesPoids[] = $pds;
            $seriesCo2[]   = round($pds * 2.4, 1);
        }

        $logoPath = public_path('images/logo.png');
        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

        $pdf = Pdf::loadView('recyclage.exports.statistiques-pdf', compact(
            'recycleur', 'stats', 'labelsM', 'seriesLots', 'seriesPoids', 'seriesCo2', 'logoBase64'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('bilan-recyclage-' . now()->format('Y-m-d') . '.pdf');
    }

    /*
    |------------------------------------------------------------------
    | Helpers labels
    |------------------------------------------------------------------
    */
    private function filiereLabel(string $filiere): string
    {
        return match($filiere) {
            'REVENTE'           => 'Revente',
            'UPCYCLING'         => 'Upcycling',
            'RECYCLAGE_FIBRE'   => 'Recyclage fibre',
            'RECYCLAGE_ENERGIE' => 'Recyclage énergie',
            default             => $filiere,
        };
    }

    private function statutLabel(string $statut): string
    {
        return match($statut) {
            'EN_ATTENTE'    => 'En attente',
            'EN_TRAITEMENT' => 'En traitement',
            'TRAITE'        => 'Traité',
            'CERTIFIE'      => 'Certifié',
            default         => $statut,
        };
    }
}
