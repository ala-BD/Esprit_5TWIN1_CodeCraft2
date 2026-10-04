<?php

namespace Database\Seeders;

use App\Models\Atelier;
use App\Models\Devis;
use App\Models\ProjetUpcycling;
use App\Models\User;
use App\Services\Upcycling\IdeeUpcyclingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UpcyclingSeeder extends Seeder
{
    public function run(IdeeUpcyclingService $ideeService): void
    {
        // -------------------------------------------------------
        // 1. Comptes de test
        // -------------------------------------------------------
        $client = $this->utilisateur('client@retiss.tn', 'Ben Ali', 'Sarra', 'CLIENT', '21655000010');

        $ateliersData = [
            ['atelier@retiss.tn',  'Trabelsi', 'Amel',  "Atelier Fil d'Or", 'SAC',        15, 'La Marsa, Tunis',
                'Spécialiste des sacs et pochettes en tissus récupérés. Chaque pièce est unique et doublée main.'],
            ['atelier2@retiss.tn', 'Gharbi',   'Youssef', 'Couture Néo',     'VETEMENT',   22, 'Sousse centre',
                'Retouche créative et transformation de vêtements : on rend vos pièces oubliées à nouveau portables.'],
            ['atelier3@retiss.tn', 'Jlassi',   'Nour',  'Maison Patch',      'PATCHWORK',  12, 'Sfax',
                'Plaids, coussins et linge de maison en patchwork à partir de vêtements usagés.'],
        ];

        $ateliers = [];
        foreach ($ateliersData as [$email, $nom, $prenom, $nomAtelier, $specialite, $tarif, $lieu, $description]) {
            $user = $this->utilisateur($email, $nom, $prenom, 'ATELIER', null);
            $ateliers[$specialite] = Atelier::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'nom'           => $nomAtelier,
                    'specialite'    => $specialite,
                    'description'   => $description,
                    'portfolio_url' => null,
                    'tarif_horaire' => $tarif,
                    'localisation'  => $lieu,
                    'actif'         => true,
                ]
            );
        }

        if (ProjetUpcycling::where('client_id', $client->id)->exists()) {
            $this->command->info('✓ UpcyclingSeeder : données déjà présentes.');
            return;
        }

        // -------------------------------------------------------
        // 2. Projets à différentes étapes
        // -------------------------------------------------------
        $projet = fn (string $type, string $matiere, string $etat, string $description, array $extra = []) =>
            ProjetUpcycling::create($extra + [
                'client_id'       => $client->id,
                'type_vetement'   => $type,
                'matiere'         => $matiere,
                'etat'            => $etat,
                'description'     => $description,
                'idee_generee_ia' => $ideeService->genererLocalement($type, $matiere, $etat),
                'source_ia'       => IdeeUpcyclingService::SOURCE_LOCAL,
                'statut'          => ProjetUpcycling::STATUT_DEMANDE,
            ]);

        // DEMANDE : idées générées, rien de choisi
        $projet('Jean', 'Denim', 'USE', "Jean slim délavé, trou au genou. J'aimerais un objet utile au quotidien.", ['budget_max' => 60]);

        // ATELIER_CHOISI + devis en attente
        $p2 = $projet('Chemise', 'Coton', 'BON', 'Chemise à carreaux de mon père, je veux la garder en souvenir sous une autre forme.', [
            'produit_final'     => 'Tote bag léger',
            'categorie_produit' => 'SAC',
            'atelier_id'        => $ateliers['SAC']->id,
            'statut'            => ProjetUpcycling::STATUT_ATELIER_CHOISI,
        ]);
        Devis::create([
            'projet_upcycling_id' => $p2->id, 'montant' => 35, 'delai_jours' => 5,
            'message' => 'Doublure coton bio incluse, anses renforcées.', 'statut' => Devis::STATUT_EN_ATTENTE, 'date_emission' => now(),
        ]);

        // CONFECTION (devis accepté)
        $p3 = $projet('Pull', 'Laine', 'ABIME', 'Trois vieux pulls troués, couleurs bleu et gris.', [
            'produit_final'     => 'Plaid patchwork en maille',
            'categorie_produit' => 'PATCHWORK',
            'atelier_id'        => $ateliers['PATCHWORK']->id,
            'statut'            => ProjetUpcycling::STATUT_CONFECTION,
            'date_debut'        => now()->subDays(6),
        ]);
        Devis::create([
            'projet_upcycling_id' => $p3->id, 'montant' => 120, 'delai_jours' => 14,
            'message' => 'Feutrage, découpe et assemblage, doublure polaire.', 'statut' => Devis::STATUT_ACCEPTE, 'date_emission' => now()->subDays(7),
        ]);

        // TERMINÉS + notés
        $termines = [
            ['Robe', 'Soie', 'BON', 'Robe de soirée portée une fois.', 'Pochette de soirée', 'SAC', 'SAC', 55, 5, 'Magnifique pochette, finitions parfaites !'],
            ['Veste', 'Denim', 'USE', 'Veste en jean trop petite.', 'Sac à dos', 'SAC', 'SAC', 90, 4, 'Très beau travail, un peu de retard.'],
            ['Chemise', 'Lin', 'BON', 'Chemise en lin trop large.', 'Top cache-cœur', 'VETEMENT', 'VETEMENT', 45, 5, 'Je la porte tout le temps.'],
        ];
        foreach ($termines as $i => [$type, $matiere, $etat, $desc, $produit, $categorie, $specialite, $montant, $note, $commentaire]) {
            $p = $projet($type, $matiere, $etat, $desc, [
                'produit_final'      => $produit,
                'categorie_produit'  => $categorie,
                'atelier_id'         => $ateliers[$specialite]->id,
                'statut'             => ProjetUpcycling::STATUT_TERMINE,
                'date_debut'         => now()->subDays(40 - $i * 5),
                'date_fin'           => now()->subDays(30 - $i * 5),
                'note_client'        => $note,
                'commentaire_client' => $commentaire,
            ]);
            Devis::create([
                'projet_upcycling_id' => $p->id, 'montant' => $montant, 'delai_jours' => 10,
                'statut' => Devis::STATUT_ACCEPTE, 'date_emission' => now()->subDays(41 - $i * 5),
            ]);
        }

        foreach ($ateliers as $atelier) {
            $atelier->recalculerNote();
        }

        $this->command->info('✓ UpcyclingSeeder exécuté avec succès !');
        $this->command->info('  Client  : client@retiss.tn / Password123!');
        $this->command->info('  Atelier : atelier@retiss.tn / Password123! (+ atelier2, atelier3)');
    }

    private function utilisateur(string $email, string $nom, string $prenom, string $role, ?string $telephone): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            [
                'name'      => $nom,
                'prenom'    => $prenom,
                'telephone' => $telephone,
                'role'      => $role,
                'actif'     => true,
                'password'  => Hash::make('Password123!'),
            ]
        );
    }
}
