<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Favori;
use App\Models\RechercheSauvegardee;
use App\Models\User;
use App\Notifications\FavoriBientotEnRuptureNotification;
use App\Notifications\FavoriPrixBaisseNotification;
use App\Notifications\FavoriRetourStockNotification;
use App\Notifications\NouvelArticleRechercheNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FavoriEtAlertesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_toggle_favori_on_and_off(): void
    {
        $user = User::factory()->create();
        $seller = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $article = Article::factory()->create([
            'user_id' => $seller->id,
            'prix'    => 45.00,
            'stock'   => 4,
            'statut'  => Article::STATUT_DISPONIBLE,
        ]);

        // 1. Ajouter aux favoris (AJAX JSON)
        $response = $this->actingAs($user)
            ->postJson(route('favoris.toggle', $article));

        $response->assertOk()
            ->assertJson([
                'is_favori'    => true,
                'favori_count' => 1,
            ]);

        $this->assertDatabaseHas('favoris', [
            'user_id'    => $user->id,
            'article_id' => $article->id,
            'prix_au_moment_ajout' => 45.00,
        ]);

        // 2. Retirer des favoris
        $response2 = $this->actingAs($user)
            ->postJson(route('favoris.toggle', $article));

        $response2->assertOk()
            ->assertJson([
                'is_favori'    => false,
                'favori_count' => 0,
            ]);

        $this->assertDatabaseMissing('favoris', [
            'user_id'    => $user->id,
            'article_id' => $article->id,
        ]);
    }

    public function test_user_can_view_wishlist_page(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['prix' => 60.00]);

        Favori::create([
            'user_id'              => $user->id,
            'article_id'           => $article->id,
            'prix_au_moment_ajout' => 70.00, // prix baissé de 70 à 60
        ]);

        $response = $this->actingAs($user)->get(route('favoris.index'));

        $response->assertOk()
            ->assertSee('Ma Liste de Souhaits')
            ->assertSee($article->titre)
            ->assertSee('60.00 DT')
            ->assertSee('-10 DT'); // Prix réduit badge
    }

    public function test_user_can_save_and_delete_search(): void
    {
        $user = User::factory()->create();

        // 1. Sauvegarder recherche
        $response = $this->actingAs($user)->postJson(route('recherches-sauvegardees.store'), [
            'nom'       => 'Veste en Jean Vintage',
            'q'         => 'jean',
            'categorie' => 'Vetements Homme',
            'taille'    => 'M',
            'prix_max'  => 50,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('recherches_sauvegardees', [
            'user_id' => $user->id,
            'nom'     => 'Veste en Jean Vintage',
        ]);

        $recherche = RechercheSauvegardee::where('user_id', $user->id)->first();
        $this->assertEquals('Vetements Homme', $recherche->filtres['categorie']);
        $this->assertEquals('M', $recherche->filtres['taille']);

        // 2. Supprimer la recherche
        $deleteResponse = $this->actingAs($user)->deleteJson(route('recherches-sauvegardees.destroy', $recherche));
        $deleteResponse->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('recherches_sauvegardees', [
            'id' => $recherche->id,
        ]);
    }

    public function test_price_drop_triggers_notification_to_wishlisters(): void
    {
        Notification::fake();

        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => User::ROLE_ATELIER]);

        $article = Article::factory()->create([
            'user_id' => $seller->id,
            'prix'    => 100.00,
            'stock'   => 5,
            'statut'  => Article::STATUT_DISPONIBLE,
        ]);

        Favori::create([
            'user_id'              => $buyer->id,
            'article_id'           => $article->id,
            'prix_au_moment_ajout' => 100.00,
        ]);

        // Mise à jour du prix : baisse de 100 à 75 DT
        $article->update(['prix' => 75.00]);

        Notification::assertSentTo(
            $buyer,
            FavoriPrixBaisseNotification::class,
            function (FavoriPrixBaisseNotification $n) use ($article) {
                return $n->article->id === $article->id
                    && $n->ancienPrix == 100.00
                    && $n->nouveauPrix == 75.00;
            }
        );
    }

    public function test_back_in_stock_triggers_notification_to_wishlisters(): void
    {
        Notification::fake();

        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => User::ROLE_ATELIER]);

        $article = Article::factory()->create([
            'user_id' => $seller->id,
            'prix'    => 50.00,
            'stock'   => 0, // initialement rupture
            'statut'  => Article::STATUT_DISPONIBLE,
        ]);

        Favori::create([
            'user_id'              => $buyer->id,
            'article_id'           => $article->id,
            'prix_au_moment_ajout' => 50.00,
        ]);

        // Retour en stock : stock passe de 0 à 3
        $article->update(['stock' => 3]);

        Notification::assertSentTo(
            $buyer,
            FavoriRetourStockNotification::class,
            function (FavoriRetourStockNotification $n) use ($article) {
                return $n->article->id === $article->id;
            }
        );
    }

    public function test_low_stock_triggers_notification_to_wishlisters(): void
    {
        Notification::fake();

        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => User::ROLE_ATELIER]);

        $article = Article::factory()->create([
            'user_id' => $seller->id,
            'prix'    => 50.00,
            'stock'   => 5, // initialement 5
            'statut'  => Article::STATUT_DISPONIBLE,
        ]);

        Favori::create([
            'user_id'              => $buyer->id,
            'article_id'           => $article->id,
            'prix_au_moment_ajout' => 50.00,
        ]);

        // Stock tombe à 2 (plus que 2 exemplaires)
        $article->update(['stock' => 2]);

        Notification::assertSentTo(
            $buyer,
            FavoriBientotEnRuptureNotification::class,
            function (FavoriBientotEnRuptureNotification $n) use ($article) {
                return $n->article->id === $article->id && $n->stockRestant === 2;
            }
        );
    }

    public function test_new_matching_article_triggers_notification_for_saved_search(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $seller = User::factory()->create(['role' => User::ROLE_ATELIER]);

        // Sauvegarder recherche : Robes taille S
        RechercheSauvegardee::create([
            'user_id' => $user->id,
            'nom'     => 'Robes d\'été',
            'filtres' => [
                'categorie' => 'Vetements Femme',
                'taille'    => 'S',
            ],
        ]);

        // Créer un nouvel article qui correspond aux critères
        $article = Article::create([
            'user_id'   => $seller->id,
            'titre'     => 'Superbe robe lin',
            'prix'      => 45.00,
            'stock'     => 1,
            'categorie' => 'Vetements Femme',
            'taille'    => 'S',
            'statut'    => Article::STATUT_DISPONIBLE,
        ]);

        Notification::assertSentTo(
            $user,
            NouvelArticleRechercheNotification::class,
            function (NouvelArticleRechercheNotification $n) use ($article) {
                return $n->article->id === $article->id
                    && $n->recherche->nom === 'Robes d\'été';
            }
        );
    }

    public function test_artisan_daily_alerts_command(): void
    {
        Notification::fake();

        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => User::ROLE_ATELIER]);

        $article = Article::factory()->create([
            'user_id' => $seller->id,
            'prix'    => 30.00,
            'stock'   => 1, // presque épuisé
            'statut'  => Article::STATUT_DISPONIBLE,
        ]);

        Favori::create([
            'user_id'              => $buyer->id,
            'article_id'           => $article->id,
            'prix_au_moment_ajout' => 40.00, // baisse de prix
        ]);

        $this->artisan('retiss:alertes-quotidiennes')
            ->assertExitCode(0);

        Notification::assertSentTo($buyer, FavoriBientotEnRuptureNotification::class);
        Notification::assertSentTo($buyer, FavoriPrixBaisseNotification::class);
    }
}
