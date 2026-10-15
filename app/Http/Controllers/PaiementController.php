<?php

namespace App\Http\Controllers;

use App\Mail\CommandeConfirmeeMail;
use App\Models\Adresse;
use App\Models\Article;
use App\Models\Commande;
use App\Models\LigneCommande;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class PaiementController extends Controller
{
    use AuthorizesRequests;

    // =========================================================
    // CHECKOUT — Create Stripe session & redirect
    // =========================================================
    public function checkout(Request $request): RedirectResponse
    {
        $user = $request->user();

        // -------------------------------------------------------------
        // Case A: Pay an existing unpaid order (Policy: payer)
        // -------------------------------------------------------------
        if ($request->filled('commande_id')) {
            $commande = Commande::with(['lignes.article', 'user'])->findOrFail($request->input('commande_id'));

            // Policy check: user can only pay their own order, and cannot re-pay paid/cancelled orders
            $this->authorize('payer', $commande);

            if ($commande->estPaye()) {
                return redirect()->route('commandes.show', $commande)
                    ->with('info', 'Cette commande est déjà payée.');
            }

            return $this->redirectToStripeSession($commande, $user);
        }

        // -------------------------------------------------------------
        // Case B: Create order from cart & checkout (Policy: create)
        // -------------------------------------------------------------
        $this->authorize('create', Commande::class);

        // ----------- 1. Rebuild items from cart (server-side, never trust the browser) -----------
        $panier = $user->panier;
        if (! $panier || $panier->lignes()->count() === 0) {
            return redirect()->route('panier.index')
                ->with('error', 'Votre panier est vide.');
        }

        $panier->load('lignes.article.user');

        // Collect article IDs and lock rows for stock check
        $articleIds = $panier->lignes->pluck('article_id')->filter();
        $articles   = Article::whereIn('id', $articleIds)->lockForUpdate()->get()->keyBy('id');

        $lines = [];
        foreach ($panier->lignes as $panierLigne) {
            $article = $articles->get($panierLigne->article_id);
            $qty     = (int) $panierLigne->quantite;

            if (! $article) {
                return redirect()->route('panier.index')
                    ->with('error', "Un article de votre panier n'existe plus.");
            }
            if ($article->user_id === $user->id) {
                return redirect()->route('panier.index')
                    ->with('error', "Vous ne pouvez pas acheter votre propre article « {$article->titre} ».");
            }
            if (! $article->isDisponible()) {
                return redirect()->route('panier.index')
                    ->with('error', "L'article « {$article->titre} » n'est plus disponible.");
            }
            if ($article->stock < $qty) {
                return redirect()->route('panier.index')
                    ->with('error', "Stock insuffisant pour « {$article->titre} » (disponible : {$article->stock}).");
            }

            $sellerIsAtelier = $article->user && $article->user->role === User::ROLE_ATELIER;
            $hasRemise       = $user->getsRemise() && $sellerIsAtelier;
            $remisePct       = $hasRemise ? Commande::TAUX_REMISE : 0;
            $remiseMontant   = round($article->prix * $remisePct, 2);

            $lines[] = [
                'article'       => $article,
                'quantite'      => $qty,
                'prix_unitaire' => (float) $article->prix,
                'remise'        => $remiseMontant,
                'total_ligne'   => round(($article->prix - $remiseMontant) * $qty, 2),
            ];
        }

        // ----------- 2. Resolve delivery address -----------
        $adresseId    = $request->input('adresse_id');
        $adresseModel = $adresseId ? Adresse::find($adresseId) : null;
        if (! $adresseModel) {
            $adresseModel = $user->adresseParDefaut()->first();
        }
        $snapshot = $adresseModel?->formatted_address;

        $deliveryDays = 4;
        if ($adresseModel && $adresseModel->gouvernorat) {
            $gouv = mb_strtolower($adresseModel->gouvernorat);
            if (str_contains($gouv, 'tunis') || str_contains($gouv, 'ariana') || str_contains($gouv, 'ben arous') || str_contains($gouv, 'manouba')) {
                $deliveryDays = 2;
            } elseif (str_contains($gouv, 'sousse') || str_contains($gouv, 'monastir') || str_contains($gouv, 'nabeul') || str_contains($gouv, 'bizerte')) {
                $deliveryDays = 3;
            } else {
                $deliveryDays = 5;
            }
        }

        // ----------- 3. Compute totals -----------
        $sousTotal    = collect($lines)->sum(fn ($l) => $l['prix_unitaire'] * $l['quantite']);
        $totalRemise  = collect($lines)->sum(fn ($l) => $l['remise'] * $l['quantite']);
        $fraisLiv     = Commande::FRAIS_LIVRAISON;
        $total        = round($sousTotal - $totalRemise + $fraisLiv, 2);

        // ----------- 4. If total is 0 (free donation), confirm directly without Stripe -----------
        if ($total <= 0) {
            $commande = $this->creerCommande(
                $user, $lines, $sousTotal, $totalRemise, $fraisLiv, $total,
                $adresseModel?->id, $snapshot, $deliveryDays, 'GRATUIT'
            );
            $commande->update([
                'statut'          => Commande::STATUT_CONFIRMEE,
                'statut_paiement' => Commande::STATUT_PAIEMENT_PAYE,
                'date_paiement'   => now(),
            ]);
            $commande->pushHistorique(Commande::STATUT_CONFIRMEE, 'Don gratuit — aucun paiement requis');
            $this->postConfirmation($commande, $articleIds->toArray(), $user);

            // Empty cart
            if ($user->panier) {
                $user->panier->lignes()->delete();
            }

            return redirect()->route('paiement.succes', $commande)
                ->with('success', 'Commande confirmée (don gratuit) !');
        }

        // ----------- 5. Create the commande in DB (pending payment) -----------
        $commande = $this->creerCommande(
            $user, $lines, $sousTotal, $totalRemise, $fraisLiv, $total,
            $adresseModel?->id, $snapshot, $deliveryDays, 'STRIPE'
        );

        // ----------- 6. Build Stripe Checkout session & redirect -----------
        return $this->redirectToStripeSession($commande, $user);
    }

    /**
     * Build Stripe line items payload.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildStripeLineItems(Commande $commande, string $currency): array
    {
        $items = [];

        foreach ($commande->lignes as $ligne) {
            $prixApresRemise = round($ligne->prix_unitaire - $ligne->remise, 2);
            $items[] = [
                'price_data' => [
                    'currency'     => $currency,
                    'unit_amount'  => (int) round($prixApresRemise * 100),
                    'product_data' => [
                        'name'     => $ligne->article?->titre ?? 'Article RETISS',
                        'metadata' => ['article_id' => (string) $ligne->article_id],
                    ],
                ],
                'quantity' => $ligne->quantite,
            ];
        }

        if ($commande->frais_livraison > 0) {
            $items[] = [
                'price_data' => [
                    'currency'     => $currency,
                    'unit_amount'  => (int) round($commande->frais_livraison * 100),
                    'product_data' => ['name' => 'Frais de livraison'],
                ],
                'quantity' => 1,
            ];
        }

        return $items;
    }

    /**
     * Perform the actual Stripe session creation API call.
     *
     * Isolated in its own method so that Intelephense's type-inference depth limit
     * (triggered by the Stripe SDK's deeply-nested OpenAPI annotations) does not
     * propagate warnings to callers.
     *
     * @param  array<string, mixed> $params
     * @return object
     * @phpstan-return object{id: string, url: string, payment_status: string, payment_intent: string|null, metadata: object}
     */
    private function createCheckoutSession(array $params): object
    {
        /** @var mixed $client */
        $client = new StripeClient((string) config('services.stripe.secret'));

        /** @var object $session */
        $session = $client->checkout->sessions->create($params);

        return $session;
    }

    /**
     * Create Stripe session for a given Commande and redirect away to Stripe.
     */
    private function redirectToStripeSession(Commande $commande, User $user): RedirectResponse
    {
        $commande->loadMissing(['lignes.article']);
        $currency  = (string) config('services.stripe.currency', 'eur');

        $lineItems = $this->buildStripeLineItems($commande, $currency);

        $session = $this->createCheckoutSession([
            'mode'                 => 'payment',
            'line_items'           => $lineItems,
            'customer_email'       => $user->email,
            'metadata'             => [
                'commande_id' => (string) $commande->id,
                'user_id'     => (string) $user->id,
            ],
            'payment_intent_data'  => [
                'metadata' => ['commande_id' => (string) $commande->id],
            ],
            'success_url' => route('paiement.succes', ['commande' => $commande->id]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('paiement.annule', ['commande' => $commande->id]),
            'expires_at'  => now()->addMinutes(30)->timestamp,
        ]);

        $commande->update(['stripe_session_id' => (string) $session->id]);

        return redirect()->away((string) $session->url);
    }

    // =========================================================
    // SUCCESS — Return page (verifies payment & shows confirmation)
    // =========================================================
    public function succes(Request $request, Commande $commande): View
    {
        // Only the order owner can see this page
        $this->authorize('view', $commande);

        // If order is not yet marked paid, verify directly with Stripe API
        // (Ensures instant confirmation even without Stripe CLI webhook forwarder in local dev)
        if (! $commande->estPaye()) {
            $sessionId = $request->query('session_id') ?? $commande->stripe_session_id;
            if ($sessionId) {
                try {
                    $secretKey = (string) config('services.stripe.secret');
                    $stripe    = new StripeClient($secretKey);
                    $session   = $stripe->checkout->sessions->retrieve($sessionId);

                    if ($session && $session->payment_status === 'paid') {
                        $this->handleCheckoutCompleted($session);
                        $commande->refresh();
                    }
                } catch (\Throwable $e) {
                    Log::warning('[Stripe] Verification on success page: ' . $e->getMessage());
                }
            }
        }

        $commande->load(['lignes.article', 'adresse', 'user']);

        return view('paiement.succes', compact('commande'));
    }

    // =========================================================
    // CANCEL — Return page when Stripe checkout is cancelled
    // =========================================================
    public function annule(Request $request, Commande $commande): View|RedirectResponse
    {
        $this->authorize('view', $commande);

        // If already paid (webhook was faster), redirect to success
        if ($commande->estPaye()) {
            return redirect()->route('paiement.succes', $commande)
                ->with('info', 'Votre paiement a bien été enregistré.');
        }

        // Mark as cancelled if still in pending payment state (restore stock because product is returned)
        if ($commande->enAttenteDeStripe()) {
            DB::transaction(function () use ($commande) {
                $commande->load('lignes.article');
                foreach ($commande->lignes as $ligne) {
                    if ($ligne->article) {
                        $ligne->article->increment('stock', $ligne->quantite);
                        if ($ligne->article->statut === Article::STATUT_VENDU) {
                            $ligne->article->update(['statut' => Article::STATUT_DISPONIBLE]);
                        }
                    }
                }
                $commande->update(['statut' => Commande::STATUT_ANNULEE]);
                $commande->pushHistorique(Commande::STATUT_ANNULEE, 'Paiement Stripe annulé par l\'utilisateur (stock restitué car produit rendu)');
            });
        }

        return view('paiement.annule', compact('commande'));
    }

    // =========================================================
    // WEBHOOK — Stripe event handler (no CSRF, no auth)
    // =========================================================
    public function webhook(Request $request): \Illuminate\Http\Response
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        // Verify Stripe signature — reject any unsigned request
        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('[Stripe Webhook] Invalid signature: ' . $e->getMessage());
            return response('Invalid signature', 400);
        } catch (\Exception $e) {
            Log::error('[Stripe Webhook] Parse error: ' . $e->getMessage());
            return response('Webhook error', 400);
        }

        Log::info('[Stripe Webhook] Received event: ' . $event->type);

        match ($event->type) {
            'checkout.session.completed'   => $this->handleCheckoutCompleted($event->data->object),
            'checkout.session.expired'     => $this->handleCheckoutExpired($event->data->object),
            'payment_intent.payment_failed'=> $this->handlePaymentFailed($event->data->object),
            'charge.refunded'              => $this->handleChargeRefunded($event->data->object),
            default                        => null,
        };

        return response('OK', 200);
    }

    // =========================================================
    // REFUND — Admin triggers a Stripe refund
    // =========================================================
    public function rembourser(Request $request, Commande $commande): RedirectResponse
    {
        $this->authorize('updateStatut', $commande);

        if (! $commande->estPaye()) {
            return back()->with('error', 'Cette commande n\'est pas payée via Stripe, impossible de rembourser.');
        }

        if (! $commande->stripe_payment_intent) {
            return back()->with('error', 'Aucun identifiant de paiement Stripe disponible pour le remboursement.');
        }

        if ($commande->statut_paiement === Commande::STATUT_PAIEMENT_REMBOURSE) {
            return back()->with('info', 'Cette commande est déjà remboursée.');
        }

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $stripe->refunds->create([
                'payment_intent' => $commande->stripe_payment_intent,
            ]);

            DB::transaction(function () use ($commande) {
                // Restore stock car le produit est rendu
                $commande->load('lignes.article');
                foreach ($commande->lignes as $ligne) {
                    $ligne->article?->increment('stock', $ligne->quantite);
                    if ($ligne->article?->statut === Article::STATUT_VENDU) {
                        $ligne->article->update(['statut' => Article::STATUT_DISPONIBLE]);
                    }
                }

                $commande->update([
                    'statut'          => Commande::STATUT_ANNULEE,
                    'statut_paiement' => Commande::STATUT_PAIEMENT_REMBOURSE,
                ]);
                $commande->pushHistorique(Commande::STATUT_ANNULEE, 'Remboursement Stripe effectué par l\'administrateur (produits rendus — stock réaugmenté)');
            });

            // Notify user
            try {
                $commande->user->notify(new \App\Notifications\CommandeStatutNotification(
                    $commande,
                    Commande::STATUT_ANNULEE,
                    "L'argent (" . number_format($commande->montant_total, 2) . " DT) a été remboursé sur votre compte via Stripe. Le stock du produit a été augmenté car le produit est rendu."
                ));
            } catch (\Throwable $e) {
                Log::warning('[Stripe] Notification error after refund: ' . $e->getMessage());
            }

            return back()
                ->with('success', "L'argent (" . number_format($commande->montant_total, 2) . " DT) a été remboursé avec succès sur le compte du client via Stripe ! Le stock du produit a été augmenté car le produit est rendu.")
                ->with('remboursement', [
                    'numero'  => $commande->numero,
                    'montant' => number_format($commande->montant_total, 2),
                    'message' => "L'argent a été remboursé avec succès ! Le montant de " . number_format($commande->montant_total, 2) . " DT a été reversé sur le compte via Stripe. Le stock du produit a bien été augmenté car le produit est rendu.",
                ]);

        } catch (\Exception $e) {
            Log::error('[Stripe] Refund error: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors du remboursement Stripe : ' . $e->getMessage());
        }
    }

    // =========================================================
    // PRIVATE — Webhook handlers
    // =========================================================

    private function handleCheckoutCompleted(object $session): void
    {
        $commandeId = $session->metadata->commande_id ?? null;
        if (! $commandeId) {
            Log::error('[Stripe] checkout.session.completed — missing commande_id in metadata');
            return;
        }

        $commande = Commande::find($commandeId);
        if (! $commande) {
            Log::error("[Stripe] checkout.session.completed — Commande #{$commandeId} not found");
            return;
        }

        // Idempotency: if already paid, skip
        if ($commande->statut_paiement === Commande::STATUT_PAIEMENT_PAYE) {
            Log::info("[Stripe] Commande #{$commandeId} already paid — skipping");
            return;
        }

        $paymentIntentId = $session->payment_intent ?? null;

        DB::transaction(function () use ($commande, $session, $paymentIntentId) {
            $articleIds = $commande->lignes()->pluck('article_id');

            // Update commande
            $commande->update([
                'statut'                => Commande::STATUT_CONFIRMEE,
                'stripe_session_id'     => $session->id,
                'stripe_payment_intent' => $paymentIntentId,
                'statut_paiement'       => Commande::STATUT_PAIEMENT_PAYE,
                'date_paiement'         => now(),
            ]);
            $commande->pushHistorique(Commande::STATUT_CONFIRMEE, 'Paiement Stripe confirmé');

            // Clear cart
            $commande->load('user');
            if ($commande->user->panier) {
                $commande->user->panier->lignes()
                    ->whereIn('article_id', $articleIds)
                    ->delete();
            }
        });

        $commande->refresh()->load(['lignes.article.user', 'adresse', 'user']);

        // Send confirmation email + notification
        $this->postConfirmation(
            $commande,
            $commande->lignes->pluck('article_id')->toArray(),
            $commande->user
        );
    }

    private function handleCheckoutExpired(object $session): void
    {
        $commandeId = $session->metadata->commande_id ?? null;
        if (! $commandeId) {
            return;
        }

        $commande = Commande::find($commandeId);
        if (! $commande || $commande->statut_paiement === Commande::STATUT_PAIEMENT_PAYE) {
            return;
        }

        DB::transaction(function () use ($commande) {
            // Restore stock car les articles sont remis en stock (produits rendus)
            $commande->load('lignes.article');
            foreach ($commande->lignes as $ligne) {
                if ($ligne->article) {
                    $ligne->article->increment('stock', $ligne->quantite);
                    if ($ligne->article->statut === Article::STATUT_VENDU) {
                        $ligne->article->update(['statut' => Article::STATUT_DISPONIBLE]);
                    }
                }
            }

            $commande->update([
                'statut'          => Commande::STATUT_ANNULEE,
                'statut_paiement' => Commande::STATUT_PAIEMENT_ECHOUE,
            ]);
            $commande->pushHistorique(Commande::STATUT_ANNULEE, 'Session Stripe expirée (articles remis en stock car produits rendus)');
        });

        Log::info("[Stripe] Commande #{$commandeId} expired.");
    }

    private function handlePaymentFailed(object $paymentIntent): void
    {
        $commandeId = $paymentIntent->metadata->commande_id ?? null;
        if (! $commandeId) {
            return;
        }

        $commande = Commande::find($commandeId);
        if (! $commande || $commande->statut_paiement === Commande::STATUT_PAIEMENT_PAYE) {
            return;
        }

        DB::transaction(function () use ($commande) {
            $commande->load('lignes.article');
            foreach ($commande->lignes as $ligne) {
                if ($ligne->article) {
                    $ligne->article->increment('stock', $ligne->quantite);
                    if ($ligne->article->statut === Article::STATUT_VENDU) {
                        $ligne->article->update(['statut' => Article::STATUT_DISPONIBLE]);
                    }
                }
            }

            $commande->update([
                'statut'          => Commande::STATUT_ANNULEE,
                'statut_paiement' => Commande::STATUT_PAIEMENT_ECHOUE,
            ]);
            $commande->pushHistorique(Commande::STATUT_ANNULEE, 'Échec du paiement Stripe (articles remis en stock car produits rendus)');
        });

        Log::info("[Stripe] Payment failed for Commande #{$commandeId}.");
    }

    private function handleChargeRefunded(object $charge): void
    {
        $paymentIntentId = $charge->payment_intent ?? null;
        if (! $paymentIntentId) {
            return;
        }

        $commande = Commande::where('stripe_payment_intent', $paymentIntentId)->first();
        if (! $commande) {
            return;
        }

        DB::transaction(function () use ($commande) {
            if ($commande->statut !== Commande::STATUT_ANNULEE) {
                $commande->load('lignes.article');
                foreach ($commande->lignes as $ligne) {
                    if ($ligne->article) {
                        $ligne->article->increment('stock', $ligne->quantite);
                        if ($ligne->article->statut === Article::STATUT_VENDU) {
                            $ligne->article->update(['statut' => Article::STATUT_DISPONIBLE]);
                        }
                    }
                }
            }

            $commande->update([
                'statut'          => Commande::STATUT_ANNULEE,
                'statut_paiement' => Commande::STATUT_PAIEMENT_REMBOURSE,
            ]);
            $commande->pushHistorique(Commande::STATUT_ANNULEE, 'Remboursement Stripe confirmé via webhook (articles remis en stock car produits rendus)');
        });
    }

    // =========================================================
    // PRIVATE — Post-confirmation: email + notification
    // =========================================================
    private function postConfirmation(Commande $commande, array $articleIds, User $user): void
    {
        // Send email
        try {
            Mail::to($user->email)->queue(new CommandeConfirmeeMail($commande));
        } catch (\Throwable $e) {
            Log::warning('[Mail] CommandeConfirmeeMail error: ' . $e->getMessage());
        }

        // In-app notification
        try {
            $user->notify(new \App\Notifications\CommandeStatutNotification(
                $commande,
                Commande::STATUT_CONFIRMEE,
                'Votre paiement a été confirmé. Merci pour votre commande !'
            ));
        } catch (\Throwable $e) {
            Log::warning('[Notif] CommandeStatutNotification error: ' . $e->getMessage());
        }
    }

    // =========================================================
    // PRIVATE — Create the Commande record in a transaction
    // =========================================================
    private function creerCommande(
        User $user,
        array $lines,
        float $sousTotal,
        float $totalRemise,
        float $fraisLivraison,
        float $total,
        ?int $adresseId,
        ?string $snapshot,
        int $deliveryDays,
        string $modePaiement
    ): Commande {
        return DB::transaction(function () use (
            $user, $lines, $sousTotal, $totalRemise, $fraisLivraison, $total,
            $adresseId, $snapshot, $deliveryDays, $modePaiement
        ) {
            $commande = Commande::create([
                'user_id'            => $user->id,
                'adresse_id'         => $adresseId,
                'adresse_snapshot'   => $snapshot,
                'numero'             => Commande::genererNumero(),
                'statut'             => 'EN_ATTENTE_PAIEMENT',
                'montant_sous_total' => $sousTotal,
                'remise'             => $totalRemise,
                'frais_livraison'    => $fraisLivraison,
                'montant_total'      => $total,
                'mode_paiement'      => $modePaiement,
                'statut_paiement'    => Commande::STATUT_PAIEMENT_EN_ATTENTE,
                'date_livraison_estimee' => now()->addDays($deliveryDays)->toDateString(),
                'historique_statuts' => [[
                    'statut'      => 'EN_ATTENTE_PAIEMENT',
                    'date'        => now()->toIso8601String(),
                    'commentaire' => 'Commande créée — en attente de paiement Stripe',
                ]],
            ]);

            foreach ($lines as $l) {
                LigneCommande::create([
                    'commande_id'   => $commande->id,
                    'article_id'    => $l['article']->id,
                    'quantite'      => $l['quantite'],
                    'prix_unitaire' => $l['prix_unitaire'],
                    'remise'        => $l['remise'],
                    'total_ligne'   => $l['total_ligne'],
                ]);

                // Décrémenter le stock dès la création pour réserver l'article et éviter les surventes
                $l['article']->decrement('stock', $l['quantite']);
                $freshArticle = $l['article']->fresh();
                if ($freshArticle && $freshArticle->stock <= 0) {
                    $freshArticle->update([
                        'statut' => Article::STATUT_VENDU,
                        'stock'  => 0,
                    ]);
                }
            }

            return $commande;
        });
    }
}
