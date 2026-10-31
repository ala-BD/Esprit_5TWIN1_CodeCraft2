<?php

namespace App\Services\Recyclage;

use App\Models\LotTextile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Client du moteur IA de filière (micro-service Python ai-recyclage).
 *
 * Modèle LightGBM entraîné, probabilités calibrées et explications SHAP.
 * Si le service est indisponible, les règles métier de LotTextile prennent le relais.
 */
class FiliereIA
{
    /**
     * Prédit la filière d'un lot et renvoie les colonnes IA à enregistrer sur le lot.
     */
    public function predire(LotTextile $lot): array
    {
        $don = $lot->donVetement;

        try {
            $r = $this->client()->post('/predire', [
                'composition' => $lot->composition,
                'poids_kg'    => (float) $lot->poids_kg,
                'etat'        => $don?->etat,
                'type'        => $don?->type,
            ])->throw()->json();

            return [
                'filiere_recommandee_ia' => $r['filiere'],
                'score_confiance_ia'     => $r['confiance'],
                'probabilites_ia'        => $r['probabilites'],
                'explication_ia'         => [
                    'raisons'        => $r['explication'],
                    'avertissements' => $r['avertissements'],
                    'fibres'         => $r['analyse']['fibres'] ?? [],
                ],
                'source_ia'              => 'MODELE',
                'version_modele_ia'      => $r['modele']['version'] ?? null,
            ];
        } catch (Throwable $e) {
            Log::warning('IA filière indisponible, règles métier utilisées : ' . $e->getMessage());

            return [
                'filiere_recommandee_ia' => LotTextile::recommanderFiliereIA($lot->composition, (float) $lot->poids_kg),
                'score_confiance_ia'     => null,
                'probabilites_ia'        => null,
                'explication_ia'         => [
                    'raisons'        => [],
                    'avertissements' => ['Service IA indisponible : recommandation calculée par les règles métier.'],
                    'fibres'         => [],
                ],
                'source_ia'              => 'REGLES',
                'version_modele_ia'      => null,
            ];
        }
    }

    /**
     * Réentraîne le modèle avec toutes les décisions validées par les recycleurs.
     */
    public function entrainer(): array
    {
        $retours = LotTextile::whereNotNull('filiere_validee')
            ->with('donVetement')
            ->get()
            ->map(fn (LotTextile $lot) => [
                'composition' => $lot->composition,
                'poids_kg'    => (float) $lot->poids_kg,
                'etat'        => $lot->donVetement?->etat,
                'type'        => $lot->donVetement?->type,
                'filiere'     => $lot->filiere_validee,
            ])
            ->values()
            ->all();

        return $this->client()->timeout(600)
            ->post('/entrainer', ['retours' => $retours])
            ->throw()
            ->json();
    }

    public function metriques(): ?array
    {
        try {
            return $this->client()->get('/metriques')->throw()->json();
        } catch (Throwable) {
            return null;
        }
    }

    private function client()
    {
        return Http::baseUrl(rtrim(config('services.ai_recyclage.url'), '/'))
            ->timeout((int) config('services.ai_recyclage.timeout'))
            ->acceptJson();
    }
}
