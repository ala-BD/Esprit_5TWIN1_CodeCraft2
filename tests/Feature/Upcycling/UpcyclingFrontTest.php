<?php

namespace Tests\Feature\Upcycling;

use App\Models\Atelier;
use App\Models\Devis;
use App\Models\MessageProjet;
use App\Models\ProjetUpcycling;
use App\Models\User;
use App\Notifications\UpcyclingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fonctionnalités front avancées du module M3 : discussion en temps réel (AJAX),
 * notifications en direct, tableau Kanban (glisser-déposer), recherche live.
 */
class UpcyclingFrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.key' => null]);
        Http::preventStrayRequests();
    }

    private function projetEnCours(string $statut = 'CONCEPTION'): ProjetUpcycling
    {
        $atelier = Atelier::factory()->specialite('SAC')->create();

        $projet = ProjetUpcycling::factory()->vetement(0)->pourAtelier($atelier)->enCours()
            ->has(Devis::factory()->accepte(), 'devis')->create();
        $projet->update(['statut' => $statut]);

        return $projet;
    }

    /*
    |------------------------------------------------------------------
    | Discussion AJAX
    |------------------------------------------------------------------
    */

    public function test_un_message_envoye_en_ajax_renvoie_les_bulles_a_afficher(): void
    {
        $projet = $this->projetEnCours();

        $this->actingAs($projet->client)
            ->postJson(route('upcycling.messages.store', $projet), ['contenu' => 'Une idée de doublure ?', 'ia' => true])
            ->assertCreated()
            ->assertJsonCount(2, 'ids')
            ->assertJsonPath('total', 2)
            ->assertJsonPath('ia', true)
            ->assertJson(fn ($json) => $json->where('html', fn ($html) => str_contains($html, 'Une idée de doublure ?')
                && str_contains($html, 'data-message-id'))->etc());
    }

    public function test_la_validation_ajax_renvoie_une_erreur_422(): void
    {
        $projet = $this->projetEnCours();

        $this->actingAs($projet->client)
            ->postJson(route('upcycling.messages.store', $projet), ['contenu' => 'a'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['contenu' => 'Le message est trop court.']);
    }

    public function test_l_actualisation_ne_renvoie_que_les_nouveaux_messages(): void
    {
        $projet = $this->projetEnCours();
        $ancien = MessageProjet::factory()->for($projet, 'projetUpcycling')->create(['contenu' => 'Ancien message']);
        $nouveau = MessageProjet::factory()->for($projet, 'projetUpcycling')->parAtelier()->create(['contenu' => 'Le tissu est arrivé']);

        $this->actingAs($projet->client)
            ->getJson(route('upcycling.messages.index', [$projet, 'apres' => $ancien->id]))
            ->assertOk()
            ->assertJsonPath('ids', [$nouveau->id])
            ->assertJsonPath('dernier_id', $nouveau->id)
            ->assertJson(fn ($json) => $json->where('html', fn ($html) => str_contains($html, 'Le tissu est arrivé')
                && !str_contains($html, 'Ancien message'))->etc());
    }

    public function test_un_tiers_ne_peut_pas_lire_la_discussion(): void
    {
        $projet = $this->projetEnCours();

        $this->actingAs(User::factory()->create(['role' => 'CLIENT']))
            ->getJson(route('upcycling.messages.index', $projet))
            ->assertForbidden();
    }

    /*
    |------------------------------------------------------------------
    | Notifications en direct
    |------------------------------------------------------------------
    */

    public function test_le_resume_des_notifications_donne_le_compteur_et_la_liste(): void
    {
        $projet = $this->projetEnCours();
        $projet->client->notify(new UpcyclingNotification($projet, 'Votre projet avance'));

        $this->actingAs($projet->client)
            ->getJson(route('notifications.resume'))
            ->assertOk()
            ->assertJsonPath('non_lues', 1)
            ->assertJsonPath('derniere', 'Votre projet avance')
            ->assertJson(fn ($json) => $json->where('html', fn ($html) => str_contains($html, 'Votre projet avance'))->etc());
    }

    /*
    |------------------------------------------------------------------
    | Tableau Kanban
    |------------------------------------------------------------------
    */

    public function test_l_atelier_voit_ses_projets_par_colonne(): void
    {
        $projet = $this->projetEnCours('CONFECTION');
        $autre  = $this->projetEnCours('FINITION');      // projet d'un autre atelier

        $this->actingAs($projet->atelier->user)
            ->get(route('upcycling.projets.kanban'))
            ->assertOk()
            ->assertSee('Tableau de production')
            ->assertSee('data-url-avancer="' . route('upcycling.projets.avancer', $projet) . '"', false)
            ->assertDontSee('data-url-avancer="' . route('upcycling.projets.avancer', $autre) . '"', false)
            ->assertViewHas('colonnes', fn ($c) => $c['CONFECTION']['projets']->contains($projet)
                && $c['FINITION']['projets']->isEmpty());
    }

    public function test_le_kanban_est_reserve_aux_ateliers(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'CLIENT']))
            ->get(route('upcycling.projets.kanban'))
            ->assertForbidden();
    }

    public function test_deplacer_une_carte_fait_avancer_le_projet(): void
    {
        $projet = $this->projetEnCours('CONCEPTION');

        $this->actingAs($projet->atelier->user)
            ->patchJson(route('upcycling.projets.avancer', $projet))
            ->assertOk()
            ->assertJsonPath('statut', 'CONFECTION')
            ->assertJson(fn ($json) => $json->where('carte', fn ($html) => str_contains($html, 'data-suivante="FINITION"'))->etc());

        $this->assertSame('CONFECTION', $projet->fresh()->statut);
        $this->assertSame(1, $projet->client->notifications()->count());
    }

    public function test_terminer_depuis_le_kanban_enregistre_la_photo(): void
    {
        $disque = Storage::fake('public');
        $projet = $this->projetEnCours('FINITION');

        $this->actingAs($projet->atelier->user)
            ->patchJson(route('upcycling.projets.avancer', $projet), ['photo_resultat' => UploadedFile::fake()->image('sac.jpg')])
            ->assertOk()
            ->assertJsonPath('statut', 'TERMINE');

        $disque->assertExists($projet->fresh()->photo_resultat);
    }

    public function test_une_etape_impossible_renvoie_une_erreur_json(): void
    {
        $projet = $this->projetEnCours('TERMINE');

        $this->actingAs($projet->atelier->user)
            ->patchJson(route('upcycling.projets.avancer', $projet))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Aucune étape suivante pour ce projet.');
    }

    /*
    |------------------------------------------------------------------
    | Pages enrichies
    |------------------------------------------------------------------
    */

    public function test_la_recherche_live_et_les_confirmations_sont_branchees(): void
    {
        $client = User::factory()->create(['role' => 'CLIENT']);
        ProjetUpcycling::factory()->vetement(0)->for($client, 'client')->create();

        $this->actingAs($client)->get(route('upcycling.projets.index', ['q' => 'introuvable']))
            ->assertOk()
            ->assertSee('data-recherche-live', false)
            ->assertSee('id="resultats-projets"', false)
            ->assertSee('Aucun résultat');

        $this->actingAs($client)->get(route('upcycling.projets.index'))
            ->assertSee('data-confirm=', false)
            ->assertDontSee('return confirm(', false);
    }

    public function test_le_projet_termine_affiche_le_comparateur_avant_apres(): void
    {
        $projet = $this->projetEnCours('TERMINE');
        $projet->update(['photo_resultat' => 'upcycling/projets/resultat.jpg']);

        $this->actingAs($projet->client)->get(route('upcycling.projets.show', $projet))
            ->assertOk()
            ->assertSee('data-comparateur', false)
            ->assertSee('Faites glisser pour comparer');
    }
}
