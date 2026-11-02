<?php

namespace Tests\Unit;

use App\Services\IaClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IaClientTest extends TestCase
{
    protected IaClient $client;

    protected array $sampleData = [
        'titre'          => 'Veste en denim upcyclée',
        'categorie'      => 'Vetements Homme',
        'matiere'        => 'Coton',
        'etat'           => 'Bon etat',
        'taille'         => 'M',
        'couleur'        => 'Bleu',
        'poids_kg'       => 0.6,
        'cout_upcycling' => 15.0,
        'cout_recyclage' => 2.0,
        'prix_conseille' => 35.0,
        'prix_min'       => 28.0,
        'prix_max'       => 45.0,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config(['services.ai.api_key' => 'fake-key']);
        $this->client = new IaClient();
    }

    protected function makeGeminiResponse(array $payload, ?string $finishReason = 'STOP'): array
    {
        return [
            'candidates' => [
                [
                    'finishReason' => $finishReason,
                    'content'      => [
                        'parts' => [
                            ['text' => json_encode($payload)],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_succes_ia_avec_mise_en_cache_et_deuxieme_appel_depuis_cache(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->makeGeminiResponse([
                    'prix_min'       => 30.0,
                    'prix_conseille' => 40.0,
                    'prix_max'       => 50.0,
                    'confiance'      => 'eleve',
                    'explication'    => 'Excellente pièce pour la revente.',
                ]),
                200
            ),
        ]);

        // Premier appel (non mis en cache)
        $res1 = $this->client->estimerArticle($this->sampleData);
        $this->assertEquals('ia', $res1['source']);
        $this->assertNull($res1['raison_fallback']);
        $this->assertNull($res1['raison_label']);
        $this->assertFalse($res1['depuis_cache']);
        $this->assertFalse($res1['is_fallback']);
        $this->assertEquals(40.0, $res1['prix_conseille']);
        $this->assertNotEmpty($res1['modele']);

        // Deuxième appel avec les mêmes données (doit provenir du cache)
        $res2 = $this->client->estimerArticle($this->sampleData);
        $this->assertEquals('ia', $res2['source']);
        $this->assertNull($res2['raison_fallback']);
        $this->assertTrue($res2['depuis_cache']);
        $this->assertEquals(40.0, $res2['prix_conseille']);

        // Une seule requête HTTP doit avoir été envoyée
        Http::assertSentCount(1);
    }

    public function test_delai_depasse_bascule_en_plan_b(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => fn () => throw new ConnectionException('cURL error 28: Operation timed out'),
        ]);

        $res = $this->client->estimerArticle($this->sampleData);

        $this->assertEquals('regles', $res['source']);
        $this->assertEquals('delai_depasse', $res['raison_fallback']);
        $this->assertTrue($res['is_fallback']);
        $this->assertNotNull($res['raison_label']);
        $this->assertFalse($res['depuis_cache']);
    }

    public function test_erreur_429_quota_depasse(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'Quota exceeded']], 429),
        ]);

        $res = $this->client->estimerArticle($this->sampleData);

        $this->assertEquals('regles', $res['source']);
        $this->assertEquals('quota_depasse', $res['raison_fallback']);
        $this->assertTrue($res['is_fallback']);
    }

    public function test_erreur_404_modele_introuvable(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'Model not found']], 404),
        ]);

        $res = $this->client->estimerArticle($this->sampleData);

        $this->assertEquals('regles', $res['source']);
        $this->assertEquals('modele_introuvable', $res['raison_fallback']);
        $this->assertTrue($res['is_fallback']);
    }

    public function test_erreur_400_requete_refusee(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'Invalid argument']], 400),
        ]);

        $res = $this->client->estimerArticle($this->sampleData);

        $this->assertEquals('regles', $res['source']);
        $this->assertEquals('requete_refusee', $res['raison_fallback']);
        $this->assertTrue($res['is_fallback']);
    }

    public function test_json_invalide_texte_libre(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'finishReason' => 'STOP',
                        'content'      => [
                            'parts' => [
                                ['text' => 'Ceci est une explication sans aucun format JSON exploitable.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $res = $this->client->estimerArticle($this->sampleData);

        $this->assertEquals('regles', $res['source']);
        $this->assertEquals('json_invalide', $res['raison_fallback']);
        $this->assertTrue($res['is_fallback']);
    }

    public function test_reponse_tronquee_max_tokens(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'finishReason' => 'MAX_TOKENS',
                        'content'      => [
                            'parts' => [
                                ['text' => '{"prix_conseille": 35.0, "explication": "Tronqu'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $res = $this->client->estimerArticle($this->sampleData);

        $this->assertEquals('regles', $res['source']);
        $this->assertEquals('reponse_tronquee', $res['raison_fallback']);
        $this->assertTrue($res['is_fallback']);
    }

    public function test_prix_incoherent_10_fois_superieur(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->makeGeminiResponse([
                    'prix_min'       => 300.0,
                    'prix_conseille' => 350.0, // > 35.0 * 3 = 105.0 -> incohérent
                    'prix_max'       => 400.0,
                    'confiance'      => 'eleve',
                    'explication'    => 'Prix déraisonnablement haut.',
                ]),
                200
            ),
        ]);

        $res = $this->client->estimerArticle($this->sampleData);

        $this->assertEquals('regles', $res['source']);
        $this->assertEquals('valeurs_incoherentes', $res['raison_fallback']);
        $this->assertTrue($res['is_fallback']);
    }

    public function test_cle_absente_bascule_en_plan_b_sans_requete_http(): void
    {
        config(['services.ai.api_key' => null]);
        putenv('AI_API_KEY=');

        $client = new IaClient();
        $res = $client->estimerArticle($this->sampleData);

        $this->assertEquals('regles', $res['source']);
        $this->assertEquals('cle_absente', $res['raison_fallback']);
        $this->assertTrue($res['is_fallback']);
        $this->assertNull($res['modele']);
        Http::assertNothingSent();
    }

    public function test_la_cle_est_envoyee_dans_en_tete_et_absente_de_url(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                $this->makeGeminiResponse([
                    'prix_min'       => 28.0,
                    'prix_conseille' => 35.0,
                    'prix_max'       => 42.0,
                    'confiance'      => 'moyen',
                    'explication'    => 'Test en-tête et url.',
                ]),
                200
            ),
        ]);

        $this->client->estimerArticle($this->sampleData);

        Http::assertSent(function (Request $request) {
            $hasHeader = $request->hasHeader('x-goog-api-key', 'fake-key');
            $noKeyInUrl = !str_contains($request->url(), 'key=');

            return $hasHeader && $noKeyInUrl;
        });
    }

    public function test_un_plan_b_n_est_jamais_mis_en_cache(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push(['error' => ['message' => 'Rate limit']], 429)
                ->push($this->makeGeminiResponse([
                    'prix_min'       => 28.0,
                    'prix_conseille' => 35.0,
                    'prix_max'       => 42.0,
                    'confiance'      => 'moyen',
                    'explication'    => 'Réponse IA après rétablissement.',
                ]), 200),
        ]);

        // 1er appel en échec 429 -> plan B
        $res1 = $this->client->estimerArticle($this->sampleData);
        $this->assertEquals('regles', $res1['source']);
        $this->assertEquals('quota_depasse', $res1['raison_fallback']);

        // 2e appel avec mêmes données : l'API répond 200 -> doit appeler l'IA car le plan B n'a pas été mis en cache
        $res2 = $this->client->estimerArticle($this->sampleData);
        $this->assertEquals('ia', $res2['source']);
        $this->assertNull($res2['raison_fallback']);
        $this->assertFalse($res2['depuis_cache']);
    }
}
