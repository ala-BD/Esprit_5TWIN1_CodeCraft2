<?php

use App\Http\Controllers\PointCollecteController;
use App\Http\Controllers\DonVetementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Recyclage\LotTextileController;
use App\Http\Controllers\Recyclage\EtapeTraitementController;
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
    | Module M3 — Upcycling
    |----------------------------------------------------------------------
    */
    Route::prefix('upcycling')->name('upcycling.')->group(function () {

        // Dashboard (client, atelier ou admin)
        Route::get('/', [ProjetUpcyclingController::class, 'dashboard'])
            ->name('dashboard');

        // CRUD Ateliers (catalogue + portfolio)
        Route::resource('ateliers', AtelierController::class);

        // CRUD Projets d'upcycling
        Route::resource('projets', ProjetUpcyclingController::class)
            ->parameters(['projets' => 'projet']);

        // IA : analyse de photo (AJAX) et idées d'upcycling
        Route::post('analyse-photo', [ProjetUpcyclingController::class, 'analyserPhoto'])
            ->name('analyse-photo');
        Route::post('projets/{projet}/idees', [ProjetUpcyclingController::class, 'regenererIdees'])
            ->name('projets.idees');
        Route::patch('projets/{projet}/idee', [ProjetUpcyclingController::class, 'choisirIdee'])
            ->name('projets.idee');

        // Matching atelier
        Route::get('projets/{projet}/matching', [ProjetUpcyclingController::class, 'matching'])
            ->name('projets.matching');
        Route::patch('projets/{projet}/atelier', [ProjetUpcyclingController::class, 'choisirAtelier'])
            ->name('projets.atelier');

        // Suivi par étapes
        Route::patch('projets/{projet}/avancer', [ProjetUpcyclingController::class, 'avancer'])
            ->name('projets.avancer');
        Route::patch('projets/{projet}/annuler', [ProjetUpcyclingController::class, 'annuler'])
            ->name('projets.annuler');
        Route::patch('projets/{projet}/noter', [ProjetUpcyclingController::class, 'noter'])
            ->name('projets.noter');

        // Devis
        Route::post('projets/{projet}/devis', [DevisController::class, 'store'])
            ->name('devis.store');
        Route::patch('devis/{devis}/accepter', [DevisController::class, 'accepter'])
            ->name('devis.accepter');
        Route::patch('devis/{devis}/refuser', [DevisController::class, 'refuser'])
            ->name('devis.refuser');
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
