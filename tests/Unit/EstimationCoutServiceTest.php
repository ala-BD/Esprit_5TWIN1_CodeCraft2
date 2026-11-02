<?php

namespace Tests\Unit;

use App\Models\ParametreCout;
use App\Models\User;
use App\Services\EstimationCoutService;
use Tests\TestCase;

class EstimationCoutServiceTest extends TestCase
{
    protected EstimationCoutService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EstimationCoutService();
    }

    public function test_calcul_recyclage_matiere_standard(): void
    {
        $param = new ParametreCout([
            'matiere'                   => 'Coton',
            'cout_recyclage_par_kg'     => 1.80,
            'cout_tri_fixe'             => 0.50,
            'cout_transport_par_kg'     => 0.30,
            'majoration_mixte_pourcent' => 0.00,
        ]);

        $poids = 0.50; // 500g
        $res = $this->service->calculerRecyclage('Coton', $poids, $param);

        // Tri: 0.50, Traitement: 0.50 * 1.80 = 0.90, Majoration: 0, Transport: 0.50 * 0.30 = 0.15 (min 0.20 -> 0.20)
        // Total = 0.50 + 0.90 + 0.20 = 1.60 DT
        $this->assertEquals(0.50, $res['tri']);
        $this->assertEquals(0.90, $res['traitement_base']);
        $this->assertEquals(0.00, $res['majoration_mixte']);
        $this->assertEquals(1.60, $res['total']);
    }

    public function test_calcul_recyclage_avec_majoration_matiere_mixte(): void
    {
        $param = new ParametreCout([
            'matiere'                   => 'Mixte',
            'cout_recyclage_par_kg'     => 2.80,
            'cout_tri_fixe'             => 0.70,
            'cout_transport_par_kg'     => 0.30,
            'majoration_mixte_pourcent' => 25.00,
        ]);

        $poids = 1.0;
        $res = $this->service->calculerRecyclage('Mixte', $poids, $param);

        // Traitement: 1.0 * 2.80 = 2.80 DT
        // Majoration 25%: 2.80 * 0.25 = 0.70 DT
        // Tri: 0.70 DT, Transport: 0.30 DT
        // Total = 0.70 + 2.80 + 0.70 + 0.30 = 4.50 DT
        $this->assertEquals(0.70, $res['majoration_mixte']);
        $this->assertEquals(4.50, $res['total']);
    }

    public function test_calcul_upcycling_avec_tarif_horaire_atelier(): void
    {
        $atelier = new User([
            'role'          => User::ROLE_ATELIER,
            'tarif_horaire' => 25.00, // 25 DT/h
        ]);

        $param = new ParametreCout([
            'matiere'              => 'defaut',
            'tarif_horaire_defaut' => 15.00,
            'cout_transport_par_kg'=> 0.30,
        ]);

        $res = $this->service->calculerUpcycling(
            coutAchat: 5.00,
            heuresTravail: 2.0,
            coutMateriaux: 3.50,
            poidsKg: 0.60,
            atelier: $atelier,
            parametre: $param
        );

        // Achat: 5.00 DT
        // Main d'oeuvre: 2h * 25 DT/h = 50.00 DT
        // Matériaux: 3.50 DT
        // Transport: 0.60 * 0.30 = 0.18 (min 0.20 -> 0.20 DT)
        // Total = 5.00 + 50.00 + 3.50 + 0.20 = 58.70 DT
        $this->assertEquals(25.00, $res['tarif_horaire']);
        $this->assertEquals(50.00, $res['main_d_oeuvre']);
        $this->assertEquals(58.70, $res['total']);
    }

    public function test_comparaison_filieres_recommande_recyclage_si_travail_excessif_sur_piece_usee(): void
    {
        $coutRecyclage = ['total' => 2.50];
        $coutUpcycling = ['total' => 85.00, 'heures' => 4.0];

        $res = $this->service->comparerFilieres($coutRecyclage, $coutUpcycling, 'Usage');

        $this->assertEquals('RECYCLAGE', $res['filiere_recommandee']);
    }
}

