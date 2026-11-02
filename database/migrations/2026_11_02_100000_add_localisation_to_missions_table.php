<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| M5 Logistique — Localisation des missions
|--------------------------------------------------------------------------
| Coordonnées GPS de chaque arrêt (carte, optimisation d'itinéraire,
| affectation automatique) et horodatage de fin (suivi, statistiques).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('adresse');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            // D'où viennent les coordonnées : COMMANDE, DONATEUR, GEOCODAGE ou MANUELLE
            $table->string('source_localisation', 20)->nullable()->after('longitude');
            $table->timestamp('terminee_le')->nullable()->after('preuve_livraison');
        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'source_localisation', 'terminee_le']);
        });
    }
};
