<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            PointCollecteSeeder::class,
            RecyclageSeeder::class,
            UpcyclingSeeder::class,
            LogistiqueSeeder::class,
        ]);
    }
}
