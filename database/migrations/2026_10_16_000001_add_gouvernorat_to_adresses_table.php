<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adresses', function (Blueprint $table) {
            $table->string('gouvernorat')->nullable()->after('ville');
        });
    }

    public function down(): void
    {
        Schema::table('adresses', function (Blueprint $table) {
            $table->dropColumn('gouvernorat');
        });
    }
};
