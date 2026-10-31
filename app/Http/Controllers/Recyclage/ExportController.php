<?php

namespace App\Http\Controllers\Recyclage;

use App\Http\Controllers\Controller;
use App\Models\LotTextile;
use App\Models\Recycleur;
use App\Services\Recyclage\ExportExcel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /** Facteurs d'impact (identiques au passeport numérique) */
    const CO2_PAR_KG = 2.4;
    const EAU_PAR_KG = 3000;

    /*
    |------------------------------------------------------------------
    | GET /recyclage/lots/export — Inventaire des lots (Excel .xlsx ou CSV)
    |------------------------------------------------------------------
    */
    public function exportLots(Request $request, ExportExcel $excel): Response
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        $lots = $this->lotsFiltres($request, $recycleur);

        if ($request->get('format') === 'csv') {
            return $this->csv('inventaire-lots-' . now()->format('Y-m-d') . '.csv', $this->lignesCsvLots($lots));
        }

        return $excel->telecharger(
            $excel->inventaireLots($lots, $recycleur, $this->descriptionFiltres($request)),
            'inventaire-lots-retiss-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /*
    |------------------------------------------------------------------
    | GET /recyclage/statistiques/export — Bilan d'activité (Excel .xlsx ou CSV)
    |------------------------------------------------------------------
    */
    public function exportStatistiques(Request $request, ExportExcel $excel): Response
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();
        $bilan = $this->bilan($recycleur);

        if ($request->get('format') === 'csv') {
            return $this->csv('bilan-recyclage-' . now()->format('Y-m-d') . '.csv', $this->lignesCsvBilan($recycleur, $bilan));
        }

        return $excel->telecharger(
            $excel->bilan($recycleur, $bilan),
            'bilan-activite-retiss-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    /*
    |------------------------------------------------------------------
    | GET /recyclage/statistiques/export-pdf — Bilan d'activité (PDF)
    |------------------------------------------------------------------
    */
    public function exportStatistiquesPdf()
    {
        $recycleur = Recycleur::where('user_id', Auth::id())->firstOrFail();

        return Pdf::loadView('recyclage.exports.statistiques-pdf', [
            'recycleur' => $recycleur,
            'bilan'     => $this->bilan($recycleur),
        ])->setOption('isFontSubsettingEnabled', true)
          ->setPaper('a4', 'portrait')
          ->download('bilan-recyclage-' . now()->format('Y-m-d') . '.pdf');
    }

    /*
    |------------------------------------------------------------------
    | Données
    |------------------------------------------------------------------
    */

    /** Lots du recycleur avec les mêmes filtres que la liste */
    private function lotsFiltres(Request $request, Recycleur $recycleur): Collection
    {
        $query = LotTextile::where('recycleur_id', $recycleur->id)
            ->with(['etapeTraitements', 'passeportNumerique', 'donVetement']);

        if ($request->filled('q')) {
            $q = '%' . $request->q . '%';
            $query->where(fn ($sub) => $sub->where('reference', 'like', $q)
                ->orWhere('composition', 'like', $q)
                ->orWhere('origine', 'like', $q));
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

        return $query->orderByDesc('created_at')->get();
    }

    private function descriptionFiltres(Request $request): string
    {
        $f = [];
        if ($request->filled('q'))          $f[] = "recherche « {$request->q} »";
        if ($request->filled('statut'))     $f[] = 'statut ' . self::statutLabel($request->statut);
        if ($request->filled('filiere'))    $f[] = 'filière ' . LotTextile::libelleFiliere($request->filiere);
        if ($request->filled('date_debut')) $f[] = 'depuis le ' . date('d/m/Y', strtotime($request->date_debut));
        if ($request->filled('date_fin'))   $f[] = "jusqu'au " . date('d/m/Y', strtotime($request->date_fin));
        if ($request->filled('poids_min'))  $f[] = "≥ {$request->poids_min} kg";
        if ($request->filled('poids_max'))  $f[] = "≤ {$request->poids_max} kg";

        return $f ? 'Filtres : ' . implode(' · ', $f) : 'Tous les lots';
    }

    /**
     * Indicateurs du bilan d'activité + évolution sur les 12 derniers mois.
     * Les mois partent du 1er du mois (évite le décalage des fins de mois : 31/10 − 1 mois ≠ 31/11).
     */
    private function bilan(Recycleur $recycleur): array
    {
        $lots = LotTextile::where('recycleur_id', $recycleur->id)->get(['poids_kg', 'statut', 'filiere_recommandee_ia', 'created_at']);
        $totalKg = (float) $lots->sum('poids_kg');

        $mois = [];
        $debut = now()->startOfMonth();
        for ($i = 11; $i >= 0; $i--) {
            $m = $debut->copy()->subMonths($i);
            $duMois = $lots->filter(fn ($l) => $l->created_at->format('Y-m') === $m->format('Y-m'));
            $kg = (float) $duMois->sum('poids_kg');
            $mois[] = [
                'label' => ucfirst($m->translatedFormat('M Y')),
                'lots'  => $duMois->count(),
                'kg'    => $kg,
                'co2'   => round($kg * self::CO2_PAR_KG, 1),
            ];
        }

        return [
            'total_lots' => $lots->count(),
            'total_kg'   => $totalKg,
            'co2'        => round($totalKg * self::CO2_PAR_KG, 1),
            'eau'        => round($totalKg * self::EAU_PAR_KG),
            'capacite'   => $recycleur->capacite_kg > 0 ? round($totalKg / $recycleur->capacite_kg * 100) : 0,
            'statuts'    => collect(['EN_ATTENTE', 'EN_TRAITEMENT', 'TRAITE', 'CERTIFIE'])
                ->mapWithKeys(fn ($s) => [$s => $lots->where('statut', $s)->count()])->all(),
            'filieres'   => collect(LotTextile::FILIERES)
                ->mapWithKeys(fn ($f) => [$f => $lots->where('filiere_recommandee_ia', $f)->count()])->all(),
            'mois'       => $mois,
        ];
    }

    /*
    |------------------------------------------------------------------
    | CSV : données brutes, une ligne d'en-tête, prêt pour Excel FR
    | (séparateur « ; », décimales « , », UTF-8 avec BOM)
    |------------------------------------------------------------------
    */
    private function lignesCsvLots(Collection $lots): array
    {
        $lignes = [[
            'Référence', 'Date de création', 'Composition', 'Poids (kg)', 'Origine',
            'Filière IA', 'Confiance IA (%)', 'Filière retenue', 'Statut', 'Progression (%)',
            'Étapes terminées', 'Don rattaché', 'Passeport (QR)', 'CO2 évité (kg)', 'Eau économisée (L)',
        ]];

        foreach ($lots as $lot) {
            $circuit = \App\Models\EtapeTraitement::CIRCUITS[$lot->filiere_finale] ?? [];
            $lignes[] = [
                $lot->reference,
                $lot->created_at->format('d/m/Y H:i'),
                $lot->composition,
                self::nombre($lot->poids_kg, 1),
                $lot->origine,
                LotTextile::libelleFiliere($lot->filiere_recommandee_ia),
                $lot->score_confiance_ia !== null ? self::nombre($lot->score_confiance_ia * 100, 0) : '',
                $lot->filiere_validee ? LotTextile::libelleFiliere($lot->filiere_validee) : '',
                self::statutLabel($lot->statut),
                $lot->progression,
                $lot->etapeTraitements->whereNotNull('date_fin')->whereIn('type', $circuit)->count() . '/' . count($circuit),
                $lot->donVetement ? "Don n°{$lot->donVetement->id} ({$lot->donVetement->type})" : '',
                $lot->passeportNumerique?->qr_code ?? '',
                self::nombre($lot->poids_kg * self::CO2_PAR_KG, 1),
                self::nombre($lot->poids_kg * self::EAU_PAR_KG, 0),
            ];
        }

        return $lignes;
    }

    private function lignesCsvBilan(Recycleur $recycleur, array $bilan): array
    {
        $l = [['Section', 'Indicateur', 'Valeur', 'Unité']];
        $l[] = ['Recycleur', 'Nom', $recycleur->nom, ''];
        $l[] = ['Recycleur', 'Agrément', $recycleur->agrement, ''];
        $l[] = ['Recycleur', 'Localisation', $recycleur->localisation, ''];
        $l[] = ['Recycleur', 'Capacité', self::nombre($recycleur->capacite_kg, 0), 'kg'];
        $l[] = ['Activité', 'Lots enregistrés', $bilan['total_lots'], 'lots'];
        $l[] = ['Activité', 'Poids total', self::nombre($bilan['total_kg'], 1), 'kg'];
        $l[] = ['Activité', 'Capacité utilisée', $bilan['capacite'], '%'];
        foreach ($bilan['statuts'] as $s => $n) {
            $l[] = ['Statut', self::statutLabel($s), $n, 'lots'];
        }
        foreach ($bilan['filieres'] as $f => $n) {
            $l[] = ['Filière IA', LotTextile::libelleFiliere($f), $n, 'lots'];
        }
        $l[] = ['Impact', 'CO2 évité', self::nombre($bilan['co2'], 1), 'kg'];
        $l[] = ['Impact', 'Eau économisée', self::nombre($bilan['eau'], 0), 'L'];
        foreach ($bilan['mois'] as $m) {
            $l[] = ['Mensuel', $m['label'] . ' — lots', $m['lots'], 'lots'];
            $l[] = ['Mensuel', $m['label'] . ' — poids', self::nombre($m['kg'], 1), 'kg'];
        }

        return $l;
    }

    private function csv(string $fichier, array $lignes): StreamedResponse
    {
        return response()->stream(function () use ($lignes) {
            $h = fopen('php://output', 'w');
            fwrite($h, "\xEF\xBB\xBF");
            foreach ($lignes as $ligne) {
                fputcsv($h, $ligne, ';');
            }
            fclose($h);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fichier . '"',
            'Cache-Control'       => 'no-store, no-cache',
        ]);
    }

    /** Nombre au format français (virgule décimale, sans séparateur de milliers pour rester numérique) */
    private static function nombre(float $valeur, int $decimales): string
    {
        return number_format($valeur, $decimales, ',', '');
    }

    public static function statutLabel(string $statut): string
    {
        return match ($statut) {
            'EN_ATTENTE'    => 'En attente',
            'EN_TRAITEMENT' => 'En traitement',
            'TRAITE'        => 'Traité',
            'CERTIFIE'      => 'Certifié',
            default         => $statut,
        };
    }
}
