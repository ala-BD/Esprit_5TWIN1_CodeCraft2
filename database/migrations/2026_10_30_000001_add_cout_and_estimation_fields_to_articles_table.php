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
        Schema::table('articles', function (Blueprint $table) {
            $table->decimal('poids_kg', 8, 3)->nullable()->after('couleur')
                ->comment('Poids en kg pour calcul de recyclage et transport');
            $table->decimal('cout_achat', 10, 2)->default(0.00)->after('poids_kg')
                ->comment('Coût d acquisition ou d achat (0 pour don)');
            $table->decimal('heures_travail', 8, 2)->default(0.00)->after('cout_achat')
                ->comment('Heures estimées de travail/upcycling');
            $table->decimal('cout_materiaux', 10, 2)->default(0.00)->after('heures_travail')
                ->comment('Coût des matériaux de transformation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'poids_kg',
                'cout_achat',
                'heures_travail',
                'cout_materiaux',
            ]);
        });
    }
};

