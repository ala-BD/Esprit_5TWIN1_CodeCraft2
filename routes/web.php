<?php

use App\Http\Controllers\Admin\StatistiqueController as AdminStatistiqueController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\Logistique\MissionController;
use App\Http\Controllers\Logistique\TourneeController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureCollecteur;
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
    | Module M4 — Recyclage
    |----------------------------------------------------------------------
    */
    Route::prefix('recyclage')->name('recyclage.')->group(function () {

        // Dashboard recycleur
        Route::get('/', [LotTextileController::class, 'dashboard'])
            ->name('dashboard');

        // CRUD Lots textiles
        Route::resource('lots', LotTextileController::class);

        // Étapes de traitement (nested sous un lot)
        Route::post('lots/{lot}/etapes', [EtapeTraitementController::class, 'store'])
            ->name('etapes.store');

        Route::patch('etapes/{etape}/terminer', [EtapeTraitementController::class, 'terminer'])
            ->name('etapes.terminer');

        // Passeport numérique
        Route::get('lots/{lot}/passeport', [PasseportNumeriqueController::class, 'show'])
            ->name('passeport.show');

        Route::post('lots/{lot}/passeport', [PasseportNumeriqueController::class, 'generer'])
            ->name('passeport.generer');

        Route::get('lots/{lot}/passeport/pdf', [PasseportNumeriqueController::class, 'telechargerPdf'])
            ->name('passeport.pdf');
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
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Routes Auth Breeze
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
