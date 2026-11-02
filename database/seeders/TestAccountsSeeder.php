<?php

namespace Database\Seeders;

use App\Models\Adresse;
use App\Models\Recycleur;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestAccountsSeeder extends Seeder
{
    /**
     * Crée des comptes de test prêts à l'emploi pour CHAQUE rôle de RETISS.
     * Mot de passe unique et simple pour tous les comptes : password
     */
    public function run(): void
    {
        $password = Hash::make('password');

        $comptes = [
            [
                'email'       => 'admin@retiss.tn',
                'name'        => 'Ben Salem',
                'prenom'      => 'Mohamed',
                'role'        => User::ROLE_ADMIN,
                'telephone'   => '+216 71 000 001',
                'ville'       => 'Tunis',
                'gouvernorat' => 'Tunis',
                'rue'         => 'Avenue Habib Bourguiba, Centre Ville',
                'code_postal' => 1000,
            ],
            [
                'email'       => 'client@retiss.tn',
                'name'        => 'Ben Ali',
                'prenom'      => 'Yasmine',
                'role'        => User::ROLE_CLIENT,
                'telephone'   => '+216 98 111 222',
                'ville'       => 'La Marsa',
                'gouvernorat' => 'Tunis',
                'rue'         => 'Rue de la Plage, Résidence Carthage',
                'code_postal' => 2070,
            ],
            [
                'email'       => 'donateur@retiss.tn',
                'name'        => 'Gharbi',
                'prenom'      => 'Sami',
                'role'        => User::ROLE_DONATEUR,
                'telephone'   => '+216 50 333 444',
                'ville'       => 'Menzah',
                'gouvernorat' => 'Ariana',
                'rue'         => 'Rue El Manar 2, Cité Ennasr',
                'code_postal' => 2037,
            ],
            [
                'email'       => 'collecteur@retiss.tn',
                'name'        => 'Chaabane',
                'prenom'      => 'Nour',
                'role'        => User::ROLE_COLLECTEUR,
                'telephone'   => '+216 52 555 666',
                'ville'       => 'Radès',
                'gouvernorat' => 'Ben Arous',
                'rue'         => 'Avenue de la République, Zone Portuaire',
                'code_postal' => 2040,
            ],
            [
                'email'       => 'atelier@retiss.tn',
                'name'        => 'Khemir',
                'prenom'      => 'Sonia (Atelier El Fil)',
                'role'        => User::ROLE_ATELIER,
                'telephone'   => '+216 54 777 888',
                'ville'       => 'Nabeul',
                'gouvernorat' => 'Nabeul',
                'rue'         => 'Rue des Potiers, Centre Artisanal',
                'code_postal' => 8000,
            ],
            [
                'email'       => 'recycleur@retiss.tn',
                'name'        => 'Mansouri',
                'prenom'      => 'Karim (EcoTextile)',
                'role'        => User::ROLE_RECYCLEUR,
                'telephone'   => '+216 55 000 001',
                'ville'       => 'Bir El Bey',
                'gouvernorat' => 'Ben Arous',
                'rue'         => 'Zone Industrielle Bir El Bey',
                'code_postal' => 2055,
            ],
        ];

        foreach ($comptes as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'prenom'            => $data['prenom'],
                    'role'              => $data['role'],
                    'telephone'         => $data['telephone'],
                    'actif'             => true,
                    'password'          => $password,
                    'email_verified_at' => now(),
                ]
            );

            // Créer ou mettre à jour l'adresse par défaut
            Adresse::updateOrCreate(
                ['user_id' => $user->id, 'par_defaut' => true],
                [
                    'libelle'     => 'Adresse principale',
                    'rue'         => $data['rue'],
                    'ville'       => $data['ville'],
                    'gouvernorat' => $data['gouvernorat'],
                    'code_postal' => $data['code_postal'],
                    'latitude'    => 36.8065,
                    'longitude'   => 10.1815,
                    'par_defaut'  => true,
                ]
            );

            // Pour le recycleur, s'assurer que le profil Recycleur existe
            if ($user->role === User::ROLE_RECYCLEUR) {
                Recycleur::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nom'          => 'EcoTextile Mansouri',
                        'agrement'     => 'AGR-TN-2026-001',
                        'capacite_kg'  => 5000,
                        'localisation' => 'Zone industrielle Bir El Bey, Ben Arous',
                        'actif'        => true,
                    ]
                );
            }
        }
    }
}
