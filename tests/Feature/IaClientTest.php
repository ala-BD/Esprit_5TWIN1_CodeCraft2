<?php

namespace Tests\Feature;

use App\Services\IaClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IaClientTest extends TestCase
{
    public function test_plan_b_utilise_regles_quand_cle_api_absente(): void
    {
        config(['services.ai.api_key' => null]);
        putenv('AI_API_KEY=');

        $client = new IaClient();

        $data = [
            'titre'          => 'Veste vintage brodée',
            'categorie'      => 'Vetements Femme',
            'matiere'        => 'Lin',
            'etat'           => 'Tres bon etat',
            'poids_kg'       => 0.45,
            'cout_upcycling' => 20.00,
            'cout_recyclage' => 2.00,
            'prix_conseille' => 38.00,
            'prix_min'       => 30.00,
            'prix_max'       => 48.00,
        ];

        $res = $client->estimerArticle($data);

        $this->assertEquals('regles', $res['source']);
        $this->assertTrue($res['is_fallback']);
        $this->assertNotEmpty($res['explication']);
        $this->assertEquals(38.00, $res['prix_conseille']);
    }

    public function test_appel_ia_reussi_avec_http_fake(): void
    {
        config(['services.ai.api_key' => 'test_api_key_12345']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'prix_min'       => 35.0,
                                        'prix_conseille' => 45.0,
                                        'prix_max'       => 55.0,
                                        'confiance'      => 'eleve',
                                        'explication'    => 'Pièce en lin de belle confection avec forte valeur marchande.',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $client = new IaClient();

        $data = [
            'titre'          => 'Robe d été pure soie',
            'categorie'      => 'Vetements Femme',
            'matiere'        => 'Soie',
            'etat'           => 'Neuf',
            'poids_kg'       => 0.30,
            'cout_upcycling' => 15.00,
            'prix_conseille' => 40.00,
        ];

        $res = $client->estimerArticle($data);

        $this->assertEquals('ia', $res['source']);
        $this->assertEquals('eleve', $res['confiance']);
        $this->assertEquals(45.00, $res['prix_conseille']);
        $this->assertFalse($res['is_fallback']);
    }

    public function test_mise_en_cache_empeche_les_appels_multiples(): void
    {
        config(['services.ai.api_key' => 'test_api_key_12345']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'prix_min'       => 20.0,
                                        'prix_conseille' => 28.0,
                                        'prix_max'       => 35.0,
                                        'confiance'      => 'moyen',
                                        'explication'    => 'Réponse IA testée.',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $client = new IaClient();
        $data = [
            'titre'     => 'Chemise coton bio',
            'categorie' => 'Vetements Homme',
            'matiere'   => 'Coton',
            'etat'      => 'Bon etat',
        ];

        // Premier appel (met en cache)
        $client->estimerArticle($data);
        // Deuxième appel identique
        $client->estimerArticle($data);

        // Vérifier que Http::fake n'a été appelé qu'une seule fois grâce au cache
        Http::assertSentCount(1);
    }
}

