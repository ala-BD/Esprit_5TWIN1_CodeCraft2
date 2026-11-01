<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('don_vetements', function (Blueprint $table) {
            $table->string('photo_originale_url')->nullable()->after('photo_url');
            $table->string('photo_amelioree_url')->nullable()->after('photo_originale_url');
        });
    }

    public function down(): void
    {
        Schema::table('don_vetements', function (Blueprint $table) {
            $table->dropColumn(['photo_originale_url', 'photo_amelioree_url']);
        });
    }
};
