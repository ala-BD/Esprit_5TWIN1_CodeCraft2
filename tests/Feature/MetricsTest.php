<?php

namespace Tests\Feature;

use App\Models\ProjetUpcycling;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Point d'entrée de supervision lu par Prometheus (GET /api/metrics).
 */
class MetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sans_jeton_configure_le_point_d_entree_est_desactive(): void
    {
        config(['services.metrics.token' => null]);

        $this->get('/api/metrics')->assertNotFound();
    }

    public function test_un_mauvais_jeton_est_refuse(): void
    {
        config(['services.metrics.token' => 'secret']);

        $this->get('/api/metrics')->assertUnauthorized();
        $this->withToken('autre')->get('/api/metrics')->assertUnauthorized();
    }

    public function test_les_indicateurs_sont_au_format_prometheus(): void
    {
        config(['services.metrics.token' => 'secret']);
        Http::fake(['*/sante' => Http::response(['statut' => 'ok'])]);
        User::factory()->count(2)->create(['role' => 'CLIENT']);
        ProjetUpcycling::factory()->vetement(0)->create();

        $reponse = $this->withToken('secret')->get('/api/metrics');

        $reponse->assertOk()->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
        $texte = $reponse->getContent();

        $this->assertStringContainsString('# TYPE retiss_base_disponible gauge', $texte);
        $this->assertStringContainsString('retiss_base_disponible 1', $texte);
        $this->assertStringContainsString('retiss_ia_recyclage_disponible 1', $texte);
        $this->assertMatchesRegularExpression('/^retiss_utilisateurs\{role="CLIENT"\} [3-9]$/m', $texte);
        $this->assertStringContainsString('retiss_projets_upcycling{statut="DEMANDE"} 1', $texte);
    }

    public function test_l_ia_indisponible_est_signalee(): void
    {
        config(['services.metrics.token' => 'secret']);
        Http::fake(['*/sante' => Http::response(null, 500)]);

        $this->withToken('secret')->get('/api/metrics')
            ->assertOk()
            ->assertSee('retiss_ia_recyclage_disponible 0');
    }
}
