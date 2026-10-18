<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('avis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            
            $table->unsignedTinyInteger('note_livraison');
            $table->unsignedTinyInteger('note_article');
            $table->unsignedTinyInteger('note_clarte');
            $table->unsignedTinyInteger('note_service');
            $table->decimal('note_globale', 3, 2);
            $table->string('commentaire', 500)->nullable();
            
            $table->timestamps();

            // Règle métier : Un seul avis par article et par commande pour un utilisateur
            $table->unique(['commande_id', 'article_id', 'user_id'], 'unique_avis_commande_article_user');
            
            $table->index('article_id');
            $table->index('user_id');
            $table->index('commande_id');
            $table->index('note_globale');
        });

        Schema::create('avis_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avis_id')->constrained('avis')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['like', 'dislike']);
            $table->timestamps();

            // Un seul vote par utilisateur et par avis
            $table->unique(['avis_id', 'user_id'], 'unique_avis_vote_user');
            
            $table->index('avis_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avis_votes');
        Schema::dropIfExists('avis');
    }
};
