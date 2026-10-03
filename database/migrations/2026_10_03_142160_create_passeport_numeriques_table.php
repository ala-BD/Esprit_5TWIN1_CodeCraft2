<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passeport_numeriques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_textile_id')->unique()->constrained('lot_textiles')->onDelete('cascade');
            $table->string('qr_code')->unique();          // code QR unique
            $table->string('hash_integrite')->unique();   // SHA-256 pour vérifier l'intégrité
            $table->float('co2_evite_kg');                // kg CO₂ économisé
            $table->float('eau_economisee_l');            // litres d'eau économisés
            $table->dateTime('date_emission');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passeport_numeriques');
    }
};
