<?php

use App\Http\Controllers\PointCollecteController;
use App\Http\Controllers\DonVetementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Recyclage\LotTextileController;
use App\Http\Controllers\Recyclage\EtapeTraitementController;
use App\Http\Controllers\Recyclage\PasseportNumeriqueController;
use App\Http\Controllers\Recyclage\StatistiqueController;
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
    | Module M1 — Collecte / Points de collecte
    |----------------------------------------------------------------------
    */
    Route::get('/collecte', [PointCollecteController::class, 'dashboard'])
        ->name('collecte.dashboard');

    Route::prefix('collecte')->name('collecte.')->group(function () {
        Route::resource('points', PointCollecteController::class);

        Route::get('dons', [DonVetementController::class, 'index'])->name('dons.index');
        Route::get('dons/create', [DonVetementController::class, 'create'])->name('dons.create');
        Route::post('dons', [DonVetementController::class, 'store'])->name('dons.store');
        Route::get('dons/{don}', [DonVetementController::class, 'show'])->name('dons.show');
        Route::patch('dons/{don}/statut', [DonVetementController::class, 'updateStatut'])->name('dons.statut');
    });

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
        Route::get('passeports', [PasseportNumeriqueController::class, 'index'])
            ->name('passeports.index');

        Route::get('lots/{lot}/passeport', [PasseportNumeriqueController::class, 'show'])
            ->name('passeport.show');

        Route::post('lots/{lot}/passeport', [PasseportNumeriqueController::class, 'generer'])
            ->name('passeport.generer');

        Route::get('lots/{lot}/passeport/pdf', [PasseportNumeriqueController::class, 'telechargerPdf'])
            ->name('passeport.pdf');

        // Statistiques
        Route::get('statistiques', [StatistiqueController::class, 'index'])
            ->name('statistiques');
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
