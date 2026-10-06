<?php

use App\Http\Controllers\Admin\StatistiqueController as AdminStatistiqueController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AdresseController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\CommandeController;
use App\Http\Controllers\DonVetementController;
use App\Http\Controllers\Logistique\MissionController;
use App\Http\Controllers\Logistique\TourneeController;
use App\Http\Controllers\PointCollecteController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureCollecteur;
use App\Http\Controllers\Recyclage\EtapeTraitementController;
use App\Http\Controllers\Recyclage\LotTextileController;
use App\Http\Controllers\Recyclage\PasseportNumeriqueController;
use App\Http\Controllers\Recyclage\StatistiqueController;
use App\Http\Controllers\Upcycling\AtelierController;
use App\Http\Controllers\Upcycling\DevisController;
use App\Http\Controllers\Upcycling\ProjetUpcyclingController;
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
| Routes protégées (auth requis)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    // Dashboard général
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    /*
    |----------------------------------------------------------------------
    | Module M1 — Collecte / Points de collecte (Wassim)
    |----------------------------------------------------------------------
    */
    Route::get('/collecte', [PointCollecteController::class, 'dashboard'])
        ->name('collecte.dashboard');

    Route::prefix('collecte')->name('collecte.')->group(function () {
        Route::resource('points', PointCollecteController::class);

        Route::get('dons',              [DonVetementController::class, 'index'])->name('dons.index');
        Route::get('dons/create',       [DonVetementController::class, 'create'])->name('dons.create');
        Route::post('dons',             [DonVetementController::class, 'store'])->name('dons.store');
        Route::get('dons/{don}',        [DonVetementController::class, 'show'])->name('dons.show');
        Route::patch('dons/{don}/statut',[DonVetementController::class, 'updateStatut'])->name('dons.statut');
    });

    /*
    |----------------------------------------------------------------------
    | Module M2 — Marketplace Articles & Commandes (Yahya)
    |----------------------------------------------------------------------
    */

    // Adresses utilisateur
    Route::get('adresses/reverse-geocode', [AdresseController::class, 'reverseGeocode'])
        ->name('adresses.reverse-geocode');
    Route::resource('adresses', AdresseController::class)
        ->parameters(['adresses' => 'adresse'])
        ->except(['show']);
    Route::patch('adresses/{adresse}/defaut', [AdresseController::class, 'setDefault'])
        ->name('adresses.defaut');

    // Marketplace — Articles textiles
    Route::resource('articles', ArticleController::class);

    // Commandes — routes custom avant resource pour éviter le shadowing
    Route::get('commandes/create',              [CommandeController::class, 'create'])->name('commandes.create');
    Route::get('commandes/new',                 [CommandeController::class, 'create']);
    Route::post('commandes',                    [CommandeController::class, 'store'])->name('commandes.store');
    Route::get('commandes/{commande}/invoice',  [CommandeController::class, 'invoice'])->name('commandes.invoice');
    Route::get('commandes/{commande}/qrcode',   [CommandeController::class, 'qrCode'])->name('commandes.qrcode');
    Route::patch('commandes/{commande}/statut', [CommandeController::class, 'updateStatut'])->name('commandes.statut');
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

        // CRUD Projets
        Route::resource('projets', ProjetUpcyclingController::class)
            ->parameters(['projets' => 'projet']);

        // IA
        Route::post('analyse-photo', [ProjetUpcyclingController::class, 'analyserPhoto'])->name('analyse-photo');
        Route::post('projets/{projet}/idees', [ProjetUpcyclingController::class, 'regenererIdees'])->name('projets.idees');
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
    });

    /*
    |----------------------------------------------------------------------
    | Module M4 — Recyclage (Ala)
    |----------------------------------------------------------------------
    */
    Route::prefix('recyclage')->name('recyclage.')->group(function () {

        Route::get('/', [LotTextileController::class, 'dashboard'])->name('dashboard');

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

        // Statistiques
        Route::get('statistiques', [StatistiqueController::class, 'index'])->name('statistiques');
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
