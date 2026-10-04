<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\DonVetement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/articles');
        $response->assertRedirect('/login');

        $response = $this->get('/articles/create');
        $response->assertRedirect('/login');
    }

    public function test_user_can_view_articles_list(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $article1 = Article::factory()->create(['user_id' => $user1->id, 'titre' => 'Veste Jean Bleue']);
        $article2 = Article::factory()->create(['user_id' => $user2->id, 'titre' => 'Robe Vintage Verte']);

        $response = $this->actingAs($user1)->get('/articles');

        $response->assertOk();
        $response->assertSee('Veste Jean Bleue');
        $response->assertSee('Robe Vintage Verte');
        $response->assertSee('Marketplace Textile');
    }

    public function test_user_can_view_article_detail(): void
    {
        $owner = User::factory()->create(['name' => 'Alice']);
        $viewer = User::factory()->create(['name' => 'Bob']);

        $article = Article::factory()->create([
            'user_id'     => $owner->id,
            'titre'       => 'Chemisier en Soie',
            'description' => 'Superbe pièce vintage bien conservée.',
            'prix'        => 65.50,
            'stock'       => 2,
        ]);

        $response = $this->actingAs($viewer)->get("/articles/{$article->id}");

        $response->assertOk();
        $response->assertSee('Chemisier en Soie');
        $response->assertSee('65.50');
        $response->assertSee('Alice');
        // Non-owner should not see edit/delete buttons
        $response->assertDontSee("articles/{$article->id}/edit");
    }

    public function test_owner_and_admin_see_management_buttons_on_detail_page(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $article = Article::factory()->create(['user_id' => $owner->id]);

        // Owner sees edit button
        $response = $this->actingAs($owner)->get("/articles/{$article->id}");
        $response->assertOk();
        $response->assertSee("articles/{$article->id}/edit");

        // Admin also sees edit button
        $response = $this->actingAs($admin)->get("/articles/{$article->id}");
        $response->assertOk();
        $response->assertSee("articles/{$article->id}/edit");
    }

    public function test_user_can_view_create_article_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/articles/create');

        $response->assertOk();
        $response->assertSee('Publier un article textile');
    }

    public function test_user_can_create_article_and_user_id_is_always_auth_id(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $payload = [
            'titre'       => 'Pantalon Velours Upcyclé',
            'description' => 'Création originale à partir de chutes textiles.',
            'prix'        => 79.90,
            'stock'       => 3,
            'categorie'   => 'Vêtements Homme',
            'statut'      => 'DISPONIBLE',
            // Tentative d'usurpation de user_id dans le formulaire
            'user_id'     => $anotherUser->id,
        ];

        $response = $this->actingAs($user)->post('/articles', $payload);

        $response->assertSessionHasNoErrors();
        $article = Article::where('titre', 'Pantalon Velours Upcyclé')->first();
        $this->assertNotNull($article);

        // Doit appartenir à $user (auth()->id()), JAMAIS à $anotherUser
        $this->assertSame($user->id, $article->user_id);
        $this->assertEquals(79.90, $article->prix);
        $this->assertSame(3, $article->stock);

        $response->assertRedirect("/articles/{$article->id}");
    }

    public function test_user_can_upload_multiple_images_when_creating_article(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $images = [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.png'),
            UploadedFile::fake()->image('photo3.webp'),
        ];

        $payload = [
            'titre'     => 'Veste Multi-Photos',
            'prix'      => 120.00,
            'stock'     => 1,
            'categorie' => 'Vêtements Femme',
            'statut'    => 'DISPONIBLE',
            'images'    => $images,
        ];

        $response = $this->actingAs($user)->post('/articles', $payload);

        $response->assertSessionHasNoErrors();
        $article = Article::where('titre', 'Veste Multi-Photos')->first();
        $this->assertNotNull($article);
        $this->assertCount(3, $article->images);

        foreach ($article->images as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    public function test_validation_fails_for_missing_title_negative_price_or_non_integer_stock(): void
    {
        $user = User::factory()->create();

        // Titre manquant, prix négatif, stock non entier
        $response = $this->actingAs($user)->post('/articles', [
            'titre'       => '',
            'prix'        => -15.00,
            'stock'       => 2.5,
            'categorie'   => '',
        ]);

        $response->assertSessionHasErrors(['titre', 'prix', 'stock', 'categorie']);
        $this->assertSame(0, Article::count());
    }

    public function test_price_must_be_strictly_positive(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/articles', [
            'titre'     => 'Article Gratuit Test',
            'prix'      => 0,
            'stock'     => 1,
            'categorie' => 'Autre',
        ]);

        $response->assertSessionHasErrors(['prix']);
        $this->assertSame(0, Article::count());
    }

    public function test_owner_can_access_edit_form(): void
    {
        $owner = User::factory()->create();
        $article = Article::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->get("/articles/{$article->id}/edit");

        $response->assertOk();
        $response->assertSee('Modifier votre article');
    }

    public function test_non_owner_cannot_access_edit_form(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $article = Article::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($stranger)->get("/articles/{$article->id}/edit");

        $response->assertForbidden();
    }

    public function test_admin_can_access_edit_form_of_any_article(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $article = Article::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($admin)->get("/articles/{$article->id}/edit");

        $response->assertOk();
    }

    public function test_owner_can_update_their_article(): void
    {
        $owner = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $owner->id,
            'titre'   => 'Ancien Titre',
            'prix'    => 40.00,
            'stock'   => 1,
        ]);

        $response = $this->actingAs($owner)->put("/articles/{$article->id}", [
            'titre'     => 'Nouveau Titre Mis à Jour',
            'prix'      => 45.00,
            'stock'     => 5,
            'categorie' => $article->categorie,
            'statut'    => 'DISPONIBLE',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect("/articles/{$article->id}");

        $article->refresh();
        $this->assertSame('Nouveau Titre Mis à Jour', $article->titre);
        $this->assertEquals(45.00, $article->prix);
        $this->assertSame(5, $article->stock);
    }

    public function test_non_owner_cannot_update_another_users_article(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $owner->id,
            'titre'   => 'Titre Original',
        ]);

        $response = $this->actingAs($stranger)->put("/articles/{$article->id}", [
            'titre'     => 'Attaque Pirate',
            'prix'      => 10.00,
            'stock'     => 1,
            'categorie' => 'Autre',
        ]);

        $response->assertForbidden();
        $this->assertSame('Titre Original', $article->fresh()->titre);
    }

    public function test_admin_can_update_another_users_article(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $article = Article::factory()->create([
            'user_id' => $owner->id,
            'titre'   => 'Titre Par Vendeur',
        ]);

        $response = $this->actingAs($admin)->put("/articles/{$article->id}", [
            'titre'     => 'Titre Modifié Par Modérateur Admin',
            'prix'      => 30.00,
            'stock'     => 2,
            'categorie' => $article->categorie,
            'statut'    => 'DISPONIBLE',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('Titre Modifié Par Modérateur Admin', $article->fresh()->titre);
    }

    public function test_owner_can_delete_their_article(): void
    {
        $owner = User::factory()->create();
        $article = Article::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->delete("/articles/{$article->id}");

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/articles');
        $this->assertNull(Article::find($article->id));
    }

    public function test_non_owner_cannot_delete_another_users_article(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $article = Article::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($stranger)->delete("/articles/{$article->id}");

        $response->assertForbidden();
        $this->assertNotNull(Article::find($article->id));
    }

    public function test_admin_can_delete_any_article(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $article = Article::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($admin)->delete("/articles/{$article->id}");

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/articles');
        $this->assertNull(Article::find($article->id));
    }

    public function test_user_can_filter_to_see_only_their_articles(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Article::factory()->create(['user_id' => $user1->id, 'titre' => 'Mon Article Spécial']);
        Article::factory()->create(['user_id' => $user2->id, 'titre' => 'Article D un Autre Vendeur']);

        $response = $this->actingAs($user1)->get('/articles?filter=mine');

        $response->assertOk();
        $response->assertSee('Mon Article Spécial');
        $response->assertDontSee('Article D un Autre Vendeur');
    }

    public function test_article_can_be_associated_with_a_donation(): void
    {
        $user = User::factory()->create();
        $don = DonVetement::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post('/articles', [
            'titre'           => 'Robe Dérivée du Don',
            'prix'            => 50.00,
            'stock'           => 1,
            'categorie'       => 'Textile Upcyclé',
            'don_vetement_id' => $don->id,
        ]);

        $response->assertSessionHasNoErrors();
        $article = Article::where('titre', 'Robe Dérivée du Don')->first();
        $this->assertNotNull($article);
        $this->assertSame($don->id, $article->don_vetement_id);
    }
}
