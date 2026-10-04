<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('projet_upcycling_id')->constrained('projet_upcyclings')->onDelete('cascade');
            $table->decimal('montant', 10, 2);           // en DT
            $table->unsignedInteger('delai_jours');
            $table->text('message')->nullable();         // détail de la prestation
            $table->enum('statut', [
                'EN_ATTENTE',
                'ACCEPTE',
                'REFUSE',
            ])->default('EN_ATTENTE');
            $table->date('date_emission');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devis');
    }
};
