<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $atelier;
    protected User $admin;
    protected User $donateur;
    protected User $client;
    protected User $recycleur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->atelier = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->donateur = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $this->client = User::factory()->create(['role' => User::ROLE_CLIENT]);
        $this->recycleur = User::factory()->create(['role' => User::ROLE_RECYCLEUR]);
    }

    public function test_atelier_peut_calculer_une_estimation(): void
    {
        $response = $this->actingAs($this->atelier)->postJson(route('atelier.estimations.calculer'), [
            'titre'          => 'Pantalon upcyclé en denim',
            'categorie'      => 'Vetements Homme',
            'matiere'        => 'Coton',
            'etat'           => 'Tres bon etat',
            'poids_kg'       => 0.60,
            'cout_achat'     => 5.00,
            'heures_travail' => 1.5,
            'cout_materiaux' => 2.00,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'cout_recyclage',
                'cout_upcycling',
                'prix_conseille',
                'rentabilite' => [
                    'niveau_alerte',
                    'marge_nette',
                    'marge_pourcent',
                    'conseil',
                ],
                'source',
                'confiance',
            ],
        ]);
    }

    public function test_admin_peut_calculer_une_estimation(): void
    {
        $response = $this->actingAs($this->admin)->postJson(route('atelier.estimations.calculer'), [
            'titre'          => 'Gilet upcyclé',
            'categorie'      => 'Vetements Femme',
            'matiere'        => 'Laine',
            'poids_kg'       => 0.40,
        ]);

        $response->assertStatus(200);
    }

    public function test_donateur_recoit_403_sur_estimation(): void
    {
        $response = $this->actingAs($this->donateur)->postJson(route('atelier.estimations.calculer'), [
            'titre' => 'T-shirt don',
        ]);

        $response->assertStatus(403);
    }

    public function test_client_recoit_403_sur_estimation(): void
    {
        $response = $this->actingAs($this->client)->postJson(route('atelier.estimations.calculer'), [
            'titre' => 'T-shirt',
        ]);

        $response->assertStatus(403);
    }

    public function test_recycleur_recoit_403_sur_estimation(): void
    {
        $response = $this->actingAs($this->recycleur)->postJson(route('atelier.estimations.calculer'), [
            'titre' => 'Lot de chutes',
        ]);

        $response->assertStatus(403);
    }

    public function test_seul_admin_peut_acceder_aux_parametres_cout(): void
    {
        // Atelier doit recevoir 403
        $this->actingAs($this->atelier)->get(route('admin.parametres-cout.index'))
            ->assertStatus(403);

        // Admin reçoit 200
        $this->actingAs($this->admin)->get(route('admin.parametres-cout.index'))
            ->assertStatus(200);
    }

    public function test_atelier_peut_estimer_un_don_gratuit_cree_par_un_donateur(): void
    {
        $don = \App\Models\Article::factory()->create([
            'user_id'        => $this->donateur->id,
            'prix'           => 0.00,
            'stock'          => 12,
            'matiere'        => 'Coton',
            'poids_kg'       => 0.25,
            'heures_travail' => 0.8,
            'cout_materiaux' => 1.50,
            'statut'         => \App\Models\Article::STATUT_DISPONIBLE,
        ]);

        $response = $this->actingAs($this->atelier)->postJson(route('atelier.estimations.calculer'), [
            'article_id'     => $don->id,
            'matiere'        => 'Coton',
            'poids_kg'       => 0.25,
            'heures_travail' => 0.8,
            'cout_materiaux' => 1.50,
            'cout_achat'     => 0.00,
            'quantite'       => 12,
            'save'           => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'data' => [
                'rentabilite' => ['niveau_alerte', 'marge_nette', 'marge_pourcent'],
                'quantite'    => ['nombre_pieces', 'chiffre_affaires_total', 'cout_total', 'marge_totale'],
            ],
        ]);

        // Vérifier que l'estimation a été enregistrée pour cet atelier
        $this->assertDatabaseHas('estimations', [
            'article_id' => $don->id,
            'user_id'    => $this->atelier->id,
        ]);
    }

    public function test_atelier_peut_consulter_le_catalogue_des_dons(): void
    {
        \App\Models\Article::factory()->create([
            'user_id' => $this->donateur->id,
            'prix'    => 0.00,
            'stock'   => 5,
            'statut'  => \App\Models\Article::STATUT_DISPONIBLE,
        ]);

        $response = $this->actingAs($this->atelier)->get(route('atelier.dons'));
        $response->assertStatus(200);
        $response->assertViewIs('atelier.dons.index');
        $response->assertSee('Gisement de Dons Textiles Disponibles');
    }

    public function test_client_ne_peut_pas_acceder_au_catalogue_des_dons_atelier(): void
    {
        $response = $this->actingAs($this->client)->get(route('atelier.dons'));
        $response->assertStatus(403);
    }
}

