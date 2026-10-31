<?php

namespace Tests\Unit;

use App\Models\Estimation;
use App\Models\ParametreCout;
use App\Services\RentabiliteService;
use Tests\TestCase;

class RentabiliteServiceTest extends TestCase
{
    protected RentabiliteService $service;
    protected ParametreCout $param;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RentabiliteService();
        $this->param = new ParametreCout([
            'matiere'                   => 'defaut',
            'marge_cible_pourcent'      => 20.00,
            'marge_minimale_pourcent'   => 5.00,
            'frais_plateforme_pourcent' => 5.00,
        ]);
    }

    public function test_alerte_verte_quand_marge_depasse_seuil_cible(): void
    {
        // Prix de vente: 50 DT
        // Coût de revient: 25 DT
        // Frais plateforme 5%: 2.50 DT
        // Marge nette: 50 - 25 - 2.50 = 22.50 DT
        // Marge %: 22.50 / 50 = 45% (>= 20% cible -> VERT)
        $res = $this->service->analyserRentabilite(
            prixVente: 50.00,
            coutRevient: 25.00,
            coutRecyclage: 2.00,
            parametre: $this->param
        );

        $this->assertEquals(Estimation::ALERTE_VERT, $res['niveau_alerte']);
        $this->assertEquals('Article gagnant', $res['titre_alerte']);
        $this->assertEquals(22.50, $res['marge_nette']);
        $this->assertEquals(45.0, $res['marge_pourcent']);
    }

    public function test_alerte_orange_quand_marge_positive_mais_inferieure_a_la_cible(): void
    {
        // Prix de vente: 40 DT
        // Coût de revient: 34 DT
        // Frais plateforme 5%: 2.00 DT
        // Marge nette: 40 - 34 - 2.00 = 4.00 DT
        // Marge %: 4 / 40 = 10% (positive mais < 20% cible -> ORANGE)
        $res = $this->service->analyserRentabilite(
            prixVente: 40.00,
            coutRevient: 34.00,
            coutRecyclage: 2.00,
            parametre: $this->param
        );

        $this->assertEquals(Estimation::ALERTE_ORANGE, $res['niveau_alerte']);
        $this->assertEquals('À surveiller', $res['titre_alerte']);
        $this->assertEquals(4.00, $res['marge_nette']);
        $this->assertEquals(10.0, $res['marge_pourcent']);
        $this->assertStringContainsString('Augmentez le prix', $res['conseil']);
    }

    public function test_alerte_rouge_quand_vente_a_perte(): void
    {
        // Prix de vente: 20 DT
        // Coût de revient: 30 DT
        // Frais plateforme 5%: 1.00 DT
        // Marge nette: 20 - 30 - 1 = -11.00 DT (négative -> ROUGE)
        $res = $this->service->analyserRentabilite(
            prixVente: 20.00,
            coutRevient: 30.00,
            coutRecyclage: 2.00,
            parametre: $this->param
        );

        $this->assertEquals(Estimation::ALERTE_ROUGE, $res['niveau_alerte']);
        $this->assertEquals('Article perdant', $res['titre_alerte']);
        $this->assertEquals(-11.00, $res['marge_nette']);
        $this->assertTrue($res['marge_pourcent'] < 0);
    }

    public function test_calcul_prix_requis_pour_atteindre_marge_cible(): void
    {
        // Coût revient = 30 DT
        // Frais plateforme = 5%, Marge cible = 20%
        // Dénominateur = 1 - 0.25 = 0.75
        // Prix pour cible = 30 / 0.75 = 40.00 DT
        $res = $this->service->analyserRentabilite(
            prixVente: 35.00,
            coutRevient: 30.00,
            coutRecyclage: 2.00,
            parametre: $this->param
        );

        $this->assertEquals(40.00, $res['prix_pour_cible']);
    }

    public function test_estimation_revenus_par_piece_et_par_quantite(): void
    {
        // Don textile (achat 0 DT)
        // Prix unitaire visé: 35.00 DT
        // Coût unitaire de confection: 14.00 DT
        // Quantité de pièces dans le lot: 10
        // Heures travail / pièce: 0.8 h
        // Frais plateforme 5%: 1.75 DT
        // Marge nette / pièce: 35 - 14 - 1.75 = 19.25 DT (55%) -> VERT
        $res = $this->service->analyserRevenusQuantite(
            prixVenteUnitaire: 35.00,
            coutRevientUnitaire: 14.00,
            quantite: 10,
            coutRecyclageUnitaire: 2.00,
            heuresTravailUnitaire: 0.8,
            poidsKgUnitaire: 0.3,
            parametre: $this->param
        );

        // Vérification unitaire
        $this->assertEquals(Estimation::ALERTE_VERT, $res['unitaire']['niveau_alerte']);
        $this->assertEquals(19.25, $res['unitaire']['marge_nette']);
        $this->assertEquals(55.0, $res['unitaire']['marge_pourcent']);

        // Vérification lot par quantité
        $this->assertEquals(10, $res['quantite']['nombre_pieces']);
        $this->assertEquals(350.00, $res['quantite']['chiffre_affaires_total']);
        $this->assertEquals(140.00, $res['quantite']['cout_total']);
        $this->assertEquals(192.50, $res['quantite']['marge_totale']);
        $this->assertEquals(8.0, $res['quantite']['heures_totales']);
        $this->assertEquals(3.0, $res['quantite']['poids_total_kg']);
        $this->assertEquals(24.06, $res['quantite']['gain_horaire_net']); // 192.50 / 8.0 = 24.06 DT/h
        $this->assertStringContainsString('350 DT', $res['quantite']['conseil_quantite']);
    }
}

