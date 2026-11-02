<?php

namespace Database\Seeders;

use App\Models\Adresse;
use App\Models\Article;
use App\Models\Commande;
use App\Models\DonVetement;
use App\Models\Mission;
use App\Models\PositionCollecteur;
use App\Models\Tournee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * M5 Logistique — jeu de démonstration réaliste sur le Grand Tunis.
 *
 * Donateurs et clients à de vraies adresses (coordonnées GPS exactes), dons à
 * collecter et commandes à livrer, puis des tournées qui s'appuient dessus.
 * Une partie reste volontairement non planifiée : c'est la file « À planifier ».
 */
class LogistiqueSeeder extends Seeder
{
    /** [prénom, nom, rue, code postal, ville, gouvernorat, latitude, longitude] */
    private const DONATEURS = [
        ['Leila',   'Ben Ammar', '12 Rue de Marseille',          '1000', 'Tunis',          'Tunis',    36.8008, 10.1815],
        ['Hichem',  'Jaziri',    '5 Rue Ibn Khaldoun',           '2080', 'Ariana',         'Ariana',   36.8625, 10.1956],
        ['Sonia',   'Mejri',     '23 Avenue Hédi Nouira',        '2037', 'Ennasr 2',       'Ariana',   36.8601, 10.1622],
        ['Karim',   'Belhadj',   '8 Rue Habib Thameur',          '2000', 'Le Bardo',       'Tunis',    36.8092, 10.1342],
        ['Mouna',   'Ferchichi', '17 Rue de Carthage',           '2015', 'Le Kram',        'Tunis',    36.8333, 10.3167],
        ['Anis',    'Gharbi',    '4 Rue Ali Belhouane',          '2070', 'La Marsa',       'Tunis',    36.8782, 10.3247],
    ];

    private const CLIENTS = [
        ['Rania',   'Toumi',     '30 Avenue de la Liberté',      '1002', 'Lafayette',      'Tunis',    36.8105, 10.1830],
        ['Fares',   'Ayadi',     '11 Rue du Lac Léman',          '1053', 'Les Berges du Lac', 'Tunis', 36.8320, 10.2330],
        ['Ines',    'Kammoun',   '9 Avenue Farhat Hached',       '2040', 'Radès',          'Ben Arous', 36.7686, 10.2753],
        ['Walid',   'Sassi',     '21 Rue de Rome',               '2033', 'Mégrine',        'Ben Arous', 36.7700, 10.2330],
        ['Amel',    'Chebbi',    '6 Rue Hédi Zarrouk',           '2026', 'Sidi Bou Saïd',  'Tunis',    36.8687, 10.3417],
        ['Yassine', 'Dridi',     '14 Avenue Habib Bourguiba',    '2060', 'La Goulette',    'Tunis',    36.8183, 10.3050],
        ['Olfa',    'Hamdi',     '3 Rue El Moez',                '2091', 'El Menzah 6',    'Ariana',   36.8460, 10.1750],
        ['Slim',    'Bouaziz',   "25 Avenue de l'Indépendance",  '2000', 'Le Bardo',       'Tunis',    36.8110, 10.1390],
        ['Nadia',   'Zouari',    '7 Rue de Palestine',           '1073', 'Montplaisir',    'Tunis',    36.8195, 10.1950],
        ['Mehdi',   'Khelil',    '19 Rue Ibn Sina',              '2013', 'Ben Arous',      'Ben Arous', 36.7531, 10.2189],
        // Clients de la tournée de démonstration de l'optimisation (dispersés sur le Grand Tunis)
        ['Hela',    'Mabrouk',   '12 Rue de la Liberté',         '2010', 'La Manouba',     'Manouba',  36.8101, 10.0956],
        ['Sami',    'Tlili',     '5 Avenue de la République',    '2034', 'Ezzahra',        'Ben Arous', 36.7436, 10.3083],
        ['Rim',     'Jlassi',    '8 Rue des Orangers',           '2081', 'Ariana Soghra',  'Ariana',   36.8800, 10.1950],
        ['Bilel',   'Hammami',   "17 Avenue de l'Environnement", '2074', 'El Mourouj',     'Ben Arous', 36.7300, 10.2100],
        ['Dorra',   'Sfar',      '3 Rue de la Corniche',         '2078', 'Gammarth',       'Tunis',    36.9180, 10.2880],
        ['Aymen',   'Nasri',     '10 Rue Ibn Rachiq',            '2011', 'Den Den',        'Manouba',  36.8030, 10.1100],
    ];

    private const VETEMENTS = [
        ['Veste', 'Laine', 'M'], ['Jean', 'Coton', '40'], ['Robe', 'Lin', 'S'],
        ['Manteau', 'Polyester', 'L'], ['Chemise', 'Coton', 'XL'], ['Pull', 'Laine', 'M'],
    ];

    public function run(): void
    {
        // -------------------------------------------------------
        // 1. Collecteurs
        // -------------------------------------------------------
        $youssef = $this->utilisateur('collecteur@retiss.tn', 'Youssef', 'Trabelsi', 'COLLECTEUR', '21655000002');
        $nour    = $this->utilisateur('nour.collecte@retiss.tn', 'Nour', 'Haddad', 'COLLECTEUR', '21655000003');

        // -------------------------------------------------------
        // 2. Donateurs avec un don à collecter, clients avec une commande à livrer
        // -------------------------------------------------------
        $dons = collect(self::DONATEURS)->map(function (array $p, int $i) {
            $user = $this->utilisateur($this->email($p), $p[0], $p[1], 'DONATEUR', '2165510' . str_pad($i, 4, '0', STR_PAD_LEFT));
            $this->adresse($user, $p);

            return DonVetement::firstOrCreate(['user_id' => $user->id], [
                'type'       => self::VETEMENTS[$i % count(self::VETEMENTS)][0],
                'matiere'    => self::VETEMENTS[$i % count(self::VETEMENTS)][1],
                'taille'     => self::VETEMENTS[$i % count(self::VETEMENTS)][2],
                'etat'       => 'BON',
                'statut'     => 'DEPOSE',
                'date_depot' => today()->subDays(6 - $i),
            ]);
        });

        $article = Article::where('prix', '>', 0)->orderBy('id')->first();

        $commandes = collect(self::CLIENTS)->map(function (array $p, int $i) use ($article) {
            $user    = $this->utilisateur($this->email($p), $p[0], $p[1], 'CLIENT', '2165520' . str_pad($i, 4, '0', STR_PAD_LEFT));
            $adresse = $this->adresse($user, $p);

            return $user->commandes()->first() ?? $this->commande($user, $adresse, $article, $i);
        });

        // -------------------------------------------------------
        // 3. Tournées
        // -------------------------------------------------------

        // Hier : terminée, avec une livraison échouée
        $this->tournee($youssef, today()->subDay(), 'Ariana — Ennasr', 'Camionnette 123 TU 4567', 'TERMINEE', [
            ['COLLECTE',  'don',      $dons[1],      '09:00', 'TERMINEE', '2 sacs collectés'],
            ['COLLECTE',  'don',      $dons[2],      '09:40', 'TERMINEE', '1 sac collecté'],
            ['LIVRAISON', 'commande', $commandes[6], '10:30', 'ECHOUEE',  'Client absent'],
        ]);

        // Aujourd'hui : en cours dans le centre et le Lac
        $this->tournee($youssef, today(), 'Tunis Centre — Lac', 'Camionnette 123 TU 4567', 'EN_COURS', [
            ['COLLECTE',  'don',      $dons[0],      '08:30', 'TERMINEE', '3 sacs collectés'],
            ['LIVRAISON', 'commande', $commandes[0], '09:15', 'TERMINEE', 'Remis en main propre'],
            ['LIVRAISON', 'commande', $commandes[8], '10:00', 'EN_COURS', null],
            ['LIVRAISON', 'commande', $commandes[1], '11:00', 'A_FAIRE',  null],
            ['COLLECTE',  'adresse',  ['Atelier Fil Vert, Rue Bab El Khadra, Tunis', 36.8090, 10.1700], '14:00', 'A_FAIRE', null],
        ]);

        // Aujourd'hui, autre collecteur : Bardo
        $this->tournee($nour, today(), 'Le Bardo', 'Fourgon 87 TU 2210', 'EN_COURS', [
            ['COLLECTE',  'don',      $dons[3],      '09:00', 'TERMINEE', '2 sacs collectés'],
            ['LIVRAISON', 'commande', $commandes[7], '10:15', 'A_FAIRE',  null],
        ]);

        // Demain : six livraisons saisies dans le désordre — de quoi montrer l'optimisation d'itinéraire
        $this->tournee($nour, today()->addDay(), 'Grand Tunis', 'Fourgon 87 TU 2210', 'PLANIFIEE', [
            ['LIVRAISON', 'commande', $commandes[14], '09:00', 'A_FAIRE', null],   // Gammarth
            ['LIVRAISON', 'commande', $commandes[13], '09:30', 'A_FAIRE', null],   // El Mourouj
            ['LIVRAISON', 'commande', $commandes[12], '10:00', 'A_FAIRE', null],   // Ariana Soghra
            ['LIVRAISON', 'commande', $commandes[11], '10:30', 'A_FAIRE', null],   // Ezzahra
            ['LIVRAISON', 'commande', $commandes[15], '11:00', 'A_FAIRE', null],   // Den Den
            ['LIVRAISON', 'commande', $commandes[10], '11:30', 'A_FAIRE', null],   // La Manouba
        ]);

        // Dans deux jours : planifiée sur la côte nord
        $this->tournee($youssef, today()->addDays(2), 'La Marsa — Carthage', 'Fourgon 87 TU 2210', 'PLANIFIEE', [
            ['COLLECTE',  'don',      $dons[5],      '09:30', 'A_FAIRE',  null],
            ['LIVRAISON', 'commande', $commandes[4], '10:30', 'A_FAIRE',  null],
        ]);

        // Quinze mois d'historique de dons pour la prévision des volumes
        $this->call(HistoriqueDonsSeeder::class);

        // Dernières positions GPS : l'un en direct près de Montplaisir, l'autre silencieux depuis 12 minutes
        PositionCollecteur::where('user_id', $youssef->id)->orWhere('user_id', $nour->id)->delete();
        PositionCollecteur::create(['user_id' => $youssef->id, 'latitude' => 36.8168, 'longitude' => 10.1905, 'precision_m' => 12, 'enregistree_le' => now()->subMinutes(1)]);
        PositionCollecteur::create(['user_id' => $nour->id, 'latitude' => 36.8100, 'longitude' => 10.1365, 'precision_m' => 20, 'enregistree_le' => now()->subMinutes(12)]);

        // Non planifiés : dons[4] (Le Kram) et les commandes 2, 3, 5, 9 restent dans « À planifier »
    }

    private function utilisateur(string $email, string $prenom, string $nom, string $role, string $telephone): User
    {
        return User::firstOrCreate(['email' => $email], [
            'name' => $nom, 'prenom' => $prenom, 'telephone' => $telephone, 'role' => $role,
            'actif' => true, 'password' => Hash::make('Password123!'), 'email_verified_at' => now(),
        ]);
    }

    private function email(array $p): string
    {
        return strtolower(\Illuminate\Support\Str::ascii($p[0] . '.' . str_replace(' ', '', $p[1]))) . '@demo.retiss.tn';
    }

    private function adresse(User $user, array $p): Adresse
    {
        return Adresse::firstOrCreate(['user_id' => $user->id, 'rue' => $p[2]], [
            'libelle' => 'Domicile', 'code_postal' => $p[3], 'ville' => $p[4], 'gouvernorat' => $p[5],
            'latitude' => $p[6], 'longitude' => $p[7], 'par_defaut' => true,
        ]);
    }

    private function commande(User $user, Adresse $adresse, ?Article $article, int $i): Commande
    {
        $prix = (float) ($article?->prix ?? 25);

        $commande = Commande::create([
            'user_id'                => $user->id,
            'adresse_id'             => $adresse->id,
            'adresse_snapshot'       => $adresse->formatted_address,
            'numero'                 => Commande::genererNumero(),
            'statut'                 => Commande::STATUT_CONFIRMEE,
            'montant_sous_total'     => $prix,
            'frais_livraison'        => Commande::FRAIS_LIVRAISON,
            'montant_total'          => $prix + Commande::FRAIS_LIVRAISON,
            'mode_paiement'          => 'CARTE',
            'statut_paiement'        => Commande::STATUT_PAIEMENT_PAYE,
            'date_paiement'          => now()->subDays(3 - $i % 3),
            'date_commande'          => now()->subDays(3 - $i % 3),
            'date_livraison_estimee' => today()->addDays(2),
        ]);

        if ($article) {
            $commande->lignes()->create(['article_id' => $article->id, 'quantite' => 1, 'prix_unitaire' => $prix, 'remise' => 0, 'total_ligne' => $prix]);
        }

        $commande->pushHistorique(Commande::STATUT_CONFIRMEE, 'Paiement accepté.');

        return $commande;
    }

    /**
     * Crée la tournée et ses missions si elle n'existe pas encore.
     * Arrêt : [type, lien (don|commande|adresse), élément, heure, statut, preuve].
     */
    private function tournee(User $collecteur, $date, string $zone, string $vehicule, string $statut, array $arrets): void
    {
        // whereDate : la base stocke la date avec une heure, une égalité stricte ne la retrouverait pas
        $tournee = Tournee::where('user_id', $collecteur->id)->whereDate('date', $date)->where('zone', $zone)->first()
            ?? Tournee::create(['user_id' => $collecteur->id, 'date' => $date, 'zone' => $zone, 'vehicule' => $vehicule, 'statut' => $statut]);

        if ($tournee->missions()->exists()) {
            return;
        }

        foreach ($arrets as $i => [$type, $lien, $element, $heure, $statutMission, $preuve]) {
            $mission = [
                'type' => $type, 'ordre' => $i + 1, 'heure_prevue' => $heure,
                'statut' => $statutMission, 'preuve_livraison' => $preuve,
            ];

            // Arrêt clos : terminé une vingtaine de minutes après l'heure prévue
            if (in_array($statutMission, [Mission::STATUT_TERMINEE, Mission::STATUT_ECHOUEE], true)) {
                $mission['terminee_le'] = $date->copy()->setTimeFromTimeString($heure)->addMinutes(18 + $i * 4);
            }

            // Le lien fournit l'adresse et la position (voir LocalisationMission)
            $mission += match ($lien) {
                'don'      => ['don_vetement_id' => $element->id],
                'commande' => ['commande_id' => $element->id],
                default    => ['adresse' => $element[0], 'latitude' => $element[1], 'longitude' => $element[2]],
            };

            $tournee->missions()->create($mission);
        }
    }
}
