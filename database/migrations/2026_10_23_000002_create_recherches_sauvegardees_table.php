<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des recherches sauvegardées par les utilisateurs.
     * Les filtres sont stockés en JSON (q, categorie, taille, prix_min, prix_max, etc.)
     * pour pouvoir rejouer la même requête quand un nouvel article est créé.
     */
    public function up(): void
    {
        Schema::create('recherches_sauvegardees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nom')->comment('Nom donné par l\'utilisateur à cette recherche');
            $table->json('filtres')->comment('Filtres actifs au moment de la sauvegarde (q, categorie, taille, matiere, etat, couleur, prix_min, prix_max, en_stock, gratuits, vendeur_role, note_min, tri)');
            $table->timestamp('derniere_notification_at')->nullable()
                  ->comment('Dernière fois qu\'une notification a été envoyée pour cette recherche');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recherches_sauvegardees');
    }
};
