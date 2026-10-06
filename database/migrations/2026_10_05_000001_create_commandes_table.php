<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('adresse_id')->nullable()->constrained('adresses')->nullOnDelete();
            $table->string('adresse_snapshot')->nullable(); // Snapshot in case address is deleted
            $table->string('numero')->unique();             // TC-2026-000001
            $table->string('statut')->default('EN_ATTENTE');
            // EN_ATTENTE | CONFIRMEE | EN_PREPARATION | EXPEDIEE | LIVREE | ANNULEE
            $table->decimal('montant_sous_total', 10, 2)->default(0);
            $table->decimal('remise', 10, 2)->default(0);
            $table->decimal('frais_livraison', 10, 2)->default(0);
            $table->decimal('montant_total', 10, 2)->default(0);
            $table->string('mode_paiement')->default('CARTE');
            // CARTE | VIREMENT | A_LA_LIVRAISON
            $table->date('date_livraison_estimee')->nullable();
            $table->timestamp('date_commande')->useCurrent();
            $table->json('historique_statuts')->nullable(); // Status timeline
            $table->timestamps();
        });

        Schema::create('ligne_commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('articles')->restrictOnDelete();
            $table->unsignedInteger('quantite');
            $table->decimal('prix_unitaire', 10, 2);  // Price locked at purchase time
            $table->decimal('remise', 10, 2)->default(0); // 10% discount per line
            $table->decimal('total_ligne', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_commandes');
        Schema::dropIfExists('commandes');
    }
};
