<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Favori;
use App\Models\Panier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Barre de l'espace connecté (layouts.app-auth) : accès à la marketplace, aux favoris et au panier.
 */
class NavigationMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_client_voit_les_boutons_favoris_et_panier_sur_son_tableau_de_bord(): void
    {
        $client = User::factory()->create(['role' => User::ROLE_CLIENT]);

        // Après connexion, le client arrive sur /dashboard
        $this->post('/login', ['email' => $client->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('id="topbar-favoris-link"', false)
            ->assertSee('id="topbar-panier-link"', false)
            ->assertSee(route('favoris.index'), false)
            ->assertSee(route('panier.index'), false)
            ->assertSee(route('articles.index'), false)
            // barre latérale « Achats » et actions rapides du tableau de bord
            ->assertSeeInOrder(['Mes commandes', 'Mes favoris', 'Mon panier'])
            ->assertSeeInOrder(['Actions rapides', 'Commandes', 'Panier', 'Favoris']);
    }

    public function test_le_donateur_a_favoris_et_panier_dans_sa_barre_laterale(): void
    {
        $donateur = User::factory()->create(['role' => User::ROLE_DONATEUR]);

        $this->actingAs($donateur)->get('/dashboard')
            ->assertOk()
            ->assertSeeInOrder(['Publier un article', 'Mes favoris', 'Mon panier']);
    }

    public function test_tous_les_acheteurs_voient_les_boutons_sur_leurs_pages(): void
    {
        foreach ([User::ROLE_CLIENT, User::ROLE_DONATEUR, User::ROLE_COLLECTEUR, User::ROLE_RECYCLEUR, User::ROLE_ADMIN] as $role) {
            $user = User::factory()->create(['role' => $role]);

            foreach (['/dashboard', '/articles', '/commandes'] as $page) {
                $this->actingAs($user)->get($page)
                    ->assertOk()
                    ->assertSee('id="topbar-favoris-link"', false)
                    ->assertSee('id="topbar-panier-link"', false);
            }
        }
    }

    public function test_l_atelier_voit_la_marketplace_sans_favoris_ni_panier(): void
    {
        $atelier = User::factory()->create(['role' => User::ROLE_ATELIER]);

        $this->actingAs($atelier)->get('/articles')
            ->assertOk()
            ->assertSee(route('articles.index'), false)
            ->assertDontSee('id="topbar-favoris-link"', false)
            ->assertDontSee('id="topbar-panier-link"', false);
    }

    public function test_les_compteurs_affichent_le_contenu_du_panier_et_des_favoris(): void
    {
        $client = User::factory()->create(['role' => User::ROLE_CLIENT]);
        $article = Article::factory()->create();

        Panier::create(['user_id' => $client->id])->lignes()->create(['article_id' => $article->id, 'quantite' => 3]);
        Favori::create(['user_id' => $client->id, 'article_id' => $article->id]);

        $page = $this->actingAs($client)->get('/dashboard')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/class="cart-badge-count[^"]*"\s+style="[^"]*display:flex">\s*3\s*</', $page);
        $this->assertMatchesRegularExpression('/class="favoris-badge-count[^"]*"\s+style="[^"]*display:flex">\s*1\s*</', $page);
    }
}
