<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\DonVetement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DonGratuitSeeder extends Seeder
{
    /**
     * Crée des dons textiles gratuits disponibles sur la marketplace pour l'évaluation par les ateliers.
     */
    public function run(): void
    {
        $donateur = User::firstOrCreate(
            ['email' => 'donateur@retiss.tn'],
            [
                'name'              => 'Gharbi',
                'prenom'            => 'Sami',
                'role'              => User::ROLE_DONATEUR,
                'telephone'         => '+216 50 333 444',
                'actif'             => true,
                'password'          => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $dons = [
            [
                'titre'          => 'Lot de 10 Chemises Coton Vintage — Idéal Upcycling',
                'description'    => 'Lot de 10 chemises d\'hommes en popeline de coton 100%. Cols légèrement usés mais tissu principal en excellent état, parfait pour découpe de patrons, création de tops upcyclés ou tote-bags.',
                'stock'          => 10,
                'categorie'      => 'Vetements Homme',
                'taille'         => 'M',
                'matiere'        => 'Coton',
                'etat'           => 'Bon etat',
                'couleur'        => 'Blanc',
                'poids_kg'       => 0.25, // par pièce (lot = 2.5 kg)
                'cout_achat'     => 0.00,
                'heures_travail' => 0.8,
                'cout_materiaux' => 1.50,
                'type_don'       => 'Chemise',
                'images'         => [
                    'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=800&q=80',
                ],
            ],
            [
                'titre'          => 'Veste Blazer en Pure Laine d\'Hiver — Gris Anthracite',
                'description'    => 'Superbe veste blazer vintage en pure laine vierge. Doublure intérieure soyeuse à réparer au niveau de l\'emmanchure. Excellente tenue et fibre chaude noble, fort potentiel de valorisation.',
                'stock'          => 1,
                'categorie'      => 'Vetements Homme',
                'taille'         => 'L',
                'matiere'        => 'Laine',
                'etat'           => 'Tres bon etat',
                'couleur'        => 'Gris',
                'poids_kg'       => 1.10,
                'cout_achat'     => 0.00,
                'heures_travail' => 1.5,
                'cout_materiaux' => 4.00,
                'type_don'       => 'Veste',
                'images'         => [
                    'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=800&q=80',
                ],
            ],
            [
                'titre'          => 'Lot de 15 Robes d\'Été en Lin Naturel & Broderie',
                'description'    => 'Lot de 15 robes d\'été fluides en lin beige naturel. Quelques tâches lavables ou boutons manquants. Matière respirante de première qualité, très recherchée sur le marché du prêt-à-porter upcyclé.',
                'stock'          => 15,
                'categorie'      => 'Vetements Femme',
                'taille'         => 'M',
                'matiere'        => 'Lin',
                'etat'           => 'Bon etat',
                'couleur'        => 'Beige',
                'poids_kg'       => 0.32,
                'cout_achat'     => 0.00,
                'heures_travail' => 0.9,
                'cout_materiaux' => 2.50,
                'type_don'       => 'Robe',
                'images'         => [
                    'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1572804013309-59a88b7e92f1?auto=format&fit=crop&w=800&q=80',
                ],
            ],
            [
                'titre'          => 'Lot de 20 T-shirts Coton Unis (Idéal Confection Accessoires)',
                'description'    => '20 t-shirts en jersey de coton doux, couleurs assorties. Idéal pour un atelier créant des tote-bags, bandeaux, ou garnissage coussins éco-responsables.',
                'stock'          => 20,
                'categorie'      => 'Textile Upcycle',
                'taille'         => 'Unique',
                'matiere'        => 'Coton',
                'etat'           => 'Bon etat',
                'couleur'        => 'Bleu',
                'poids_kg'       => 0.18,
                'cout_achat'     => 0.00,
                'heures_travail' => 0.4,
                'cout_materiaux' => 0.80,
                'type_don'       => 'T-shirt',
                'images'         => [
                    'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?auto=format&fit=crop&w=800&q=80',
                ],
            ],
            [
                'titre'          => 'Manteau Imperméable Synthétique Usagé (Fermeture HS & Déchirures)',
                'description'    => 'Manteau long en polyester synthétique avec zip cassé et plusieurs accrocs importants sur la manche. Nécessite beaucoup de temps de reprise technique sur tissu synthétique.',
                'stock'          => 1,
                'categorie'      => 'Vetements Homme',
                'taille'         => 'XL',
                'matiere'        => 'Synthetique',
                'etat'           => 'Usage',
                'couleur'        => 'Noir',
                'poids_kg'       => 1.40,
                'cout_achat'     => 0.00,
                'heures_travail' => 4.5,
                'cout_materiaux' => 14.00,
                'type_don'       => 'Manteau',
                'images'         => [
                    'https://images.unsplash.com/photo-1544923246-77307dd654cb?auto=format&fit=crop&w=800&q=80',
                ],
            ],
            [
                'titre'          => 'Lot de 8 Jeans Denim Coton — Base Solide Customisation',
                'description'    => 'Lot de 8 pantalons jeans denim robustes en 100% coton. Genoux râpés, parfaits pour la technique du boro/sashiko, vestes en patchwork ou sacs à dos upcyclés.',
                'stock'          => 8,
                'categorie'      => 'Vetements Homme',
                'taille'         => 'L',
                'matiere'        => 'Coton',
                'etat'           => 'Bon etat',
                'couleur'        => 'Bleu',
                'poids_kg'       => 0.65,
                'cout_achat'     => 0.00,
                'heures_travail' => 1.2,
                'cout_materiaux' => 3.00,
                'type_don'       => 'Pantalon',
                'images'         => [
                    'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1542272604-780c96856592?auto=format&fit=crop&w=800&q=80',
                ],
            ],
            [
                'titre'          => 'Chutes & Coupons Linge de Maison Mixte (5 kg)',
                'description'    => 'Ensemble de draps et rideaux en mélange polycoton coloré. Grande surface de tissu utilisable pour création de doublures, patchwork ou réemploi créatif.',
                'stock'          => 5,
                'categorie'      => 'Linge de maison',
                'taille'         => 'Unique',
                'matiere'        => 'Mixte',
                'etat'           => 'Bon etat',
                'couleur'        => 'Multicolore',
                'poids_kg'       => 1.00,
                'cout_achat'     => 0.00,
                'heures_travail' => 1.8,
                'cout_materiaux' => 4.00,
                'type_don'       => 'Autre',
                'images'         => [
                    'https://images.unsplash.com/photo-1528459801416-a9e53bbf4e17?auto=format&fit=crop&w=800&q=80',
                ],
            ],
        ];

        foreach ($dons as $data) {
            // Créer d'abord la traçabilité DonVetement
            $donVetement = DonVetement::create([
                'user_id'            => $donateur->id,
                'type'               => $data['type_don'],
                'matiere'            => $data['matiere'],
                'taille'             => $data['taille'],
                'etat'               => $data['etat'],
                'photo_url'          => $data['images'][0] ?? null,
                'qr_code'            => 'DON-' . strtoupper(Str::random(10)),
                'statut'             => 'UPCYCLING',
                'date_depot'         => now()->subDays(rand(1, 14)),
                'categorie_ia'       => $data['categorie'],
                'score_confiance_ia' => 0.95,
            ]);

            // Créer l'article gratuit publié dans la marketplace
            Article::updateOrCreate(
                [
                    'titre'   => $data['titre'],
                    'user_id' => $donateur->id,
                ],
                [
                    'don_vetement_id'  => $donVetement->id,
                    'description'      => $data['description'],
                    'prix'             => 0.00, // GRATUIT
                    'stock'            => $data['stock'],
                    'categorie'        => $data['categorie'],
                    'taille'           => $data['taille'],
                    'matiere'          => $data['matiere'],
                    'etat'             => $data['etat'],
                    'couleur'          => $data['couleur'],
                    'poids_kg'         => $data['poids_kg'],
                    'cout_achat'       => $data['cout_achat'],
                    'heures_travail'   => $data['heures_travail'],
                    'cout_materiaux'   => $data['cout_materiaux'],
                    'statut'           => Article::STATUT_DISPONIBLE,
                    'co2_economise_kg' => round($data['poids_kg'] * 5.8 * $data['stock'], 2),
                    'images'           => $data['images'],
                ]
            );
        }
    }
}
