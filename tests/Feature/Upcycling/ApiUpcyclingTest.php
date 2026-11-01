<?php

namespace Tests\Feature\Upcycling;

use App\Models\Atelier;
use App\Models\Devis;
use App\Models\ProjetUpcycling;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API REST v1 : Sanctum (tokens + permissions), API Resources, Form Requests, rate limiting.
 */
class ApiUpcyclingTest extends TestCase
{
    use RefreshDatabase;

    const TOUTES = ['upcycling:lire', 'upcycling:ecrire', 'upcycling:ia'];

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.key' => null]);
        Http::preventStrayRequests();
    }

    private function client(array $abilities = self::TOUTES): User
    {
        $user = User::factory()->create(['role' => 'CLIENT']);
        Sanctum::actingAs($user, $abilities);

        return $user;
    }

    /*
    |------------------------------------------------------------------
    | Sanctum : tokens et permissions
    |------------------------------------------------------------------
    */

    public function test_un_utilisateur_obtient_un_token_avec_ses_identifiants(): void
    {
        User::factory()->create(['email' => 'sarra@test.tn', 'password' => Hash::make('secret123'), 'role' => 'CLIENT']);

        $reponse = $this->postJson('/api/v1/auth/token', [
            'email' => 'sarra@test.tn', 'password' => 'secret123', 'nom_appareil' => 'postman',
            'abilities' => ['upcycling:lire'],
        ]);

        $reponse->assertCreated()
            ->assertJsonStructure(['token', 'type', 'abilities', 'expire_le', 'utilisateur' => ['id', 'nom_complet', 'role', 'email']])
            ->assertJsonPath('abilities', ['upcycling:lire'])
            ->assertJsonMissingPath('utilisateur.password');

        // Le token fonctionne pour lire, pas pour écrire
        $token = $reponse->json('token');
        $this->withToken($token)->getJson('/api/v1/projets')->assertOk();
        $this->withToken($token)->postJson('/api/v1/projets', [])->assertForbidden();
    }

    public function test_de_mauvais_identifiants_sont_refuses_en_json(): void
    {
        User::factory()->create(['email' => 'sarra@test.tn', 'password' => Hash::make('secret123')]);

        $this->postJson('/api/v1/auth/token', ['email' => 'sarra@test.tn', 'password' => 'faux', 'nom_appareil' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_sans_token_l_api_repond_401_en_json(): void
    {
        $this->getJson('/api/v1/projets')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
        $this->get('/api/v1/projets')->assertUnauthorized(); // même sans en-tête Accept
    }

    public function test_un_token_peut_etre_revoque(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test', self::TOUTES)->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/auth/moi')->assertOk()->assertJsonPath('data.id', $user->id);
        $this->withToken($token)->deleteJson('/api/v1/auth/token')->assertNoContent();

        $this->assertSame(0, $user->tokens()->count());
    }

    /*
    |------------------------------------------------------------------
    | API Resources
    |------------------------------------------------------------------
    */

    public function test_la_liste_des_ateliers_est_paginee_et_ne_fuit_pas_l_embedding(): void
    {
        $this->client();
        Atelier::factory()->count(3)->specialite('SAC')->create(['embedding_ia' => [0.1, 0.2]]);
        Atelier::factory()->specialite('VETEMENT')->create();

        $reponse = $this->getJson('/api/v1/ateliers?specialite=SAC&par_page=2');

        $reponse->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure(['data' => [['id', 'nom', 'specialite' => ['code', 'libelle'], 'tarif_horaire', 'liens']], 'links', 'meta']);
        $this->assertStringNotContainsString('embedding', $reponse->getContent());
    }

    public function test_un_client_ne_voit_que_ses_projets(): void
    {
        $client = $this->client();
        ProjetUpcycling::factory()->count(2)->for($client, 'client')->create();
        ProjetUpcycling::factory()->create(); // projet d'un autre client

        $this->getJson('/api/v1/projets')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_le_detail_d_un_projet_d_un_autre_client_est_interdit(): void
    {
        $this->client();
        $projet = ProjetUpcycling::factory()->create();

        $this->getJson("/api/v1/projets/{$projet->id}")->assertForbidden()->assertJson(['message' => 'Accès non autorisé.']);
    }

    public function test_une_ressource_inexistante_renvoie_un_404_sans_details_internes(): void
    {
        $this->client();

        $reponse = $this->getJson('/api/v1/projets/9999');

        $reponse->assertNotFound()->assertExactJson(['message' => 'Ressource introuvable.']);
    }

    /*
    |------------------------------------------------------------------
    | Parcours complet par l'API
    |------------------------------------------------------------------
    */

    public function test_parcours_complet_par_l_api(): void
    {
        $client  = $this->client();
        $atelier = Atelier::factory()->specialite('SAC')->create();

        // 1. Création : le générateur local propose 3 idées
        $projetId = $this->postJson('/api/v1/projets', [
            'type_vetement' => 'Jean', 'matiere' => 'Denim', 'etat' => 'USE', 'description' => 'Vieux jean troué au genou.',
        ])->assertCreated()
          ->assertJsonPath('data.statut.code', 'DEMANDE')
          ->assertJsonCount(3, 'data.ia.idees')
          ->json('data.id');

        // 2. Choix de l'idée, matching, choix de l'atelier
        $this->patchJson("/api/v1/projets/{$projetId}/idee", ['index' => 0])
            ->assertOk()->assertJsonPath('data.produit.titre', 'Sac cabas en denim');
        $this->getJson("/api/v1/projets/{$projetId}/matching")
            ->assertOk()
            ->assertJsonPath('data.0.rang', 1)
            ->assertJsonPath('data.0.atelier.id', $atelier->id)
            ->assertJsonPath('meta.methode_affinite', 'LEXICALE');
        $this->patchJson("/api/v1/projets/{$projetId}/atelier", ['atelier_id' => $atelier->id])
            ->assertOk()->assertJsonPath('data.statut.code', 'ATELIER_CHOISI');

        // 3. L'atelier envoie un devis, le client l'accepte
        Sanctum::actingAs($atelier->user, self::TOUTES);
        $devisId = $this->postJson("/api/v1/projets/{$projetId}/devis", ['montant' => 45, 'delai_jours' => 6])
            ->assertCreated()->assertJsonPath('data.statut', 'EN_ATTENTE')->json('data.id');

        Sanctum::actingAs($client, self::TOUTES);
        $this->postJson("/api/v1/devis/{$devisId}/acceptation")->assertOk()->assertJsonPath('data.statut', 'ACCEPTE');
        // Règle métier : un devis déjà accepté ne peut pas être refusé
        $this->postJson("/api/v1/devis/{$devisId}/refus")
            ->assertUnprocessable()->assertJson(['message' => 'Ce devis a déjà reçu une réponse.']);

        // 4. L'atelier termine, le client note
        Sanctum::actingAs($atelier->user, self::TOUTES);
        foreach (['CONCEPTION', 'CONFECTION', 'FINITION', 'TERMINE'] as $etape) {
            $this->postJson("/api/v1/projets/{$projetId}/avancer")->assertOk()->assertJsonPath('data.statut.code', $etape);
        }

        Sanctum::actingAs($client, self::TOUTES);
        $this->postJson("/api/v1/projets/{$projetId}/noter", ['note_client' => 5, 'commentaire_client' => 'Parfait'])
            ->assertOk()->assertJsonPath('data.avis.note', 5);
        $this->assertSame(5.0, $atelier->fresh()->note_moyenne);
    }

    public function test_un_atelier_ne_peut_pas_accepter_son_propre_devis(): void
    {
        $atelier = Atelier::factory()->create();
        $projet  = ProjetUpcycling::factory()->pourAtelier($atelier)->has(Devis::factory(), 'devis')->create();
        Sanctum::actingAs($atelier->user, self::TOUTES);

        $this->postJson("/api/v1/devis/{$projet->devis()->first()->id}/acceptation")->assertForbidden();
    }

    /*
    |------------------------------------------------------------------
    | Form Requests
    |------------------------------------------------------------------
    */

    public function test_la_validation_renvoie_des_erreurs_json_en_francais(): void
    {
        $this->client();

        $this->postJson('/api/v1/projets', ['etat' => 'INCONNU'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type_vetement', 'matiere', 'etat', 'description'])
            ->assertJsonPath('errors.type_vetement.0', 'Le type de vêtement est obligatoire.');
    }

    public function test_la_mise_a_jour_partielle_ne_valide_que_les_champs_envoyes(): void
    {
        $client = $this->client();
        $projet = ProjetUpcycling::factory()->vetement(0)->for($client, 'client')->create();

        $this->patchJson("/api/v1/projets/{$projet->id}", ['budget_max' => 99])
            ->assertOk()->assertJsonPath('data.budget_max', 99);
        $this->assertSame('Jean', $projet->fresh()->type_vetement);
    }

    /*
    |------------------------------------------------------------------
    | IA : permission dédiée et rate limiting
    |------------------------------------------------------------------
    */

    public function test_le_styliste_ia_exige_la_permission_ia(): void
    {
        $client  = $this->client(['upcycling:lire', 'upcycling:ecrire']);
        $atelier = Atelier::factory()->create();
        $projet  = ProjetUpcycling::factory()->vetement(0)->pourAtelier($atelier)->for($client, 'client')->create();

        $this->postJson("/api/v1/projets/{$projet->id}/styliste", ['contenu' => 'Une idée ?'])->assertForbidden();

        Sanctum::actingAs($client, self::TOUTES);
        $this->postJson("/api/v1/projets/{$projet->id}/styliste", ['contenu' => 'Une idée ?'])
            ->assertCreated()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.auteur', 'IA');
    }

    public function test_les_appels_ia_sont_limites_a_10_par_minute(): void
    {
        $client = $this->client();
        $projet = ProjetUpcycling::factory()->vetement(0)->for($client, 'client')->create();

        for ($i = 0; $i < 10; $i++) {
            $this->postJson("/api/v1/projets/{$projet->id}/idees")->assertOk();
        }

        $this->postJson("/api/v1/projets/{$projet->id}/idees")
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertJsonStructure(['message', 'reessayer_dans']);
    }

    public function test_la_connexion_est_limitee_contre_le_brute_force(): void
    {
        User::factory()->create(['email' => 'cible@test.tn']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/token', ['email' => 'cible@test.tn', 'password' => 'faux', 'nom_appareil' => 'x'])
                ->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/token', ['email' => 'cible@test.tn', 'password' => 'faux', 'nom_appareil' => 'x'])
            ->assertTooManyRequests();
    }

    /*
    |------------------------------------------------------------------
    | Page « Accès API » du site
    |------------------------------------------------------------------
    */

    public function test_la_page_acces_api_cree_et_revoque_des_tokens(): void
    {
        $user = User::factory()->create(['role' => 'CLIENT']);

        $this->actingAs($user)->get(route('upcycling.api.index'))->assertOk()->assertSee('API REST');

        $this->actingAs($user)->post(route('upcycling.api.tokens.store'), [
            'nom' => 'Postman', 'abilities' => ['upcycling:lire'], 'expiration' => '7',
        ])->assertSessionHas('nouveau_token');

        $token = $user->tokens()->first();
        $this->assertSame(['upcycling:lire'], $token->abilities);
        $this->assertTrue($token->expires_at->isFuture());

        $this->actingAs($user)->delete(route('upcycling.api.tokens.destroy', $token->id))->assertRedirect();
        $this->assertSame(0, $user->tokens()->count());

        // On ne peut pas révoquer le token d'un autre
        $autre = User::factory()->create()->createToken('x')->accessToken;
        $this->actingAs($user)->delete(route('upcycling.api.tokens.destroy', $autre->id))->assertNotFound();
    }
}
