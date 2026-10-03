<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lot_textiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recycleur_id')->constrained('recycleurs')->onDelete('cascade');
            $table->foreignId('don_vetement_id')->nullable()->constrained('don_vetements')->onDelete('set null');
            $table->string('reference')->unique();       // ex: LOT-2026-001
            $table->float('poids_kg');
            $table->string('composition');              // ex: 60% coton, 40% polyester
            $table->string('origine');                  // ex: collecte Tunis centre
            $table->enum('filiere_recommandee_ia', [
                'REVENTE',
                'UPCYCLING',
                'RECYCLAGE_FIBRE',
                'RECYCLAGE_ENERGIE',
            ])->default('RECYCLAGE_FIBRE');
            $table->enum('statut', [
                'EN_ATTENTE',
                'EN_TRAITEMENT',
                'TRAITE',
                'CERTIFIE',
            ])->default('EN_ATTENTE');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lot_textiles');
    }
};
