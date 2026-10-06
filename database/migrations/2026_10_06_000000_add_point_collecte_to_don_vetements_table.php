<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('don_vetements', function (Blueprint $table) {
            $table->foreignId('point_collecte_id')
                ->nullable()
                ->after('user_id')
                ->constrained('point_collectes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('don_vetements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('point_collecte_id');
        });
    }
};