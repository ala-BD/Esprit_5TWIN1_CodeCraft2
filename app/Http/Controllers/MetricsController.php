<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Indicateurs de la plateforme au format texte de Prometheus (GET /api/metrics).
 *
 * Lu toutes les 15 s par Prometheus (devops/monitoring/prometheus.yml) et affiché dans Grafana :
 * santé technique (base, IA M4, Gemini) et activité métier des cinq modules.
 * Accès protégé par le jeton METRICS_TOKEN (en-tête « Authorization: Bearer … »).
 */
class MetricsController extends Controller
{
    /** @var list<string> */
    private array $lignes = [];

    public function __invoke(Request $request): Response
    {
        $jeton = config('services.metrics.token');
        abort_if(blank($jeton), 404);
        abort_unless(hash_equals((string) $jeton, (string) $request->bearerToken()), 401);

        $this->sante();
        $this->activite();

        return response(implode("\n", $this->lignes) . "\n", 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }

    /*
    |------------------------------------------------------------------
    | Santé technique
    |------------------------------------------------------------------
    */
    private function sante(): void
    {
        $this->metrique('retiss_info', 'Version de l\'application', 'gauge', [[
            ['env' => app()->environment(), 'php' => PHP_VERSION, 'laravel' => app()->version()], 1,
        ]]);

        $debut = microtime(true);
        try {
            DB::select('SELECT 1');
            $baseOk = 1;
        } catch (Throwable) {
            $baseOk = 0;
        }
        $this->metrique('retiss_base_disponible', 'La base de données répond (1 = oui)', 'gauge', [[[], $baseOk]]);
        $this->metrique('retiss_base_latence_secondes', 'Temps de réponse de la base', 'gauge', [[[], round(microtime(true) - $debut, 5)]]);

        try {
            $iaOk = Http::timeout(2)->get(rtrim(config('services.ai_recyclage.url'), '/') . '/sante')->successful() ? 1 : 0;
        } catch (Throwable) {
            $iaOk = 0;
        }
        $this->metrique('retiss_ia_recyclage_disponible', 'Le micro-service IA du module M4 répond (1 = oui)', 'gauge', [[[], $iaOk]]);
        $this->metrique('retiss_gemini_configure', 'Clé Gemini présente pour le module M3 (1 = oui)', 'gauge', [[[], filled(config('services.gemini.key')) ? 1 : 0]]);

        if ($baseOk) {
            $this->compter('retiss_jobs_en_attente', 'Tâches en attente dans la file', 'jobs');
            $this->compter('retiss_jobs_echoues', 'Tâches en échec', 'failed_jobs');
        }
    }

    /*
    |------------------------------------------------------------------
    | Activité métier (une série par statut)
    |------------------------------------------------------------------
    */
    private function activite(): void
    {
        $this->parStatut('retiss_utilisateurs', 'Comptes par rôle', 'users', 'role');
        $this->parStatut('retiss_dons', 'Dons de vêtements par statut (M1)', 'don_vetements');
        $this->parStatut('retiss_articles', 'Articles de la marketplace par statut (M2)', 'articles');
        $this->parStatut('retiss_commandes', 'Commandes par statut (M2)', 'commandes');
        $this->parStatut('retiss_projets_upcycling', 'Projets d\'upcycling par statut (M3)', 'projet_upcyclings');
        $this->parStatut('retiss_devis', 'Devis d\'upcycling par statut (M3)', 'devis');
        $this->parStatut('retiss_lots_textiles', 'Lots textiles par statut (M4)', 'lot_textiles');
        $this->parStatut('retiss_missions', 'Missions logistiques par statut (M5)', 'missions');

        $this->somme('retiss_ventes_dinars', 'Montant total des commandes marketplace (DT)', 'commandes', 'montant_total');
        $this->somme('retiss_devis_acceptes_dinars', 'Montant des devis d\'upcycling acceptés (DT)', 'devis', 'montant', ['statut' => 'ACCEPTE']);
        $this->compter('retiss_notifications_non_lues', 'Notifications in-app non lues', 'notifications', fn ($q) => $q->whereNull('read_at'));
    }

    /*
    |------------------------------------------------------------------
    | Helpers
    |------------------------------------------------------------------
    */
    private function parStatut(string $nom, string $aide, string $table, string $colonne = 'statut'): void
    {
        try {
            $series = DB::table($table)->select($colonne, DB::raw('COUNT(*) as total'))->groupBy($colonne)->get()
                ->map(fn ($ligne) => [[$colonne => (string) $ligne->{$colonne}], (int) $ligne->total])->all();
        } catch (Throwable) {
            return;     // table absente (module non migré) : on n'expose rien plutôt que d'échouer
        }
        $this->metrique($nom, $aide, 'gauge', $series);
    }

    private function compter(string $nom, string $aide, string $table, ?callable $filtre = null): void
    {
        try {
            $requete = DB::table($table);
            if ($filtre) {
                $filtre($requete);
            }
            $this->metrique($nom, $aide, 'gauge', [[[], $requete->count()]]);
        } catch (Throwable) {
        }
    }

    private function somme(string $nom, string $aide, string $table, string $colonne, array $conditions = []): void
    {
        try {
            $this->metrique($nom, $aide, 'gauge', [[[], (float) DB::table($table)->where($conditions)->sum($colonne)]]);
        } catch (Throwable) {
        }
    }

    /** @param list<array{0: array<string, string>, 1: int|float}> $series */
    private function metrique(string $nom, string $aide, string $type, array $series): void
    {
        $this->lignes[] = "# HELP {$nom} {$aide}";
        $this->lignes[] = "# TYPE {$nom} {$type}";
        foreach ($series as [$etiquettes, $valeur]) {
            $texte = collect($etiquettes)
                ->map(fn ($v, $k) => $k . '="' . addcslashes($v, "\\\"\n") . '"')
                ->implode(',');
            $this->lignes[] = $nom . ($texte ? "{{$texte}}" : '') . ' ' . $valeur;
        }
    }
}
