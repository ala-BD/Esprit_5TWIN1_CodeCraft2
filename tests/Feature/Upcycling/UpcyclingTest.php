<?php

namespace Tests\Feature\Upcycling;

use App\Models\Atelier;
use App\Models\Devis;
use App\Models\ProjetUpcycling;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpcyclingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Par défaut : pas de clé → générateur local, jamais d'appel réel à l'API
        config(['services.gemini.key' => null]);
        Http::preventStrayRequests();
    }

    private function client(): User
    {
        return User::factory()->create(['role' => 'CLIENT']);
    }

    /** Simule une réponse Gemini contenant le JSON donné */
    private function fakeGemini(array $json): void
    {
        config(['services.gemini.key' => 'cle-de-test']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($json)]]]]],
            ]),
        ]);
    }

    private function ideeGemini(string $titre, string $categorie): array
    {
        return [
            'titre' => $titre, 'description' => 'Description', 'categorie' => $categorie, 'difficulte' => 'MOYEN',
            'duree_heures' => 3, 'etapes' => ['Couper', 'Coudre'], 'materiaux' => ['Fil'], 'prix_min' => 30, 'prix_max' => 50,
        ];
    }

    public function test_un_client_cree_une_demande_avec_le_generateur_local(): void
    {
        $client = $this->client();

        $response = $this->actingAs($client)->post(route('upcycling.projets.store'), [
            'type_vetement' => 'Jean',
            'matiere'       => 'Denim',
            'etat'          => 'USE',
            'description'   => 'Vieux jean troué, je veux un sac.',
            'budget_max'    => 50,
        ]);

        $projet = ProjetUpcycling::first();
        $response->assertRedirect(route('upcycling.projets.show', $projet));
        $this->assertSame('DEMANDE', $projet->statut);
        $this->assertSame('LOCAL', $projet->source_ia);
        $this->assertCount(3, $projet->idee_generee_ia);
        $this->assertSame('Sac cabas en denim', $projet->idee_generee_ia[0]['titre']);
        $this->assertNotEmpty($projet->idee_generee_ia[0]['etapes']);
        $this->assertSame(33.0, $projet->co2_evite_kg);
        $this->assertSame(7500, $projet->eau_economisee_l);
    }

    public function test_la_demande_avec_photo_utilise_gemini(): void
    {
        Storage::fake('public');
        $this->fakeGemini([
            'defauts' => ['Trou au genou'],
            'idees'   => [$this->ideeGemini('Sac banane', 'SAC'), $this->ideeGemini('Coussin', 'DECORATION'), $this->ideeGemini('Short', 'VETEMENT')],
        ]);

        $this->actingAs($this->client())->post(route('upcycling.projets.store'), [
            'type_vetement' => 'Jean', 'matiere' => 'Denim', 'couleur' => 'Bleu', 'etat' => 'ABIME',
            'description'   => 'Jean troué au genou.',
            'photo'         => UploadedFile::fake()->image('jean.jpg', 400, 400),
        ])->assertRedirect();

        $projet = ProjetUpcycling::first();
        $this->assertSame('GEMINI', $projet->source_ia);
        $this->assertSame('Sac banane', $projet->idee_generee_ia[0]['titre']);
        $this->assertSame(['Trou au genou'], $projet->analyse_ia['defauts']);
        Storage::disk('public')->assertExists($projet->photo);

        // La photo est bien envoyée à Gemini
        Http::assertSent(fn ($request) => str_contains($request->body(), 'inline_data'));
    }

    public function test_si_gemini_echoue_le_generateur_local_prend_le_relais(): void
    {
        config(['services.gemini.key' => 'cle-de-test']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => ['message' => 'quota']], 429)]);

        $this->actingAs($this->client())->post(route('upcycling.projets.store'), [
            'type_vetement' => 'Chemise', 'matiere' => 'Coton', 'etat' => 'BON', 'description' => 'Chemise trop grande.',
        ]);

        $this->assertSame('LOCAL', ProjetUpcycling::first()->source_ia);
    }

    public function test_l_ia_analyse_la_photo_pour_pre_remplir_le_formulaire(): void
    {
        $this->fakeGemini([
            'est_vetement' => true, 'type_vetement' => 'blazer', 'matiere' => 'laine', 'couleur' => 'bleu marine',
            'etat' => 'BON', 'description' => 'Blazer classique.', 'defauts' => [],
        ]);

        $this->actingAs($this->client())
            ->postJson(route('upcycling.analyse-photo'), ['photo' => UploadedFile::fake()->image('blazer.jpg')])
            ->assertOk()
            ->assertJsonPath('analyse.type_vetement', 'Blazer')
            ->assertJsonPath('analyse.couleur', 'Bleu marine')
            ->assertJsonPath('analyse.etat', 'BON');
    }

    public function test_l_analyse_refuse_une_photo_sans_vetement(): void
    {
        $this->fakeGemini([
            'est_vetement' => false, 'type_vetement' => '', 'matiere' => '', 'couleur' => '', 'etat' => 'BON', 'description' => 'Un chat.', 'defauts' => [],
        ]);

        $this->actingAs($this->client())
            ->postJson(route('upcycling.analyse-photo'), ['photo' => UploadedFile::fake()->image('chat.jpg')])
            ->assertStatus(422);
    }

    public function test_la_consigne_du_client_est_transmise_a_l_ia(): void
    {
        $client = $this->client();
        $projet = ProjetUpcycling::factory()->vetement(0)->for($client, 'client')->create();
        $this->fakeGemini(['defauts' => [], 'idees' => [$this->ideeGemini('Sac enfant', 'SAC')]]);

        $this->actingAs($client)->post(route('upcycling.projets.idees', $projet), ['consigne' => 'pour un enfant']);

        $this->assertSame('Sac enfant', $projet->fresh()->idee_generee_ia[0]['titre']);
        Http::assertSent(fn ($request) => str_contains($request->body(), 'pour un enfant'));
    }

    public function test_la_validation_refuse_une_demande_incomplete(): void
    {
        $this->actingAs($this->client())
            ->post(route('upcycling.projets.store'), [
                'type_vetement' => '', 'etat' => 'INCONNU',
                'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors(['type_vetement', 'matiere', 'etat', 'description', 'photo']);

        $this->assertDatabaseCount('projet_upcyclings', 0);
    }

    public function test_un_atelier_ne_peut_pas_creer_de_demande(): void
    {
        $atelier = Atelier::factory()->create();

        $this->actingAs($atelier->user)->get(route('upcycling.projets.create'))->assertForbidden();
    }

    public function test_le_matching_classe_d_abord_l_atelier_de_la_bonne_specialite(): void
    {
        $client = $this->client();
        $sac = Atelier::factory()->specialite('SAC')->create(['tarif_horaire' => 20]);
        Atelier::factory()->specialite('VETEMENT')->create(['tarif_horaire' => 10, 'note_moyenne' => 5]);

        $projet = ProjetUpcycling::factory()->vetement(0)->avecIdee('SAC')->for($client, 'client')->create();

        $this->actingAs($client)
            ->get(route('upcycling.projets.matching', $projet))
            ->assertOk()
            ->assertViewHas('classement', fn ($classement) => $classement->first()['atelier']->is($sac)
                && $classement->first()['details']['specialite'] === 40);
    }

    public function test_parcours_complet_devis_suivi_photo_resultat_et_note(): void
    {
        Storage::fake('public');
        $client  = $this->client();
        $atelier = Atelier::factory()->specialite('SAC')->create();
        $projet  = ProjetUpcycling::factory()->vetement(0)->for($client, 'client')->create();

        // 1. Le client retient la première idée puis choisit l'atelier
        $this->actingAs($client)->patch(route('upcycling.projets.idee', $projet), ['index' => 0])
            ->assertRedirect(route('upcycling.projets.matching', $projet));
        $this->assertNotNull($projet->fresh()->prix_estime_min);
        $this->actingAs($client)->patch(route('upcycling.projets.atelier', $projet), ['atelier_id' => $atelier->id]);
        $this->assertSame('ATELIER_CHOISI', $projet->fresh()->statut);

        // 2. L'atelier envoie un devis, le client l'accepte
        $this->actingAs($atelier->user)->post(route('upcycling.devis.store', $projet), ['montant' => 40, 'delai_jours' => 7]);
        $devis = Devis::first();
        $this->actingAs($client)->patch(route('upcycling.devis.accepter', $devis));
        $this->assertSame('ACCEPTE', $devis->fresh()->statut);
        $this->assertSame('DEVIS_ACCEPTE', $projet->fresh()->statut);

        // 3. L'atelier avance jusqu'à la fin et ajoute la photo du produit fini
        foreach (['CONCEPTION', 'CONFECTION', 'FINITION'] as $etape) {
            $this->actingAs($atelier->user)->patch(route('upcycling.projets.avancer', $projet));
            $this->assertSame($etape, $projet->fresh()->statut);
        }
        $this->actingAs($atelier->user)->patch(route('upcycling.projets.avancer', $projet), [
            'photo_resultat' => UploadedFile::fake()->image('sac.jpg'),
        ]);
        $projet->refresh();
        $this->assertSame('TERMINE', $projet->statut);
        Storage::disk('public')->assertExists($projet->photo_resultat);
        $this->actingAs($atelier->user)->patch(route('upcycling.projets.avancer', $projet))->assertStatus(422);

        // 4. Le client note l'atelier : la moyenne est recalculée
        $this->actingAs($client)->patch(route('upcycling.projets.noter', $projet), ['note_client' => 4, 'commentaire_client' => 'Top']);
        $this->assertSame(4.0, $atelier->fresh()->note_moyenne);
    }

    public function test_seul_l_atelier_assigne_peut_envoyer_un_devis(): void
    {
        $atelier = Atelier::factory()->create();
        $autre   = Atelier::factory()->create();
        $projet  = ProjetUpcycling::factory()->pourAtelier($atelier)->create();

        $this->actingAs($autre->user)
            ->post(route('upcycling.devis.store', $projet), ['montant' => 40, 'delai_jours' => 7])
            ->assertForbidden();

        $this->assertDatabaseCount('devis', 0);
    }

    public function test_un_client_ne_voit_pas_le_projet_d_un_autre(): void
    {
        $projet = ProjetUpcycling::factory()->create();

        $this->actingAs($this->client())->get(route('upcycling.projets.show', $projet))->assertForbidden();
    }

    public function test_changer_d_atelier_refuse_le_devis_en_attente(): void
    {
        $ancien  = Atelier::factory()->create();
        $nouveau = Atelier::factory()->create();
        $projet  = ProjetUpcycling::factory()->pourAtelier($ancien)->has(Devis::factory(), 'devis')->create();

        $this->actingAs($projet->client)->patch(route('upcycling.projets.atelier', $projet), ['atelier_id' => $nouveau->id]);

        $this->assertSame('REFUSE', $projet->devis()->first()->statut);
        $this->assertSame($nouveau->id, $projet->fresh()->atelier_id);
    }

    public function test_la_suppression_d_une_demande_supprime_sa_photo(): void
    {
        Storage::fake('public');
        $client = $this->client();
        $chemin = UploadedFile::fake()->image('robe.jpg')->store('upcycling/projets', 'public');
        $projet = ProjetUpcycling::factory()->for($client, 'client')->create(['photo' => $chemin]);

        $this->actingAs($client)->delete(route('upcycling.projets.destroy', $projet))->assertRedirect();

        $this->assertModelMissing($projet);
        Storage::disk('public')->assertMissing($chemin);
    }

    public function test_un_atelier_gere_son_profil_avec_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'ATELIER']);

        $this->actingAs($user)->post(route('upcycling.ateliers.store'), [
            'nom' => 'Mon Atelier', 'specialite' => 'DECORATION', 'tarif_horaire' => 18, 'localisation' => 'Nabeul',
            'photo' => UploadedFile::fake()->image('atelier.jpg'),
        ])->assertRedirect();

        $atelier = Atelier::where('user_id', $user->id)->firstOrFail();
        Storage::disk('public')->assertExists($atelier->photo);

        $this->actingAs($user)->put(route('upcycling.ateliers.update', $atelier), [
            'nom' => 'Mon Atelier', 'specialite' => 'DECORATION', 'tarif_horaire' => 25, 'localisation' => 'Nabeul', 'actif' => '1',
        ]);
        $this->assertSame(25.0, $atelier->fresh()->tarif_horaire);

        $this->actingAs($this->client())->put(route('upcycling.ateliers.update', $atelier), [
            'nom' => 'Piraté', 'specialite' => 'SAC', 'tarif_horaire' => 1, 'localisation' => 'X',
        ])->assertForbidden();
    }

    public function test_le_seeder_cree_des_donnees_coherentes(): void
    {
        $this->seed(\Database\Seeders\UpcyclingSeeder::class);

        $this->assertGreaterThan(5, ProjetUpcycling::count());
        $this->assertTrue(ProjetUpcycling::whereNotNull('photo')->exists());
        $this->assertTrue(Atelier::where('note_moyenne', '>', 0)->exists());
        $this->assertSame(0, ProjetUpcycling::whereNotNull('atelier_id')->whereDoesntHave('atelier')->count());
    }

    public function test_les_pages_du_module_s_affichent(): void
    {
        $this->seed(\Database\Seeders\UpcyclingSeeder::class);
        $client  = User::where('email', 'client@retiss.tn')->first();
        $atelier = Atelier::where('nom', "Atelier Fil d'Or")->first();
        $projet  = ProjetUpcycling::where('client_id', $client->id)->where('statut', 'ATELIER_CHOISI')->first();
        $libre   = ProjetUpcycling::where('client_id', $client->id)->whereNotNull('produit_final')->whereNull('atelier_id')->first();

        foreach ([
            route('upcycling.dashboard'),
            route('upcycling.projets.index'),
            route('upcycling.projets.create'),
            route('upcycling.projets.show', $projet),
            route('upcycling.projets.edit', $projet),
            route('upcycling.projets.matching', $libre),
            route('upcycling.ateliers.index'),
            route('upcycling.ateliers.show', $atelier),
        ] as $url) {
            $this->actingAs($client)->get($url)->assertOk();
        }

        foreach ([
            route('upcycling.dashboard'),
            route('upcycling.projets.index'),
            route('upcycling.projets.show', $projet),
            route('upcycling.ateliers.edit', $atelier),
        ] as $url) {
            $this->actingAs($atelier->user)->get($url)->assertOk();
        }
    }
}
