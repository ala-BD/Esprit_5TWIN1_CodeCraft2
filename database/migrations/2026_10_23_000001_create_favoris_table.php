<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des articles mis en favoris (wishlist) par les utilisateurs.
     * - prix_au_moment_ajout : prix mémorisé à l'instant du favori pour détecter les baisses de prix
     * - unique(user_id, article_id) : un utilisateur ne peut mettre qu'un seul favori par article
     */
    public function up(): void
    {
        Schema::create('favoris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('article_id')->constrained('articles')->onDelete('cascade');
            $table->decimal('prix_au_moment_ajout', 10, 2)->nullable()
                  ->comment('Prix de l\'article au moment où il a été mis en favori');
            $table->timestamps();

            $table->unique(['user_id', 'article_id'], 'favoris_user_article_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favoris');
    }
};
