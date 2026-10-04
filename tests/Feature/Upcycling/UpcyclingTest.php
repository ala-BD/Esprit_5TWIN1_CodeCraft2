<?php

namespace Tests\Feature\Upcycling;

use App\Models\Atelier;
use App\Models\Devis;
use App\Models\ProjetUpcycling;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpcyclingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Jamais d'appel réel à l'API pendant les tests : générateur local
        config(['services.anthropic.key' => null]);
    }

    private function client(): User
    {
        return User::factory()->create(['role' => 'CLIENT']);
    }

    private function atelier(string $specialite = 'SAC', float $tarif = 15, float $note = 0): Atelier
    {
        return Atelier::create([
            'user_id'       => User::factory()->create(['role' => 'ATELIER'])->id,
            'nom'           => 'Atelier ' . $specialite,
            'specialite'    => $specialite,
            'tarif_horaire' => $tarif,
            'localisation'  => 'Tunis',
            'note_moyenne'  => $note,
            'actif'         => true,
        ]);
    }

    private function demande(User $client, array $extra = []): ProjetUpcycling
    {
        return ProjetUpcycling::create($extra + [
            'client_id'       => $client->id,
            'type_vetement'   => 'Jean',
            'matiere'         => 'Denim',
            'etat'            => 'USE',
            'description'     => 'Vieux jean troué au genou.',
            'idee_generee_ia' => app(\App\Services\Upcycling\IdeeUpcyclingService::class)->genererLocalement('Jean', 'Denim', 'USE'),
            'source_ia'       => 'LOCAL',
            'statut'          => 'DEMANDE',
        ]);
    }

    public function test_un_client_cree_une_demande_et_recoit_trois_idees(): void
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
    }

    public function test_la_validation_refuse_une_demande_incomplete(): void
    {
        $this->actingAs($this->client())
            ->post(route('upcycling.projets.store'), ['type_vetement' => '', 'etat' => 'INCONNU'])
            ->assertSessionHasErrors(['type_vetement', 'matiere', 'etat', 'description']);

        $this->assertDatabaseCount('projet_upcyclings', 0);
    }

    public function test_un_atelier_ne_peut_pas_creer_de_demande(): void
    {
        $atelier = $this->atelier();

        $this->actingAs($atelier->user)->get(route('upcycling.projets.create'))->assertForbidden();
    }

    public function test_le_matching_classe_d_abord_l_atelier_de_la_bonne_specialite(): void
    {
        $client = $this->client();
        $sac = $this->atelier('SAC', 20);
        $this->atelier('VETEMENT', 10, 5);

        $projet = $this->demande($client, ['produit_final' => 'Sac cabas en denim', 'categorie_produit' => 'SAC']);

        $this->actingAs($client)
            ->get(route('upcycling.projets.matching', $projet))
            ->assertOk()
            ->assertViewHas('classement', fn ($classement) => $classement->first()['atelier']->is($sac)
                && $classement->first()['details']['specialite'] === 40);
    }

    public function test_parcours_complet_devis_suivi_et_note(): void
    {
        $client  = $this->client();
        $atelier = $this->atelier('SAC');
        $projet  = $this->demande($client);

        // 1. Le client retient la première idée puis choisit l'atelier
        $this->actingAs($client)->patch(route('upcycling.projets.idee', $projet), ['index' => 0])
            ->assertRedirect(route('upcycling.projets.matching', $projet));
        $this->actingAs($client)->patch(route('upcycling.projets.atelier', $projet), ['atelier_id' => $atelier->id]);
        $this->assertSame('ATELIER_CHOISI', $projet->fresh()->statut);
        $this->assertSame('SAC', $projet->fresh()->categorie_produit);

        // 2. L'atelier envoie un devis, le client l'accepte
        $this->actingAs($atelier->user)->post(route('upcycling.devis.store', $projet), ['montant' => 40, 'delai_jours' => 7]);
        $devis = Devis::first();
        $this->assertSame('EN_ATTENTE', $devis->statut);

        $this->actingAs($client)->patch(route('upcycling.devis.accepter', $devis));
        $this->assertSame('ACCEPTE', $devis->fresh()->statut);
        $this->assertSame('DEVIS_ACCEPTE', $projet->fresh()->statut);
        $this->assertNotNull($projet->fresh()->date_debut);

        // 3. L'atelier fait avancer le projet jusqu'à la fin
        foreach (['CONCEPTION', 'CONFECTION', 'FINITION', 'TERMINE'] as $etape) {
            $this->actingAs($atelier->user)->patch(route('upcycling.projets.avancer', $projet));
            $this->assertSame($etape, $projet->fresh()->statut);
        }
        $this->assertNotNull($projet->fresh()->date_fin);
        $this->actingAs($atelier->user)->patch(route('upcycling.projets.avancer', $projet))->assertStatus(422);

        // 4. Le client note l'atelier : la moyenne est recalculée
        $this->actingAs($client)->patch(route('upcycling.projets.noter', $projet), ['note_client' => 4, 'commentaire_client' => 'Top']);
        $this->assertSame(4.0, $atelier->fresh()->note_moyenne);
    }

    public function test_seul_l_atelier_assigne_peut_envoyer_un_devis(): void
    {
        $client = $this->client();
        $atelier = $this->atelier();
        $autre = $this->atelier('VETEMENT');
        $projet = $this->demande($client, ['atelier_id' => $atelier->id, 'statut' => 'ATELIER_CHOISI']);

        $this->actingAs($autre->user)
            ->post(route('upcycling.devis.store', $projet), ['montant' => 40, 'delai_jours' => 7])
            ->assertForbidden();

        $this->assertDatabaseCount('devis', 0);
    }

    public function test_un_client_ne_voit_pas_le_projet_d_un_autre(): void
    {
        $projet = $this->demande($this->client());

        $this->actingAs($this->client())->get(route('upcycling.projets.show', $projet))->assertForbidden();
    }

    public function test_changer_d_atelier_refuse_le_devis_en_attente(): void
    {
        $client = $this->client();
        $ancien = $this->atelier();
        $nouveau = $this->atelier('VETEMENT');
        $projet = $this->demande($client, ['atelier_id' => $ancien->id, 'statut' => 'ATELIER_CHOISI', 'produit_final' => 'Sac']);
        $devis = Devis::create(['projet_upcycling_id' => $projet->id, 'montant' => 30, 'delai_jours' => 3, 'statut' => 'EN_ATTENTE', 'date_emission' => now()]);

        $this->actingAs($client)->patch(route('upcycling.projets.atelier', $projet), ['atelier_id' => $nouveau->id]);

        $this->assertSame('REFUSE', $devis->fresh()->statut);
        $this->assertSame($nouveau->id, $projet->fresh()->atelier_id);
    }

    public function test_un_atelier_gere_son_profil(): void
    {
        $user = User::factory()->create(['role' => 'ATELIER']);

        $this->actingAs($user)->post(route('upcycling.ateliers.store'), [
            'nom' => 'Mon Atelier', 'specialite' => 'DECORATION', 'tarif_horaire' => 18, 'localisation' => 'Nabeul',
        ])->assertRedirect();

        $atelier = Atelier::where('user_id', $user->id)->firstOrFail();
        $this->actingAs($user)->put(route('upcycling.ateliers.update', $atelier), [
            'nom' => 'Mon Atelier', 'specialite' => 'DECORATION', 'tarif_horaire' => 25, 'localisation' => 'Nabeul', 'actif' => '1',
        ]);
        $this->assertSame(25.0, $atelier->fresh()->tarif_horaire);

        $this->actingAs($this->client())->put(route('upcycling.ateliers.update', $atelier), [
            'nom' => 'Piraté', 'specialite' => 'SAC', 'tarif_horaire' => 1, 'localisation' => 'X',
        ])->assertForbidden();
    }

    public function test_les_pages_du_module_s_affichent(): void
    {
        $client  = $this->client();
        $atelier = $this->atelier();
        $projet  = $this->demande($client, ['atelier_id' => $atelier->id, 'statut' => 'ATELIER_CHOISI', 'produit_final' => 'Sac cabas en denim', 'categorie_produit' => 'SAC']);

        foreach ([
            route('upcycling.dashboard'),
            route('upcycling.projets.index'),
            route('upcycling.projets.create'),
            route('upcycling.projets.show', $projet),
            route('upcycling.projets.edit', $projet),
            route('upcycling.projets.matching', $projet),
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
