<?php

namespace Database\Seeders;

use App\Models\EtapeTraitement;
use App\Models\LotTextile;
use App\Models\PasseportNumerique;
use App\Models\Recycleur;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RecyclageSeeder extends Seeder
{
    public function run(): void
    {
        // -------------------------------------------------------
        // 1. Utilisateur RECYCLEUR de test
        // -------------------------------------------------------
        $user = User::firstOrCreate(
            ['email' => 'recycleur@retiss.tn'],
            [
                'name'      => 'Mansouri',
                'prenom'    => 'Karim',
                'telephone' => '21655000001',
                'role'      => 'RECYCLEUR',
                'actif'     => true,
                'password'  => Hash::make('Password123!'),
            ]
        );

        // -------------------------------------------------------
        // 2. Profil Recycleur
        // -------------------------------------------------------
        $recycleur = Recycleur::firstOrCreate(
            ['user_id' => $user->id],
            [
                'nom'          => 'EcoTextile Mansouri',
                'agrement'     => 'AGR-TN-2026-001',
                'capacite_kg'  => 5000,
                'localisation' => 'Zone industrielle Bir El Bey, Tunis',
                'actif'        => true,
            ]
        );

        // -------------------------------------------------------
        // 3. Lot 1 — EN ATTENTE (aucune étape)
        // -------------------------------------------------------
        LotTextile::firstOrCreate(
            ['reference' => 'LOT-2026-001'],
            [
                'recycleur_id'           => $recycleur->id,
                'poids_kg'               => 25.5,
                'composition'            => '60% coton, 40% polyester',
                'origine'               => 'Point de collecte Ariana',
                'filiere_recommandee_ia' => 'RECYCLAGE_FIBRE',
                'statut'                => 'EN_ATTENTE',
            ]
        );

        // -------------------------------------------------------
        // 4. Lot 2 — EN TRAITEMENT (3 étapes dont 2 terminées)
        // -------------------------------------------------------
        $lot2 = LotTextile::firstOrCreate(
            ['reference' => 'LOT-2026-002'],
            [
                'recycleur_id'           => $recycleur->id,
                'poids_kg'               => 48.0,
                'composition'            => '100% laine',
                'origine'               => 'Point de collecte Tunis Centre',
                'filiere_recommandee_ia' => 'UPCYCLING',
                'statut'                => 'EN_TRAITEMENT',
            ]
        );

        if ($lot2->etapeTraitements()->count() === 0) {
            EtapeTraitement::create([
                'lot_textile_id'   => $lot2->id,
                'type'             => 'RECEPTION',
                'date_debut'       => now()->subDays(5),
                'date_fin'         => now()->subDays(5)->addHours(2),
                'resultat'         => 'Lot réceptionné, pesée confirmée : 48 kg',
                'poids_sortant_kg' => 48.0,
            ]);
            EtapeTraitement::create([
                'lot_textile_id'   => $lot2->id,
                'type'             => 'TRI',
                'date_debut'       => now()->subDays(4),
                'date_fin'         => now()->subDays(4)->addHours(3),
                'resultat'         => 'Tri effectué : 45 kg laine pure, 3 kg mixte',
                'poids_sortant_kg' => 45.0,
            ]);
            EtapeTraitement::create([
                'lot_textile_id'   => $lot2->id,
                'type'             => 'NETTOYAGE',
                'date_debut'       => now()->subDays(2),
                'date_fin'         => null,
                'resultat'         => null,
                'poids_sortant_kg' => null,
            ]);
        }

        // -------------------------------------------------------
        // 5. Lot 3 — TRAITE (toutes les 6 étapes terminées)
        // -------------------------------------------------------
        $lot3 = LotTextile::firstOrCreate(
            ['reference' => 'LOT-2026-003'],
            [
                'recycleur_id'           => $recycleur->id,
                'poids_kg'               => 120.0,
                'composition'            => '80% polyester, 20% élasthanne',
                'origine'               => 'Point de collecte Sfax',
                'filiere_recommandee_ia' => 'RECYCLAGE_FIBRE',
                'statut'                => 'TRAITE',
            ]
        );

        if ($lot3->etapeTraitements()->count() === 0) {
            $types = array_keys(EtapeTraitement::ORDRE);
            $debut = now()->subDays(15);
            $poids = 120.0;
            foreach ($types as $i => $type) {
                $poids = round($poids * 0.97, 1); // perte ~3% par étape
                EtapeTraitement::create([
                    'lot_textile_id'   => $lot3->id,
                    'type'             => $type,
                    'date_debut'       => $debut->copy()->addDays($i * 2),
                    'date_fin'         => $debut->copy()->addDays($i * 2)->addHours(4),
                    'resultat'         => EtapeTraitement::LABELS[$type] . ' complétée avec succès.',
                    'poids_sortant_kg' => $poids,
                ]);
            }
        }

        // -------------------------------------------------------
        // 6. Lot 4 — CERTIFIE (passeport généré)
        // -------------------------------------------------------
        $lot4 = LotTextile::firstOrCreate(
            ['reference' => 'LOT-2026-004'],
            [
                'recycleur_id'           => $recycleur->id,
                'poids_kg'               => 30.0,
                'composition'            => '100% coton biologique',
                'origine'               => 'Collecte Monastir',
                'filiere_recommandee_ia' => 'REVENTE',
                'statut'                => 'CERTIFIE',
            ]
        );

        if ($lot4->etapeTraitements()->count() === 0) {
            $types = array_keys(EtapeTraitement::ORDRE);
            $debut = now()->subDays(30);
            $poids = 30.0;
            foreach ($types as $i => $type) {
                $poids = round($poids * 0.98, 1);
                EtapeTraitement::create([
                    'lot_textile_id'   => $lot4->id,
                    'type'             => $type,
                    'date_debut'       => $debut->copy()->addDays($i),
                    'date_fin'         => $debut->copy()->addDays($i)->addHours(3),
                    'resultat'         => EtapeTraitement::LABELS[$type] . ' — OK',
                    'poids_sortant_kg' => $poids,
                ]);
            }
        }

        if (!$lot4->passeportNumerique) {
            PasseportNumerique::genererPourLot($lot4);
        }

        $this->command->info('✓ RecyclageSeeder exécuté avec succès !');
        $this->command->info('  Utilisateur : recycleur@retiss.tn / Password123!');
        $this->command->info('  4 lots créés : EN_ATTENTE, EN_TRAITEMENT, TRAITE, CERTIFIE');
    }
}
