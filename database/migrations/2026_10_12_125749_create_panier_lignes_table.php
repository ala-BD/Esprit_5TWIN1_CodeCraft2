<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cart lines: each line links a Panier to an Article with a quantity.
     * The unique constraint on (panier_id, article_id) ensures the same article
     * never appears twice — adding it again just increments the quantity.
     */
    public function up(): void
    {
        Schema::create('panier_lignes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('panier_id')
                  ->constrained('paniers')
                  ->cascadeOnDelete();

            $table->foreignId('article_id')
                  ->constrained('articles')
                  ->cascadeOnDelete();

            // Minimum 1, must be a whole number
            $table->unsignedInteger('quantite')->default(1);

            // Unique: one line per article per cart
            $table->unique(['panier_id', 'article_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panier_lignes');
    }
};
