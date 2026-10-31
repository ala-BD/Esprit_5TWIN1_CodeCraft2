<?php

namespace Tests\Feature\Recyclage;

use App\Models\DonVetement;
use App\Models\EtapeTraitement;
use App\Models\LotHistorique;
use App\Models\LotTextile;
use App\Models\PasseportNumerique;
use App\Models\Recycleur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Module M4 — Recyclage (LotTextile ↔ EtapeTraitement, passeport QR, IA filière).
 */
class RecyclageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Aucun appel réel au micro-service IA : sans Http::fake, les règles métier prennent le relais
        Http::preventStrayRequests();
    }

    private function recycleur(array $attrs = []): Recycleur
    {
        $user = User::factory()->create(['role' => User::ROLE_RECYCLEUR]);

        return Recycleur::create(array_merge([
            'user_id'      => $user->id,
            'nom'          => 'EcoTextile',
            'agrement'     => 'AGR-' . uniqid(),
            'capacite_kg'  => 5000,
            'localisation' => 'Tunis',
        ], $attrs));
    }

    private function lot(Recycleur $r, array $attrs = []): LotTextile
    {
        return LotTextile::create(array_merge([
            'recycleur_id'           => $r->id,
            'reference'              => 'LOT-' . uniqid(),
            'poids_kg'               => 20,
            'composition'            => '100% coton',
            'origine'                => 'Ariana',
            'filiere_recommandee_ia' => 'REVENTE',
            'statut'                 => 'EN_ATTENTE',
        ], $attrs));
    }

    private function lireXlsx(string $binaire): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $fichier = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($fichier, $binaire);
        $lecteur = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $lecteur->setIncludeCharts(true);
        $feuille = $lecteur->load($fichier)->getActiveSheet();
        unlink($fichier);

        return $feuille;
    }

    /** Ajoute les étapes restantes du circuit, terminées et dans l'ordre → lot TRAITE */
    private function traiterLot(Recycleur $r, LotTextile $lot): void
    {
        $lot = $lot->fresh();
        foreach ($lot->circuit_etapes as $i => $type) {
            if ($lot->etapeTraitements->contains('type', $type)) {
                continue;
            }
            $jour = now()->subDays(20 - $i);
            $this->actingAs($r->user)->post(route('recyclage.etapes.store', $lot), [
                'type'       => $type,
                'date_debut' => $jour->copy()->setTime(8, 0)->format('Y-m-d H:i'),
                'date_fin'   => $jour->copy()->setTime(17, 0)->format('Y-m-d H:i'),
            ])->assertSessionHasNoErrors();
        }
    }

    /* ---------------------------------------------------------------
     | Accès
     * ------------------------------------------------------------- */

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('recyclage.dashboard'))->assertRedirect(route('login'));
        $this->get(route('recyclage.lots.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_recycleur_profile_sees_incomplete_profile_message(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_RECYCLEUR]);

        $this->actingAs($user)->get(route('recyclage.dashboard'))
            ->assertOk()->assertSee('Profil recycleur incomplet');
        $this->actingAs($user)->get(route('recyclage.lots.index'))->assertNotFound();
    }

    public function test_recycleur_cannot_access_another_recycleurs_lot(): void
    {
        $a = $this->recycleur();
        $b = $this->recycleur();
        $lotB = $this->lot($b);

        $this->actingAs($a->user)->get(route('recyclage.lots.show', $lotB))->assertForbidden();
        $this->actingAs($a->user)->get(route('recyclage.lots.edit', $lotB))->assertForbidden();
        $this->actingAs($a->user)->put(route('recyclage.lots.update', $lotB), [
            'composition' => 'x', 'origine' => 'y', 'statut' => 'TRAITE',
        ])->assertForbidden();
        $this->actingAs($a->user)->delete(route('recyclage.lots.destroy', $lotB))->assertForbidden();
        $this->actingAs($a->user)->get(route('recyclage.passeport.show', $lotB))->assertForbidden();
    }

    /* ---------------------------------------------------------------
     | Pages
     * ------------------------------------------------------------- */

    public function test_module_pages_render(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r);
        $this->actingAs($r->user);

        $this->get(route('recyclage.dashboard'))->assertOk();
        $this->get(route('recyclage.lots.index'))->assertOk()->assertSee($lot->reference);
        $this->get(route('recyclage.lots.create'))->assertOk();
        $this->get(route('recyclage.lots.show', $lot))->assertOk()->assertSee($lot->reference);
        $this->get(route('recyclage.lots.edit', $lot))->assertOk();
        $this->get(route('recyclage.passeports.index'))->assertOk();
        $this->get(route('recyclage.passeport.show', $lot))->assertOk();
    }

    public function test_statistiques_page_renders(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('StatistiqueController utilise DATE_FORMAT (MySQL uniquement).');
        }
        $r = $this->recycleur();
        $this->lot($r);
        $this->actingAs($r->user)->get(route('recyclage.statistiques'))->assertOk();
    }

    /* ---------------------------------------------------------------
     | CRUD Lot
     * ------------------------------------------------------------- */

    public function test_recycleur_can_create_lot_with_ia_filiere_and_history(): void
    {
        $r = $this->recycleur();

        $this->actingAs($r->user)->post(route('recyclage.lots.store'), [
            'reference' => 'lot-test-1', 'poids_kg' => 30,
            'composition' => '60% coton, 40% polyester', 'origine' => 'Ariana',
        ])->assertRedirect();

        $lot = LotTextile::where('reference', 'LOT-TEST-1')->firstOrFail();
        $this->assertSame('RECYCLAGE_FIBRE', $lot->filiere_recommandee_ia);
        $this->assertSame('EN_ATTENTE', $lot->statut);
        $this->assertSame($r->id, $lot->recycleur_id);
        $this->assertDatabaseHas('lot_historiques', [
            'lot_textile_id' => $lot->id, 'evenement' => LotHistorique::CREATION,
        ]);
    }

    public function test_lot_validation(): void
    {
        $r = $this->recycleur();
        $this->lot($r, ['reference' => 'DUP-1']);

        $this->actingAs($r->user)->post(route('recyclage.lots.store'), [
            'reference' => 'DUP-1', 'poids_kg' => 0,
        ])->assertSessionHasErrors(['reference', 'poids_kg', 'composition', 'origine']);
    }

    public function test_recycleur_can_update_lot_and_history_is_recorded(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r);

        $this->actingAs($r->user)->put(route('recyclage.lots.update', $lot), [
            'composition' => '100% laine', 'origine' => 'Sousse', 'statut' => 'EN_TRAITEMENT',
        ])->assertRedirect(route('recyclage.lots.show', $lot));

        $lot->refresh();
        $this->assertSame('100% laine', $lot->composition);
        $this->assertSame('Sousse', $lot->origine);
        $this->assertSame('EN_ATTENTE', $lot->statut);   // statut automatique : le champ envoyé est ignoré
        // composition + nouvelle prédiction IA
        $this->assertSame(2, $lot->historiques()->count());
        $this->assertDatabaseHas('lot_historiques', ['lot_textile_id' => $lot->id, 'evenement' => LotHistorique::IA_PREDICTION]);
    }

    public function test_recycleur_can_delete_lot_but_not_certified_one(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r);
        $certifie = $this->lot($r, ['statut' => 'CERTIFIE']);

        $this->actingAs($r->user)->delete(route('recyclage.lots.destroy', $lot))
            ->assertRedirect(route('recyclage.lots.index'));
        $this->assertModelMissing($lot);

        $this->actingAs($r->user)->delete(route('recyclage.lots.destroy', $certifie))->assertStatus(422);
        $this->assertModelExists($certifie);
    }

    public function test_lots_index_filters_and_sort(): void
    {
        $r = $this->recycleur();
        $this->lot($r, ['reference' => 'AAA-COTON', 'statut' => 'EN_ATTENTE', 'poids_kg' => 10]);
        $this->lot($r, ['reference' => 'BBB-LAINE', 'statut' => 'TRAITE', 'composition' => 'laine', 'poids_kg' => 90]);

        $this->actingAs($r->user)->get(route('recyclage.lots.index', ['statut' => 'TRAITE']))
            ->assertSee('BBB-LAINE')->assertDontSee('AAA-COTON');
        $this->actingAs($r->user)->get(route('recyclage.lots.index', ['q' => 'laine']))
            ->assertSee('BBB-LAINE')->assertDontSee('AAA-COTON');
        $this->actingAs($r->user)->get(route('recyclage.lots.index', ['poids_max' => 50]))
            ->assertSee('AAA-COTON')->assertDontSee('BBB-LAINE');
        $this->actingAs($r->user)->get(route('recyclage.lots.index', ['sort' => 'DROP TABLE', 'direction' => 'x']))
            ->assertOk();
    }

    /* ---------------------------------------------------------------
     | IA — recommandation de filière
     * ------------------------------------------------------------- */

    public function test_ia_filiere_rules(): void
    {
        $this->assertSame('RECYCLAGE_FIBRE',   LotTextile::recommanderFiliereIA('100% polyester', 10));
        $this->assertSame('RECYCLAGE_FIBRE',   LotTextile::recommanderFiliereIA('50% coton 50% nylon', 10));
        $this->assertSame('UPCYCLING',         LotTextile::recommanderFiliereIA('100% laine', 80));
        $this->assertSame('RECYCLAGE_FIBRE',   LotTextile::recommanderFiliereIA('100% soie', 150));
        $this->assertSame('REVENTE',           LotTextile::recommanderFiliereIA('100% coton', 40));
        $this->assertSame('RECYCLAGE_FIBRE',   LotTextile::recommanderFiliereIA('Coton', 60));
        $this->assertSame('RECYCLAGE_ENERGIE', LotTextile::recommanderFiliereIA('inconnu', 10));
        $this->assertSame('RECYCLAGE_FIBRE',   LotTextile::recommanderFiliereIA('inconnu', 60));
    }

    /* ---------------------------------------------------------------
     | Étapes de traitement
     * ------------------------------------------------------------- */

    public function test_adding_and_finishing_steps_updates_lot_status(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r, ['filiere_recommandee_ia' => 'RECYCLAGE_FIBRE']);   // circuit complet : 6 étapes

        $this->actingAs($r->user)->post(route('recyclage.etapes.store', $lot), [
            'type' => 'RECEPTION', 'date_debut' => now()->subDays(25)->format('Y-m-d H:i'),
        ])->assertRedirect();
        $this->assertSame('EN_TRAITEMENT', $lot->fresh()->statut);

        $etape = $lot->etapeTraitements()->first();
        $this->actingAs($r->user)->patch(route('recyclage.etapes.terminer', $etape), [
            'date_fin' => now()->subDays(24)->format('Y-m-d H:i'), 'poids_sortant_kg' => 18,
        ])->assertRedirect();
        $this->assertNotNull($etape->fresh()->date_fin);
        $this->assertSame(17, $lot->fresh()->progression); // 1/6

        // doublon refusé
        $this->actingAs($r->user)->post(route('recyclage.etapes.store', $lot), [
            'type' => 'RECEPTION', 'date_debut' => now()->subDays(23)->format('Y-m-d H:i'),
        ])->assertSessionHasErrors(['type' => 'Cette étape existe déjà pour ce lot.']);

        $this->traiterLot($r, $lot);
        $this->assertSame('TRAITE', $lot->fresh()->statut);
        $this->assertSame(100, $lot->fresh()->progression);
    }

    public function test_step_validation(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r);

        $this->actingAs($r->user)->post(route('recyclage.etapes.store', $lot), [
            'type' => 'INVALIDE', 'date_debut' => '2026-10-05', 'date_fin' => '2026-10-01',
        ])->assertSessionHasErrors(['type', 'date_fin']);
    }

    public function test_step_dates_cannot_be_in_the_future(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r);

        $this->actingAs($r->user)->post(route('recyclage.etapes.store', $lot), [
            'type' => 'RECEPTION', 'date_debut' => now()->subHour()->format('Y-m-d H:i'),
            'date_fin' => now()->addDays(3)->format('Y-m-d H:i'),
        ])->assertSessionHasErrors(['date_fin' => 'La date de fin ne peut pas être dans le futur.']);

        $this->actingAs($r->user)->post(route('recyclage.etapes.store', $lot), [
            'type' => 'RECEPTION', 'date_debut' => now()->addDay()->format('Y-m-d H:i'),
        ])->assertSessionHasErrors('date_debut');

        $this->actingAs($r->user)->post(route('recyclage.etapes.store', $lot), [
            'type' => 'RECEPTION', 'date_debut' => now()->subDays(2)->format('Y-m-d H:i'),
        ])->assertSessionHasNoErrors();
        $etape = $lot->etapeTraitements()->first();
        $this->actingAs($r->user)->patch(route('recyclage.etapes.terminer', $etape), [
            'date_fin' => now()->addDays(3)->format('Y-m-d H:i'),
        ])->assertSessionHasErrors('date_fin');
        $this->assertNull($etape->fresh()->date_fin);
    }

    public function test_step_cannot_start_before_previous_step_ends(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r);

        $this->actingAs($r->user)->post(route('recyclage.etapes.store', $lot), [
            'type' => 'RECEPTION', 'date_debut' => now()->subDays(5)->format('Y-m-d H:i'),
            'date_fin' => now()->subDays(2)->format('Y-m-d H:i'),
        ])->assertSessionHasNoErrors();

        $this->actingAs($r->user)->post(route('recyclage.etapes.store', $lot), [
            'type' => 'TRI', 'date_debut' => now()->subDays(4)->format('Y-m-d H:i'),
        ])->assertSessionHasErrors('date_debut');
        $this->assertSame(1, $lot->etapeTraitements()->count());
    }

    public function test_new_step_requires_previous_step_finished(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r);

        $this->actingAs($r->user)->post(route('recyclage.etapes.store', $lot), [
            'type' => 'RECEPTION', 'date_debut' => now()->subDays(2)->format('Y-m-d H:i'),
        ]);
        $this->actingAs($r->user)->post(route('recyclage.etapes.store', $lot), [
            'type' => 'TRI', 'date_debut' => now()->subDay()->format('Y-m-d H:i'),
        ])->assertSessionHasErrors('type');
        $this->assertSame(1, $lot->etapeTraitements()->count());
        $this->assertNull($lot->fresh()->prochaine_etape);
    }

    public function test_steps_must_follow_circuit_order(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r);

        $this->actingAs($r->user)->post(route('recyclage.etapes.store', $lot), [
            'type' => 'TRI', 'date_debut' => now()->subDay()->format('Y-m-d H:i'),
        ])->assertSessionHasErrors(['type' => "Les étapes doivent suivre l'ordre du circuit : prochaine étape attendue « Réception »."]);
    }

    public function test_circuit_depends_on_filiere(): void
    {
        $r = $this->recycleur();

        // Revente : pas d'étape TRAITEMENT, 5 étapes suffisent
        $revente = $this->lot($r, ['filiere_recommandee_ia' => 'REVENTE']);
        $this->assertSame(['RECEPTION', 'TRI', 'NETTOYAGE', 'CONTROLE_QUALITE', 'EXPEDITION'], $revente->circuit_etapes);
        $this->traiterLot($r, $revente);
        $this->assertSame('TRAITE', $revente->fresh()->statut);
        $this->assertSame(5, $revente->etapeTraitements()->count());
        $this->assertFalse($revente->etapeTraitements()->where('type', 'TRAITEMENT')->exists());

        // Recyclage énergie : 4 étapes
        $energie = $this->lot($r, ['filiere_recommandee_ia' => 'RECYCLAGE_ENERGIE']);
        $this->traiterLot($r, $energie);
        $this->assertSame('TRAITE', $energie->fresh()->statut);
        $this->assertSame(4, $energie->etapeTraitements()->count());

        // La filière validée par le recycleur prime sur la recommandation IA
        $lot = $this->lot($r, ['filiere_recommandee_ia' => 'REVENTE', 'filiere_validee' => 'RECYCLAGE_FIBRE']);
        $this->assertContains('TRAITEMENT', $lot->circuit_etapes);
    }

    public function test_changing_filiere_updates_circuit_and_status(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r, ['filiere_recommandee_ia' => 'RECYCLAGE_ENERGIE']);
        $this->traiterLot($r, $lot);                                   // RECEPTION, TRI, TRAITEMENT, EXPEDITION
        $this->assertSame('TRAITE', $lot->fresh()->statut);

        // Le recycleur corrige vers recyclage fibre : il manque NETTOYAGE et CONTROLE_QUALITE
        $this->actingAs($r->user)->post(route('recyclage.lots.filiere.valider', $lot), ['filiere' => 'RECYCLAGE_FIBRE']);
        $lot = $lot->fresh();
        $this->assertSame('EN_TRAITEMENT', $lot->statut);
        $this->assertSame(67, $lot->progression);                     // 4/6
        $this->assertSame('NETTOYAGE', $lot->prochaine_etape);
    }

    /* ---------------------------------------------------------------
     | Passeport numérique
     * ------------------------------------------------------------- */

    public function test_passport_requires_treated_lot(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r);

        $this->actingAs($r->user)->post(route('recyclage.passeport.generer', $lot))->assertStatus(422);
        $this->assertDatabaseCount('passeport_numeriques', 0);
    }

    public function test_full_flow_passport_generation_pdf_and_public_verification(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r, ['poids_kg' => 10]);
        $this->traiterLot($r, $lot);

        $this->actingAs($r->user)->post(route('recyclage.passeport.generer', $lot))
            ->assertRedirect(route('recyclage.passeport.show', $lot));

        $lot->refresh();
        $passeport = $lot->passeportNumerique;
        $this->assertSame('CERTIFIE', $lot->statut);
        $this->assertSame(24.0, $passeport->co2_evite_kg);
        $this->assertSame(30000.0, $passeport->eau_economisee_l);
        $this->assertSame(64, strlen($passeport->hash_integrite));

        // second passeport refusé
        $this->actingAs($r->user)->post(route('recyclage.passeport.generer', $lot))->assertStatus(422);

        $this->actingAs($r->user)->get(route('recyclage.passeport.show', $lot))->assertOk()->assertSee($passeport->qr_code);
        $this->actingAs($r->user)->get(route('recyclage.passeports.index'))->assertOk()->assertSee($passeport->qr_code);

        $pdf = $this->actingAs($r->user)->get(route('recyclage.passeport.pdf', $lot));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));

        // vérification publique (sans auth)
        auth()->logout();
        $this->get(route('passeport.verifier', $passeport->qr_code))->assertOk()->assertSee($lot->reference);
        $this->get(route('passeport.verifier', 'FAUX-QR'))->assertNotFound();
    }

    /* ---------------------------------------------------------------
     | Lien Don (M1) → Lot (M4)
     * ------------------------------------------------------------- */

    public function test_attach_and_detach_donation(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r);
        $don = DonVetement::factory()->create(['statut' => 'EN_TRI']);
        $deposé = DonVetement::factory()->create(['statut' => 'DEPOSE']);

        $this->actingAs($r->user)->getJson(route('recyclage.dons-disponibles'))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $don->id);

        $this->actingAs($r->user)->post(route('recyclage.lots.rattacher-don', $lot), [
            'don_vetement_id' => $deposé->id,
        ])->assertStatus(422);

        $this->actingAs($r->user)->post(route('recyclage.lots.rattacher-don', $lot), [
            'don_vetement_id' => $don->id,
        ])->assertRedirect();
        $this->assertSame($don->id, $lot->fresh()->don_vetement_id);
        $this->assertSame('RECYCLE', $don->fresh()->statut);

        $this->actingAs($r->user)->delete(route('recyclage.lots.detacher-don', $lot))->assertRedirect();
        $this->assertNull($lot->fresh()->don_vetement_id);
        $this->assertSame('EN_TRI', $don->fresh()->statut);
    }

    /* ---------------------------------------------------------------
     | Exports
     * ------------------------------------------------------------- */

    public function test_exports(): void
    {
        $r = $this->recycleur();
        $this->lot($r, ['reference' => 'EXP-1']);
        $this->actingAs($r->user);

        $csv = $this->get(route('recyclage.lots.export', ['format' => 'csv']));
        $csv->assertOk();
        $contenu = $csv->streamedContent();
        $this->assertStringContainsString('EXP-1', $contenu);
        $this->assertStringContainsString(';20,0;', $contenu);   // décimales à la française pour Excel FR

        // Inventaire .xlsx : logo officiel, filtre, totaux
        $xlsx = $this->get(route('recyclage.lots.export'));
        $xlsx->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $feuille = $this->lireXlsx($xlsx->streamedContent());
        $this->assertCount(1, $feuille->getDrawingCollection());
        $this->assertStringContainsString('Inventaire des lots', $feuille->getCell('A1')->getValue());
        $this->assertNotEmpty($feuille->getAutoFilter()->getRange());

        $this->get(route('recyclage.statistiques.export', ['format' => 'csv']))->assertOk();

        // Bilan .xlsx : logo + graphique Excel natif, mois sans activité affichés à 0
        $bilan = $this->get(route('recyclage.statistiques.export'));
        $bilan->assertOk();
        $feuille = $this->lireXlsx($bilan->streamedContent());
        $this->assertCount(1, $feuille->getDrawingCollection());
        $this->assertSame(1, $feuille->getChartCount());
        $this->assertSame(0, $feuille->getCell('B20')->getValue());

        $pdf = $this->get(route('recyclage.statistiques.export-pdf'));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
    }

    /* ---------------------------------------------------------------
     | Dashboard — alertes
     * ------------------------------------------------------------- */

    public function test_dashboard_shows_alerts(): void
    {
        $r = $this->recycleur(['capacite_kg' => 100]);
        $old = $this->lot($r, ['poids_kg' => 90]);
        $old->forceFill(['created_at' => now()->subDays(10)])->save();

        $this->actingAs($r->user)->get(route('recyclage.dashboard'))
            ->assertOk()
            ->assertSee('Lots en attente trop longtemps')
            ->assertSee('Capacité presque saturée');
    }

    /* ---------------------------------------------------------------
     | IA filière — modèle entraîné, explicable, auto-apprenant
     * ------------------------------------------------------------- */

    private function fakePrediction(string $filiere = 'UPCYCLING', float $confiance = 0.91): array
    {
        return [
            'filiere'        => $filiere,
            'confiance'      => $confiance,
            'probabilites'   => ['RECYCLAGE_ENERGIE' => 0.01, 'RECYCLAGE_FIBRE' => 0.05, 'REVENTE' => 0.03, 'UPCYCLING' => 0.91],
            'explication'    => [['facteur' => 'Part de fibres nobles : 100 %', 'impact' => 41.8, 'sens' => 'pour']],
            'avertissements' => [],
            'analyse'        => ['fibres' => ['laine' => 1.0], 'etat_score' => 1.0, 'famille' => 'pull'],
            'modele'         => ['version' => 3, 'algorithme' => 'LightGBM'],
        ];
    }

    public function test_ia_model_prediction_is_stored_with_confidence_and_explanation(): void
    {
        Http::fake(['*/predire' => Http::response($this->fakePrediction())]);
        $r = $this->recycleur();

        $this->actingAs($r->user)->post(route('recyclage.lots.store'), [
            'reference' => 'IA-1', 'poids_kg' => 15, 'composition' => '100% laine', 'origine' => 'Tunis',
        ])->assertRedirect()->assertSessionHas('success', fn ($m) => str_contains($m, '91 % de confiance'));

        $lot = LotTextile::where('reference', 'IA-1')->firstOrFail();
        $this->assertSame('UPCYCLING', $lot->filiere_recommandee_ia);
        $this->assertSame(0.91, $lot->score_confiance_ia);
        $this->assertSame('MODELE', $lot->source_ia);
        $this->assertSame(3, $lot->version_modele_ia);
        $this->assertSame('Part de fibres nobles : 100 %', $lot->explication_ia['raisons'][0]['facteur']);

        Http::assertSent(fn ($req) => str_ends_with($req->url(), '/predire')
            && $req['composition'] === '100% laine' && $req['poids_kg'] == 15.0);

        $this->actingAs($r->user)->get(route('recyclage.lots.show', $lot))
            ->assertOk()
            ->assertSee('Pourquoi cette filière ?')
            ->assertSee('Part de fibres nobles : 100 %')
            ->assertSee('Modèle v3');
    }

    public function test_ia_falls_back_to_business_rules_when_service_is_down(): void
    {
        Http::fake(['*' => Http::failedConnection()]);
        $r = $this->recycleur();

        $this->actingAs($r->user)->post(route('recyclage.lots.store'), [
            'reference' => 'IA-2', 'poids_kg' => 30, 'composition' => '100% polyester', 'origine' => 'Tunis',
        ])->assertRedirect();

        $lot = LotTextile::where('reference', 'IA-2')->firstOrFail();
        $this->assertSame('RECYCLAGE_FIBRE', $lot->filiere_recommandee_ia);
        $this->assertSame('REGLES', $lot->source_ia);
        $this->assertNull($lot->score_confiance_ia);
    }

    public function test_attaching_a_donation_sends_its_state_and_type_to_the_model(): void
    {
        Http::fake(['*/predire' => Http::response($this->fakePrediction('REVENTE', 0.8))]);
        $r = $this->recycleur();
        $lot = $this->lot($r);
        $don = DonVetement::factory()->create(['statut' => 'EN_TRI', 'etat' => 'Très bon', 'type' => 'Chemise']);

        $this->actingAs($r->user)->post(route('recyclage.lots.rattacher-don', $lot), ['don_vetement_id' => $don->id]);

        Http::assertSent(fn ($req) => $req['etat'] === 'Très bon' && $req['type'] === 'Chemise');
        $this->assertSame('REVENTE', $lot->fresh()->filiere_recommandee_ia);
        $this->assertDatabaseHas('lot_historiques', ['lot_textile_id' => $lot->id, 'evenement' => LotHistorique::IA_PREDICTION]);
    }

    public function test_recycleur_can_confirm_or_correct_the_ia_decision(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r, ['filiere_recommandee_ia' => 'REVENTE']);

        $this->actingAs($r->user)->post(route('recyclage.lots.filiere.valider', $lot), ['filiere' => 'INVALIDE'])
            ->assertSessionHasErrors('filiere');

        $this->actingAs($r->user)->post(route('recyclage.lots.filiere.valider', $lot), ['filiere' => 'UPCYCLING'])
            ->assertRedirect(route('recyclage.lots.show', $lot));

        $lot->refresh();
        $this->assertSame('UPCYCLING', $lot->filiere_validee);
        $this->assertNotNull($lot->filiere_validee_le);
        $this->assertSame('UPCYCLING', $lot->filiere_finale);
        $this->assertDatabaseHas('lot_historiques', [
            'lot_textile_id' => $lot->id, 'evenement' => LotHistorique::FILIERE_VALIDEE, 'nouvelle_valeur' => 'UPCYCLING',
        ]);

        $autre = $this->recycleur();
        $this->actingAs($autre->user)->post(route('recyclage.lots.filiere.valider', $lot), ['filiere' => 'REVENTE'])
            ->assertForbidden();
    }

    public function test_recalculer_updates_the_prediction(): void
    {
        Http::fake(['*/predire' => Http::response($this->fakePrediction('RECYCLAGE_FIBRE', 0.77))]);
        $r = $this->recycleur();
        $lot = $this->lot($r);

        $this->actingAs($r->user)->post(route('recyclage.lots.filiere.recalculer', $lot))->assertRedirect();

        $this->assertSame('RECYCLAGE_FIBRE', $lot->fresh()->filiere_recommandee_ia);
        $this->assertSame(0.77, $lot->fresh()->score_confiance_ia);
    }

    public function test_retraining_sends_all_validated_decisions(): void
    {
        $r = $this->recycleur();
        $don = DonVetement::factory()->create(['etat' => 'Bon', 'type' => 'Pull']);
        $this->lot($r, ['composition' => '100% laine', 'filiere_validee' => 'UPCYCLING', 'don_vetement_id' => $don->id]);
        $this->lot($r, ['filiere_validee' => null]);

        Http::fake(['*/entrainer' => Http::response([
            'retours_utilises' => 1,
            'metriques' => ['version' => 4, 'algorithme' => 'LightGBM', 'test' => ['precision' => 0.79]],
        ])]);

        $this->actingAs($r->user)->post(route('recyclage.ia.entrainer'))
            ->assertRedirect(route('recyclage.ia.index'))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'v4') && str_contains($m, '79 %'));

        Http::assertSent(fn ($req) => str_ends_with($req->url(), '/entrainer')
            && count($req['retours']) === 1
            && $req['retours'][0] === ['composition' => '100% laine', 'poids_kg' => 20.0, 'etat' => 'Bon', 'type' => 'Pull', 'filiere' => 'UPCYCLING']);
    }

    public function test_ia_page_shows_service_down_message(): void
    {
        Http::fake(['*' => Http::failedConnection()]);
        $r = $this->recycleur();

        $this->actingAs($r->user)->get(route('recyclage.ia.index'))->assertOk()->assertSee('Service IA arrêté');
    }

    public function test_ia_page_renders_real_metrics_file(): void
    {
        $fichier = base_path('ai-recyclage/modeles/metriques.json');
        if (!file_exists($fichier)) {
            $this->markTestSkipped('Modèle non entraîné (python ai-recyclage/entrainement.py).');
        }
        $metriques = json_decode(file_get_contents($fichier), true);
        $metriques['historique'] = [[
            'version' => $metriques['version'], 'date' => $metriques['date'], 'algorithme' => $metriques['algorithme'],
            'precision' => $metriques['test']['precision'], 'decisions_recycleurs' => 0,
        ]];
        Http::fake(['*/metriques' => Http::response($metriques)]);

        $r = $this->recycleur();
        $this->actingAs($r->user)->get(route('recyclage.ia.index'))
            ->assertOk()
            ->assertSee($metriques['algorithme'])
            ->assertSee('Matrice de confusion')
            ->assertSee('Fiabilité de la confiance');
    }

    /* ---------------------------------------------------------------
     | Anomalies détectées (ces tests documentent des bugs)
     * ------------------------------------------------------------- */

    public function test_cannot_attach_second_donation_when_lot_already_has_one(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r);
        $don1 = DonVetement::factory()->create(['statut' => 'EN_TRI']);
        $don2 = DonVetement::factory()->create(['statut' => 'EN_TRI']);

        $this->actingAs($r->user)->post(route('recyclage.lots.rattacher-don', $lot), ['don_vetement_id' => $don1->id]);
        $this->actingAs($r->user)->post(route('recyclage.lots.rattacher-don', $lot), ['don_vetement_id' => $don2->id]);

        // Attendu : le 2e rattachement est refusé et don1 reste lié
        $this->assertSame($don1->id, $lot->fresh()->don_vetement_id, 'Le don 1 a été écrasé et reste orphelin en statut RECYCLE.');
    }

    public function test_status_cannot_be_set_manually_via_edit_form(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r);

        $this->actingAs($r->user)->put(route('recyclage.lots.update', $lot), [
            'composition' => 'coton', 'origine' => 'x', 'statut' => 'CERTIFIE',
        ]);

        $this->assertNotSame('CERTIFIE', $lot->fresh()->statut, 'Un lot peut être marqué CERTIFIE sans passeport via le formulaire d\'édition.');
    }

    public function test_certified_lot_steps_are_locked(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r);
        $this->traiterLot($r, $lot);
        $this->actingAs($r->user)->post(route('recyclage.passeport.generer', $lot));
        $this->assertSame('CERTIFIE', $lot->fresh()->statut);

        $etape = $lot->etapeTraitements()->first();
        $this->actingAs($r->user)->patch(route('recyclage.etapes.terminer', $etape), ['date_fin' => now()->subHour()->format('Y-m-d H:i')])
            ->assertSessionHasErrors('date_fin');

        $this->assertSame('CERTIFIE', $lot->fresh()->statut, 'Terminer une étape sur un lot certifié le repasse en TRAITE.');
    }

    public function test_expedition_cannot_be_added_first(): void
    {
        $r = $this->recycleur();
        $lot = $this->lot($r);

        $this->actingAs($r->user)->post(route('recyclage.etapes.store', $lot), [
            'type' => 'EXPEDITION', 'date_debut' => '2026-10-01', 'date_fin' => '2026-10-01',
        ])->assertSessionHasErrors('type');
        $this->assertSame(0, $lot->etapeTraitements()->count());

        $this->assertNotSame('TRAITE', $lot->fresh()->statut, 'Une seule étape EXPEDITION suffit à passer le lot en TRAITE (ordre non imposé).');
    }

    public function test_registration_must_not_allow_admin_role(): void
    {
        $this->post('/register', [
            'name' => 'Pirate', 'email' => 'pirate@test.tn', 'role' => 'ADMIN',
            'password' => 'password', 'password_confirmation' => 'password',
        ]);

        $this->assertNotSame('ADMIN', User::where('email', 'pirate@test.tn')->value('role'), 'N\'importe qui peut s\'inscrire comme ADMIN.');
    }
}
