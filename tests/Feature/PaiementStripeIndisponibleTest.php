<?php

namespace Tests\Feature;

use App\Http\Controllers\PaiementController;
use App\Models\Article;
use App\Models\Commande;
use App\Models\Panier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Exception\AuthenticationException;
use Tests\TestCase;

/**
 * « Payer avec Stripe » quand Stripe n'est pas configuré ou ne répond pas :
 * pas d'erreur 500, pas de commande fantôme, stock intact.
 */
class PaiementStripeIndisponibleTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Article} */
    private function clientAvecPanier(): array
    {
        $vendeur = User::factory()->create(['role' => User::ROLE_ATELIER]);
        $article = Article::factory()->create(['user_id' => $vendeur->id, 'stock' => 5, 'prix' => 30.00]);
        $client = User::factory()->create(['role' => User::ROLE_CLIENT]);

        Panier::create(['user_id' => $client->id])->lignes()->create(['article_id' => $article->id, 'quantite' => 2]);

        return [$client, $article];
    }

    public function test_sans_cle_stripe_le_client_revient_au_panier_avec_un_message(): void
    {
        config(['services.stripe.secret' => null]);
        [$client, $article] = $this->clientAvecPanier();

        $this->actingAs($client)->post('/paiement/checkout')
            ->assertRedirect('/panier')
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Stripe n\'est pas configuré'));

        $this->assertSame(0, Commande::count(), 'aucune commande fantôme');
        $this->assertSame(5, $article->fresh()->stock, 'stock intact');
        $this->assertSame(1, $client->panier->lignes()->count(), 'panier conservé');

        $this->actingAs($client)->get('/panier')->assertOk()->assertSee('Stripe n&#039;est pas configuré', false);
    }

    public function test_si_stripe_refuse_la_commande_est_annulee_et_le_stock_rendu(): void
    {
        config(['services.stripe.secret' => 'sk_test_invalide']);
        [$client, $article] = $this->clientAvecPanier();

        $controleur = Mockery::mock(PaiementController::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $controleur->shouldReceive('createCheckoutSession')->andThrow(new AuthenticationException('Invalid API Key provided'));
        $this->app->instance(PaiementController::class, $controleur);

        $this->actingAs($client)->post('/paiement/checkout')
            ->assertRedirect('/panier')
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'n\'a pas pu démarrer'));

        $commande = Commande::sole();
        $this->assertSame(Commande::STATUT_ANNULEE, $commande->statut);
        $this->assertSame(Commande::STATUT_PAIEMENT_ECHOUE, $commande->statut_paiement);
        $this->assertSame(5, $article->fresh()->stock, 'stock rendu');
        $this->assertSame(1, $client->panier->lignes()->count(), 'panier conservé');
    }

    public function test_un_article_gratuit_paie_la_livraison_donc_aussi_bloque_sans_stripe(): void
    {
        config(['services.stripe.secret' => null]);
        $donateur = User::factory()->create(['role' => User::ROLE_DONATEUR]);
        $article = Article::factory()->create(['user_id' => $donateur->id, 'stock' => 1, 'prix' => 0]);
        $client = User::factory()->create(['role' => User::ROLE_CLIENT]);
        Panier::create(['user_id' => $client->id])->lignes()->create(['article_id' => $article->id, 'quantite' => 1]);

        // Frais de livraison fixes (Commande::FRAIS_LIVRAISON) : le total n'est jamais nul
        $this->assertGreaterThan(0, Commande::FRAIS_LIVRAISON);

        $this->actingAs($client)->post('/paiement/checkout')
            ->assertRedirect('/panier')
            ->assertSessionHas('error');

        $this->assertSame(0, Commande::count());
        $this->assertSame(1, $article->fresh()->stock);
    }
}
