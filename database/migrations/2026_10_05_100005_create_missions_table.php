<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournee_id')->constrained('tournees')->onDelete('cascade');
            $table->foreignId('don_vetement_id')->nullable()->constrained('don_vetements')->onDelete('set null');
            // Commande (M2) : la table n'existe pas encore sur cette branche, pas de contrainte
            $table->unsignedBigInteger('commande_id')->nullable()->index();
            $table->enum('type', [
                'COLLECTE',
                'LIVRAISON',
            ]);
            $table->string('adresse');
            $table->unsignedInteger('ordre')->default(1);   // position dans la tournée
            $table->time('heure_prevue');
            $table->enum('statut', [
                'A_FAIRE',
                'EN_COURS',
                'TERMINEE',
                'ECHOUEE',
            ])->default('A_FAIRE');
            $table->string('preuve_livraison')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};
