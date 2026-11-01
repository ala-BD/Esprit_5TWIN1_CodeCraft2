<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fil de discussion d'un projet : client, atelier et assistant styliste IA
        Schema::create('message_projets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_upcycling_id')->constrained('projet_upcyclings')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');  // null = IA
            $table->enum('auteur', ['CLIENT', 'ATELIER', 'IA']);
            $table->text('contenu');
            $table->timestamps();
        });

        Schema::table('projet_upcyclings', function (Blueprint $table) {
            $table->json('controle_qualite_ia')->nullable()->after('analyse_ia');  // comparaison avant / après
            $table->foreignId('article_id')->nullable()->after('don_vetement_id')
                ->constrained('articles')->onDelete('set null');                    // revente sur la marketplace (M2)
        });

        Schema::table('ateliers', function (Blueprint $table) {
            $table->json('embedding_ia')->nullable()->after('photo');              // vecteur Gemini (matching sémantique)
            $table->string('embedding_empreinte', 32)->nullable()->after('embedding_ia');  // md5 du texte vectorisé
        });
    }

    public function down(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            $table->dropColumn(['embedding_ia', 'embedding_empreinte']);
        });

        Schema::table('projet_upcyclings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('article_id');
            $table->dropColumn('controle_qualite_ia');
        });

        Schema::dropIfExists('message_projets');
    }
};
