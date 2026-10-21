<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lot_historiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_textile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('evenement');          // ex: CREATION, STATUT_CHANGE, ETAPE_AJOUTEE...
            $table->string('champ')->nullable();  // champ modifié
            $table->text('ancienne_valeur')->nullable();
            $table->text('nouvelle_valeur')->nullable();
            $table->text('description');          // message lisible
            $table->string('icone')->default('fa-history');
            $table->string('couleur')->default('gray');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lot_historiques');
    }
};
