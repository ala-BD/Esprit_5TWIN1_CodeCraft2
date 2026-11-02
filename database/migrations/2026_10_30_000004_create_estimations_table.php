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
        Schema::create('estimations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->nullable()->constrained('articles')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('cout_recyclage', 10, 2)->default(0.00);
            $table->decimal('cout_upcycling', 10, 2)->default(0.00);
            $table->decimal('prix_min', 10, 2)->default(0.00);
            $table->decimal('prix_conseille', 10, 2)->default(0.00);
            $table->decimal('prix_max', 10, 2)->default(0.00);
            $table->decimal('marge_nette', 10, 2)->default(0.00);
            $table->decimal('marge_pourcent', 6, 2)->default(0.00);
            $table->string('niveau_alerte', 20)->default('vert')->comment('vert, orange, rouge');
            $table->string('confiance', 20)->default('moyen')->comment('faible, moyen, eleve');
            $table->text('explication')->nullable();
            $table->string('source', 20)->default('regles')->comment('ia ou regles');
            $table->json('details_calcul')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['article_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimations');
    }
};

