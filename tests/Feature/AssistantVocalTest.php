<?php

namespace Tests\Feature;

use App\Models\Mission;
use App\Models\Tournee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as RequeteHttp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantVocalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['assistant.api_key' => 'cle-de-test', 'assistant.base_url' => 'https://ia.test/v1']);
        Http::preventStrayRequests();

        // Une seule simulation, dont la réponse change au fil du test (les Http::fake successifs ne se remplacent pas)
        Http::fake([
            'ia.test/v1/audio/speech' => fn () => $this->statutVoix === 200
                ? Http::response('RIFF-son-de-test', 200, ['Content-Type' => 'audio/wav'])
                : Http::response(['error' => ['message' => 'requires terms acceptance', 'code' => 'model_terms_required']], $this->statutVoix),
            'ia.test/v1/audio/*' => fn () => Http::response(['text' => ' Désactive Karim Mansouri. ']),
            'ia.test/*'          => fn () => Http::response($this->reponseIa, $this->statutIa),
        ]);
    }

    private array $reponseIa = [];

    private int $statutIa = 200;

    private int $statutVoix = 200;

    private function admin(): User
    {
        return User::factory()->create(['prenom' => 'Ada', 'name' => 'Admin', 'role' => User::ROLE_ADMIN]);
    }

    private function collecteur(): User
    {
        return User::factory()->create(['prenom' => 'Youssef', 'role' => User::ROLE_COLLECTEUR]);
    }

    /** Le modèle répond par un appel d'outil */
    private function iaAppelle(string $action, array $arguments): void
    {
        $this->reponseIa = ['choices' => [['message' => [
            'role'       => 'assistant',
            'content'    => null,
            'tool_calls' => [['id' => 'appel_1', 'type' => 'function', 'function' => ['name' => $action, 'arguments' => json_encode($arguments)]]],
        ]]]];
    }

    /** Le modèle répond par du texte (question de suivi) */
    private function iaRepond(string $texte): void
    {
        $this->reponseIa = ['choices' => [['message' => ['role' => 'assistant', 'content' => $texte]]]];
    }

    private function dicter(User $user, string $texte, array $extra = [])
    {
        return $this->actingAs($user)->postJson('/assistant/interpreter', ['texte' => $texte, 'langue' => 'fr', ...$extra]);
    }

    private function confirmer(User $user, string $action, array $arguments)
    {
        return $this->actingAs($user)->postJson('/assistant/executer', ['action' => $action, 'arguments' => $arguments, 'langue' => 'fr']);
    }

    /*
    |------------------------------------------------------------------
    | Accès et configuration
    |------------------------------------------------------------------
    */

    public function test_guest_cannot_use_the_assistant(): void
    {
        $this->postJson('/assistant/interpreter', ['texte' => 'bonjour', 'langue' => 'fr'])->assertUnauthorized();
        $this->postJson('/assistant/executer', ['action' => 'supprimer_utilisateur'])->assertUnauthorized();
    }

    public function test_roles_without_an_assistant_get_a_message_and_no_ai_call(): void
    {
        $donateur = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $this->dicter($donateur, 'ajoute un utilisateur')->assertOk()->assertJsonPath('type', 'message');
        Http::assertNothingSent();
    }

    public function test_missing_api_key_is_reported_without_calling_the_ai(): void
    {
        config(['assistant.api_key' => null]);

        $this->dicter($this->admin(), 'ajoute un utilisateur')
            ->assertOk()
            ->assertJsonPath('type', 'message')
            ->assertJsonFragment(['message' => "L'assistant n'est pas encore configuré : ajoutez ASSISTANT_API_KEY dans le fichier .env."]);
        Http::assertNothingSent();
    }

    public function test_request_sent_to_the_ai_carries_the_key_the_role_tools_and_the_history(): void
    {
        $this->iaRepond('Quelle est son adresse e-mail ?');

        $this->dicter($this->admin(), 'ajoute Karim Mansouri', ['historique' => [['role' => 'user', 'content' => 'bonjour'], ['role' => 'assistant', 'content' => 'Bonjour.']]])
            ->assertOk()
            ->assertJson(['type' => 'message', 'message' => 'Quelle est son adresse e-mail ?', 'voix' => 'fr']);

        Http::assertSent(function (RequeteHttp $requete) {
            $outils   = array_column(array_column($requete['tools'], 'function'), 'name');
            $messages = $requete['messages'];

            return $requete->url() === 'https://ia.test/v1/chat/completions'
                && $requete->hasHeader('Authorization', 'Bearer cle-de-test')
                && in_array('creer_utilisateur', $outils) && !in_array('creer_tournee', $outils)
                && $messages[0]['role'] === 'system'
                && $messages[1]['content'] === 'bonjour'
                && $messages[3]['content'] === 'ajoute Karim Mansouri';
        });
    }

    public function test_ai_errors_become_readable_messages(): void
    {
        $this->reponseIa = ['error' => 'quota'];
        $this->statutIa  = 429;

        $this->dicter($this->admin(), 'ouvre les statistiques')
            ->assertOk()
            ->assertJsonPath('type', 'message')
            ->assertJsonPath('reessayer', 15)   // le navigateur patiente puis renvoie la commande tout seul
            ->assertJsonFragment(['message' => "Le quota gratuit du service d'IA est atteint pour le moment. Réessayez dans une minute."]);
    }

    /*
    |------------------------------------------------------------------
    | Administration
    |------------------------------------------------------------------
    */

    public function test_dictating_a_user_creation_only_proposes_it(): void
    {
        $this->iaAppelle('creer_utilisateur', ['prenom' => 'Karim', 'nom' => 'Mansouri', 'email' => 'Karim@Exemple.tn', 'telephone' => '55 000 001', 'role' => 'COLLECTEUR']);

        $this->dicter($this->admin(), 'ajoute un utilisateur Karim Mansouri…')
            ->assertOk()
            ->assertJson([
                'type'      => 'confirmation',
                'resume'    => "Créer l'utilisateur Karim Mansouri ?",
                'danger'    => false,
                'action'    => 'creer_utilisateur',
                'arguments' => ['email' => 'karim@exemple.tn', 'telephone' => '55000001', 'role' => 'COLLECTEUR', 'actif' => true],
            ]);

        // Rien n'est créé tant que ce n'est pas confirmé
        $this->assertDatabaseMissing('users', ['email' => 'karim@exemple.tn']);
    }

    public function test_confirming_creates_the_user_with_a_generated_password(): void
    {
        $reponse = $this->confirmer($this->admin(), 'creer_utilisateur', [
            'prenom' => 'Karim', 'nom' => 'Mansouri', 'email' => 'karim@exemple.tn', 'telephone' => '55000001', 'role' => 'COLLECTEUR', 'actif' => true,
        ])->assertOk()->assertJsonPath('type', 'succes');

        $user = User::where('email', 'karim@exemple.tn')->firstOrFail();
        $this->assertSame('Mansouri', $user->name);
        $this->assertSame(User::ROLE_COLLECTEUR, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check($reponse->json('secret.valeur'), $user->password));
        $this->assertGreaterThanOrEqual(8, strlen($reponse->json('secret.valeur')));
    }

    public function test_user_creation_goes_through_the_form_validation(): void
    {
        $admin = $this->admin();

        $this->confirmer($admin, 'creer_utilisateur', ['prenom' => 'Karim', 'nom' => 'Mansouri', 'email' => $admin->email])
            ->assertJson(['type' => 'message', 'message' => 'Cette adresse e-mail est déjà utilisée.']);

        $this->confirmer($admin, 'creer_utilisateur', ['prenom' => 'Karim', 'nom' => 'Mansouri', 'email' => 'k@exemple.tn', 'role' => 'SUPERHERO'])
            ->assertJson(['type' => 'message', 'message' => 'Le rôle sélectionné est invalide.']);

        $this->assertSame(1, User::count());
    }

    public function test_user_can_be_modified_by_name(): void
    {
        $admin = $this->admin();
        $karim = User::factory()->create(['prenom' => 'Karim', 'name' => 'Mansouri', 'role' => User::ROLE_DONATEUR]);
        $this->iaAppelle('modifier_utilisateur', ['cible' => 'karim mansouri', 'actif' => false, 'role' => 'RECYCLEUR']);

        $proposition = $this->dicter($admin, 'désactive Karim Mansouri et passe-le recycleur')
            ->assertJson(['type' => 'confirmation', 'resume' => 'Modifier Karim Mansouri ?', 'arguments' => ['utilisateur_id' => $karim->id]])
            ->json();

        $this->assertTrue($karim->refresh()->actif);

        $this->confirmer($admin, $proposition['action'], $proposition['arguments'])->assertJsonPath('type', 'succes');

        $karim->refresh();
        $this->assertFalse($karim->actif);
        $this->assertSame(User::ROLE_RECYCLEUR, $karim->role);
    }

    public function test_ambiguous_or_unknown_user_asks_for_precision(): void
    {
        $admin = $this->admin();
        User::factory()->create(['prenom' => 'Karim', 'name' => 'Mansouri']);
        User::factory()->create(['prenom' => 'Karim', 'name' => 'Dridi']);

        $this->confirmer($admin, 'supprimer_utilisateur', ['cible' => 'Karim'])
            ->assertJsonPath('type', 'message');
        $this->confirmer($admin, 'supprimer_utilisateur', ['cible' => 'Personne Inconnue'])
            ->assertJson(['type' => 'message', 'message' => 'Aucun utilisateur ne correspond à « Personne Inconnue ».']);

        $this->assertSame(3, User::count());
    }

    public function test_deleting_a_user_is_flagged_as_dangerous_and_needs_confirmation(): void
    {
        $admin = $this->admin();
        $karim = User::factory()->create(['prenom' => 'Karim', 'name' => 'Mansouri']);
        $this->iaAppelle('supprimer_utilisateur', ['cible' => $karim->email]);

        $this->dicter($admin, 'supprime karim')->assertJson(['type' => 'confirmation', 'danger' => true]);
        $this->assertDatabaseHas('users', ['id' => $karim->id]);

        $this->confirmer($admin, 'supprimer_utilisateur', ['cible' => $karim->email])->assertJsonPath('type', 'succes');
        $this->assertDatabaseMissing('users', ['id' => $karim->id]);
    }

    public function test_admin_cannot_delete_or_demote_themself_by_voice(): void
    {
        $admin = $this->admin();

        $this->confirmer($admin, 'supprimer_utilisateur', ['cible' => $admin->email])->assertJsonPath('type', 'message');
        $this->confirmer($admin, 'modifier_utilisateur', ['cible' => $admin->email, 'role' => 'CLIENT'])->assertJsonPath('type', 'message');
        $this->confirmer($admin, 'modifier_utilisateur', ['cible' => $admin->email, 'actif' => false])->assertJsonPath('type', 'message');

        $admin->refresh();
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertTrue($admin->actif);
    }

    public function test_search_and_pages_navigate_without_confirmation(): void
    {
        $admin = $this->admin();

        $this->iaAppelle('rechercher_utilisateurs', ['role' => 'RECYCLEUR', 'actif' => true]);
        $this->dicter($admin, 'montre les recycleurs actifs')
            ->assertJson(['type' => 'navigation', 'url' => route('admin.users.index', ['role' => 'RECYCLEUR', 'actif' => '1'])]);

        $this->iaAppelle('ouvrir_page', ['page' => 'statistiques']);
        $this->dicter($admin, 'ouvre les statistiques')
            ->assertJson(['type' => 'navigation', 'url' => route('admin.statistiques')]);
    }

    /*
    |------------------------------------------------------------------
    | Cloisonnement des rôles
    |------------------------------------------------------------------
    */

    public function test_actions_of_another_role_are_refused(): void
    {
        $karim = User::factory()->create(['prenom' => 'Karim']);

        // Un collecteur ne peut pas exécuter une action d'administration, même en forgeant la requête
        $this->confirmer($this->collecteur(), 'supprimer_utilisateur', ['cible' => $karim->email])
            ->assertJson(['type' => 'message', 'message' => "Cette action n'est pas disponible dans votre espace."]);
        $this->confirmer($this->admin(), 'creer_tournee', ['date' => '2026-10-12', 'zone' => 'X', 'vehicule' => 'Y'])
            ->assertJsonPath('type', 'message');
        $this->confirmer($this->admin(), 'methodeInexistante', [])->assertJsonPath('type', 'message');

        $this->assertDatabaseHas('users', ['id' => $karim->id]);
        $this->assertSame(0, Tournee::count());
    }

    /*
    |------------------------------------------------------------------
    | Logistique
    |------------------------------------------------------------------
    */

    public function test_collecteur_can_plan_a_tournee_by_voice(): void
    {
        $user = $this->collecteur();
        $this->iaAppelle('creer_tournee', ['date' => '2026-10-12', 'zone' => 'La Marsa', 'vehicule' => 'Fourgon 87 TU 2210']);

        $proposition = $this->dicter($user, 'planifie une tournée…')
            ->assertJson(['type' => 'confirmation', 'resume' => 'Planifier une tournée à La Marsa le 12/10/2026 ?'])
            ->json();
        $this->assertSame(0, Tournee::count());

        $this->confirmer($user, $proposition['action'], $proposition['arguments'])->assertJsonPath('type', 'succes');

        $tournee = Tournee::firstOrFail();
        $this->assertSame($user->id, $tournee->user_id);
        $this->assertSame('2026-10-12', $tournee->date->format('Y-m-d'));
        $this->assertSame(Tournee::STATUT_PLANIFIEE, $tournee->statut);
    }

    public function test_collecteur_can_add_update_and_delete_a_mission_by_voice(): void
    {
        $user    = $this->collecteur();
        $tournee = Tournee::create(['user_id' => $user->id, 'date' => '2026-10-12', 'zone' => 'La Marsa', 'vehicule' => 'Fourgon', 'statut' => Tournee::STATUT_PLANIFIEE]);

        // Ajout : heure dictée « 9h30 », ordre calculé
        $this->iaAppelle('ajouter_mission', ['tournee_zone' => 'marsa', 'type' => 'COLLECTE', 'adresse' => '12 rue de Marseille', 'heure_prevue' => '9h30']);
        $proposition = $this->dicter($user, 'ajoute une collecte…')
            ->assertJson(['type' => 'confirmation', 'arguments' => ['tournee_id' => $tournee->id, 'heure_prevue' => '09:30', 'ordre' => 1]])
            ->json();
        $this->confirmer($user, $proposition['action'], $proposition['arguments'])->assertJsonPath('type', 'succes');

        $mission = Mission::firstOrFail();
        $this->assertSame('09:30', $mission->heure);

        // Modification par ordre de passage
        $this->iaAppelle('modifier_mission', ['tournee_date' => '2026-10-12', 'ordre' => 1, 'statut' => 'TERMINEE', 'preuve_livraison' => '2 sacs']);
        $proposition = $this->dicter($user, 'marque la mission 1 comme terminée')
            ->assertJson(['type' => 'confirmation', 'arguments' => ['mission_id' => $mission->id]])
            ->json();
        $this->confirmer($user, $proposition['action'], $proposition['arguments'])->assertJsonPath('type', 'succes');

        $this->assertSame(Mission::STATUT_TERMINEE, $mission->refresh()->statut);
        $this->assertSame('2 sacs', $mission->preuve_livraison);

        // Suppression
        $this->confirmer($user, 'supprimer_mission', ['mission_id' => $mission->id])->assertJsonPath('type', 'succes');
        $this->assertSame(0, Mission::count());
    }

    public function test_collecteur_cannot_reach_another_collecteurs_data_by_voice(): void
    {
        $autre   = $this->collecteur();
        $tournee = Tournee::create(['user_id' => $autre->id, 'date' => '2026-10-12', 'zone' => 'La Marsa', 'vehicule' => 'Fourgon', 'statut' => Tournee::STATUT_PLANIFIEE]);
        $mission = $tournee->missions()->create(['type' => 'COLLECTE', 'adresse' => 'X', 'ordre' => 1, 'heure_prevue' => '09:00', 'statut' => 'A_FAIRE']);
        $moi     = $this->collecteur();

        $this->confirmer($moi, 'supprimer_tournee', ['tournee_id' => $tournee->id])->assertJsonPath('type', 'message');
        $this->confirmer($moi, 'supprimer_tournee', ['tournee_zone' => 'Marsa'])->assertJsonPath('type', 'message');
        $this->confirmer($moi, 'supprimer_mission', ['mission_id' => $mission->id])->assertJsonPath('type', 'message');
        $this->confirmer($moi, 'modifier_mission', ['mission_id' => $mission->id, 'statut' => 'TERMINEE'])->assertJsonPath('type', 'message');

        $this->assertSame(1, Tournee::count());
        $this->assertSame('A_FAIRE', $mission->refresh()->statut);
    }

    public function test_tournee_without_date_or_zone_defaults_to_today(): void
    {
        $user = $this->collecteur();
        Tournee::create(['user_id' => $user->id, 'date' => today()->subDay(), 'zone' => 'Ariana', 'vehicule' => 'Fourgon', 'statut' => Tournee::STATUT_TERMINEE]);
        $aujourdhui = Tournee::create(['user_id' => $user->id, 'date' => today(), 'zone' => 'Tunis Centre', 'vehicule' => 'Fourgon', 'statut' => Tournee::STATUT_EN_COURS]);

        $this->iaAppelle('ouvrir_tournee', []);
        $this->dicter($user, 'ouvre ma tournée')
            ->assertJson(['type' => 'navigation', 'url' => route('logistique.tournees.show', $aujourdhui)]);
    }

    /*
    |------------------------------------------------------------------
    | Intelligence : contexte, langue, commandes multiples
    |------------------------------------------------------------------
    */

    public function test_the_ai_is_given_the_directory_the_page_on_screen_and_the_local_date(): void
    {
        $admin = $this->admin();
        $karim = User::factory()->create(['prenom' => 'Karim', 'name' => 'Mansouri', 'role' => User::ROLE_RECYCLEUR]);
        $this->iaRepond('ok');

        $this->dicter($admin, 'désactive ce compte', ['page' => "/admin/users/{$karim->id}", 'aujourdhui' => '2026-10-06']);

        Http::assertSent(function (RequeteHttp $requete) use ($karim) {
            $consigne = $requete['messages'][0]['content'];

            return str_contains($consigne, "#{$karim->id} | Karim Mansouri | {$karim->email} | RECYCLEUR | active")
                && str_contains($consigne, "ON SCREEN RIGHT NOW: the profile of user #{$karim->id}")
                && str_contains($consigne, '(2026-10-06)')
                && str_contains($consigne, 'Reply in FRENCH');
        });
    }

    public function test_admin_context_carries_the_statistics_so_the_ai_can_analyse_them(): void
    {
        $admin = $this->admin();
        User::factory()->count(2)->create(['role' => User::ROLE_RECYCLEUR, 'created_at' => now()->subDays(2)]);
        User::factory()->create(['role' => User::ROLE_CLIENT, 'actif' => false, 'email_verified_at' => null, 'created_at' => now()->subDays(45)]);
        $this->iaRepond('Nous avons quatre comptes, dont deux recycleurs.');

        // Une question sur les données reçoit une réponse en texte, sans navigation ni action
        $this->dicter($admin, 'dis-moi ce qui se passe')
            ->assertJson(['type' => 'message', 'message' => 'Nous avons quatre comptes, dont deux recycleurs.']);

        Http::assertSent(function (RequeteHttp $requete) {
            $consigne = $requete['messages'][0]['content'];

            return str_contains($consigne, 'Accounts: 4 in total, 3 active (75%), 1 deactivated.')
                && str_contains($consigne, 'Verified emails: 3 (75%).')
                && str_contains($consigne, 'New accounts in the last 30 days: 3 (2 compared with the 30 days before')
                && str_contains($consigne, 'RECYCLEUR 2')
                && str_contains($consigne, 'ANSWERING QUESTIONS');
        });
    }

    public function test_collecteur_context_summarises_their_missions(): void
    {
        $moi = $this->collecteur();
        $tournee = Tournee::create(['user_id' => $moi->id, 'date' => today(), 'zone' => 'Tunis Centre', 'vehicule' => 'Fourgon', 'distance_km' => 18, 'statut' => Tournee::STATUT_EN_COURS]);
        $tournee->missions()->createMany([
            ['type' => 'COLLECTE', 'adresse' => 'A', 'ordre' => 1, 'heure_prevue' => '09:00', 'statut' => 'TERMINEE'],
            ['type' => 'LIVRAISON', 'adresse' => 'B', 'ordre' => 2, 'heure_prevue' => '10:00', 'statut' => 'A_FAIRE'],
        ]);
        $this->iaRepond('ok');

        $this->dicter($moi, 'comment se passe ma journée');

        Http::assertSent(function (RequeteHttp $requete) {
            $consigne = $requete['messages'][0]['content'];

            return str_contains($consigne, 'SUMMARY: 1 rounds listed (EN_COURS 1), 2 missions in total (A_FAIRE 1, EN_COURS 0, TERMINEE 1, ECHOUEE 0)')
                && str_contains($consigne, '1 pickups and 1 deliveries, 18 km planned.')
                && str_contains($consigne, 'Today: 2 missions');
        });
    }

    public function test_noise_transcribed_as_a_recitation_of_names_is_discarded(): void
    {
        $admin = $this->admin();
        User::factory()->create(['prenom' => 'Karim', 'name' => 'Mansouri']);
        User::factory()->create(['prenom' => 'Lina', 'name' => 'Ben Salah']);
        $audio = fn () => \Illuminate\Http\UploadedFile::fake()->create('c.webm', 10, 'audio/webm');

        // Whisper récite le vocabulaire fourni : ce n'est pas une commande
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['ia.test/*' => Http::sequence()
            ->push(['text' => 'Karim Mansouri, Lina Ben Salah, Ada Admin, Karim Mansouri.'])
            ->push(['text' => 'Merci.', 'segments' => [['no_speech_prob' => 0.9, 'avg_logprob' => -0.4]]])
            ->push(['text' => 'Supprime Lina Ben Salah', 'segments' => [['no_speech_prob' => 0.02, 'avg_logprob' => -0.2]]])]);

        $poster = fn () => $this->actingAs($admin)->post('/assistant/transcrire', ['audio' => $audio(), 'langue' => 'fr'], ['Accept' => 'application/json']);

        $poster()->assertExactJson(['type' => 'texte', 'texte' => '']);
        $poster()->assertExactJson(['type' => 'texte', 'texte' => '']);                    // « pas de parole » selon Whisper
        $poster()->assertExactJson(['type' => 'texte', 'texte' => 'Supprime Lina Ben Salah']);   // vraie commande conservée
    }

    public function test_collecteur_context_lists_only_their_own_rounds(): void
    {
        $moi = $this->collecteur();
        Tournee::create(['user_id' => $moi->id, 'date' => today(), 'zone' => 'Tunis Centre', 'vehicule' => 'Fourgon', 'statut' => Tournee::STATUT_EN_COURS])
            ->missions()->create(['type' => 'COLLECTE', 'adresse' => '12 rue de Marseille', 'ordre' => 1, 'heure_prevue' => '09:00', 'statut' => 'A_FAIRE']);
        Tournee::create(['user_id' => $this->collecteur()->id, 'date' => today(), 'zone' => 'Zone Secrète', 'vehicule' => 'Fourgon', 'statut' => Tournee::STATUT_EN_COURS]);
        $this->iaRepond('ok');

        $this->dicter($moi, 'ajoute une collecte');

        Http::assertSent(function (RequeteHttp $requete) {
            $consigne = $requete['messages'][0]['content'];

            return str_contains($consigne, 'zone: Tunis Centre')
                && str_contains($consigne, '(TODAY)')
                && str_contains($consigne, 'stop 1 | COLLECTE | 09:00 | 12 rue de Marseille')
                && !str_contains($consigne, 'Zone Secrète');
        });
    }

    public function test_everything_is_answered_in_english_when_dictating_in_english(): void
    {
        $admin = $this->admin();
        $karim = User::factory()->create(['prenom' => 'Karim', 'name' => 'Mansouri', 'role' => User::ROLE_DONATEUR]);

        $this->iaAppelle('modifier_utilisateur', ['utilisateur_id' => $karim->id, 'role' => 'COLLECTEUR', 'actif' => false]);
        $this->dicter($admin, 'deactivate karim and make him a collector', ['langue' => 'en'])
            ->assertJson(['type' => 'confirmation', 'resume' => 'Update Karim Mansouri?', 'voix' => 'en'])
            ->assertJsonFragment(['label' => 'Role', 'valeur' => 'Donor → Collector'])
            ->assertJsonFragment(['label' => 'Account', 'valeur' => 'Active → Deactivated']);

        $en = ['langue' => 'en'];
        $this->actingAs($admin)->postJson('/assistant/executer', ['action' => 'supprimer_utilisateur', 'arguments' => ['cible' => 'Nobody Here'], ...$en])
            ->assertJson(['type' => 'message', 'message' => 'No user matches "Nobody Here".', 'voix' => 'en']);
        $this->actingAs($admin)->postJson('/assistant/executer', ['action' => 'creer_utilisateur', 'arguments' => ['prenom' => 'A', 'nom' => 'B', 'email' => $karim->email], ...$en])
            ->assertJson(['type' => 'message', 'message' => 'The adresse e-mail has already been taken.']);
        $this->actingAs($admin)->postJson('/assistant/executer', ['action' => 'supprimer_utilisateur', 'arguments' => ['utilisateur_id' => $karim->id], ...$en])
            ->assertJson(['type' => 'succes', 'message' => 'User Karim Mansouri deleted.', 'voix' => 'en']);
    }

    public function test_a_misheard_name_still_finds_the_user(): void
    {
        $admin = $this->admin();
        $karim = User::factory()->create(['prenom' => 'Karim', 'name' => 'Mansouri']);
        User::factory()->create(['prenom' => 'Lina', 'name' => 'Ben Salah']);

        $this->confirmer($admin, 'ouvrir_utilisateur', ['cible' => 'Kareem Mansoori'])
            ->assertJson(['type' => 'navigation', 'url' => route('admin.users.show', $karim)]);
    }

    public function test_a_failed_action_is_handed_back_to_the_ai_which_can_ask_instead(): void
    {
        // 1er appel : le modèle vise un utilisateur inexistant ; 2e appel : il pose une question
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake(['ia.test/*' => Http::sequence()
            ->push(['choices' => [['message' => ['content' => null, 'tool_calls' => [['id' => 'a1', 'type' => 'function', 'function' => ['name' => 'supprimer_utilisateur', 'arguments' => '{"cible":"Zed"}']]]]]]])
            ->push(['choices' => [['message' => ['content' => 'Je ne trouve pas Zed. Quel est son e-mail ?']]]])]);

        $this->dicter($this->admin(), 'supprime zed')
            ->assertJson(['type' => 'message', 'message' => 'Je ne trouve pas Zed. Quel est son e-mail ?']);

        Http::assertSentCount(2);
        Http::assertSent(fn (RequeteHttp $requete) => collect($requete['messages'])->contains(
            fn ($message) => $message['role'] === 'tool' && str_contains($message['content'], 'Aucun utilisateur ne correspond')
        ));
    }

    public function test_several_actions_in_one_command_are_queued_and_prepared_one_by_one(): void
    {
        $admin = $this->admin();
        $appel = fn (string $prenom, string $email, array $extra = []) => ['id' => $prenom, 'type' => 'function', 'function' => [
            'name' => 'creer_utilisateur', 'arguments' => json_encode(['prenom' => $prenom, 'nom' => 'Test', 'email' => $email, ...$extra]),
        ]];
        $this->reponseIa = ['choices' => [['message' => ['content' => null, 'tool_calls' => [
            $appel('Nour', 'nour@exemple.tn', ['encore' => true]),
            $appel('Omar', 'omar@exemple.tn'),
        ]]]]];

        $reponse = $this->dicter($admin, 'ajoute Nour et Omar')
            ->assertJson(['type' => 'confirmation', 'resume' => "Créer l'utilisateur Nour Test ?", 'encore' => true])
            ->assertJsonCount(1, 'suite')
            ->assertJsonPath('suite.0.arguments.email', 'omar@exemple.tn');

        // Le drapeau technique « encore » ne fait pas partie des arguments de l'action
        $this->assertArrayNotHasKey('encore', $reponse->json('arguments'));
        $this->assertSame(1, User::count());

        // L'action suivante se prépare sans repasser par l'IA
        $this->actingAs($admin)->postJson('/assistant/preparer', ['langue' => 'fr', ...$reponse->json('suite.0')])
            ->assertJson(['type' => 'confirmation', 'resume' => "Créer l'utilisateur Omar Test ?"]);
        $this->assertSame(1, User::count());
    }

    public function test_the_ai_can_report_that_nothing_is_left_to_do(): void
    {
        $this->iaRepond('NOTHING_LEFT');

        $this->dicter($this->admin(), '[suite]')->assertExactJson(['type' => 'rien']);
    }

    public function test_today_is_the_browsers_date_not_the_servers(): void
    {
        $user   = $this->collecteur();
        $demain = Tournee::create(['user_id' => $user->id, 'date' => today()->addDay(), 'zone' => 'La Marsa', 'vehicule' => 'Fourgon', 'statut' => Tournee::STATUT_PLANIFIEE]);

        // Pour le navigateur il est déjà « demain » : la tournée du jour est celle-là
        $this->actingAs($user)->postJson('/assistant/preparer', [
            'action' => 'ouvrir_tournee', 'arguments' => [], 'langue' => 'fr', 'aujourdhui' => today()->addDay()->format('Y-m-d'),
        ])->assertJson(['type' => 'navigation', 'url' => route('logistique.tournees.show', $demain)]);
    }

    public function test_times_said_in_several_ways_are_understood(): void
    {
        $user    = $this->collecteur();
        $tournee = Tournee::create(['user_id' => $user->id, 'date' => today(), 'zone' => 'Tunis', 'vehicule' => 'Fourgon', 'statut' => Tournee::STATUT_EN_COURS]);

        foreach (['9h30' => '09:30', '14 h' => '14:00', '3 pm' => '15:00', '12:05' => '12:05', '9' => '09:00'] as $dit => $attendu) {
            $this->actingAs($user)->postJson('/assistant/preparer', [
                'action' => 'ajouter_mission', 'langue' => 'fr',
                'arguments' => ['tournee_id' => $tournee->id, 'type' => 'COLLECTE', 'adresse' => 'X', 'heure_prevue' => $dit],
            ])->assertJsonPath('arguments.heure_prevue', $attendu);
        }
    }

    /*
    |------------------------------------------------------------------
    | Transcription
    |------------------------------------------------------------------
    */

    public function test_a_recording_is_transcribed_with_the_user_names_as_vocabulary(): void
    {
        $admin = $this->admin();
        User::factory()->create(['prenom' => 'Karim', 'name' => 'Mansouri']);

        $this->actingAs($admin)
            ->post('/assistant/transcrire', ['audio' => \Illuminate\Http\UploadedFile::fake()->create('commande.webm', 40, 'audio/webm'), 'langue' => 'fr'], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertExactJson(['type' => 'texte', 'texte' => 'Désactive Karim Mansouri.']);

        Http::assertSent(function (RequeteHttp $requete) {
            $champs = collect($requete->data())->pluck('contents', 'name');

            return $requete->url() === 'https://ia.test/v1/audio/transcriptions'
                && $champs['language'] === 'fr'
                && str_contains($champs['prompt'], 'Karim Mansouri');
        });
    }

    public function test_transcription_requires_a_file_and_an_allowed_role(): void
    {
        $this->actingAs($this->admin())->postJson('/assistant/transcrire', ['langue' => 'fr'])->assertJsonValidationErrors('audio');

        $donateur = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $this->actingAs($donateur)
            ->post('/assistant/transcrire', ['audio' => \Illuminate\Http\UploadedFile::fake()->create('c.webm', 10, 'audio/webm'), 'langue' => 'fr'], ['Accept' => 'application/json'])
            ->assertJsonPath('type', 'message');
    }

    /*
    |------------------------------------------------------------------
    | Voix naturelle (anglais)
    |------------------------------------------------------------------
    */

    public function test_an_english_sentence_is_read_with_the_natural_voice(): void
    {
        config(['assistant.voice_model' => 'canopylabs/orpheus-v1-english', 'assistant.voice' => 'hannah']);

        $reponse = $this->actingAs($this->admin())->postJson('/assistant/parler', ['texte' => 'We have six active accounts.']);

        $reponse->assertOk()->assertHeader('Content-Type', 'audio/wav');
        $this->assertSame('RIFF-son-de-test', $reponse->getContent());

        Http::assertSent(fn (RequeteHttp $requete) => $requete->url() === 'https://ia.test/v1/audio/speech'
            && $requete['model'] === 'canopylabs/orpheus-v1-english'
            && $requete['voice'] === 'hannah'
            && $requete['input'] === 'We have six active accounts.');
    }

    public function test_voice_failures_are_reported_so_the_browser_falls_back_to_its_own_voice(): void
    {
        $admin = $this->admin();

        // Conditions du modèle non acceptées chez le fournisseur
        $this->statutVoix = 400;
        $this->actingAs($admin)->postJson('/assistant/parler', ['texte' => 'Hello.'])
            ->assertStatus(502)->assertExactJson(['erreur' => 'conditions']);

        // Voix naturelle désactivée dans la configuration : aucun appel
        config(['assistant.voice_model' => null]);
        $this->actingAs($admin)->postJson('/assistant/parler', ['texte' => 'Hello.'])
            ->assertStatus(503)->assertExactJson(['erreur' => 'indisponible']);

        Http::assertSentCount(1);
    }

    public function test_voice_requires_a_short_text_and_an_allowed_role(): void
    {
        $this->postJson('/assistant/parler', ['texte' => 'Hello.'])->assertUnauthorized();
        $this->actingAs($this->admin())->postJson('/assistant/parler', ['texte' => str_repeat('a', 201)])->assertJsonValidationErrors('texte');
        $this->actingAs(User::factory()->create(['role' => User::ROLE_DONATEUR]))->postJson('/assistant/parler', ['texte' => 'Hello.'])->assertStatus(503);

        Http::assertNothingSent();
    }
}
