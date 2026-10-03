<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('don_vetements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type');
            $table->string('matiere');
            $table->string('taille');
            $table->string('etat');
            $table->string('photo_url')->nullable();
            $table->string('qr_code')->nullable()->unique();
            $table->enum('statut', [
                'DEPOSE',
                'EN_TRI',
                'VENDU',
                'UPCYCLING',
                'RECYCLE',
            ])->default('DEPOSE');
            $table->date('date_depot');
            $table->string('categorie_ia')->nullable();
            $table->float('score_confiance_ia')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('don_vetements');
    }
};
