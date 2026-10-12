<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les colonnes de filtrage et les index de performance sur la table articles.
     * Les valeurs (taille, matiere, etat) sont alignées sur DonVetement pour permettre
     * la conversion directe don → article.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Colonnes de filtrage (nullable car les anciens articles n'ont pas ces données)
            $table->string('taille')->nullable()->after('categorie');
            $table->string('matiere')->nullable()->after('taille');
            $table->string('etat')->nullable()->after('matiere');
            $table->string('couleur')->nullable()->after('etat');
            $table->decimal('co2_economise_kg', 8, 3)->nullable()->after('couleur')
                  ->comment('CO₂ économisé estimé en kg par rapport au neuf');

            // Index sur les colonnes les plus filtrées (prix, categorie, statut, taille déjà)
            $table->index('taille',    'articles_taille_index');
            $table->index('matiere',   'articles_matiere_index');
            $table->index('etat',      'articles_etat_index');
            $table->index('couleur',   'articles_couleur_index');
        });
    }

    /**
     * Supprime les colonnes et index ajoutés.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex('articles_taille_index');
            $table->dropIndex('articles_matiere_index');
            $table->dropIndex('articles_etat_index');
            $table->dropIndex('articles_couleur_index');

            $table->dropColumn(['taille', 'matiere', 'etat', 'couleur', 'co2_economise_kg']);
        });
    }
};
