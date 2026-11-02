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
        Schema::create('parametres_cout', function (Blueprint $table) {
            $table->id();
            $table->string('matiere', 50)->unique()->comment('Matière textile ou "defaut"');
            $table->decimal('cout_recyclage_par_kg', 8, 2)->default(2.00)->comment('Coût de recyclage au kg');
            $table->decimal('cout_tri_fixe', 8, 2)->default(0.50)->comment('Coût fixe unitaire de tri');
            $table->decimal('cout_transport_par_kg', 8, 2)->default(0.30)->comment('Coût transport par kg');
            $table->decimal('majoration_mixte_pourcent', 5, 2)->default(25.00)->comment('Majoration si mélange complexe %');
            $table->decimal('tarif_horaire_defaut', 8, 2)->default(15.00)->comment('Tarif horaire de base de l atelier');
            $table->decimal('marge_cible_pourcent', 5, 2)->default(20.00)->comment('Marge cible pour alerte verte (%)');
            $table->decimal('marge_minimale_pourcent', 5, 2)->default(5.00)->comment('Marge minimale pour alerte orange (%)');
            $table->decimal('frais_plateforme_pourcent', 5, 2)->default(5.00)->comment('Commission plateforme RETISS (%)');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametres_cout');
    }
};

