<?php

namespace Tests\Feature;

use App\Models\Adresse;
use App\Models\Article;
use App\Models\Avis;
use App\Models\AvisVote;
use App\Models\Commande;
use App\Models\LigneCommande;
use App\Models\User;
use App\Notifications\NouveauAvisNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AvisTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to set up a delivered order with an article.
     */
    private function createDeliveredOrder(User $buyer, User $seller, string $statut = Commande::STATUT_LIVREE): array
    {
        $adresse = Adresse::factory()->create(['user_id' => $buyer->id]);

        $article = Article::factory()->create([
            'user_id' => $seller->id,
            'prix'    => 50.00,
            'stock'   => 5,
            'statut'  => Article::STATUT_DISPONIBLE,
        ]);

        $commande = Commande::factory()->create([
            'user_id'             => $buyer->id,
            'adresse_id'          => $adresse->id,
            'statut'              => $statut,
            'statut_paiement'     => Commande::STATUT_PAIEMENT_PAYE,
            'montant_sous_total'  => 50.00,
            'montant_total'       => 57.00,
        ]);

        $ligne = LigneCommande::create([
            'commande_id'   => $commande->id,
            'article_id'    => $article->id,
            'quantite'      => 1,
            'prix_unitaire' => 50.00,
            'remise'        => 0.00,
            'total_ligne'   => 50.00,
        ]);

        return [$commande, $article, $ligne];
    }

    public function test_buyer_can_confirm_delivery_and_status_becomes_livree(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => User::ROLE_ATELIER]);

        [$commande, $article] = $this->createDeliveredOrder($buyer, $seller, Commande::STATUT_EXPEDIEE);

        $this->assertEquals(Commande::STATUT_EXPEDIEE, $commande->statut);
        $this->assertTrue($commande->peutConfirmerLivraison());

        $response = $this->actingAs($buyer)->patch(route('commandes.livraison', $commande));

        $response->assertRedirect(route('commandes.show', $commande));
        $response->assertSessionHas('success');

        $commande->refresh();
        $this->assertEquals(Commande::STATUT_LIVREE, $commande->statut);
        $this->assertTrue($commande->estLivree());
    }

    public function test_cannot_confirm_delivery_if_not_buyer(): void
    {
        $buyer = User::factory()->create();
        $intruder = User::factory()->create();
        $seller = User::factory()->create();

        [$commande] = $this->createDeliveredOrder($buyer, $seller, Commande::STATUT_EXPEDIEE);

        $response = $this->actingAs($intruder)->patch(route('commandes.livraison', $commande));
        $response->assertForbidden();
    }

    public function test_cannot_review_article_if_order_is_not_delivered(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => User::ROLE_ATELIER]);

        [$commande, $article] = $this->createDeliveredOrder($buyer, $seller, Commande::STATUT_EXPEDIEE);

        // Accessing the review form should be forbidden
        $response = $this->actingAs($buyer)->get(route('avis.create', [$commande, $article]));
        $response->assertForbidden();

        // Submitting review should also be forbidden
        $postResponse = $this->actingAs($buyer)->post(route('avis.store', [$commande, $article]), [
            'note_livraison' => 5,
            'note_article'   => 5,
            'note_clarte'    => 5,
            'note_service'   => 5,
        ]);
        $postResponse->assertForbidden();
    }

    public function test_cannot_review_article_not_in_order_or_by_stranger(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $stranger = User::factory()->create();

        [$commande, $article] = $this->createDeliveredOrder($buyer, $seller, Commande::STATUT_LIVREE);

        $response = $this->actingAs($stranger)->get(route('avis.create', [$commande, $article]));
        $response->assertForbidden();
    }

    public function test_seller_cannot_review_their_own_article(): void
    {
        $seller = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $adresse = Adresse::factory()->create(['user_id' => $seller->id]);

        $article = Article::factory()->create(['user_id' => $seller->id]);
        $commande = Commande::factory()->create([
            'user_id' => $seller->id,
            'adresse_id' => $adresse->id,
            'statut' => Commande::STATUT_LIVREE,
        ]);
        LigneCommande::create([
            'commande_id'   => $commande->id,
            'article_id'    => $article->id,
            'quantite'      => 1,
            'prix_unitaire' => 50.00,
            'remise'        => 0.00,
            'total_ligne'   => 50.00,
        ]);

        $response = $this->actingAs($seller)->get(route('avis.create', [$commande, $article]));
        $response->assertForbidden();
    }

    public function test_buyer_can_create_review_with_server_calculated_overall_score(): void
    {
        Notification::fake();

        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => User::ROLE_ATELIER]);

        [$commande, $article] = $this->createDeliveredOrder($buyer, $seller, Commande::STATUT_LIVREE);

        $response = $this->actingAs($buyer)->get(route('avis.create', [$commande, $article]));
        $response->assertOk();
        $response->assertSee($article->titre);

        $postData = [
            'note_livraison' => 5,
            'note_article'   => 4,
            'note_clarte'    => 5,
            'note_service'   => 4,
            'commentaire'    => 'Superbe article, livraison très rapide et soignée !',
        ];

        $postResponse = $this->actingAs($buyer)->post(route('avis.store', [$commande, $article]), $postData);

        $postResponse->assertRedirect(route('commandes.show', $commande));
        $postResponse->assertSessionHas('success');

        $this->assertDatabaseHas('avis', [
            'commande_id'    => $commande->id,
            'article_id'     => $article->id,
            'user_id'        => $buyer->id,
            'note_livraison' => 5,
            'note_article'   => 4,
            'note_clarte'    => 5,
            'note_service'   => 4,
            'note_globale'   => 4.50, // (5 + 4 + 5 + 4) / 4 = 4.5
            'commentaire'    => 'Superbe article, livraison très rapide et soignée !',
        ]);

        Notification::assertSentTo($seller, NouveauAvisNotification::class);
    }

    public function test_duplicate_review_is_prevented(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create(['role' => User::ROLE_ATELIER]);

        [$commande, $article] = $this->createDeliveredOrder($buyer, $seller, Commande::STATUT_LIVREE);

        Avis::create([
            'commande_id'    => $commande->id,
            'article_id'     => $article->id,
            'user_id'        => $buyer->id,
            'note_livraison' => 5,
            'note_article'   => 5,
            'note_clarte'    => 5,
            'note_service'   => 5,
            'note_globale'   => 5.0,
        ]);

        $response = $this->actingAs($buyer)->get(route('avis.create', [$commande, $article]));
        $response->assertForbidden();

        $postResponse = $this->actingAs($buyer)->post(route('avis.store', [$commande, $article]), [
            'note_livraison' => 4,
            'note_article'   => 4,
            'note_clarte'    => 4,
            'note_service'   => 4,
        ]);
        $postResponse->assertForbidden();
    }

    public function test_author_can_edit_and_update_review(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        [$commande, $article] = $this->createDeliveredOrder($buyer, $seller, Commande::STATUT_LIVREE);

        $avis = Avis::create([
            'commande_id'    => $commande->id,
            'article_id'     => $article->id,
            'user_id'        => $buyer->id,
            'note_livraison' => 3,
            'note_article'   => 3,
            'note_clarte'    => 3,
            'note_service'   => 3,
            'note_globale'   => 3.0,
            'commentaire'    => 'Moyen',
        ]);

        $response = $this->actingAs($buyer)->get(route('avis.edit', $avis));
        $response->assertOk();
        $response->assertSee('Moyen');

        $updateResponse = $this->actingAs($buyer)->put(route('avis.update', $avis), [
            'note_livraison' => 5,
            'note_article'   => 5,
            'note_clarte'    => 5,
            'note_service'   => 5,
            'commentaire'    => 'Finalement parfait après explication !',
        ]);

        $updateResponse->assertRedirect(route('articles.show', $article));
        $updateResponse->assertSessionHas('success');

        $avis->refresh();
        $this->assertEquals(5.0, (float) $avis->note_globale);
        $this->assertEquals('Finalement parfait après explication !', $avis->commentaire);
    }

    public function test_other_user_cannot_edit_review(): void
    {
        $buyer = User::factory()->create();
        $other = User::factory()->create();
        $seller = User::factory()->create();

        [$commande, $article] = $this->createDeliveredOrder($buyer, $seller, Commande::STATUT_LIVREE);

        $avis = Avis::create([
            'commande_id'    => $commande->id,
            'article_id'     => $article->id,
            'user_id'        => $buyer->id,
            'note_livraison' => 4,
            'note_article'   => 4,
            'note_clarte'    => 4,
            'note_service'   => 4,
            'note_globale'   => 4.0,
        ]);

        $response = $this->actingAs($other)->get(route('avis.edit', $avis));
        $response->assertForbidden();

        $putResponse = $this->actingAs($other)->put(route('avis.update', $avis), [
            'note_livraison' => 1,
            'note_article'   => 1,
            'note_clarte'    => 1,
            'note_service'   => 1,
        ]);
        $putResponse->assertForbidden();
    }

    public function test_author_can_delete_own_review_and_admin_can_delete_any_review(): void
    {
        $buyer = User::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $seller = User::factory()->create();

        [$commande, $article] = $this->createDeliveredOrder($buyer, $seller, Commande::STATUT_LIVREE);

        $avis = Avis::create([
            'commande_id'    => $commande->id,
            'article_id'     => $article->id,
            'user_id'        => $buyer->id,
            'note_livraison' => 4,
            'note_article'   => 4,
            'note_clarte'    => 4,
            'note_service'   => 4,
            'note_globale'   => 4.0,
        ]);

        // Admin can delete
        $adminDelResponse = $this->actingAs($admin)->delete(route('avis.destroy', $avis));
        $adminDelResponse->assertRedirect(route('articles.show', $article));
        $this->assertDatabaseMissing('avis', ['id' => $avis->id]);
    }

    public function test_ajax_vote_like_and_dislike_functionality(): void
    {
        $buyer = User::factory()->create();
        $voter = User::factory()->create();
        $seller = User::factory()->create();

        [$commande, $article] = $this->createDeliveredOrder($buyer, $seller, Commande::STATUT_LIVREE);

        $avis = Avis::create([
            'commande_id'    => $commande->id,
            'article_id'     => $article->id,
            'user_id'        => $buyer->id,
            'note_livraison' => 5,
            'note_article'   => 5,
            'note_clarte'    => 5,
            'note_service'   => 5,
            'note_globale'   => 5.0,
        ]);

        // 1. Voter likes the review
        $response = $this->actingAs($voter)->postJson(route('avis.vote', $avis), [
            'type' => 'like',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success'        => true,
            'user_vote'      => 'like',
            'likes_count'    => 1,
            'dislikes_count' => 0,
        ]);

        $this->assertEquals(1, $avis->likesCount());
        $this->assertEquals(0, $avis->dislikesCount());

        // 2. Voting same type toggles it off
        $toggleResponse = $this->actingAs($voter)->postJson(route('avis.vote', $avis), [
            'type' => 'like',
        ]);

        $toggleResponse->assertOk();
        $toggleResponse->assertJson([
            'success'        => true,
            'user_vote'      => null,
            'likes_count'    => 0,
            'dislikes_count' => 0,
        ]);

        // 3. Voter switches to dislike
        $dislikeResponse = $this->actingAs($voter)->postJson(route('avis.vote', $avis), [
            'type' => 'dislike',
        ]);

        $dislikeResponse->assertOk();
        $dislikeResponse->assertJson([
            'success'        => true,
            'user_vote'      => 'dislike',
            'likes_count'    => 0,
            'dislikes_count' => 1,
        ]);
    }

    public function test_author_cannot_vote_on_own_review(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        [$commande, $article] = $this->createDeliveredOrder($buyer, $seller, Commande::STATUT_LIVREE);

        $avis = Avis::create([
            'commande_id'    => $commande->id,
            'article_id'     => $article->id,
            'user_id'        => $buyer->id,
            'note_livraison' => 5,
            'note_article'   => 5,
            'note_clarte'    => 5,
            'note_service'   => 5,
            'note_globale'   => 5.0,
        ]);

        $response = $this->actingAs($buyer)->postJson(route('avis.vote', $avis), [
            'type' => 'like',
        ]);

        $response->assertForbidden();
    }

    public function test_marketplace_rating_filter_and_sort(): void
    {
        $seller = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $buyer1 = User::factory()->create();
        $buyer2 = User::factory()->create();

        [$cmd1, $artHigh] = $this->createDeliveredOrder($buyer1, $seller, Commande::STATUT_LIVREE);
        [$cmd2, $artLow]  = $this->createDeliveredOrder($buyer2, $seller, Commande::STATUT_LIVREE);

        // artHigh gets 5-star review
        Avis::create([
            'commande_id'    => $cmd1->id,
            'article_id'     => $artHigh->id,
            'user_id'        => $buyer1->id,
            'note_livraison' => 5,
            'note_article'   => 5,
            'note_clarte'    => 5,
            'note_service'   => 5,
            'note_globale'   => 5.0,
        ]);

        // artLow gets 2-star review
        Avis::create([
            'commande_id'    => $cmd2->id,
            'article_id'     => $artLow->id,
            'user_id'        => $buyer2->id,
            'note_livraison' => 2,
            'note_article'   => 2,
            'note_clarte'    => 2,
            'note_service'   => 2,
            'note_globale'   => 2.0,
        ]);

        // Test filter note_min=4
        $filterResponse = $this->actingAs($buyer1)->get(route('articles.index', ['note_min' => 4]));
        $filterResponse->assertOk();
        $filterResponse->assertSee($artHigh->titre);
        $filterResponse->assertDontSee($artLow->titre);

        // Test sort mieux_notes
        $sortResponse = $this->actingAs($buyer1)->get(route('articles.index', ['tri' => 'mieux_notes']));
        $sortResponse->assertOk();
        $sortResponse->assertSee($artHigh->titre);
        $sortResponse->assertSee($artLow->titre);
    }
}
