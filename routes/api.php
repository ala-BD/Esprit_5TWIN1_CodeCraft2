<?php

use App\Http\Controllers\Api\V1\AtelierController;
use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\DevisController;
use App\Http\Controllers\Api\V1\MessageProjetController;
use App\Http\Controllers\Api\V1\ProjetUpcyclingController;
use App\Http\Controllers\MetricsController;
use Illuminate\Support\Facades\Route;

/*
| Supervision : indicateurs pour Prometheus (jeton METRICS_TOKEN), hors session et hors CSRF
*/
Route::get('metrics', MetricsController::class)->name('metrics');

/*
|--------------------------------------------------------------------------
| API REST v1 — préfixe /api/v1
|--------------------------------------------------------------------------
| Authentification : token Sanctum (header "Authorization: Bearer <token>").
| Permissions du token (abilities) :
|   upcycling:lire   → lecture
|   upcycling:ecrire → création / modification
|   upcycling:ia     → appels à l'IA (limités à 10 / minute)
| Limites : 60 requêtes / minute, 5 tentatives de connexion / minute.
| Documentation : /upcycling/api (après connexion sur le site).
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Connexion : échange e-mail + mot de passe contre un token
    Route::post('auth/token', [AuthTokenController::class, 'store'])
        ->middleware('throttle:api-token')
        ->name('token.store');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

        Route::get('auth/moi', [AuthTokenController::class, 'moi'])->name('moi');
        Route::delete('auth/token', [AuthTokenController::class, 'destroy'])->name('token.destroy');

        /*
        | Lecture
        */
        Route::middleware('abilities:upcycling:lire')->group(function () {
            Route::get('ateliers', [AtelierController::class, 'index'])->name('ateliers.index');
            Route::get('ateliers/{atelier}', [AtelierController::class, 'show'])->name('ateliers.show');

            Route::get('projets', [ProjetUpcyclingController::class, 'index'])->name('projets.index');
            Route::get('projets/{projet}', [ProjetUpcyclingController::class, 'show'])->name('projets.show');
            Route::get('projets/{projet}/matching', [ProjetUpcyclingController::class, 'matching'])->name('projets.matching');
            Route::get('projets/{projet}/devis', [DevisController::class, 'index'])->name('projets.devis.index');
            Route::get('projets/{projet}/messages', [MessageProjetController::class, 'index'])->name('projets.messages.index');
        });

        /*
        | Écriture
        */
        Route::middleware('abilities:upcycling:ecrire')->group(function () {
            Route::post('projets', [ProjetUpcyclingController::class, 'store'])->name('projets.store');
            Route::patch('projets/{projet}', [ProjetUpcyclingController::class, 'update'])->name('projets.update');
            Route::delete('projets/{projet}', [ProjetUpcyclingController::class, 'destroy'])->name('projets.destroy');

            Route::patch('projets/{projet}/idee', [ProjetUpcyclingController::class, 'choisirIdee'])->name('projets.idee');
            Route::patch('projets/{projet}/atelier', [ProjetUpcyclingController::class, 'choisirAtelier'])->name('projets.atelier');
            Route::post('projets/{projet}/avancer', [ProjetUpcyclingController::class, 'avancer'])->name('projets.avancer');
            Route::post('projets/{projet}/annuler', [ProjetUpcyclingController::class, 'annuler'])->name('projets.annuler');
            Route::post('projets/{projet}/noter', [ProjetUpcyclingController::class, 'noter'])->name('projets.noter');

            Route::post('projets/{projet}/devis', [DevisController::class, 'store'])->name('projets.devis.store');
            Route::post('devis/{devis}/acceptation', [DevisController::class, 'accepter'])->name('devis.accepter');
            Route::post('devis/{devis}/refus', [DevisController::class, 'refuser'])->name('devis.refuser');

            Route::post('projets/{projet}/messages', [MessageProjetController::class, 'store'])->name('projets.messages.store');
        });

        /*
        | Intelligence artificielle (permission dédiée + limite stricte)
        */
        Route::middleware(['abilities:upcycling:ia', 'throttle:ia'])->group(function () {
            Route::post('analyse-photo', [ProjetUpcyclingController::class, 'analyserPhoto'])->name('analyse-photo');
            Route::post('projets/{projet}/idees', [ProjetUpcyclingController::class, 'regenererIdees'])->name('projets.idees');
            Route::post('projets/{projet}/styliste', [MessageProjetController::class, 'styliste'])->name('projets.styliste');
        });
    });
});
