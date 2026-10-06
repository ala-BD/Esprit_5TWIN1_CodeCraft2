<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projet_upcyclings', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('description');            // vêtement avant
            $table->string('photo_resultat')->nullable()->after('photo');         // produit fini (après)
            $table->string('couleur')->nullable()->after('matiere');
            $table->json('analyse_ia')->nullable()->after('source_ia');            // défauts, remarques de l'IA
            $table->decimal('prix_estime_min', 8, 2)->nullable()->after('categorie_produit');
            $table->decimal('prix_estime_max', 8, 2)->nullable()->after('prix_estime_min');
            $table->float('co2_evite_kg')->nullable()->after('prix_estime_max');
            $table->unsignedInteger('eau_economisee_l')->nullable()->after('co2_evite_kg');
        });

        Schema::table('ateliers', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('portfolio_url');           // photo de couverture
        });
    }

    public function down(): void
    {
        Schema::table('projet_upcyclings', function (Blueprint $table) {
            $table->dropColumn([
                'photo', 'photo_resultat', 'couleur', 'analyse_ia',
                'prix_estime_min', 'prix_estime_max', 'co2_evite_kg', 'eau_economisee_l',
            ]);
        });

        Schema::table('ateliers', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
    }
};
