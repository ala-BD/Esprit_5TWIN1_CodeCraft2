<?php

use App\Http\Controllers\AdresseController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ProfileController;
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
    | Profil utilisateur
    |----------------------------------------------------------------------
    */
    Route::get('/profile',          [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',        [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/role',   [ProfileController::class, 'updateRole'])->name('profile.role');
    Route::delete('/profile',       [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |----------------------------------------------------------------------
    | Adresses utilisateur (TextileCycle)
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
    |----------------------------------------------------------------------
    */
    Route::resource('articles', ArticleController::class);
});

/*
|--------------------------------------------------------------------------
| Routes Auth Breeze
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
