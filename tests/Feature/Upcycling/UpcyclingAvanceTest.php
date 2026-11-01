<?php

namespace Tests\Feature\Upcycling;

use App\Models\Article;
use App\Models\Atelier;
use App\Models\Devis;
use App\Models\MessageProjet;
use App\Models\ProjetUpcycling;
use App\Models\User;
use App\Notifications\UpcyclingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fonctionnalités avancées du module M3 : styliste IA, messagerie, notifications,
 * contrôle qualité, matching sémantique, devis PDF, revente marketplace, retards.
 */
class UpcyclingAvanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.key' => null]);
        Http::preventStrayRequests();
    }

    /** Réponse texte libre de Gemini */
    private function fakeTexteGemini(string $texte): void
    {
        config(['services.gemini.key' => 'cle-de-test']);
        Http::fake([
            '*:generateContent' => Http::response(['candidates' => [['content' => ['parts' => [['text' => $texte]]]]]]),
        ]);
    }

    private function projetEnCours(): ProjetUpcycling
    {
        $atelier = Atelier::factory()->specialite('SAC')->create();

        return ProjetUpcycling::factory()->vetement(0)->pourAtelier($atelier)->enCours()
            ->has(Devis::factory()->accepte(), 'devis')->create();
    }

    /*
    |------------------------------------------------------------------
    | Styliste IA + messagerie
    |------------------------------------------------------------------
    */

    public function test_le_styliste_ia_repond_avec_le_contexte_du_projet(): void
    {
        $projet = $this->projetEnCours();
        $this->fakeTexteGemini('Une doublure en toile de coton est idéale.');

        $this->actingAs($projet->client)
            ->post(route('upcycling.messages.store', $projet), ['contenu' => 'Quelle doublure choisir ?', 'ia' => '1'])
            ->assertRedirect();

        $messages = $projet->messages()->get();
        $this->assertCount(2, $messages);
        $this->assertSame(MessageProjet::AUTEUR_CLIENT, $messages[0]->auteur);
        $this->assertSame(MessageProjet::AUTEUR_IA, $messages[1]->auteur);
        $this->assertSame('Une doublure en toile de coton est idéale.', $messages[1]->contenu);

        // La fiche du projet et la question sont transmises à Gemini
        Http::assertSent(fn ($request) => str_contains($request->body(), $projet->produit_final)
            && str_contains($request->body(), 'Quelle doublure choisir ?'));
    }

    public function test_sans_cle_le_styliste_repond_avec_le_rappel_du_projet(): void
    {
        $projet = $this->projetEnCours();

        $this->actingAs($projet->client)
            ->post(route('upcycling.messages.store', $projet), ['contenu' => 'Une idée ?', 'ia' => '1']);

        $reponse = $projet->messages()->where('auteur', 'IA')->first();
        $this->assertStringContainsString($projet->produit_final, $reponse->contenu);
    }

    public function test_un_message_a_l_atelier_le_notifie(): void
    {
        Notification::fake();
        $projet = $this->projetEnCours();

        $this->actingAs($projet->client)
            ->post(route('upcycling.messages.store', $projet), ['contenu' => 'Bonjour, une question sur les poches.'])
            ->assertRedirect();

        $this->assertSame(1, $projet->messages()->count());
        Notification::assertSentTo($projet->atelier->user, UpcyclingNotification::class);
    }

    public function test_un_tiers_ne_peut_pas_ecrire_dans_la_discussion(): void
    {
        $projet = $this->projetEnCours();
        $intrus = User::factory()->create(['role' => 'CLIENT']);

        $this->actingAs($intrus)
            ->post(route('upcycling.messages.store', $projet), ['contenu' => 'Je ne devrais pas pouvoir écrire'])
            ->assertForbidden();
    }

    public function test_un_message_vide_est_refuse(): void
    {
        $projet = $this->projetEnCours();

        $this->actingAs($projet->client)
            ->post(route('upcycling.messages.store', $projet), ['contenu' => ''])
            ->assertSessionHasErrors('contenu');
    }

    /*
    |------------------------------------------------------------------
    | Notifications
    |------------------------------------------------------------------
    */

    public function test_le_parcours_devis_genere_des_notifications(): void
    {
        $atelier = Atelier::factory()->create();
        $projet  = ProjetUpcycling::factory()->vetement(1)->pourAtelier($atelier)->create();

        $this->actingAs($atelier->user)->post(route('upcycling.devis.store', $projet), ['montant' => 50, 'delai_jours' => 6]);
        $this->assertSame(1, $projet->client->notifications()->count());

        $this->actingAs($projet->client)->patch(route('upcycling.devis.accepter', $projet->devis()->first()));
        $this->assertSame(1, $atelier->user->notifications()->count());

        // Ouvrir la notification la marque comme lue et redirige vers le projet
        $notification = $projet->client->notifications()->first();
        $this->actingAs($projet->client)
            ->get(route('notifications.lire', $notification->id))
            ->assertRedirect(route('upcycling.projets.show', $projet));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_on_ne_peut_pas_lire_la_notification_d_un_autre(): void
    {
        $projet = $this->projetEnCours();
        $projet->client->notify(new UpcyclingNotification($projet, 'Test'));
        $notification = $projet->client->notifications()->first();

        $this->actingAs(User::factory()->create())
            ->get(route('notifications.lire', $notification->id))
            ->assertNotFound();
    }

    /*
    |------------------------------------------------------------------
    | Contrôle qualité IA
    |------------------------------------------------------------------
    */

    public function test_la_fin_du_projet_declenche_le_controle_qualite_ia(): void
    {
        Storage::fake('public');
        $projet = $this->projetEnCours();
        $projet->update(['statut' => 'FINITION']);

        config(['services.gemini.key' => 'cle-de-test']);
        Http::fake(['*:generateContent' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode([
            'conforme' => true, 'score' => 88, 'verdict' => 'Très beau travail.', 'points_forts' => ['Coutures nettes'],
            'ameliorations' => [], 'description_portfolio' => 'Un sac unique.',
        ])]]]]]])]);

        $this->actingAs($projet->atelier->user)->patch(route('upcycling.projets.avancer', $projet), [
            'photo_resultat' => UploadedFile::fake()->image('sac.jpg'),
        ]);

        $projet->refresh();
        $this->assertSame('TERMINE', $projet->statut);
        $this->assertSame(88, $projet->controle_qualite_ia['score']);
        $this->assertTrue($projet->controle_qualite_ia['conforme']);
        // Les deux photos (avant + après) sont envoyées
        Http::assertSent(fn ($request) => substr_count($request->body(), 'inline_data') === 2);
    }

    /*
    |------------------------------------------------------------------
    | Matching sémantique (embeddings)
    |------------------------------------------------------------------
    */

    public function test_le_matching_utilise_les_embeddings_et_les_met_en_cache(): void
    {
        $client = User::factory()->create(['role' => 'CLIENT']);
        $proche = Atelier::factory()->specialite('DECORATION')->create();
        $loin   = Atelier::factory()->specialite('DECORATION')->create();
        $projet = ProjetUpcycling::factory()->vetement(0)->avecIdee('SAC')->for($client, 'client')->create();

        // Vecteur du projet, puis des deux ateliers (même ordre que la requête)
        config(['services.gemini.key' => 'cle-de-test']);
        Http::fake(['*:batchEmbedContents' => Http::response(['embeddings' => [
            ['values' => [1, 0, 0]], ['values' => [0.95, 0.3, 0]], ['values' => [0, 1, 0]],
        ]])]);

        $this->actingAs($client)->get(route('upcycling.projets.matching', $projet))
            ->assertOk()
            ->assertViewHas('methodeAffinite', 'GEMINI')
            ->assertViewHas('classement', fn ($c) => $c->first()['atelier']->is($proche)
                && $c->first()['details']['affinite'] > 0
                && $c->last()['details']['affinite'] === 0);

        $this->assertNotNull($proche->fresh()->embedding_ia);
        $this->assertNotNull($loin->fresh()->embedding_empreinte);
    }

    public function test_sans_cle_l_affinite_est_lexicale(): void
    {
        $client = User::factory()->create(['role' => 'CLIENT']);
        Atelier::factory()->create(['description' => 'Spécialiste des sacs cabas en denim recyclé']);
        $projet = ProjetUpcycling::factory()->vetement(0)->avecIdee('SAC')->for($client, 'client')->create();

        $this->actingAs($client)->get(route('upcycling.projets.matching', $projet))
            ->assertOk()
            ->assertViewHas('methodeAffinite', 'LEXICALE');
    }

    /*
    |------------------------------------------------------------------
    | Devis PDF, revente marketplace, retards, dashboard
    |------------------------------------------------------------------
    */

    public function test_le_devis_se_telecharge_en_pdf(): void
    {
        $projet = $this->projetEnCours();
        $devis  = $projet->devis()->first();

        $reponse = $this->actingAs($projet->client)->get(route('upcycling.devis.pdf', $devis));
        $reponse->assertOk();
        $this->assertSame('application/pdf', $reponse->headers->get('content-type'));

        $this->actingAs(User::factory()->create())->get(route('upcycling.devis.pdf', $devis))->assertForbidden();
    }

    public function test_le_client_revend_son_produit_fini_sur_la_marketplace(): void
    {
        Storage::fake('public');
        $projet = $this->projetEnCours();
        $projet->update([
            'statut'         => 'TERMINE',
            'date_fin'       => now(),
            'photo_resultat' => UploadedFile::fake()->image('sac.jpg')->store('upcycling/projets', 'public'),
        ]);

        $this->actingAs($projet->client)
            ->post(route('upcycling.projets.publier', $projet), ['prix' => 65])
            ->assertRedirect(route('upcycling.projets.show', $projet));

        $article = Article::first();
        $this->assertSame($projet->produit_final, $article->titre);
        $this->assertSame('Textile Upcyclé', $article->categorie);
        $this->assertEquals(65, $article->prix);
        $this->assertSame($projet->client_id, $article->user_id);
        Storage::disk('public')->assertExists($article->images[0]);
        $this->assertSame($article->id, $projet->fresh()->article_id);

        // Une seule publication possible
        $this->actingAs($projet->client)->post(route('upcycling.projets.publier', $projet), ['prix' => 70])->assertStatus(422);
    }

    public function test_un_projet_en_cours_hors_delai_est_en_retard(): void
    {
        $projet = $this->projetEnCours();
        $projet->update(['date_debut' => now()->subDays(30)]);
        $projet->devis()->update(['delai_jours' => 7]);

        $this->assertTrue($projet->fresh()->en_retard);

        $projet->update(['date_debut' => now()]);
        $this->assertFalse($projet->fresh()->en_retard);
    }

    public function test_le_dashboard_atelier_fournit_les_graphiques(): void
    {
        $projet = $this->projetEnCours();
        $projet->update(['statut' => 'TERMINE', 'date_fin' => now()]);

        $this->actingAs($projet->atelier->user)->get(route('upcycling.dashboard'))
            ->assertOk()
            ->assertViewHas('graphiques', fn ($g) => count($g['mois']) === 6
                && end($g['valeurs']) == $projet->devis()->first()->montant);
    }

    public function test_la_page_projet_affiche_la_discussion_et_le_resultat(): void
    {
        $projet = $this->projetEnCours();
        MessageProjet::factory()->for($projet, 'projetUpcycling')->create(['contenu' => 'Bonjour atelier']);
        MessageProjet::factory()->for($projet, 'projetUpcycling')->parIa()->create();

        $this->actingAs($projet->client)->get(route('upcycling.projets.show', $projet))
            ->assertOk()
            ->assertSee('Discussion du projet')
            ->assertSee('Bonjour atelier')
            ->assertSee('Styliste IA');
    }
}
