<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projet_upcyclings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('atelier_id')->nullable()->constrained('ateliers')->onDelete('set null');
            $table->foreignId('don_vetement_id')->nullable()->unique()->constrained('don_vetements')->onDelete('set null');

            // Vêtement à transformer
            $table->string('type_vetement');            // ex: jean, chemise, pull
            $table->string('matiere');                  // ex: denim, coton, laine
            $table->string('etat');                     // BON, USE, ABIME
            $table->text('description');
            $table->decimal('budget_max', 8, 2)->nullable();

            // Résultat attendu
            $table->string('produit_final')->nullable();       // idée retenue
            $table->string('categorie_produit')->nullable();   // même liste que ateliers.specialite
            $table->json('idee_generee_ia')->nullable();       // idées proposées par l'IA
            $table->string('source_ia')->nullable();           // CLAUDE ou LOCAL

            $table->enum('statut', [
                'DEMANDE',
                'ATELIER_CHOISI',
                'DEVIS_ACCEPTE',
                'CONCEPTION',
                'CONFECTION',
                'FINITION',
                'TERMINE',
                'ANNULE',
            ])->default('DEMANDE');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();

            // Avis du client une fois terminé
            $table->unsignedTinyInteger('note_client')->nullable();
            $table->string('commentaire_client', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projet_upcyclings');
    }
};
