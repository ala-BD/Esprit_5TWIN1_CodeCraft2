<?php

namespace Tests\Feature;

use App\Models\Adresse;
use App\Models\Article;
use App\Models\Commande;
use App\Models\LigneCommande;
use App\Models\User;
use App\Notifications\CommandeStatutNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommandeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_commandes(): void
    {
        $response = $this->get('/commandes');
        $response->assertRedirect('/login');
    }

    public function test_user_can_view_own_commandes_list(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $c1 = Commande::factory()->create(['user_id' => $user1->id, 'numero' => 'TC-2026-000001']);
        $c2 = Commande::factory()->create(['user_id' => $user2->id, 'numero' => 'TC-2026-000002']);

        $response = $this->actingAs($user1)->get('/commandes');

        $response->assertOk();
        $response->assertSee('TC-2026-000001');
        $response->assertDontSee('TC-2026-000002');
    }

    public function test_admin_can_view_all_commandes_with_stats(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $client1 = User::factory()->create();
        $client2 = User::factory()->create();

        Commande::factory()->create(['user_id' => $client1->id, 'numero' => 'TC-2026-000101', 'montant_total' => 50.00]);
        Commande::factory()->create(['user_id' => $client2->id, 'numero' => 'TC-2026-000102', 'montant_total' => 75.00]);

        $response = $this->actingAs($admin)->get('/commandes');

        $response->assertOk();
        $response->assertSee('TC-2026-000101');
        $response->assertSee('TC-2026-000102');
        $response->assertSee('Revenu total');
        $response->assertSee('Commandes totales');
    }

    public function test_user_cannot_order_their_own_article(): void
    {
        $seller = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $article = Article::factory()->create([
            'user_id' => $seller->id,
            'prix'    => 60.00,
            'stock'   => 5,
            'statut'  => Article::STATUT_DISPONIBLE,
        ]);

        $response = $this->actingAs($seller)->get("/commandes/create?article_id={$article->id}");

        $response->assertRedirect("/articles/{$article->id}");
        $response->assertSessionHas('error');
    }

    public function test_atelier_cannot_post_order_containing_their_own_article(): void
    {
        $seller  = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $adresse = Adresse::factory()->create(['user_id' => $seller->id]);
        $article = Article::factory()->create([
            'user_id' => $seller->id,
            'prix'    => 80.00,
            'stock'   => 5,
            'statut'  => Article::STATUT_DISPONIBLE,
        ]);

        $payload = [
            'adresse_id'    => $adresse->id,
            'mode_paiement' => 'CARTE',
            'articles'      => [
                ['id' => $article->id, 'quantite' => 1],
            ],
        ];

        $response = $this->actingAs($seller)->post('/commandes', $payload);

        $response->assertSessionHasErrors(['articles']);
        $this->assertDatabaseMissing('commandes', [
            'user_id' => $seller->id,
        ]);
        $this->assertSame(5, $article->fresh()->stock);
    }

    public function test_buy_policy_forbids_atelier_from_buying_own_article(): void
    {
        $seller = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $buyer  = User::factory()->create(['role' => User::ROLE_CLIENT]);
        $article = Article::factory()->create(['user_id' => $seller->id, 'statut' => Article::STATUT_DISPONIBLE, 'stock' => 1]);

        $this->assertFalse($seller->can('buy', $article));
        $this->assertTrue($buyer->can('buy', $article));
    }

    public function test_user_cannot_order_out_of_stock_article(): void
    {
        $seller = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $buyer  = User::factory()->create(['role' => User::ROLE_CLIENT]);
        $article = Article::factory()->create([
            'user_id' => $seller->id,
            'stock'   => 0,
            'statut'  => Article::STATUT_VENDU,
        ]);

        $response = $this->actingAs($buyer)->get("/commandes/create?article_id={$article->id}");

        $response->assertRedirect("/articles/{$article->id}");
        $response->assertSessionHas('error');
    }

    public function test_user_can_place_order_and_stock_is_decremented_and_notification_sent(): void
    {
        Notification::fake();

        $seller  = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $buyer   = User::factory()->create(['role' => User::ROLE_CLIENT]);
        $adresse = Adresse::factory()->create(['user_id' => $buyer->id]);

        $article = Article::factory()->create([
            'user_id' => $seller->id,
            'prix'    => 50.00,
            'stock'   => 3,
            'statut'  => Article::STATUT_DISPONIBLE,
        ]);

        $payload = [
            'adresse_id'    => $adresse->id,
            'mode_paiement' => 'CARTE',
            'articles'      => [
                ['id' => $article->id, 'quantite' => 2],
            ],
        ];

        $response = $this->actingAs($buyer)->post('/commandes', $payload);

        $response->assertSessionHasNoErrors();
        $commande = Commande::where('user_id', $buyer->id)->first();
        $this->assertNotNull($commande);

        // Verification stock
        $this->assertSame(1, $article->fresh()->stock);

        // Totals (50 * 2 = 100 + 7 frais de livraison = 107)
        $this->assertEquals(100.00, $commande->montant_sous_total);
        $this->assertEquals(0.00, $commande->remise);
        $this->assertEquals(107.00, $commande->montant_total);

        // Ligne commande
        $this->assertCount(1, $commande->lignes);
        $ligne = $commande->lignes->first();
        $this->assertSame(2, $ligne->quantite);
        $this->assertEquals(50.00, $ligne->prix_unitaire);

        // Notification envoyée
        Notification::assertSentTo($buyer, CommandeStatutNotification::class);
    }

    public function test_collecteur_gets_automatic_10_percent_discount_on_atelier_articles(): void
    {
        $seller     = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $collecteur = User::factory()->create(['role' => User::ROLE_COLLECTEUR]);
        $adresse    = Adresse::factory()->create(['user_id' => $collecteur->id]);

        $article = Article::factory()->create([
            'user_id' => $seller->id,
            'prix'    => 100.00,
            'stock'   => 4,
            'statut'  => Article::STATUT_DISPONIBLE,
        ]);

        $payload = [
            'adresse_id'    => $adresse->id,
            'mode_paiement' => 'VIREMENT',
            'articles'      => [
                ['id' => $article->id, 'quantite' => 1],
            ],
        ];

        $response = $this->actingAs($collecteur)->post('/commandes', $payload);

        $response->assertSessionHasNoErrors();
        $commande = Commande::where('user_id', $collecteur->id)->first();

        // 100 DT - 10 DT remise + 7 DT frais livraison = 97 DT
        $this->assertEquals(100.00, $commande->montant_sous_total);
        $this->assertEquals(10.00, $commande->remise);
        $this->assertEquals(97.00, $commande->montant_total);

        $ligne = $commande->lignes->first();
        $this->assertEquals(10.00, $ligne->remise);
        $this->assertEquals(90.00, $ligne->total_ligne);
    }

    public function test_user_can_update_order_while_pending(): void
    {
        $seller  = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $buyer   = User::factory()->create(['role' => User::ROLE_CLIENT]);
        $adr1    = Adresse::factory()->create(['user_id' => $buyer->id]);
        $adr2    = Adresse::factory()->create(['user_id' => $buyer->id]);

        $article = Article::factory()->create(['user_id' => $seller->id, 'prix' => 40.00, 'stock' => 5]);

        $commande = Commande::create([
            'user_id'            => $buyer->id,
            'adresse_id'         => $adr1->id,
            'numero'             => 'TC-2026-000099',
            'statut'             => Commande::STATUT_EN_ATTENTE,
            'montant_sous_total' => 40.00,
            'montant_total'      => 47.00,
            'frais_livraison'    => 7.00,
            'mode_paiement'      => 'CARTE',
        ]);

        $ligne = LigneCommande::create([
            'commande_id'   => $commande->id,
            'article_id'    => $article->id,
            'quantite'      => 1,
            'prix_unitaire' => 40.00,
            'remise'        => 0.00,
            'total_ligne'   => 40.00,
        ]);

        $response = $this->actingAs($buyer)->put("/commandes/{$commande->id}", [
            'adresse_id'    => $adr2->id,
            'mode_paiement' => 'A_LA_LIVRAISON',
            'lignes'        => [
                ['id' => $ligne->id, 'quantite' => 2],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $commande->refresh();
        $this->assertSame($adr2->id, $commande->adresse_id);
        $this->assertSame('A_LA_LIVRAISON', $commande->mode_paiement);
        $this->assertEquals(87.00, $commande->montant_total);
    }

    public function test_user_can_cancel_order_before_shipping_and_stock_is_restored(): void
    {
        $seller  = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $buyer   = User::factory()->create(['role' => User::ROLE_CLIENT]);
        $article = Article::factory()->create(['user_id' => $seller->id, 'prix' => 30.00, 'stock' => 1]);

        $commande = Commande::create([
            'user_id'            => $buyer->id,
            'numero'             => 'TC-2026-000077',
            'statut'             => Commande::STATUT_EN_ATTENTE,
            'montant_sous_total' => 30.00,
            'montant_total'      => 37.00,
            'frais_livraison'    => 7.00,
            'mode_paiement'      => 'CARTE',
        ]);

        LigneCommande::create([
            'commande_id'   => $commande->id,
            'article_id'    => $article->id,
            'quantite'      => 2,
            'prix_unitaire' => 30.00,
            'remise'        => 0.00,
            'total_ligne'   => 60.00,
        ]);

        $response = $this->actingAs($buyer)->delete("/commandes/{$commande->id}");

        $response->assertSessionHasNoErrors();
        $this->assertSame(Commande::STATUT_ANNULEE, $commande->fresh()->statut);
        $this->assertSame(3, $article->fresh()->stock); // 1 + 2 restaurés = 3
    }

    public function test_user_cannot_cancel_order_once_shipped(): void
    {
        $buyer = User::factory()->create();

        $commande = Commande::create([
            'user_id'            => $buyer->id,
            'numero'             => 'TC-2026-000055',
            'statut'             => Commande::STATUT_EXPEDIEE,
            'montant_sous_total' => 50.00,
            'montant_total'      => 57.00,
            'frais_livraison'    => 7.00,
            'mode_paiement'      => 'CARTE',
        ]);

        $response = $this->actingAs($buyer)->delete("/commandes/{$commande->id}");

        $this->assertNotSame(Commande::STATUT_ANNULEE, $commande->fresh()->statut);
    }

    public function test_user_can_download_invoice_pdf(): void
    {
        $seller  = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $buyer   = User::factory()->create();
        $article = Article::factory()->create(['user_id' => $seller->id]);

        $commande = Commande::create([
            'user_id'            => $buyer->id,
            'numero'             => 'TC-2026-000044',
            'statut'             => Commande::STATUT_CONFIRMEE,
            'montant_sous_total' => 45.00,
            'montant_total'      => 52.00,
            'frais_livraison'    => 7.00,
            'mode_paiement'      => 'CARTE',
        ]);

        LigneCommande::create([
            'commande_id'   => $commande->id,
            'article_id'    => $article->id,
            'quantite'      => 1,
            'prix_unitaire' => 45.00,
            'remise'        => 0.00,
            'total_ligne'   => 45.00,
        ]);

        $response = $this->actingAs($buyer)->get("/commandes/{$commande->id}/invoice");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_user_can_view_order_qrcode(): void
    {
        $buyer = User::factory()->create();
        $commande = Commande::create([
            'user_id'            => $buyer->id,
            'numero'             => 'TC-2026-000033',
            'statut'             => Commande::STATUT_CONFIRMEE,
            'montant_sous_total' => 20.00,
            'montant_total'      => 27.00,
            'frais_livraison'    => 7.00,
            'mode_paiement'      => 'CARTE',
        ]);

        $response = $this->actingAs($buyer)->get("/commandes/{$commande->id}/qrcode");

        $response->assertOk();
        $this->assertStringContainsString('image/svg+xml', $response->headers->get('content-type'));
    }

    public function test_admin_can_update_order_status_and_notification_is_sent(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $buyer = User::factory()->create(['role' => User::ROLE_CLIENT]);

        $commande = Commande::create([
            'user_id'            => $buyer->id,
            'numero'             => 'TC-2026-000022',
            'statut'             => Commande::STATUT_EN_ATTENTE,
            'montant_sous_total' => 50.00,
            'montant_total'      => 57.00,
            'frais_livraison'    => 7.00,
            'mode_paiement'      => 'CARTE',
        ]);

        $response = $this->actingAs($admin)->patch("/commandes/{$commande->id}/statut", [
            'statut'      => Commande::STATUT_EN_PREPARATION,
            'commentaire' => 'Articles emballés soigneusement dans nos ateliers.',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(Commande::STATUT_EN_PREPARATION, $commande->fresh()->statut);

        Notification::assertSentTo($buyer, CommandeStatutNotification::class);
    }
}
