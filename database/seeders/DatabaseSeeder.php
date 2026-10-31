<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RecyclageSeeder::class,
            MarketplaceSeeder::class,
            TestAccountsSeeder::class,
            ParametreCoutSeeder::class,
            DonGratuitSeeder::class,
        ]);
    }
}
