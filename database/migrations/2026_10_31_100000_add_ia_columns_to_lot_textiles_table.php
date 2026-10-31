<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lot_textiles', function (Blueprint $table) {
            $table->float('score_confiance_ia')->nullable()->after('filiere_recommandee_ia');
            $table->json('probabilites_ia')->nullable()->after('score_confiance_ia');
            $table->json('explication_ia')->nullable()->after('probabilites_ia');
            $table->string('source_ia', 20)->nullable()->after('explication_ia');        // MODELE | REGLES
            $table->unsignedInteger('version_modele_ia')->nullable()->after('source_ia');
            $table->enum('filiere_validee', ['REVENTE', 'UPCYCLING', 'RECYCLAGE_FIBRE', 'RECYCLAGE_ENERGIE'])
                  ->nullable()->after('version_modele_ia');                                 // décision finale du recycleur
            $table->timestamp('filiere_validee_le')->nullable()->after('filiere_validee');
        });
    }

    public function down(): void
    {
        Schema::table('lot_textiles', function (Blueprint $table) {
            $table->dropColumn([
                'score_confiance_ia', 'probabilites_ia', 'explication_ia', 'source_ia',
                'version_modele_ia', 'filiere_validee', 'filiere_validee_le',
            ]);
        });
    }
};
