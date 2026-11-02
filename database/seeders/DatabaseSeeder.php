<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            RecyclageSeeder::class,
            UpcyclingSeeder::class,
            MarketplaceSeeder::class,
            LogistiqueSeeder::class,
            TestAccountsSeeder::class,
            ParametreCoutSeeder::class,
            DonGratuitSeeder::class,
        ]);
    }
}
