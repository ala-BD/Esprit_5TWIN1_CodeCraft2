<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Panier;
use App\Models\PanierLigne;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanierTest extends TestCase
{
    use RefreshDatabase;

    private User $client;
    private User $atelier;
    private User $collecteur;
    private Article $articleAtelier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->create(['role' => User::ROLE_CLIENT]);
        $this->atelier = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $this->collecteur = User::factory()->create(['role' => User::ROLE_COLLECTEUR]);

        $this->articleAtelier = Article::create([
            'user_id' => $this->atelier->id,
            'titre' => 'Veste Upcyclée',
            'description' => 'Superbe veste',
            'prix' => 100.00,
            'stock' => 5,
            'categorie' => 'Textile Upcyclé',
            'statut' => Article::STATUT_DISPONIBLE,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_panier(): void
    {
        $response = $this->get(route('panier.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_empty_panier(): void
    {
        $response = $this->actingAs($this->client)->get(route('panier.index'));
        $response->assertOk();
        $response->assertSee('Votre panier est vide');
    }

    public function test_user_can_add_article_to_panier(): void
    {
        $response = $this->actingAs($this->client)->post(route('panier.ajouter', $this->articleAtelier), [
            'quantite' => 2,
        ]);

        $response->assertRedirect(route('panier.index'));

        $panier = Panier::where('user_id', $this->client->id)->first();
        $this->assertNotNull($panier);
        $this->assertCount(1, $panier->lignes);
        $this->assertEquals(2, $panier->lignes->first()->quantite);
        $this->assertEquals($this->articleAtelier->id, $panier->lignes->first()->article_id);
    }

    public function test_cart_page_displays_added_articles(): void
    {
        $this->actingAs($this->client)->post(route('panier.ajouter', $this->articleAtelier), [
            'quantite' => 2,
        ]);

        $response = $this->actingAs($this->client)->get(route('panier.index'));
        $response->assertOk();
        $response->assertSee($this->articleAtelier->titre);
        $response->assertSee($this->atelier->name);
        $response->assertSee('200.00 DT');
    }

    public function test_adding_same_article_increments_quantity(): void
    {
        $this->actingAs($this->client)->post(route('panier.ajouter', $this->articleAtelier), [
            'quantite' => 2,
        ]);

        $this->actingAs($this->client)->post(route('panier.ajouter', $this->articleAtelier), [
            'quantite' => 1,
        ]);

        $panier = Panier::where('user_id', $this->client->id)->first();
        $this->assertCount(1, $panier->lignes);
        $this->assertEquals(3, $panier->lignes->first()->quantite);
    }

    public function test_user_cannot_add_own_article_to_panier(): void
    {
        $response = $this->actingAs($this->atelier)->post(route('panier.ajouter', $this->articleAtelier), [
            'quantite' => 1,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('panier_lignes', [
            'article_id' => $this->articleAtelier->id,
        ]);
    }

    public function test_user_cannot_add_more_than_available_stock(): void
    {
        $response = $this->actingAs($this->client)->post(route('panier.ajouter', $this->articleAtelier), [
            'quantite' => 10, // Stock is only 5
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('panier_lignes', [
            'article_id' => $this->articleAtelier->id,
        ]);
    }

    public function test_user_can_update_quantity_of_cart_line(): void
    {
        $panier = Panier::create(['user_id' => $this->client->id]);
        $ligne = $panier->lignes()->create([
            'article_id' => $this->articleAtelier->id,
            'quantite' => 1,
        ]);

        $response = $this->actingAs($this->client)->patch(route('panier.modifier', $ligne), [
            'quantite' => 4,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(4, $ligne->fresh()->quantite);
    }

    public function test_user_cannot_modify_another_users_cart_line(): void
    {
        $panier = Panier::create(['user_id' => $this->client->id]);
        $ligne = $panier->lignes()->create([
            'article_id' => $this->articleAtelier->id,
            'quantite' => 1,
        ]);

        // Attempt by another user
        $response = $this->actingAs($this->collecteur)->patch(route('panier.modifier', $ligne), [
            'quantite' => 2,
        ]);

        $response->assertForbidden();
    }

    public function test_user_can_remove_line_from_cart(): void
    {
        $panier = Panier::create(['user_id' => $this->client->id]);
        $ligne = $panier->lignes()->create([
            'article_id' => $this->articleAtelier->id,
            'quantite' => 1,
        ]);

        $response = $this->actingAs($this->client)->delete(route('panier.retirer', $ligne));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('panier_lignes', ['id' => $ligne->id]);
    }

    public function test_user_can_empty_cart(): void
    {
        $panier = Panier::create(['user_id' => $this->client->id]);
        $panier->lignes()->create([
            'article_id' => $this->articleAtelier->id,
            'quantite' => 2,
        ]);

        $response = $this->actingAs($this->client)->delete(route('panier.vider'));
        $response->assertRedirect(route('panier.index'));

        $this->assertCount(0, $panier->fresh()->lignes);
    }

    public function test_server_side_calculation_client_no_discount(): void
    {
        $panier = Panier::create(['user_id' => $this->client->id]);
        $panier->lignes()->create([
            'article_id' => $this->articleAtelier->id,
            'quantite' => 2, // 2 * 100 = 200 DT
        ]);

        // Client does not get 10% discount
        $this->assertEquals(200.0, $panier->sousTotal());
        $this->assertEquals(0.0, $panier->remise());
        $this->assertEquals(7.0, $panier->fraisLivraison());
        $this->assertEquals(207.0, $panier->total());
        $this->assertEquals(2, $panier->nombreArticles());
    }

    public function test_server_side_calculation_collecteur_gets_10_percent_discount(): void
    {
        $panier = Panier::create(['user_id' => $this->collecteur->id]);
        $panier->lignes()->create([
            'article_id' => $this->articleAtelier->id,
            'quantite' => 2, // 2 * 100 = 200 DT
        ]);

        // Collecteur gets 10% discount on Atelier articles: 200 * 0.10 = 20 DT
        $this->assertEquals(200.0, $panier->sousTotal());
        $this->assertEquals(20.0, $panier->remise());
        $this->assertEquals(7.0, $panier->fraisLivraison());
        $this->assertEquals(187.0, $panier->total()); // 200 - 20 + 7
    }

    public function test_auto_synchronization_adjusts_quantity_when_stock_decreases(): void
    {
        $panier = Panier::create(['user_id' => $this->client->id]);
        $ligne = $panier->lignes()->create([
            'article_id' => $this->articleAtelier->id,
            'quantite' => 5,
        ]);

        // Stock drops from 5 to 2
        $this->articleAtelier->update(['stock' => 2]);

        $response = $this->actingAs($this->client)->get(route('panier.index'));
        $response->assertOk();

        // Line should be automatically adjusted to 2
        $this->assertEquals(2, $ligne->fresh()->quantite);
        $response->assertSee('a été ajustée');
    }

    public function test_auto_synchronization_removes_line_when_article_becomes_unavailable(): void
    {
        $panier = Panier::create(['user_id' => $this->client->id]);
        $ligne = $panier->lignes()->create([
            'article_id' => $this->articleAtelier->id,
            'quantite' => 2,
        ]);

        // Article becomes sold out
        $this->articleAtelier->update(['statut' => Article::STATUT_VENDU, 'stock' => 0]);

        $response = $this->actingAs($this->client)->get(route('panier.index'));
        $response->assertOk();

        $this->assertDatabaseMissing('panier_lignes', ['id' => $ligne->id]);
        $response->assertSee('n&#039;est plus disponible', false);
    }
}
