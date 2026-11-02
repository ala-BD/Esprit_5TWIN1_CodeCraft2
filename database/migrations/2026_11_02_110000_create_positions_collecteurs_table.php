<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| M5 Logistique — Suivi en temps réel
|--------------------------------------------------------------------------
| Positions GPS envoyées par le téléphone du collecteur pendant sa tournée,
| et photo prise comme preuve de passage.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions_collecteurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tournee_id')->nullable()->constrained('tournees')->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('precision_m')->nullable();   // rayon d'incertitude annoncé par le téléphone
            $table->timestamp('enregistree_le');
            $table->index(['user_id', 'enregistree_le']);
        });

        Schema::table('missions', function (Blueprint $table) {
            $table->string('photo_preuve')->nullable()->after('preuve_livraison');
        });
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropColumn('photo_preuve');
        });

        Schema::dropIfExists('positions_collecteurs');
    }
};
