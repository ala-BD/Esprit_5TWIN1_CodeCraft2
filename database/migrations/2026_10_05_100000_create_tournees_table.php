<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // collecteur
            $table->date('date');
            $table->string('zone');                      // ex: Tunis Centre
            $table->string('vehicule');                  // ex: Camionnette 123 TU 4567
            $table->float('distance_km')->nullable();
            $table->text('itineraire_ia')->nullable();   // rempli par l'optimisation d'itinéraire
            $table->enum('statut', [
                'PLANIFIEE',
                'EN_COURS',
                'TERMINEE',
                'ANNULEE',
            ])->default('PLANIFIEE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournees');
    }
};
