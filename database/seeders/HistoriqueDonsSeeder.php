<?php

namespace Database\Seeders;

use App\Models\Adresse;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * M5 Logistique — Historique de dons pour la prévision des volumes.
 *
 * Environ quinze mois de dons déjà traités (vendus, transformés ou recyclés),
 * répartis sur quatre gouvernorats du Grand Tunis, avec une croissance régulière
 * et des saisons marquées : grands rangements au printemps et à la rentrée, creux en août.
 * Données générées de façon reproductible (graine fixe) : la démonstration est la même pour tous.
 */
class HistoriqueDonsSeeder extends Seeder
{
    private const SEMAINES = 66;

    /** gouvernorat => [part des dons, ville, code postal, latitude, longitude] */
    private const ZONES = [
        'Tunis'     => [0.36, 'Tunis',      '1000', 36.8065, 10.1815],
        'Ariana'    => [0.26, 'Ariana',     '2080', 36.8625, 10.1956],
        'Ben Arous' => [0.24, 'Ben Arous',  '2013', 36.7531, 10.2189],
        'Manouba'   => [0.14, 'La Manouba', '2010', 36.8101, 10.0956],
    ];

    /** Coefficient saisonnier par mois */
    private const SAISONS = [1 => 0.90, 2 => 0.92, 3 => 1.15, 4 => 1.25, 5 => 1.05, 6 => 0.95,
        7 => 0.80, 8 => 0.65, 9 => 1.25, 10 => 1.35, 11 => 1.10, 12 => 1.00];

    private const VETEMENTS = [['Veste', 'Laine'], ['Jean', 'Coton'], ['Robe', 'Lin'], ['Manteau', 'Polyester'],
        ['Chemise', 'Coton'], ['Pull', 'Laine'], ['T-shirt', 'Coton'], ['Pantalon', 'Polyester']];

    public function run(): void
    {
        // Donateurs « historiques » : quatre par gouvernorat
        $donateurs = [];
        foreach (self::ZONES as $gouvernorat => [, $ville, $code, $lat, $lng]) {
            for ($i = 1; $i <= 4; $i++) {
                $email = 'historique.' . strtolower(str_replace(' ', '', $gouvernorat)) . ".{$i}@demo.retiss.tn";
                $user = User::firstOrCreate(['email' => $email], [
                    'name' => "Donateur {$i}", 'prenom' => $gouvernorat, 'role' => 'DONATEUR', 'actif' => true,
                    'password' => Hash::make('Password123!'), 'email_verified_at' => now(),
                ]);
                Adresse::firstOrCreate(['user_id' => $user->id, 'par_defaut' => true], [
                    'libelle' => 'Domicile', 'rue' => "{$i} Rue de la République", 'code_postal' => $code, 'ville' => $ville,
                    'gouvernorat' => $gouvernorat, 'latitude' => $lat + $i / 500, 'longitude' => $lng - $i / 500,
                ]);
                $donateurs[$gouvernorat][] = $user->id;
            }
        }

        // Déjà généré : on ne double pas l'historique
        if (DB::table('don_vetements')->whereIn('user_id', collect($donateurs)->flatten())->exists()) {
            return;
        }

        mt_srand(42);
        $lignes = [];
        $debut = today()->startOfWeek()->subWeeks(self::SEMAINES);

        for ($s = 0; $s < self::SEMAINES; $s++) {
            $lundi = $debut->copy()->addWeeks($s);
            // Croissance de la plateforme : de 14 à environ 24 dons par semaine sur la période
            $moyenne = (14 + $s * 0.15) * self::SAISONS[$lundi->copy()->addDays(3)->month];

            foreach (self::ZONES as $gouvernorat => [$part]) {
                $attendu = $moyenne * $part;
                $nombre = max(0, (int) round($attendu + $this->normale() * sqrt($attendu)));

                for ($k = 0; $k < $nombre; $k++) {
                    $jour = $lundi->copy()->addDays(mt_rand(0, 6));
                    [$type, $matiere] = self::VETEMENTS[mt_rand(0, count(self::VETEMENTS) - 1)];
                    $lignes[] = [
                        'user_id'    => $donateurs[$gouvernorat][mt_rand(0, 3)],
                        'type'       => $type,
                        'matiere'    => $matiere,
                        'taille'     => ['S', 'M', 'L', 'XL'][mt_rand(0, 3)],
                        'etat'       => ['BON', 'TRES_BON', 'USE'][mt_rand(0, 2)],
                        // Les dons anciens ont fini leur parcours ; les plus récents sont encore au tri
                        'statut'     => $jour->lt(today()->subWeeks(2)) ? ['VENDU', 'UPCYCLING', 'RECYCLE'][mt_rand(0, 2)] : 'EN_TRI',
                        'date_depot' => $jour->format('Y-m-d'),
                        'created_at' => $jour->copy()->setTime(10, 0),
                        'updated_at' => $jour->copy()->setTime(10, 0),
                    ];
                }
            }
        }

        foreach (array_chunk($lignes, 300) as $paquet) {
            DB::table('don_vetements')->insert($paquet);
        }
    }

    /** Tirage selon une loi normale centrée réduite (Box-Muller) */
    private function normale(): float
    {
        $u = max(mt_rand() / mt_getrandmax(), 1e-9);
        $v = mt_rand() / mt_getrandmax();

        return sqrt(-2 * log($u)) * cos(2 * M_PI * $v);
    }
}
