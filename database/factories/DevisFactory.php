<?php

namespace Database\Factories;

use App\Models\Devis;
use App\Models\ProjetUpcycling;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Devis>
 */
class DevisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'projet_upcycling_id' => ProjetUpcycling::factory(),
            'montant'             => fake()->numberBetween(25, 150),
            'delai_jours'         => fake()->numberBetween(3, 21),
            'message'             => fake()->randomElement([
                'Fournitures incluses, finitions main.',
                'Doublure en coton bio comprise.',
                'Prix incluant la retouche et le repassage.',
                null,
            ]),
            'statut'              => Devis::STATUT_EN_ATTENTE,
            'date_emission'       => now()->subDays(fake()->numberBetween(0, 5)),
        ];
    }

    public function accepte(): static
    {
        return $this->state(['statut' => Devis::STATUT_ACCEPTE]);
    }

    public function refuse(): static
    {
        return $this->state(['statut' => Devis::STATUT_REFUSE]);
    }
}
