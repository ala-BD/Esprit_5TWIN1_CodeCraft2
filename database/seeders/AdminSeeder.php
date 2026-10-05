<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // -------------------------------------------------------
        // Utilisateur ADMIN de test
        // -------------------------------------------------------
        User::firstOrCreate(
            ['email' => 'admin@retiss.tn'],
            [
                'name'              => 'RETISS',
                'prenom'            => 'Admin',
                'telephone'         => '21655000000',
                'role'              => 'ADMIN',
                'actif'             => true,
                'password'          => Hash::make('Password123!'),
            ]
        );
    }
}
