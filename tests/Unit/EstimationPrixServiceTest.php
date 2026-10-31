<?php

namespace Tests\Unit;

use App\Services\EstimationPrixService;
use Tests\TestCase;

class EstimationPrixServiceTest extends TestCase
{
    protected EstimationPrixService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EstimationPrixService();
    }

    public function test_estimation_fourchette_produit_min_conseille_max(): void
    {
        $res = $this->service->estimerFourchette(
            categorie: 'Vetements Femme',
            matiere: 'Coton',
            etat: 'Tres bon etat'
        );

        $this->assertArrayHasKey('prix_min', $res);
        $this->assertArrayHasKey('prix_conseille', $res);
        $this->assertArrayHasKey('prix_max', $res);

        // Vérification de la cohérence de l'ordre min <= conseille <= max
        $this->assertLessThanOrEqual($res['prix_conseille'], $res['prix_min']);
        $this->assertGreaterThanOrEqual($res['prix_conseille'], $res['prix_max']);
    }

    public function test_matiere_noble_soie_a_un_prix_plus_eleve_que_polyester(): void
    {
        $soie = $this->service->estimerFourchette('Vetements Femme', 'Soie', 'Neuf');
        $poly = $this->service->estimerFourchette('Vetements Femme', 'Polyester', 'Neuf');

        $this->assertGreaterThan($poly['prix_conseille'], $soie['prix_conseille']);
    }
}

