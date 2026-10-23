<?php

use App\Http\Controllers\AdresseController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AvisController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoriController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\PanierController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RechercheSauvegardeeController;
use App\Http\Controllers\Recyclage\LotTextileController;
use App\Http\Controllers\Recyclage\EtapeTraitementController;
use App\Http\Controllers\Recyclage\PasseportNumeriqueController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes publiques
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| Route publique : vérification passeport par QR code
|--------------------------------------------------------------------------
*/
Route::get('/passeport/{qrCode}', [PasseportNumeriqueController::class, 'verifier'])
    ->name('passeport.verifier');

/*
|--------------------------------------------------------------------------
| Stripe Webhook — public, no auth, no CSRF (excluded in bootstrap/app.php)
|--------------------------------------------------------------------------
*/
Route::post('/stripe/webhook', [PaiementController::class, 'webhook'])
    ->name('stripe.webhook');

/*
|--------------------------------------------------------------------------
| Routes protégées (auth requis)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    /*
    |----------------------------------------------------------------------
    | Dashboards par rôle — chaque rôle a son propre dashboard
    |----------------------------------------------------------------------
    */
    // Route générique /dashboard → redirige vers le bon dashboard selon le rôle
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Dashboards dédiés par rôle
    Route::get('/dashboard/client',     [DashboardController::class, 'client'])
        ->middleware('role:CLIENT')
        ->name('dashboard.client');

    Route::get('/dashboard/atelier',    [DashboardController::class, 'atelier'])
        ->middleware('role:ATELIER')
        ->name('dashboard.atelier');

    Route::get('/dashboard/admin',      [DashboardController::class, 'admin'])
        ->middleware('role:ADMIN')
        ->name('dashboard.admin');

    Route::get('/dashboard/donateur',   [DashboardController::class, 'donateur'])
        ->middleware('role:DONATEUR')
        ->name('dashboard.donateur');

    Route::get('/dashboard/collecteur', [DashboardController::class, 'collecteur'])
        ->middleware('role:COLLECTEUR')
        ->name('dashboard.collecteur');

    Route::get('/dashboard/recycleur',  [DashboardController::class, 'recycleur'])
        ->middleware('role:RECYCLEUR')
        ->name('dashboard.recycleur');

    /*
    |----------------------------------------------------------------------
    | Profil utilisateur — tous les rôles
    |----------------------------------------------------------------------
    */
    Route::get('/profile',        [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',      [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/role', [ProfileController::class, 'updateRole'])->name('profile.role');
    Route::delete('/profile',     [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |----------------------------------------------------------------------
    | Adresses — tous les rôles authentifiés
    |----------------------------------------------------------------------
    */
    Route::get('adresses/reverse-geocode', [AdresseController::class, 'reverseGeocode'])->name('adresses.reverse-geocode');
    Route::resource('adresses', AdresseController::class)
        ->parameters(['adresses' => 'adresse'])
        ->except(['show']);
    Route::patch('adresses/{adresse}/defaut', [AdresseController::class, 'setDefault'])->name('adresses.defaut');

    /*
    |----------------------------------------------------------------------
    | Marketplace — Articles textiles
    | Lecture : tous les rôles authentifiés
    | Création/modification : ATELIER, DONATEUR, ADMIN
    |----------------------------------------------------------------------
    */
    Route::get('articles/suggest', [ArticleController::class, 'suggest'])->name('articles.suggest');

    // Lecture : tout le monde peut consulter le catalogue
    Route::get('articles',            [ArticleController::class, 'index'])->name('articles.index');
    Route::get('articles/{article}',  [ArticleController::class, 'show'])->name('articles.show');

    // Écriture : ATELIER, DONATEUR, ADMIN uniquement
    Route::middleware('role:ATELIER,DONATEUR,ADMIN')->group(function () {
        Route::get('articles/create',           [ArticleController::class, 'create'])->name('articles.create');
        Route::post('articles',                 [ArticleController::class, 'store'])->name('articles.store');
        Route::get('articles/{article}/edit',   [ArticleController::class, 'edit'])->name('articles.edit');
        Route::put('articles/{article}',        [ArticleController::class, 'update'])->name('articles.update');
        Route::patch('articles/{article}',      [ArticleController::class, 'update']);
        Route::delete('articles/{article}',     [ArticleController::class, 'destroy'])->name('articles.destroy');
    });

    /*
    |----------------------------------------------------------------------
    | Module Recyclage — RECYCLEUR et ADMIN
    |----------------------------------------------------------------------
    */
    Route::middleware('role:RECYCLEUR,ADMIN')->prefix('recyclage')->name('recyclage.')->group(function () {
        Route::get('/', [LotTextileController::class, 'dashboard'])->name('dashboard');
        Route::resource('lots', LotTextileController::class);
        Route::post('lots/{lot}/etapes',      [EtapeTraitementController::class, 'store'])->name('etapes.store');
        Route::patch('etapes/{etape}/terminer',[EtapeTraitementController::class, 'terminer'])->name('etapes.terminer');
        Route::get('lots/{lot}/passeport',    [PasseportNumeriqueController::class, 'show'])->name('passeport.show');
        Route::post('lots/{lot}/passeport',   [PasseportNumeriqueController::class, 'generer'])->name('passeport.generer');
        Route::get('lots/{lot}/passeport/pdf',[PasseportNumeriqueController::class, 'telechargerPdf'])->name('passeport.pdf');
    });

    /*
    |----------------------------------------------------------------------
    | COMMANDES — routes de gestion du statut (ATELIER + ADMIN)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:ATELIER,ADMIN')->group(function () {
        Route::patch('commandes/{commande}/statut', [CommandeController::class, 'updateStatut'])
            ->name('commandes.statut');
    });

    /*
    |----------------------------------------------------------------------
    | COMMANDES — routes acheteurs (CLIENT, DONATEUR, COLLECTEUR, RECYCLEUR, ADMIN)
    | L'ATELIER ne peut ni passer commande ni annuler.
    |----------------------------------------------------------------------
    */
    Route::middleware('role:ACHETEUR,ADMIN')->group(function () {
        Route::get('commandes/create',  [CommandeController::class, 'create'])->name('commandes.create');
        Route::get('commandes/new',     [CommandeController::class, 'create']);
        Route::post('commandes',        [CommandeController::class, 'store'])->name('commandes.store');

        // Confirmer réception (CLIENT seulement, mais Admin aussi)
        Route::patch('commandes/{commande}/livraison', [CommandeController::class, 'confirmerLivraison'])
            ->name('commandes.livraison');

        // Paiement Stripe
        Route::post('paiement/checkout',             [PaiementController::class, 'checkout'])->name('paiement.checkout');
        Route::get('paiement/succes/{commande}',      [PaiementController::class, 'succes'])->name('paiement.succes');
        Route::get('paiement/annule/{commande}',      [PaiementController::class, 'annule'])->name('paiement.annule');
        Route::post('paiement/{commande}/rembourser', [PaiementController::class, 'rembourser'])->name('paiement.rembourser');
        Route::post('paiement/{commande}/expedier',   [PaiementController::class, 'expedier'])->name('paiement.expedier');

        // Panier
        Route::get('panier',                   [PanierController::class, 'index'])->name('panier.index');
        Route::post('panier/ajouter/{article}', [PanierController::class, 'ajouter'])->name('panier.ajouter');
        Route::patch('panier/lignes/{ligne}',   [PanierController::class, 'modifier'])->name('panier.modifier');
        Route::delete('panier/lignes/{ligne}',  [PanierController::class, 'retirer'])->name('panier.retirer');
        Route::delete('panier/vider',           [PanierController::class, 'vider'])->name('panier.vider');

        // Favoris
        Route::get('favoris',                   [FavoriController::class, 'index'])->name('favoris.index');
        Route::post('favoris/toggle/{article}', [FavoriController::class, 'toggle'])->name('favoris.toggle');

        // Recherches sauvegardées
        Route::post('recherches-sauvegardees', [RechercheSauvegardeeController::class, 'store'])
            ->name('recherches-sauvegardees.store');
        Route::delete('recherches-sauvegardees/{rechercheSauvegardee}', [RechercheSauvegardeeController::class, 'destroy'])
            ->name('recherches-sauvegardees.destroy');

        // Avis après livraison
        Route::get('commandes/{commande}/articles/{article}/avis/create', [AvisController::class, 'create'])->name('avis.create');
        Route::post('commandes/{commande}/articles/{article}/avis',        [AvisController::class, 'store'])->name('avis.store');
        Route::get('avis/{avis}/edit',                                     [AvisController::class, 'edit'])->name('avis.edit');
        Route::match(['put', 'patch'], 'avis/{avis}',                      [AvisController::class, 'update'])->name('avis.update');
        Route::delete('avis/{avis}',                                       [AvisController::class, 'destroy'])->name('avis.destroy');
        Route::post('avis/{avis}/vote',                                    [AvisController::class, 'vote'])->name('avis.vote');
    });

    /*
    |----------------------------------------------------------------------
    | COMMANDES — consultation (tous les rôles authentifiés)
    | Index, show, invoice, qrcode, edit, update, destroy
    |----------------------------------------------------------------------
    */
    // custom routes before resource
    Route::get('commandes/{commande}/invoice', [CommandeController::class, 'invoice'])->name('commandes.invoice');
    Route::get('commandes/{commande}/qrcode',  [CommandeController::class, 'qrCode'])->name('commandes.qrcode');
    Route::resource('commandes', CommandeController::class)->except(['create', 'store']);

});

/*
|--------------------------------------------------------------------------
| Routes Auth Breeze
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
