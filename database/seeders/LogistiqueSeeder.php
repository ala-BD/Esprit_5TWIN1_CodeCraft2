<?php

namespace Database\Seeders;

use App\Models\Tournee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LogistiqueSeeder extends Seeder
{
    public function run(): void
    {
        // -------------------------------------------------------
        // 1. Utilisateur COLLECTEUR de test
        // -------------------------------------------------------
        $user = User::firstOrCreate(
            ['email' => 'collecteur@retiss.tn'],
            [
                'name'      => 'Trabelsi',
                'prenom'    => 'Youssef',
                'telephone' => '21655000002',
                'role'      => 'COLLECTEUR',
                'actif'     => true,
                'password'  => Hash::make('Password123!'),
            ]
        );

        // -------------------------------------------------------
        // 2. Tournée terminée (hier)
        // -------------------------------------------------------
        $terminee = Tournee::firstOrCreate(
            ['user_id' => $user->id, 'date' => today()->subDay(), 'zone' => 'Ariana'],
            ['vehicule' => 'Camionnette 123 TU 4567', 'distance_km' => 31.5, 'statut' => 'TERMINEE']
        );

        if ($terminee->missions()->doesntExist()) {
            $terminee->missions()->createMany([
                ['type' => 'COLLECTE',  'adresse' => 'Point de collecte Ariana, avenue Habib Bourguiba', 'ordre' => 1, 'heure_prevue' => '09:00', 'statut' => 'TERMINEE', 'preuve_livraison' => '4 sacs collectés'],
                ['type' => 'LIVRAISON', 'adresse' => '8 rue des Jasmins, Ariana Ville',                  'ordre' => 2, 'heure_prevue' => '10:30', 'statut' => 'TERMINEE', 'preuve_livraison' => 'Signé par Mme Gharbi'],
                ['type' => 'LIVRAISON', 'adresse' => 'Résidence El Amen, Ennasr 2',                      'ordre' => 3, 'heure_prevue' => '11:15', 'statut' => 'ECHOUEE',  'preuve_livraison' => 'Client absent'],
            ]);
        }

        // -------------------------------------------------------
        // 3. Tournée en cours (aujourd'hui)
        // -------------------------------------------------------
        $enCours = Tournee::firstOrCreate(
            ['user_id' => $user->id, 'date' => today(), 'zone' => 'Tunis Centre'],
            ['vehicule' => 'Camionnette 123 TU 4567', 'distance_km' => 18, 'statut' => 'EN_COURS']
        );

        if ($enCours->missions()->doesntExist()) {
            $enCours->missions()->createMany([
                ['type' => 'COLLECTE',  'adresse' => '12 rue de Marseille, Tunis',          'ordre' => 1, 'heure_prevue' => '08:30', 'statut' => 'TERMINEE', 'preuve_livraison' => '2 sacs collectés'],
                ['type' => 'COLLECTE',  'adresse' => 'Point de collecte Lafayette',         'ordre' => 2, 'heure_prevue' => '09:45', 'statut' => 'EN_COURS'],
                ['type' => 'LIVRAISON', 'adresse' => '45 avenue de la Liberté, Tunis',      'ordre' => 3, 'heure_prevue' => '11:00', 'statut' => 'A_FAIRE'],
                ['type' => 'LIVRAISON', 'adresse' => 'Atelier Fil Vert, Bab El Khadra',     'ordre' => 4, 'heure_prevue' => '14:00', 'statut' => 'A_FAIRE'],
            ]);
        }

        // -------------------------------------------------------
        // 4. Tournée planifiée (dans 2 jours, sans mission)
        // -------------------------------------------------------
        Tournee::firstOrCreate(
            ['user_id' => $user->id, 'date' => today()->addDays(2), 'zone' => 'La Marsa'],
            ['vehicule' => 'Fourgon 87 TU 2210', 'statut' => 'PLANIFIEE']
        );
    }
}
