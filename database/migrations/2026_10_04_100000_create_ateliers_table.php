<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ateliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('nom');
            $table->enum('specialite', [
                'VETEMENT',
                'SAC',
                'ACCESSOIRE',
                'DECORATION',
                'PATCHWORK',
            ]);
            $table->text('description')->nullable();
            $table->string('portfolio_url')->nullable();   // lien Instagram / Behance / site
            $table->decimal('tarif_horaire', 8, 2);        // en DT
            $table->string('localisation');
            $table->float('note_moyenne')->default(0);     // calculée depuis les notes clients
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ateliers');
    }
};
