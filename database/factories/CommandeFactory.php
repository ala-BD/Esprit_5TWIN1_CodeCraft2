<?php

namespace Database\Factories;

use App\Models\Commande;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Commande>
 */
class CommandeFactory extends Factory
{
    protected $model = Commande::class;

    public function definition(): array
    {
        return [
            'user_id'                => User::factory(),
            'numero'                 => Commande::genererNumero(),
            'statut'                 => Commande::STATUT_EN_ATTENTE,
            'montant_sous_total'     => 50.00,
            'remise'                 => 0.00,
            'frais_livraison'        => Commande::FRAIS_LIVRAISON,
            'montant_total'          => 57.00,
            'mode_paiement'          => 'CARTE',
            'date_livraison_estimee' => now()->addDays(4)->toDateString(),
            'date_commande'          => now(),
            'historique_statuts'     => [
                [
                    'statut'      => Commande::STATUT_EN_ATTENTE,
                    'date'        => now()->toIso8601String(),
                    'commentaire' => 'Commande initiée',
                ]
            ],
        ];
    }
}
