<?php

namespace Database\Seeders;

use App\Models\PointCollecte;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PointCollecteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $collector = User::firstOrCreate(
            ['email' => 'collecteur@retiss.tn'],
            [
                'name' => 'Ben Ali',
                'prenom' => 'Nabil',
                'telephone' => '21652000001',
                'role' => User::ROLE_COLLECTEUR,
                'actif' => true,
                'password' => Hash::make('Password123!'),
            ]
        );

        $points = [
            [
                'nom' => 'Point de collecte Ariana Centre',
                'adresse' => 'Avenue Habib Bourguiba, Ariana',
                'ville' => 'Ariana',
                'latitude' => 36.8625,
                'longitude' => 10.1956,
                'capacite_max_kg' => 450,
                'telephone' => '21652000010',
                'statut' => 'ACTIF',
                'description' => 'Point de collecte principal pour textiles usagés.',
            ],
            [
                'nom' => 'Collecte Sfax Sud',
                'adresse' => 'Rue de la République, Sfax',
                'ville' => 'Sfax',
                'latitude' => 34.7406,
                'longitude' => 10.7603,
                'capacite_max_kg' => 620,
                'telephone' => '21652000011',
                'statut' => 'ACTIF',
                'description' => 'Collecte hebdomadaire pour vêtements et tissus.',
            ],
            [
                'nom' => 'Point Monastir Nord',
                'adresse' => 'Zone industrielle, Monastir',
                'ville' => 'Monastir',
                'latitude' => 35.7642,
                'longitude' => 10.8113,
                'capacite_max_kg' => 280,
                'telephone' => '21652000012',
                'statut' => 'PLEIN',
                'description' => 'Capacité limitée, prioriser les tournées matinées.',
            ],
        ];

        foreach ($points as $point) {
            PointCollecte::firstOrCreate(
                ['nom' => $point['nom']],
                array_merge(['user_id' => $collector->id], $point)
            );
        }
    }
}
