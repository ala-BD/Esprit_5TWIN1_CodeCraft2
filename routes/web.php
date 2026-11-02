<?php

use App\Http\Controllers\Admin\ParametreCoutController;
use App\Http\Controllers\Admin\StatistiqueController as AdminStatistiqueController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AdresseController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\Atelier\EstimationController;
use App\Http\Controllers\AvisController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonVetementController;
use App\Http\Controllers\FavoriController;
use App\Http\Controllers\Logistique\MissionController;
use App\Http\Controllers\Logistique\TourneeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\PanierController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RechercheSauvegardeeController;
use App\Http\Controllers\Recyclage\EtapeTraitementController;
use App\Http\Controllers\Recyclage\IAFiliereController;
use App\Http\Controllers\Recyclage\LotTextileController;
use App\Http\Controllers\Recyclage\PasseportNumeriqueController;
use App\Http\Controllers\Recyclage\StatistiqueController;
use App\Http\Controllers\Upcycling\AccesApiController;
use App\Http\Controllers\Upcycling\AtelierController;
use App\Http\Controllers\Upcycling\DevisController;
use App\Http\Controllers\Upcycling\MessageProjetController;
use App\Http\Controllers\Upcycling\ProjetUpcyclingController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureCollecteur;
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

Route::get('/dons/verifier/{qrCode}', [DonVetementController::class, 'verifier'])
    ->name('collecte.dons.verifier');

/*
|--------------------------------------------------------------------------
| Stripe Webhook — public, no auth, no CSRF (excluded in bootstrap/app.php)
|--------------------------------------------------------------------------
*/
Route::post('/stripe/webhook', [PaiementController::class, 'webhook'])
    ->name('stripe.webhook');

/*
|--------------------------------------------------------------------------
| Routes protégées (auth + e-mail vérifié requis)
| Les routes Breeze (verify-email, verification.send, logout) restent
| dans routes/auth.php, accessibles sans vérification.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard général
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Dashboards par rôle — chaque rôle a son propre dashboard
    |----------------------------------------------------------------------
    */
    // Espace marketplace : redirige vers le tableau de bord marketplace du rôle connecté
    Route::get('/dashboard/marketplace', [DashboardController::class, 'index'])->name('dashboard.marketplace');

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

    Route::prefix('collecte')->name('collecte.')->group(function () {
        Route::get('dons',              [DonVetementController::class, 'index'])->name('dons.index');
        Route::get('dons/create',       [DonVetementController::class, 'create'])->name('dons.create');
        Route::post('dons/detecter-doublon', [DonVetementController::class, 'detecterDoublon'])->middleware('throttle:10,1')->name('dons.detecter-doublon');
        Route::post('dons/analyser-photo', [DonVetementController::class, 'analyserPhoto'])->middleware('throttle:5,1')->name('dons.analyse-photo');
        Route::post('dons/ameliorer-photo', [DonVetementController::class, 'ameliorerPhoto'])->middleware('throttle:5,1')->name('dons.ameliorer-photo');
        Route::post('dons',             [DonVetementController::class, 'store'])->name('dons.store');
        Route::get('dons/{don}',        [DonVetementController::class, 'show'])->name('dons.show');
        Route::patch('dons/{don}/statut',[DonVetementController::class, 'updateStatut'])->name('dons.statut');
    });

    /*
    |----------------------------------------------------------------------
    | Module M2 — Marketplace (Yahya)
    |----------------------------------------------------------------------
    */

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

    // Écriture : ATELIER, DONATEUR, ADMIN uniquement
    Route::middleware('role:ATELIER,DONATEUR,ADMIN')->group(function () {
        Route::get('articles/create',           [ArticleController::class, 'create'])->name('articles.create');
        Route::post('articles',                 [ArticleController::class, 'store'])->name('articles.store');
        Route::get('articles/{article}/edit',   [ArticleController::class, 'edit'])->name('articles.edit');
        Route::put('articles/{article}',        [ArticleController::class, 'update'])->name('articles.update');
        Route::patch('articles/{article}',      [ArticleController::class, 'update']);
        Route::delete('articles/{article}',     [ArticleController::class, 'destroy'])->name('articles.destroy');
    });

    // Lecture : tout le monde peut consulter le catalogue
    Route::get('articles',            [ArticleController::class, 'index'])->name('articles.index');
    Route::get('articles/{article}',  [ArticleController::class, 'show'])->name('articles.show');

    /*
    |----------------------------------------------------------------------
    | COMMANDES — routes de gestion du statut (ATELIER + ADMIN)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:ATELIER,ADMIN')->group(function () {
        Route::patch('commandes/{commande}/statut', [CommandeController::class, 'updateStatut'])
            ->name('commandes.statut');

        // Consultation des dons textiles gratuits disponibles pour l'Atelier
        Route::get('atelier/dons', [EstimationController::class, 'donsDisponibles'])
            ->name('atelier.dons');

        // IA pour l'Atelier : Estimation des coûts, prix et rentabilité
        Route::prefix('atelier/estimations')->name('atelier.estimations.')->group(function () {
            Route::post('calculer',         [EstimationController::class, 'calculer'])
                ->middleware('throttle:30,1')
                ->name('calculer');
            Route::post('recalculer-marge', [EstimationController::class, 'recalculerMarge'])
                ->name('recalculer-marge');
            Route::get('historique',        [EstimationController::class, 'historique'])
                ->name('historique');
            Route::delete('{estimation}',   [EstimationController::class, 'destroy'])
                ->name('destroy');
        });
    });

    /*
    |----------------------------------------------------------------------
    | ADMINISTRATION — Barèmes des coûts (ADMIN uniquement)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:ADMIN')->prefix('admin')->name('admin.')->group(function () {
        Route::get('parametres-cout',                        [ParametreCoutController::class, 'index'])->name('parametres-cout.index');
        Route::put('parametres-cout/{parametreCout}',        [ParametreCoutController::class, 'update'])->name('parametres-cout.update');
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

    /*
    |----------------------------------------------------------------------
    | Module M3 — Upcycling (Walid)
    |----------------------------------------------------------------------
    */
    Route::prefix('upcycling')->name('upcycling.')->group(function () {

        Route::get('/', [ProjetUpcyclingController::class, 'dashboard'])->name('dashboard');

        // CRUD Ateliers
        Route::resource('ateliers', AtelierController::class);

        // Tableau de production (Kanban) de l'atelier — avant la ressource pour ne pas être pris pour {projet}
        Route::get('projets/kanban', [ProjetUpcyclingController::class, 'kanban'])->name('projets.kanban');

        // CRUD Projets
        Route::resource('projets', ProjetUpcyclingController::class)
            ->parameters(['projets' => 'projet']);

        // IA
        Route::post('analyse-photo', [ProjetUpcyclingController::class, 'analyserPhoto'])->middleware('throttle:ia')->name('analyse-photo');
        Route::post('projets/{projet}/idees', [ProjetUpcyclingController::class, 'regenererIdees'])->middleware('throttle:ia')->name('projets.idees');
        Route::patch('projets/{projet}/idee', [ProjetUpcyclingController::class, 'choisirIdee'])->name('projets.idee');

        // Matching atelier
        Route::get('projets/{projet}/matching', [ProjetUpcyclingController::class, 'matching'])->name('projets.matching');
        Route::patch('projets/{projet}/atelier', [ProjetUpcyclingController::class, 'choisirAtelier'])->name('projets.atelier');

        // Suivi étapes
        Route::patch('projets/{projet}/avancer', [ProjetUpcyclingController::class, 'avancer'])->name('projets.avancer');
        Route::patch('projets/{projet}/annuler', [ProjetUpcyclingController::class, 'annuler'])->name('projets.annuler');
        Route::patch('projets/{projet}/noter',   [ProjetUpcyclingController::class, 'noter'])->name('projets.noter');

        // Devis
        Route::post('projets/{projet}/devis',    [DevisController::class, 'store'])->name('devis.store');
        Route::patch('devis/{devis}/accepter',   [DevisController::class, 'accepter'])->name('devis.accepter');
        Route::patch('devis/{devis}/refuser',    [DevisController::class, 'refuser'])->name('devis.refuser');
        Route::get('devis/{devis}/pdf',          [DevisController::class, 'pdf'])->name('devis.pdf');

        // Messagerie client / atelier + assistant styliste IA
        Route::get('projets/{projet}/messages', [MessageProjetController::class, 'index'])->name('messages.index');
        Route::post('projets/{projet}/messages', [MessageProjetController::class, 'store'])->middleware('throttle:ia')->name('messages.store');

        // Accès à l'API REST : documentation et tokens personnels (Sanctum)
        Route::get('api', [AccesApiController::class, 'index'])->name('api.index');
        Route::post('api/tokens', [AccesApiController::class, 'store'])->name('api.tokens.store');
        Route::delete('api/tokens/{token}', [AccesApiController::class, 'destroy'])->name('api.tokens.destroy');

        // IA : contrôle qualité avant / après
        Route::post('projets/{projet}/controle-qualite', [ProjetUpcyclingController::class, 'controleQualite'])->name('projets.controle-qualite');

        // Revente du produit fini sur la marketplace (M2)
        Route::post('projets/{projet}/publier', [ProjetUpcyclingController::class, 'publier'])->name('projets.publier');
    });

    /*
    |----------------------------------------------------------------------
    | Notifications in-app (tous modules)
    |----------------------------------------------------------------------
    */
    Route::get('notifications/resume', [NotificationController::class, 'resume'])->name('notifications.resume');
    Route::get('notifications/{id}', [NotificationController::class, 'lire'])->name('notifications.lire');
    Route::post('notifications/tout-lire', [NotificationController::class, 'toutLire'])->name('notifications.tout-lire');

    /*
    |----------------------------------------------------------------------
    | Module M4 — Recyclage (Ala)
    |----------------------------------------------------------------------
    */
    Route::middleware('role:RECYCLEUR,ADMIN')->prefix('recyclage')->name('recyclage.')->group(function () {

        Route::get('/', [LotTextileController::class, 'dashboard'])->name('dashboard');

        // Export CSV/Excel/PDF — doit être AVANT la resource pour éviter conflit avec {lot}
        Route::get('lots/export',             [\App\Http\Controllers\Recyclage\ExportController::class, 'exportLots'])->name('lots.export');
        Route::get('statistiques/export',     [\App\Http\Controllers\Recyclage\ExportController::class, 'exportStatistiques'])->name('statistiques.export');
        Route::get('statistiques/export-pdf', [\App\Http\Controllers\Recyclage\ExportController::class, 'exportStatistiquesPdf'])->name('statistiques.export-pdf');

        // CRUD Lots textiles
        Route::resource('lots', LotTextileController::class);

        // Étapes de traitement
        Route::post('lots/{lot}/etapes',        [EtapeTraitementController::class, 'store'])->name('etapes.store');
        Route::patch('etapes/{etape}/terminer', [EtapeTraitementController::class, 'terminer'])->name('etapes.terminer');

        // Passeport numérique
        Route::get('passeports',               [PasseportNumeriqueController::class, 'index'])->name('passeports.index');
        Route::get('lots/{lot}/passeport',     [PasseportNumeriqueController::class, 'show'])->name('passeport.show');
        Route::post('lots/{lot}/passeport',    [PasseportNumeriqueController::class, 'generer'])->name('passeport.generer');
        Route::get('lots/{lot}/passeport/pdf', [PasseportNumeriqueController::class, 'telechargerPdf'])->name('passeport.pdf');

        // Lien Don → Lot
        Route::post('lots/{lot}/rattacher-don',   [LotTextileController::class, 'rattacherDon'])->name('lots.rattacher-don');
        Route::delete('lots/{lot}/detacher-don',  [LotTextileController::class, 'detacherDon'])->name('lots.detacher-don');
        Route::get('dons-disponibles',            [LotTextileController::class, 'donsDisponibles'])->name('dons-disponibles');

        // Statistiques
        Route::get('statistiques', [StatistiqueController::class, 'index'])->name('statistiques');

        // IA filière (modèle entraîné, explicable, auto-apprenant)
        Route::get('ia',                             [IAFiliereController::class, 'index'])->name('ia.index');
        Route::post('ia/entrainer',                  [IAFiliereController::class, 'entrainer'])->name('ia.entrainer');
        Route::post('lots/{lot}/filiere/valider',    [IAFiliereController::class, 'valider'])->name('lots.filiere.valider');
        Route::post('lots/{lot}/filiere/recalculer', [IAFiliereController::class, 'recalculer'])->name('lots.filiere.recalculer');
    });

    /*
    |----------------------------------------------------------------------
    | Module M5 — Administration
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->middleware(EnsureAdmin::class)->group(function () {

        // CRUD Utilisateurs
        Route::resource('users', AdminUserController::class);

        // Statistiques
        Route::get('statistiques', [AdminStatistiqueController::class, 'index'])
            ->name('statistiques');
    });

    /*
    |----------------------------------------------------------------------
    | Module M5 — Logistique
    |----------------------------------------------------------------------
    */
    Route::prefix('logistique')->name('logistique.')->middleware(EnsureCollecteur::class)->group(function () {

        // CRUD Tournées
        Route::resource('tournees', TourneeController::class);

        // CRUD Missions (nested sous une tournée)
        Route::resource('tournees.missions', MissionController::class)
            ->shallow()
            ->except(['index', 'show']);
    });

    /*
    |----------------------------------------------------------------------
    | Assistant vocal (administration et logistique)
    |----------------------------------------------------------------------
    */
    Route::prefix('assistant')->name('assistant.')->middleware('throttle:90,1')->group(function () {
        Route::post('transcrire', [AssistantController::class, 'transcrire'])->name('transcrire');
        Route::post('parler', [AssistantController::class, 'parler'])->name('parler');
        Route::post('interpreter', [AssistantController::class, 'interpreter'])->name('interpreter');
        Route::post('preparer', [AssistantController::class, 'preparer'])->name('preparer');
        Route::post('executer', [AssistantController::class, 'executer'])->name('executer');
    });

    /*
    |----------------------------------------------------------------------
    | Profil utilisateur
    |----------------------------------------------------------------------
    */
    Route::get('/profile',        [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',      [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/role', [ProfileController::class, 'updateRole'])->name('profile.role');
    Route::delete('/profile',     [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Routes Auth Breeze
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
