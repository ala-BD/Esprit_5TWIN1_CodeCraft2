<?php

namespace Database\Factories;

use App\Models\PointCollecte;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PointCollecte>
 */
class PointCollecteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nom' => fake()->company() . ' - Collecte',
            'adresse' => fake()->streetAddress(),
            'ville' => fake()->randomElement(['Tunis', 'Ariana', 'Sfax', 'Sousse', 'Monastir']),
            'latitude' => fake()->latitude(33.8, 37.4),
            'longitude' => fake()->longitude(8.0, 11.5),
            'capacite_max_kg' => fake()->numberBetween(200, 1500),
            'telephone' => fake()->phoneNumber(),
            'statut' => fake()->randomElement(['ACTIF', 'INACTIF', 'PLEIN']),
            'description' => fake()->sentence(8),
        ];
    }
}
