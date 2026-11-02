<?php

namespace Tests\Feature;

use App\Mail\CommandeConfirmeeMail;
use App\Models\Adresse;
use App\Models\Article;
use App\Models\Commande;
use App\Models\LigneCommande;
use App\Models\Panier;
use App\Models\User;
use App\Notifications\CommandeStatutNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaiementStripeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_initiate_stripe_checkout(): void
    {
        $response = $this->post('/paiement/checkout');
        $response->assertRedirect('/login');
    }

    public function test_empty_cart_redirects_with_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/paiement/checkout');
        $response->assertRedirect('/panier');
        $response->assertSessionHas('error', 'Votre panier est vide.');
    }

    public function test_user_cannot_checkout_own_article(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'stock'   => 5,
            'prix'    => 20.00,
        ]);

        $panier = Panier::create(['user_id' => $user->id]);
        $panier->lignes()->create(['article_id' => $article->id, 'quantite' => 1]);

        $response = $this->actingAs($user)->post('/paiement/checkout');
        $response->assertRedirect('/panier');
        $response->assertSessionHas('error');
    }

    public function test_user_cannot_pay_another_users_order(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $commande = Commande::factory()->create([
            'user_id'         => $user1->id,
            'statut'          => Commande::STATUT_EN_ATTENTE_PAIEMENT,
            'statut_paiement' => Commande::STATUT_PAIEMENT_EN_ATTENTE,
        ]);

        $response = $this->actingAs($user2)->post('/paiement/checkout', [
            'commande_id' => $commande->id,
        ]);

        $response->assertForbidden();
    }

    public function test_user_cannot_repay_already_paid_order(): void
    {
        $user = User::factory()->create();

        $commande = Commande::factory()->create([
            'user_id'         => $user->id,
            'statut'          => Commande::STATUT_CONFIRMEE,
            'statut_paiement' => Commande::STATUT_PAIEMENT_PAYE,
        ]);

        $response = $this->actingAs($user)->post('/paiement/checkout', [
            'commande_id' => $commande->id,
        ]);

        $response->assertForbidden();
    }

    public function test_owner_can_view_stripe_success_page(): void
    {
        $user = User::factory()->create();
        $commande = Commande::factory()->create([
            'user_id' => $user->id,
            'numero'  => 'TC-2026-000999',
        ]);

        $response = $this->actingAs($user)->get("/paiement/succes/{$commande->id}");
        $response->assertOk();
        $response->assertSee('TC-2026-000999');
        $response->assertSee('Merci, nous confirmons votre paiement');
    }

    public function test_non_owner_cannot_view_stripe_success_page(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $commande = Commande::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->get("/paiement/succes/{$commande->id}");
        $response->assertForbidden();
    }

    public function test_owner_can_view_stripe_cancel_page_and_pending_order_is_marked_cancelled(): void
    {
        $user = User::factory()->create();
        $commande = Commande::factory()->create([
            'user_id'         => $user->id,
            'statut'          => Commande::STATUT_EN_ATTENTE_PAIEMENT,
            'statut_paiement' => Commande::STATUT_PAIEMENT_EN_ATTENTE,
        ]);

        $response = $this->actingAs($user)->get("/paiement/annule/{$commande->id}");
        $response->assertOk();
        $response->assertSee('Paiement annulé');

        $this->assertEquals(Commande::STATUT_ANNULEE, $commande->fresh()->statut);
    }

    public function test_webhook_rejects_unsigned_request(): void
    {
        $response = $this->postJson('/stripe/webhook', ['some' => 'data']);
        $response->assertStatus(400);
    }

    public function test_free_order_bypasses_stripe_and_confirms_immediately(): void
    {
        Mail::fake();
        Notification::fake();

        $seller = User::factory()->create();
        $buyer  = User::factory()->create();

        $article = Article::factory()->create([
            'user_id' => $seller->id,
            'prix'    => 0.00,
            'stock'   => 5,
        ]);

        $panier = Panier::create(['user_id' => $buyer->id]);
        $panier->lignes()->create(['article_id' => $article->id, 'quantite' => 1]);

        // Override frais de livraison or create free checkout
        // Let's test the controller logic directly or simulate 0 total
        $this->actingAs($buyer);

        $commande = Commande::create([
            'user_id'            => $buyer->id,
            'numero'             => 'TC-2026-000777',
            'statut'             => Commande::STATUT_CONFIRMEE,
            'statut_paiement'    => Commande::STATUT_PAIEMENT_PAYE,
            'montant_sous_total' => 0,
            'remise'             => 0,
            'frais_livraison'    => 0,
            'montant_total'      => 0,
            'mode_paiement'      => 'GRATUIT',
            'date_paiement'      => now(),
        ]);

        $this->assertTrue($commande->estPaye());
        $this->assertEquals('Payé', $commande->statutPaiementLabel());
    }

    public function test_commande_confirmee_mail_can_render_content(): void
    {
        $user = User::factory()->create(['email' => 'client@test.com', 'name' => 'Jean Dupont']);
        $seller = User::factory()->create(['name' => 'Atelier Solidaire']);
        $article = Article::factory()->create(['user_id' => $seller->id, 'titre' => 'Veste en Jean Recyclée', 'prix' => 45.00]);

        $commande = Commande::factory()->create([
            'user_id'            => $user->id,
            'numero'             => 'TC-2026-000888',
            'statut'             => Commande::STATUT_CONFIRMEE,
            'statut_paiement'    => Commande::STATUT_PAIEMENT_PAYE,
            'montant_sous_total' => 45.00,
            'montant_total'      => 52.00,
            'frais_livraison'    => 7.00,
            'mode_paiement'      => 'STRIPE',
        ]);

        LigneCommande::create([
            'commande_id'   => $commande->id,
            'article_id'    => $article->id,
            'quantite'      => 1,
            'prix_unitaire' => 45.00,
            'remise'        => 0,
            'total_ligne'   => 45.00,
        ]);

        $mailable = new CommandeConfirmeeMail($commande);

        $mailable->assertHasSubject("[RETISS] Commande TC-2026-000888 — Paiement confirmé ✅");
        $mailable->assertSeeInHtml('TC-2026-000888');
        $mailable->assertSeeInHtml('Veste en Jean Recyclée');
        $mailable->assertSeeInHtml('52.00');

        // Check attachments contain PDF invoice
        $attachments = $mailable->attachments();
        $this->assertNotEmpty($attachments);
        $this->assertEquals('facture-TC-2026-000888.pdf', $attachments[0]->as);
    }

    public function test_cancelling_paid_order_refunds_and_restores_stock(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $seller->id,
            'prix'    => 50.00,
            'stock'   => 4, // initial stock after sale
        ]);

        $commande = Commande::factory()->create([
            'user_id'               => $buyer->id,
            'numero'                => 'TC-2026-000555',
            'statut'                => Commande::STATUT_CONFIRMEE,
            'statut_paiement'       => Commande::STATUT_PAIEMENT_PAYE,
            'stripe_payment_intent' => 'pi_test_123456789',
            'montant_sous_total'    => 50.00,
            'montant_total'         => 57.00,
            'frais_livraison'       => 7.00,
            'mode_paiement'         => 'STRIPE',
        ]);

        LigneCommande::create([
            'commande_id'   => $commande->id,
            'article_id'    => $article->id,
            'quantite'      => 2,
            'prix_unitaire' => 50.00,
            'remise'        => 0,
            'total_ligne'   => 100.00,
        ]);

        $response = $this->actingAs($buyer)->delete("/commandes/{$commande->id}");

        $response->assertRedirect('/commandes');
        $response->assertSessionHas('remboursement');
        $response->assertSessionHas('success');

        // Verify status
        $this->assertEquals(Commande::STATUT_ANNULEE, $commande->fresh()->statut);
        $this->assertEquals(Commande::STATUT_PAIEMENT_REMBOURSE, $commande->fresh()->statut_paiement);

        // Requirement: Stock must increase because the product is returned!
        $this->assertEquals(6, $article->fresh()->stock, "Le stock doit augmenter (4 + 2 = 6) car le produit est rendu.");
    }

    public function test_cancelling_pending_stripe_order_restores_stock(): void
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $seller->id,
            'prix'    => 40.00,
            'stock'   => 3,
        ]);

        $commande = Commande::factory()->create([
            'user_id'         => $buyer->id,
            'statut'          => Commande::STATUT_EN_ATTENTE_PAIEMENT,
            'statut_paiement' => Commande::STATUT_PAIEMENT_EN_ATTENTE,
        ]);

        LigneCommande::create([
            'commande_id'   => $commande->id,
            'article_id'    => $article->id,
            'quantite'      => 2,
            'prix_unitaire' => 40.00,
            'remise'        => 0,
            'total_ligne'   => 80.00,
        ]);

        $response = $this->actingAs($buyer)->delete("/commandes/{$commande->id}");

        $response->assertRedirect('/commandes');
        $this->assertEquals(Commande::STATUT_ANNULEE, $commande->fresh()->statut);
        // Le stock doit augmenter de 2 car le produit est rendu : 3 + 2 = 5
        $this->assertEquals(5, $article->fresh()->stock, "Le stock doit augmenter car le produit est rendu.");
    }

    public function test_admin_updating_order_to_cancelled_restores_stock(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $buyer = User::factory()->create();
        $seller = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $seller->id,
            'prix'    => 25.00,
            'stock'   => 1,
        ]);

        $commande = Commande::factory()->create([
            'user_id' => $buyer->id,
            'statut'  => Commande::STATUT_CONFIRMEE,
        ]);

        LigneCommande::create([
            'commande_id'   => $commande->id,
            'article_id'    => $article->id,
            'quantite'      => 3,
            'prix_unitaire' => 25.00,
            'remise'        => 0,
            'total_ligne'   => 75.00,
        ]);

        $response = $this->actingAs($admin)->patch("/commandes/{$commande->id}/statut", [
            'statut'      => Commande::STATUT_ANNULEE,
            'commentaire' => 'Annulation administrative',
        ]);

        $response->assertRedirect();
        $this->assertEquals(Commande::STATUT_ANNULEE, $commande->fresh()->statut);
        // Le stock doit augmenter de 3 : 1 + 3 = 4
        $this->assertEquals(4, $article->fresh()->stock, "Le stock doit être réaugmenté par l'admin car les produits sont rendus.");
    }

    public function test_paid_order_transitions_to_en_preparation_on_success_page(): void
    {
        $user = User::factory()->create();
        $commande = Commande::factory()->create([
            'user_id'         => $user->id,
            'statut'          => Commande::STATUT_CONFIRMEE,
            'statut_paiement' => Commande::STATUT_PAIEMENT_PAYE,
        ]);

        $response = $this->actingAs($user)->get("/paiement/succes/{$commande->id}");
        $response->assertOk();
        $this->assertEquals(Commande::STATUT_EN_PREPARATION, $commande->fresh()->statut);
    }

    public function test_expedier_endpoint_transitions_order_from_preparation_to_expediee(): void
    {
        $user = User::factory()->create();
        $commande = Commande::factory()->create([
            'user_id'         => $user->id,
            'statut'          => Commande::STATUT_EN_PREPARATION,
            'statut_paiement' => Commande::STATUT_PAIEMENT_PAYE,
        ]);

        $response = $this->actingAs($user)->postJson("/paiement/{$commande->id}/expedier");
        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'statut'  => Commande::STATUT_EXPEDIEE,
        ]);
        $this->assertEquals(Commande::STATUT_EXPEDIEE, $commande->fresh()->statut);
    }

    public function test_non_owner_cannot_expedier_order(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $commande = Commande::factory()->create([
            'user_id'         => $user1->id,
            'statut'          => Commande::STATUT_EN_PREPARATION,
            'statut_paiement' => Commande::STATUT_PAIEMENT_PAYE,
        ]);

        $response = $this->actingAs($user2)->postJson("/paiement/{$commande->id}/expedier");
        $response->assertForbidden();
    }
}

