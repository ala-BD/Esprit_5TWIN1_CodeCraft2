<?php

namespace Database\Seeders;

use App\Models\Atelier;
use App\Models\Devis;
use App\Models\ProjetUpcycling;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Données de démonstration du module M3 Upcycling.
 * Utilise les factories (Atelier, ProjetUpcycling, Devis) et leurs relations.
 */
class UpcyclingSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'client@retiss.tn')->exists()) {
            $this->command->info('✓ UpcyclingSeeder : données déjà présentes.');
            return;
        }

        // -------------------------------------------------------
        // 1. Comptes de démonstration
        // -------------------------------------------------------
        $client  = $this->compte('client@retiss.tn', 'Ben Ali', 'Sarra', User::ROLE_CLIENT);
        $client2 = $this->compte('client2@retiss.tn', 'Haddad', 'Mehdi', User::ROLE_CLIENT);

        $filDor = Atelier::factory()
            ->for($this->compte('atelier@retiss.tn', 'Trabelsi', 'Amel', User::ROLE_ATELIER))
            ->create([
                'nom'           => "Atelier Fil d'Or",
                'specialite'    => 'SAC',
                'tarif_horaire' => 15,
                'localisation'  => 'La Marsa, Tunis',
                'description'   => 'Spécialiste des sacs et pochettes en tissus récupérés. Chaque pièce est unique et doublée main.',
            ]);

        $neo = Atelier::factory()
            ->for($this->compte('atelier2@retiss.tn', 'Gharbi', 'Youssef', User::ROLE_ATELIER))
            ->create([
                'nom'           => 'Couture Néo',
                'specialite'    => 'VETEMENT',
                'tarif_horaire' => 22,
                'localisation'  => 'Sousse centre',
                'description'   => 'Retouche créative et transformation de vêtements : on rend vos pièces oubliées à nouveau portables.',
            ]);

        $patch = Atelier::factory()
            ->for($this->compte('atelier3@retiss.tn', 'Jlassi', 'Nour', User::ROLE_ATELIER))
            ->create([
                'nom'           => 'Maison Patch',
                'specialite'    => 'PATCHWORK',
                'tarif_horaire' => 12,
                'localisation'  => 'Sfax',
                'description'   => 'Plaids, coussins et linge de maison en patchwork à partir de vêtements usagés.',
            ]);

        // Ateliers supplémentaires générés (avec leur compte User)
        $autres = Atelier::factory()->count(3)->sequence(
            ['specialite' => 'ACCESSOIRE'],
            ['specialite' => 'DECORATION'],
            ['specialite' => 'SAC'],
        )->create();

        // -------------------------------------------------------
        // 2. Projets de Sarra à toutes les étapes du parcours
        // -------------------------------------------------------

        // Demande : idées générées, rien de choisi
        ProjetUpcycling::factory()->vetement(0)->for($client, 'client')->create(['budget_max' => 60]);

        // Idée choisie, atelier pas encore choisi
        ProjetUpcycling::factory()->vetement(7)->avecIdee()->for($client, 'client')->create();

        // Atelier choisi + devis en attente de réponse
        ProjetUpcycling::factory()->vetement(5)->pourAtelier($filDor)->for($client, 'client')
            ->has(Devis::factory()->state(['montant' => 35, 'delai_jours' => 5, 'date_emission' => now()]), 'devis')
            ->create();

        // Atelier choisi, un premier devis refusé, en attente d'un nouveau
        ProjetUpcycling::factory()->vetement(3)->pourAtelier($filDor)->for($client, 'client')
            ->has(Devis::factory()->refuse()->state(['montant' => 90]), 'devis')
            ->create(['budget_max' => 50]);

        // Travaux en cours
        ProjetUpcycling::factory()->vetement(6)->pourAtelier($patch)->enCours('CONFECTION')->for($client, 'client')
            ->has(Devis::factory()->accepte(), 'devis')
            ->create();

        ProjetUpcycling::factory()->vetement(8)->pourAtelier($neo)->enCours('CONCEPTION')->for($client, 'client')
            ->has(Devis::factory()->accepte(), 'devis')
            ->create();

        // Terminés et notés (alimentent les portfolios et les notes)
        ProjetUpcycling::factory()->vetement(9)->pourAtelier($neo)->termine(5, 'Ma veste est méconnaissable, je la porte tous les jours !')
            ->for($client, 'client')->has(Devis::factory()->accepte(), 'devis')->create();

        ProjetUpcycling::factory()->vetement(1)->pourAtelier($filDor)->termine(5, 'Magnifique cabas, finitions parfaites.')
            ->for($client, 'client')->has(Devis::factory()->accepte(), 'devis')->create();

        // -------------------------------------------------------
        // 3. Projets d'autres clients (générés par factory)
        // -------------------------------------------------------
        foreach ([[2, $filDor, 4], [4, $patch, 5], [7, $neo, 4], [3, $autres[0], 3]] as [$vetement, $atelier, $note]) {
            ProjetUpcycling::factory()->vetement($vetement)->pourAtelier($atelier)->termine($note)
                ->for($client2, 'client')->has(Devis::factory()->accepte(), 'devis')->create();
        }

        ProjetUpcycling::factory()->count(2)->create(); // demandes de nouveaux clients

        // Notes moyennes calculées à partir des avis
        Atelier::all()->each->recalculerNote();

        $this->command->info('✓ UpcyclingSeeder exécuté avec succès !');
        $this->command->info('  Clients  : client@retiss.tn, client2@retiss.tn / Password123!');
        $this->command->info('  Ateliers : atelier@retiss.tn, atelier2@retiss.tn, atelier3@retiss.tn / Password123!');
    }

    private function compte(string $email, string $nom, string $prenom, string $role): User
    {
        return User::factory()->create([
            'email'    => $email,
            'name'     => $nom,
            'prenom'   => $prenom,
            'role'     => $role,
            'actif'    => true,
            'password' => Hash::make('Password123!'),
        ]);
    }
}
