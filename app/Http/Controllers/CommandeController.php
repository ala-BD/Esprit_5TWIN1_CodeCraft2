<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommandeRequest;
use App\Http\Requests\UpdateCommandeRequest;
use App\Models\Article;
use App\Models\Commande;
use App\Models\LigneCommande;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CommandeController extends Controller
{
    use AuthorizesRequests;

    // =========================================================
    // INDEX — List of user's own orders (Admin: all)
    // =========================================================
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Commande::class);

        $query = Commande::with(['lignes.article', 'adresse'])
            ->latest('date_commande');

        if (! $request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        if ($statut = $request->get('statut')) {
            $query->where('statut', $statut);
        }

        if ($from = $request->get('from')) {
            $query->whereDate('date_commande', '>=', $from);
        }

        if ($to = $request->get('to')) {
            $query->whereDate('date_commande', '<=', $to);
        }

        $commandes = $query->paginate(10)->withQueryString();

        // Admin stats
        $stats = null;
        if ($request->user()->isAdmin()) {
            $stats = [
                'total_revenue' => Commande::where('statut', '!=', Commande::STATUT_ANNULEE)->sum('montant_total'),
                'total_orders'  => Commande::count(),
                'by_statut'     => Commande::selectRaw('statut, count(*) as total')
                    ->groupBy('statut')
                    ->pluck('total', 'statut')
                    ->toArray(),
                'best_articles' => LigneCommande::selectRaw('article_id, sum(quantite) as total_qty')
                    ->with('article')
                    ->groupBy('article_id')
                    ->orderByDesc('total_qty')
                    ->limit(5)
                    ->get(),
            ];
        }

        return view('commandes.index', [
            'commandes'     => $commandes,
            'statuts'       => Commande::STATUTS,
            'currentStatut' => $statut,
            'stats'         => $stats,
        ]);
    }

    // =========================================================
    // SHOW — Order detail / tracking page
    // =========================================================
    public function show(Commande $commande): View
    {
        $this->authorize('view', $commande);

        $commande->load(['lignes.article.user', 'adresse', 'user']);

        return view('commandes.show', [
            'commande' => $commande,
        ]);
    }

    // =========================================================
    // CREATE — Order form (from article page or cart)
    // =========================================================
    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Commande::class);

        // Articles can be passed as ?articles[]=id:qty or single ?article_id=&quantite=
        $items = $this->resolveCartItems($request);

        if (empty($items)) {
            return redirect()->route('articles.index')
                ->with('error', 'Aucun article sélectionné.');
        }

        // Early validation: cannot buy own article or unavailable article
        foreach ($items as $it) {
            $art = $it['article'];
            if ($art->user_id === $request->user()->id) {
                return redirect()->route('articles.show', $art)
                    ->with('error', "Vous ne pouvez pas acheter votre propre article (« {$art->titre} »).");
            }
            if (! $art->isDisponible()) {
                return redirect()->route('articles.show', $art)
                    ->with('error', "L'article « {$art->titre} » n'est plus disponible.");
            }
        }

        $adresses = $request->user()->adresses()->latest()->get();
        $adresseDefaut = $request->user()->adresseParDefaut()->first();

        return view('commandes.create', [
            'items'        => $items,
            'adresses'     => $adresses,
            'adresseDefaut'=> $adresseDefaut,
            'modes'        => Commande::MODES_PAIEMENT,
        ]);
    }

    // =========================================================
    // STORE — Place the order (atomic transaction)
    // =========================================================
    public function store(CommandeRequest $request): RedirectResponse
    {
        $this->authorize('create', Commande::class);

        $user = $request->user();

        // Resolve articles with locks to prevent race conditions
        $articleIds = collect($request->input('articles'))->pluck('id');
        $articles   = Article::whereIn('id', $articleIds)->lockForUpdate()->get()->keyBy('id');

        // Build line items and validate business rules
        $lines = [];
        foreach ($request->input('articles') as $item) {
            $article = $articles->get($item['id']);
            $qty     = (int) $item['quantite'];

            if (! $article) {
                return back()->withErrors(['articles' => "L'article #{$item['id']} est introuvable."]);
            }

            // Cannot buy own article (an atelier or donor cannot buy their own article)
            if ($article->user_id === $user->id) {
                return back()->withErrors(['articles' => "Un atelier ne peut pas acheter son propre article : « {$article->titre} »."]);
            }

            // Must be published and available
            if (! $article->isDisponible()) {
                return back()->withErrors(['articles' => "L'article « {$article->titre} » n'est plus disponible."]);
            }

            // Stock check
            if ($article->stock < $qty) {
                return back()->withErrors(['articles' => "Stock insuffisant pour « {$article->titre} » (disponible : {$article->stock})."]);
            }

            // Discount: 10% if buyer is COLLECTEUR / RECYCLEUR / ATELIER and seller is ATELIER
            $sellerIsAtelier = $article->user && $article->user->role === \App\Models\User::ROLE_ATELIER;
            $hasRemise       = $user->getsRemise() && $sellerIsAtelier;
            $remisePct       = $hasRemise ? Commande::TAUX_REMISE : 0;
            $remiseMontant   = round($article->prix * $remisePct, 2);

            $lines[] = [
                'article'      => $article,
                'quantite'     => $qty,
                'prix_unitaire'=> (float) $article->prix,
                'remise'       => $remiseMontant,
                'total_ligne'  => round(($article->prix - $remiseMontant) * $qty, 2),
            ];
        }

        // Totals
        $sousTotal     = collect($lines)->sum(fn ($l) => $l['prix_unitaire'] * $l['quantite']);
        $totalRemise   = collect($lines)->sum(fn ($l) => $l['remise'] * $l['quantite']);
        $fraisLivraison = Commande::FRAIS_LIVRAISON;
        $total         = round($sousTotal - $totalRemise + $fraisLivraison, 2);

        // Delivery address snapshot & estimate
        $adresseId     = $request->input('adresse_id');
        $adresseModel  = $adresseId ? \App\Models\Adresse::find($adresseId) : null;
        $snapshot      = $adresseModel ? $adresseModel->formatted_address : null;

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

        DB::transaction(function () use ($user, $request, $lines, $sousTotal, $totalRemise, $fraisLivraison, $total, $adresseId, $snapshot, $deliveryDays) {
            // Create the order
            $commande = Commande::create([
                'user_id'            => $user->id,
                'adresse_id'         => $adresseId,
                'adresse_snapshot'   => $snapshot,
                'numero'             => Commande::genererNumero(),
                'statut'             => Commande::STATUT_EN_ATTENTE,
                'montant_sous_total' => $sousTotal,
                'remise'             => $totalRemise,
                'frais_livraison'    => $fraisLivraison,
                'montant_total'      => $total,
                'mode_paiement'      => $request->input('mode_paiement'),
                'date_livraison_estimee' => now()->addDays($deliveryDays)->toDateString(),
                'historique_statuts' => [[
                    'statut' => Commande::STATUT_EN_ATTENTE,
                    'date'   => now()->toIso8601String(),
                    'commentaire' => 'Commande créée par le client',
                ]],
            ]);

            // Create order lines & decrement stock
            foreach ($lines as $l) {
                LigneCommande::create([
                    'commande_id'   => $commande->id,
                    'article_id'    => $l['article']->id,
                    'quantite'      => $l['quantite'],
                    'prix_unitaire' => $l['prix_unitaire'],
                    'remise'        => $l['remise'],
                    'total_ligne'   => $l['total_ligne'],
                ]);

                // Decrement stock
                $l['article']->decrement('stock', $l['quantite']);

                // Mark as sold-out if stock hits 0
                if ($l['article']->fresh()->stock === 0) {
                    $l['article']->update(['statut' => Article::STATUT_VENDU]);
                }
            }

            // Store commande id for redirect outside the closure
            $this->lastCommandeId = $commande->id;
        });

        $createdCommande = Commande::find($this->lastCommandeId);

        // In-app & email notification
        if ($createdCommande) {
            try {
                $createdCommande->user->notify(new \App\Notifications\CommandeStatutNotification(
                    $createdCommande,
                    Commande::STATUT_EN_ATTENTE,
                    'Votre commande a été confirmée et est en attente de traitement.'
                ));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Notification failure: ' . $e->getMessage());
            }
        }

        return redirect()
            ->route('commandes.show', $this->lastCommandeId)
            ->with('success', "Commande passée avec succès ! Numéro : " . $createdCommande?->numero);
    }

    // =========================================================
    // EDIT — Edit form (only while EN_ATTENTE)
    // =========================================================
    public function edit(Commande $commande): View
    {
        $this->authorize('update', $commande);

        $commande->load(['lignes.article', 'adresse']);
        $adresses = auth()->user()->adresses()->latest()->get();

        return view('commandes.edit', [
            'commande' => $commande,
            'adresses' => $adresses,
            'modes'    => Commande::MODES_PAIEMENT,
        ]);
    }

    // =========================================================
    // UPDATE — Update address/payment and quantities
    // =========================================================
    public function update(UpdateCommandeRequest $request, Commande $commande): RedirectResponse
    {
        $this->authorize('update', $commande);

        DB::transaction(function () use ($request, $commande) {
            $adresseId    = $request->input('adresse_id');
            $adresseModel = $adresseId ? \App\Models\Adresse::find($adresseId) : null;

            $commande->update([
                'adresse_id'       => $adresseId,
                'adresse_snapshot' => $adresseModel?->formatted_address,
                'mode_paiement'    => $request->input('mode_paiement'),
            ]);

            // Update quantities
            if ($request->filled('lignes')) {
                foreach ($request->input('lignes') as $ligneData) {
                    $ligne = LigneCommande::find($ligneData['id']);
                    if (! $ligne || $ligne->commande_id !== $commande->id) {
                        continue;
                    }

                    $newQty = (int) $ligneData['quantite'];
                    $diff   = $newQty - $ligne->quantite;
                    $article = $ligne->article;

                    // Check stock
                    if ($diff > 0 && $article->stock < $diff) {
                        abort(422, "Stock insuffisant pour « {$article->titre} ».");
                    }

                    if ($diff !== 0) {
                        $article->decrement('stock', $diff);
                    }

                    $ligne->update([
                        'quantite'   => $newQty,
                        'total_ligne'=> round(($ligne->prix_unitaire - $ligne->remise) * $newQty, 2),
                    ]);
                }

                // Recalculate totals
                $commande->load('lignes');
                $sousTotal    = $commande->lignes->sum(fn ($l) => $l->prix_unitaire * $l->quantite);
                $totalRemise  = $commande->lignes->sum(fn ($l) => $l->remise * $l->quantite);
                $total        = round($sousTotal - $totalRemise + $commande->frais_livraison, 2);

                $commande->update([
                    'montant_sous_total' => $sousTotal,
                    'remise'             => $totalRemise,
                    'montant_total'      => $total,
                ]);
            }
        });

        return redirect()
            ->route('commandes.show', $commande)
            ->with('success', 'Commande mise à jour avec succès.');
    }

    // =========================================================
    // DESTROY — Cancel the order, restore stock
    // =========================================================
    public function destroy(Commande $commande): RedirectResponse
    {
        $this->authorize('delete', $commande);

        if ($commande->estExpediee()) {
            return back()->withErrors(['error' => 'Cette commande a déjà été expédiée et ne peut plus être annulée.']);
        }

        DB::transaction(function () use ($commande) {
            $commande->load('lignes.article');

            // Restore stock
            foreach ($commande->lignes as $ligne) {
                $ligne->article->increment('stock', $ligne->quantite);
                // Re-publish if it was marked VENDU
                if ($ligne->article->statut === Article::STATUT_VENDU) {
                    $ligne->article->update(['statut' => Article::STATUT_DISPONIBLE]);
                }
            }

            $commande->pushHistorique(Commande::STATUT_ANNULEE, 'Annulée par l\'utilisateur');
            $commande->update(['statut' => Commande::STATUT_ANNULEE]);
        });

        // In-app & email notification
        $commande->load('user');
        try {
            $commande->user->notify(new \App\Notifications\CommandeStatutNotification(
                $commande,
                Commande::STATUT_ANNULEE,
                'Votre commande a été annulée et les articles remis en vente.'
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Notification failure: ' . $e->getMessage());
        }

        return redirect()
            ->route('commandes.index')
            ->with('success', "La commande {$commande->numero} a été annulée et le stock restauré.");
    }

    // =========================================================
    // PDF INVOICE
    // =========================================================
    public function invoice(Commande $commande)
    {
        $this->authorize('view', $commande);

        $commande->load(['lignes.article.user', 'adresse', 'user']);

        $pdf = Pdf::loadView('commandes.invoice-pdf', ['commande' => $commande])
            ->setPaper('a4', 'portrait');

        return $pdf->download("facture-{$commande->numero}.pdf");
    }

    // =========================================================
    // QR CODE — Tracking URL (SVG output for universal support)
    // =========================================================
    public function qrCode(Commande $commande)
    {
        $this->authorize('view', $commande);

        $url = route('commandes.show', $commande);

        return response(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::size(200)->generate($url),
            200,
            ['Content-Type' => 'image/svg+xml']
        );
    }

    // =========================================================
    // ADMIN: Update status
    // =========================================================
    public function updateStatut(Request $request, Commande $commande): RedirectResponse
    {
        $this->authorize('updateStatut', $commande);

        $request->validate([
            'statut' => ['required', 'string', \Illuminate\Validation\Rule::in(Commande::STATUTS)],
        ]);

        $nouveauStatut = $request->input('statut');
        $commentaire   = $request->input('commentaire');

        $commande->update(['statut' => $nouveauStatut]);
        $commande->pushHistorique($nouveauStatut, $commentaire);

        // In-app & email notification
        $commande->load('user');
        try {
            $commande->user->notify(new \App\Notifications\CommandeStatutNotification(
                $commande,
                $nouveauStatut,
                $commentaire
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Notification failure: ' . $e->getMessage());
        }

        return back()->with('success', "Statut mis à jour : {$commande->statutLabel()}");
    }

    // =========================================================
    // HELPER — resolve cart items from request
    // =========================================================
    private function resolveCartItems(Request $request): array
    {
        $items = [];

        // Format 1: direct single article
        if ($request->filled('article_id')) {
            $article = Article::with('user')->find($request->input('article_id'));
            if ($article) {
                $items[] = ['article' => $article, 'quantite' => max(1, (int) $request->input('quantite', 1))];
            }
        }

        // Format 2: articles[]=id:qty
        if ($request->filled('articles')) {
            foreach ($request->input('articles') as $entry) {
                [$id, $qty] = array_pad(explode(':', $entry), 2, 1);
                $article = Article::with('user')->find($id);
                if ($article) {
                    $items[] = ['article' => $article, 'quantite' => max(1, (int) $qty)];
                }
            }
        }

        return $items;
    }

    /** Temporary holder for commande id across the transaction closure */
    private int $lastCommandeId = 0;
}
