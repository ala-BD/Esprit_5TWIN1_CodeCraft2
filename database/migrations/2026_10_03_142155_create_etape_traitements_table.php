<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etape_traitements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_textile_id')->constrained('lot_textiles')->onDelete('cascade');
            $table->enum('type', [
                'RECEPTION',
                'TRI',
                'NETTOYAGE',
                'TRAITEMENT',
                'CONTROLE_QUALITE',
                'EXPEDITION',
            ]);
            $table->dateTime('date_debut');
            $table->dateTime('date_fin')->nullable();
            $table->string('resultat')->nullable();     // observations / résultats
            $table->float('poids_sortant_kg')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etape_traitements');
    }
};
