<?php

namespace Database\Seeders;

use App\Models\Adresse;
use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================================
        // 1. COMPTE ADMIN
        // =====================================================
        $admin = User::firstOrCreate(
            ['email' => 'admin@retiss.tn'],
            [
                'name'               => 'RETISS',
                'prenom'             => 'Admin',
                'telephone'          => '+216 71 000 000',
                'role'               => User::ROLE_ADMIN,
                'password'           => Hash::make('password'),
                'email_verified_at'  => now(),
            ]
        );

        // =====================================================
        // 2. ATELIERS PARTENAIRES (vendeurs principaux)
        // =====================================================
        $ateliers = [
            [
                'name'      => 'Atelier El Fil',
                'prenom'    => 'Sonia',
                'email'     => 'atelier.elfil@retiss.tn',
                'telephone' => '+216 52 111 222',
                'ville'     => 'Tunis',
                'rue'       => 'Rue de la République 14',
                'gouv'      => 'Tunis',
            ],
            [
                'name'      => 'Recyclerie Carthage',
                'prenom'    => 'Karim',
                'email'     => 'recyclerie.carthage@retiss.tn',
                'telephone' => '+216 55 333 444',
                'ville'     => 'La Marsa',
                'rue'       => 'Avenue Habib Bourguiba 42',
                'gouv'      => 'Tunis',
            ],
            [
                'name'      => 'Atelier Djerba Créations',
                'prenom'    => 'Fatma',
                'email'     => 'djerba.creations@retiss.tn',
                'telephone' => '+216 75 555 666',
                'ville'     => 'Houmt Souk',
                'rue'       => 'Rue des Artisans 7',
                'gouv'      => 'Médenine',
            ],
            [
                'name'      => 'Upcycle Sfax',
                'prenom'    => 'Mehdi',
                'email'     => 'upcycle.sfax@retiss.tn',
                'telephone' => '+216 74 777 888',
                'ville'     => 'Sfax',
                'rue'       => 'Avenue de l\'Indépendance 21',
                'gouv'      => 'Sfax',
            ],
            [
                'name'      => 'Couture Sousse',
                'prenom'    => 'Leila',
                'email'     => 'couture.sousse@retiss.tn',
                'telephone' => '+216 73 999 010',
                'ville'     => 'Sousse',
                'rue'       => 'Rue Hédi Chaker 33',
                'gouv'      => 'Sousse',
            ],
        ];

        $atelierUsers = [];
        foreach ($ateliers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'prenom'            => $data['prenom'],
                    'telephone'         => $data['telephone'],
                    'role'              => User::ROLE_ATELIER,
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            // Adresse de l'atelier
            if ($user->adresses()->count() === 0) {
                $user->adresses()->create([
                    'libelle'     => 'Atelier',
                    'rue'         => $data['rue'],
                    'ville'       => $data['ville'],
                    'gouvernorat' => $data['gouv'],
                    'code_postal' => rand(1000, 9000),
                    'latitude'    => rand(3300, 3700) / 100,
                    'longitude'   => rand(900, 1100) / 100,
                    'par_defaut'  => true,
                ]);
            }

            $atelierUsers[] = $user;
        }

        // =====================================================
        // 3. CLIENTS & COLLECTEURS
        // =====================================================
        $clients = [
            ['name' => 'Ben Ali', 'prenom' => 'Yasmine', 'email' => 'yasmine@retiss.tn', 'role' => User::ROLE_CLIENT],
            ['name' => 'Trabelsi', 'prenom' => 'Omar', 'email' => 'omar.trabelsi@retiss.tn', 'role' => User::ROLE_CLIENT],
            ['name' => 'Chaabane', 'prenom' => 'Nour', 'email' => 'nour.chaabane@retiss.tn', 'role' => User::ROLE_COLLECTEUR],
            ['name' => 'Haddad', 'prenom' => 'Amine', 'email' => 'amine.haddad@retiss.tn', 'role' => User::ROLE_COLLECTEUR],
            ['name' => 'Saidi', 'prenom' => 'Rima', 'email' => 'rima.saidi@retiss.tn', 'role' => User::ROLE_CLIENT],
        ];

        $clientUsers = [];
        foreach ($clients as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'prenom'            => $data['prenom'],
                    'telephone'         => '+216 2' . rand(1000000, 9999999),
                    'role'              => $data['role'],
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            if ($user->adresses()->count() === 0) {
                $gouvernorats = ['Tunis', 'Ariana', 'Ben Arous', 'Sousse', 'Sfax'];
                $gouv = $gouvernorats[array_rand($gouvernorats)];
                $user->adresses()->create([
                    'libelle'     => 'Domicile',
                    'rue'         => 'Rue ' . rand(1, 50) . ' ' . fake()->word(),
                    'ville'       => $gouv,
                    'gouvernorat' => $gouv,
                    'code_postal' => rand(1000, 9000),
                    'latitude'    => rand(3300, 3700) / 100,
                    'longitude'   => rand(900, 1100) / 100,
                    'par_defaut'  => true,
                ]);
            }

            $clientUsers[] = $user;
        }

        // =====================================================
        // 4. ARTICLES PAR CATÉGORIE
        // =====================================================

        // --- Atelier El Fil (Sonia) ---
        $atelier0 = $atelierUsers[0];
        $this->creerArticles($atelier0, [
            [
                'titre'       => 'Chemise Lin Upcyclée Homme — Blanc Cassé',
                'description' => 'Chemise en lin naturel upcyclée à partir de surplus de tissu de luxe. Coupe droite moderne, col classique. Idéale pour l\'été tunisien. Confectionnée à la main par nos artisanes.',
                'prix'        => 45.00,
                'stock'       => 8,
                'categorie'   => 'Vetements Homme',
                'taille'      => 'M',
                'matiere'     => 'Lin',
                'etat'        => 'Neuf',
                'couleur'     => 'Blanc',
            ],
            [
                'titre'       => 'Pantalon Chino Recyclé Homme — Beige',
                'description' => 'Pantalon chino taillé dans des tissus recyclés de haute qualité. Coupe slim, ceinture élastique ajustable. Parfait pour un look décontracté-chic.',
                'prix'        => 55.00,
                'stock'       => 5,
                'categorie'   => 'Vetements Homme',
                'taille'      => 'L',
                'matiere'     => 'Coton',
                'etat'        => 'Neuf',
                'couleur'     => 'Beige',
            ],
            [
                'titre'       => 'Robe Longue Bohème Femme — Imprimé Floral',
                'description' => 'Robe longue fluide confectionnée à partir de tissus vintage sélectionnés. Imprimé floral coloré, manches courtes, bretelles ajustables. Style bohème intemporel.',
                'prix'        => 65.00,
                'stock'       => 6,
                'categorie'   => 'Vetements Femme',
                'taille'      => 'S',
                'matiere'     => 'Coton',
                'etat'        => 'Tres bon etat',
                'couleur'     => 'Multicolore',
            ],
            [
                'titre'       => 'Blouse Lin & Coton Femme — Terracotta',
                'description' => 'Blouse légère mi-longue en mélange lin-coton recyclé. Coloris terracotta tendance. Parfaite pour les journées chaudes, confort maximal.',
                'prix'        => 38.00,
                'stock'       => 10,
                'categorie'   => 'Vetements Femme',
                'taille'      => 'M',
                'matiere'     => 'Lin',
                'etat'        => 'Neuf',
                'couleur'     => 'Orange',
            ],
            [
                'titre'       => 'T-shirt Graphique Upcyclé — "RETISS Love"',
                'description' => 'T-shirt 100% coton organique upcyclé, sérigraphié à la main avec encres écologiques. Message "RETISS Love" en arabe et français. Édition limitée.',
                'prix'        => 22.00,
                'stock'       => 15,
                'categorie'   => 'Vetements Homme',
                'taille'      => 'L',
                'matiere'     => 'Coton',
                'etat'        => 'Neuf',
                'couleur'     => 'Blanc',
            ],
        ]);

        // --- Recyclerie Carthage (Karim) ---
        $atelier1 = $atelierUsers[1];
        $this->creerArticles($atelier1, [
            [
                'titre'       => 'Veste Jean Recyclée Femme — Broderies Traditionnelles',
                'description' => 'Veste en jean upcyclée ornée de broderies traditionnelles tunisiennes faites main. Pièce unique alliant modernité et artisanat local. Fermeture boutons dorés.',
                'prix'        => 85.00,
                'stock'       => 3,
                'categorie'   => 'Vetements Femme',
                'taille'      => 'S',
                'matiere'     => 'Coton',
                'etat'        => 'Tres bon etat',
                'couleur'     => 'Bleu',
            ],
            [
                'titre'       => 'Sac Cabas Tissu Recyclé — Rayures Berbères',
                'description' => 'Grand sac cabas cousu à partir de textiles recyclés à motifs berbères. Poignées en cuir végétal. Intérieur doublé, poche zippée. Capacité 15L. Parfait pour le marché ou la plage.',
                'prix'        => 42.00,
                'stock'       => 12,
                'categorie'   => 'Accessoires & Sacs',
                'taille'      => 'Unique',
                'matiere'     => 'Coton',
                'etat'        => 'Neuf',
                'couleur'     => 'Multicolore',
            ],
            [
                'titre'       => 'Tote Bag Upcyclé — Logo RETISS Brodé',
                'description' => 'Tote bag en toile de coton recyclée. Logo RETISS brodé en fil écru. Contenance 10L, anses longues pour porter à l\'épaule. Lavable en machine.',
                'prix'        => 18.00,
                'stock'       => 25,
                'categorie'   => 'Accessoires & Sacs',
                'taille'      => 'Unique',
                'matiere'     => 'Coton',
                'etat'        => 'Neuf',
                'couleur'     => 'Blanc',
            ],
            [
                'titre'       => 'Pantalon Large Femme — Tissu Madras Recyclé',
                'description' => 'Pantalon large (palazzo) confectionné dans un beau tissu madras coloré récupéré. Taille élastique, jambes amples pour une silhouette fluide et moderne.',
                'prix'        => 48.00,
                'stock'       => 7,
                'categorie'   => 'Vetements Femme',
                'taille'      => 'L',
                'matiere'     => 'Coton',
                'etat'        => 'Neuf',
                'couleur'     => 'Multicolore',
            ],
            [
                'titre'       => 'Écharpe Soie Recyclée — Dégradé Coucher de Soleil',
                'description' => 'Écharpe en soie recyclée teinte à la main en dégradé orangé-rouge. 180x45 cm. Peut se porter en foulard, ceinture ou bandeau. Chaque pièce est unique.',
                'prix'        => 35.00,
                'stock'       => 9,
                'categorie'   => 'Accessoires & Sacs',
                'taille'      => 'Unique',
                'matiere'     => 'Soie',
                'etat'        => 'Neuf',
                'couleur'     => 'Orange',
            ],
        ]);

        // --- Atelier Djerba Créations (Fatma) ---
        $atelier2 = $atelierUsers[2];
        $this->creerArticles($atelier2, [
            [
                'titre'       => 'Fouta de Plage Upcyclée — Rayures Turquoise & Blanc',
                'description' => 'Fouta traditionnelle de Djerba upcyclée à partir de fils recyclés de qualité. Légère, absorbante, séchage rapide. Utilisable en serviette, pareo ou plaid. Fait main.',
                'prix'        => 28.00,
                'stock'       => 20,
                'categorie'   => 'Linge de maison',
                'taille'      => 'Unique',
                'matiere'     => 'Coton',
                'etat'        => 'Neuf',
                'couleur'     => 'Bleu',
            ],
            [
                'titre'       => 'Coussin Berbère Recyclé — Motifs Géométriques',
                'description' => 'Housse de coussin 45x45 cm tissée à partir de laines recyclées avec motifs géométriques berbères. Fermeture zip discret. Lavable en machine 30°C.',
                'prix'        => 32.00,
                'stock'       => 11,
                'categorie'   => 'Linge de maison',
                'taille'      => 'Unique',
                'matiere'     => 'Laine',
                'etat'        => 'Neuf',
                'couleur'     => 'Multicolore',
            ],
            [
                'titre'       => 'Robe Enfant Tissée Main — Coton Bio',
                'description' => 'Petite robe pour fille (3-5 ans) tissée à la main en coton biologique recyclé. Broderies colorées sur l\'encolure, sans produits chimiques. Douce pour la peau des enfants.',
                'prix'        => 25.00,
                'stock'       => 8,
                'categorie'   => 'Vetements Enfant',
                'taille'      => 'S',
                'matiere'     => 'Coton',
                'etat'        => 'Neuf',
                'couleur'     => 'Rose',
            ],
            [
                'titre'       => 'Salopette Enfant Upcyclée — Denim Souple',
                'description' => 'Salopette pour enfant (6-8 ans) cousue dans du denim récupéré et assoupli. Bretelles ajustables, poches latérales pratiques. Idéale pour jouer sans contrainte.',
                'prix'        => 30.00,
                'stock'       => 6,
                'categorie'   => 'Vetements Enfant',
                'taille'      => 'M',
                'matiere'     => 'Coton',
                'etat'        => 'Neuf',
                'couleur'     => 'Bleu',
            ],
            [
                'titre'       => 'Plaid Laine Recyclée — Bandes Naturelles',
                'description' => 'Plaid 130x170 cm tricoté à partir de laines recyclées naturelles (blanc, beige, gris). Chaud, doux, résistant. Parfait pour les soirées fraîches en famille.',
                'prix'        => 72.00,
                'stock'       => 4,
                'categorie'   => 'Linge de maison',
                'taille'      => 'Unique',
                'matiere'     => 'Laine',
                'etat'        => 'Neuf',
                'couleur'     => 'Beige',
            ],
        ]);

        // --- Upcycle Sfax (Mehdi) ---
        $atelier3 = $atelierUsers[3];
        $this->creerArticles($atelier3, [
            [
                'titre'       => 'Veste Upcyclée Homme — Patchwork Vintage',
                'description' => 'Veste patchwork homme créée à partir de chutes de tissus divers (velours, tweed, coton). Coupe oversize, doublure contrastante. Pièce unique, portez votre originalité.',
                'prix'        => 95.00,
                'stock'       => 2,
                'categorie'   => 'Vetements Homme',
                'taille'      => 'L',
                'matiere'     => 'Mixte',
                'etat'        => 'Tres bon etat',
                'couleur'     => 'Marron',
            ],
            [
                'titre'       => 'Short Cycliste Recyclé Femme — Noir Mat',
                'description' => 'Short cycliste femme en polyester recyclé haute performance. Maintien excellent, toucher doux. Coutures plates anti-irritation. Idéal pour le sport ou le casual.',
                'prix'        => 29.00,
                'stock'       => 13,
                'categorie'   => 'Vetements Femme',
                'taille'      => 'M',
                'matiere'     => 'Synthetique',
                'etat'        => 'Neuf',
                'couleur'     => 'Noir',
            ],
            [
                'titre'       => 'Chemise Wax Upcyclée Homme — Imprimé Africain',
                'description' => 'Chemise taillée dans du tissu wax récupéré, aux motifs africains vibrants. Coupe droite, col mao, manches courtes. Pièce festive et engagée.',
                'prix'        => 52.00,
                'stock'       => 5,
                'categorie'   => 'Vetements Homme',
                'taille'      => 'XL',
                'matiere'     => 'Coton',
                'etat'        => 'Neuf',
                'couleur'     => 'Multicolore',
            ],
            [
                'titre'       => 'Sweat Oversize Recyclé — Gris Chiné',
                'description' => 'Sweat oversize confectionné à partir de coton recyclé chiné. Poche kangourou brodée du logo RETISS. Confort premium pour l\'hiver, toucher doux garanti.',
                'prix'        => 58.00,
                'stock'       => 9,
                'categorie'   => 'Vetements Homme',
                'taille'      => 'XL',
                'matiere'     => 'Coton',
                'etat'        => 'Neuf',
                'couleur'     => 'Gris',
            ],
            [
                'titre'       => 'Combinaison Femme Recyclée — Vert Olive',
                'description' => 'Combinaison pantalon taillée dans un tissu sergé recyclé vert olive. Bretelles réglables, ceinture intégrée. Style utilitaire chic, parfaite toute saison.',
                'prix'        => 78.00,
                'stock'       => 4,
                'categorie'   => 'Vetements Femme',
                'taille'      => 'S',
                'matiere'     => 'Coton',
                'etat'        => 'Neuf',
                'couleur'     => 'Vert',
            ],
        ]);

        // --- Couture Sousse (Leila) ---
        $atelier4 = $atelierUsers[4];
        $this->creerArticles($atelier4, [
            [
                'titre'       => 'Jupe Midi Plissée Recyclée — Mauve Poudré',
                'description' => 'Jupe midi plissée confectionnée à partir de tissus satin recyclés. Taille haute élastique, tombé parfait. Couleur mauve poudré délicate. Porte-jarretelles amovible.',
                'prix'        => 44.00,
                'stock'       => 7,
                'categorie'   => 'Vetements Femme',
                'taille'      => 'S',
                'matiere'     => 'Synthetique',
                'etat'        => 'Neuf',
                'couleur'     => 'Violet',
            ],
            [
                'titre'       => 'Manteau Laine Recyclée Femme — Camel',
                'description' => 'Manteau mi-long en laine recyclée couleur camel. Fermeture double boutonnage, poches latérales avec rabat. Doublure en soie recyclée. Élégance circulaire.',
                'prix'        => 145.00,
                'stock'       => 3,
                'categorie'   => 'Vetements Femme',
                'taille'      => 'M',
                'matiere'     => 'Laine',
                'etat'        => 'Tres bon etat',
                'couleur'     => 'Beige',
            ],
            [
                'titre'       => 'Pyjama Enfant Coton Recyclé — Motifs Étoiles',
                'description' => 'Ensemble pyjama 2 pièces (haut + pantalon) pour enfant (4-6 ans). Coton doux 100% recyclé, motifs étoiles sérigraphiés. Certifié sans allergènes.',
                'prix'        => 28.00,
                'stock'       => 10,
                'categorie'   => 'Vetements Enfant',
                'taille'      => 'S',
                'matiere'     => 'Coton',
                'etat'        => 'Neuf',
                'couleur'     => 'Bleu',
            ],
            [
                'titre'       => 'Nappe Coton Recyclé — Carreaux Bistrot Rouge & Blanc',
                'description' => 'Nappe rectangulaire 140x200 cm en coton recyclé à carreaux bistrot rouge et blanc. Résistante aux taches, lavable 60°C. Donne du caractère à votre table.',
                'prix'        => 22.00,
                'stock'       => 14,
                'categorie'   => 'Linge de maison',
                'taille'      => 'Unique',
                'matiere'     => 'Coton',
                'etat'        => 'Neuf',
                'couleur'     => 'Rouge',
            ],
            [
                'titre'       => 'Robe Courte Coton Femme — Broderie Anglaise Upcyclée',
                'description' => 'Robe courte en coton upcyclé ornée de broderie anglaise délicate. Encolure carrée, manches bouffantes. Chic et romantique pour les soirées estivales.',
                'prix'        => 58.00,
                'stock'       => 6,
                'categorie'   => 'Vetements Femme',
                'taille'      => 'M',
                'matiere'     => 'Coton',
                'etat'        => 'Neuf',
                'couleur'     => 'Blanc',
            ],
            [
                'titre'       => 'Ceinture Tissu Recyclé — Set de 3 coloris',
                'description' => 'Lot de 3 ceintures en tissu recyclé (noir, bleu marine, bordeaux). Boucle dorée. Idéales pour structurer une tenue. Largeur 3 cm, longueur ajustable 60-95 cm.',
                'prix'        => 19.00,
                'stock'       => 18,
                'categorie'   => 'Accessoires & Sacs',
                'taille'      => 'Unique',
                'matiere'     => 'Coton',
                'etat'        => 'Neuf',
                'couleur'     => 'Noir',
            ],
        ]);

        // =====================================================
        // 5. QUELQUES ARTICLES EN TEXTILE UPCYCLÉ UNIQUE
        // =====================================================
        $this->creerArticles($atelier0, [
            [
                'titre'       => 'Tableau Textile Upcyclé — Paysage de Sidi Bou Saïd',
                'description' => 'Œuvre textile unique représentant Sidi Bou Saïd. Techniques mixtes : broderie, collage de tissus recyclés, fils dorés. Format 40x60 cm, livré avec cadre en bois récupéré.',
                'prix'        => 110.00,
                'stock'       => 1,
                'categorie'   => 'Textile Upcycle',
                'taille'      => 'Unique',
                'matiere'     => 'Mixte',
                'etat'        => 'Neuf',
                'couleur'     => 'Bleu',
            ],
        ]);

        $this->creerArticles($atelier2, [
            [
                'titre'       => 'Macramé Décoratif Recyclé — Boho Naturel',
                'description' => 'Suspension murale en macramé réalisée à partir de ficelles et cordes recyclées. Style boho naturel, nœuds faits main. Hauteur 80 cm, largeur 40 cm. Pièce unique.',
                'prix'        => 48.00,
                'stock'       => 2,
                'categorie'   => 'Textile Upcycle',
                'taille'      => 'Unique',
                'matiere'     => 'Coton',
                'etat'        => 'Neuf',
                'couleur'     => 'Beige',
            ],
        ]);

        $this->command->info('✅ MarketplaceSeeder terminé !');
        $this->command->info('   👤 Admin          : admin@retiss.tn / password');
        $this->command->info('   🧵 Ateliers (5)   : atelier.elfil@retiss.tn, recyclerie.carthage@retiss.tn, ...');
        $this->command->info('   🛍  Clients (5)   : yasmine@retiss.tn, omar.trabelsi@retiss.tn, ...');
        $this->command->info('   📦 Articles créés : ' . Article::count());
        $this->command->info('   📋 Mot de passe   : password (pour tous les comptes)');
    }

    // =========================================================
    // HELPER — Création en lot d'articles pour un atelier
    // =========================================================
    private function creerArticles(User $atelier, array $articles): void
    {
        foreach ($articles as $data) {
            Article::firstOrCreate(
                [
                    'user_id' => $atelier->id,
                    'titre'   => $data['titre'],
                ],
                array_merge($data, [
                    'user_id'         => $atelier->id,
                    'statut'          => Article::STATUT_DISPONIBLE,
                    'prix_estime_ia'  => round($data['prix'] * (rand(90, 110) / 100), 2),
                    'co2_economise_kg'=> round(rand(15, 80) / 10, 1),
                ])
            );
        }
    }
}
