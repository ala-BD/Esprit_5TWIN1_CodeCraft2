<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('don_vetements')
            ->whereNull('qr_code')
            ->orderBy('id')
            ->select('id')
            ->chunkById(100, function ($dons): void {
                foreach ($dons as $don) {
                    DB::table('don_vetements')
                        ->where('id', $don->id)
                        ->whereNull('qr_code')
                        ->update(['qr_code' => (string) Str::uuid()]);
                }
            });
    }

    public function down(): void
    {
        // Existing QR references are retained on rollback.
    }
};
