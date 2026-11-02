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
                'images'      => [
                    'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1572804013309-59a88b7e92f1?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1564257631407-4deb1f99d992?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1583496661160-fb5886a0aaaa?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1544441893-675973e31985?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1597484661643-2f5fef640dd1?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1622560480605-d83c853bc5c3?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1509551388413-e18d0ac5d495?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1601924994987-69e26d50dc26?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1520903920243-00d872a2d1c9?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1507652313519-d4e9174996dd?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1584100936595-c0654b55a2e2?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1518831959646-742c3a14ebf7?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1622290291468-a28f7a7dc6a8?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1519457431-44ccd64a579b?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1522771930-78848d9293e8?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1580301762395-21ce84d00bc6?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1600121848594-d8644e57abab?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1548883354-7622d03aca27?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1506629082955-511b1aa562c8?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1598033129183-c4f50c736f10?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1607345366928-199ea26cfe3e?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1578587018452-892bacefd3f2?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1502716119720-b23a93e5fe1b?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1583496661160-fb5886a0aaaa?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1582142407894-ec85a1260a46?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1539533018447-63fcce667883?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1544441893-675973e31985?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1514090458221-65bb69cf63e6?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1503919545889-aef636e10ad4?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1528458909336-e7a0adfed0a5?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1578749556568-bc2c40e68b61?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1595777457583-95e059d581b8?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1568252542512-9fe8fe9c87bb?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1624222247344-550fb60583dc?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1582562124811-c09040d0a901?auto=format&fit=crop&w=800&q=80',
                ],
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
                'images'      => [
                    'https://images.unsplash.com/photo-1534349762230-e0cadf78f5da?auto=format&fit=crop&w=800&q=80',
                    'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?auto=format&fit=crop&w=800&q=80',
                ],
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
            Article::updateOrCreate(
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
