<?php

namespace Database\Factories;

use App\Models\Adresse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Adresse>
 */
class AdresseFactory extends Factory
{
    protected $model = Adresse::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'libelle'     => fake()->randomElement(['Maison', 'Travail', 'Atelier', 'Dépôt', 'Bureau']),
            'rue'         => fake()->streetAddress(),
            'ville'       => fake()->city(),
            'code_postal' => fake()->postcode(),
            'latitude'    => fake()->latitude(36.0, 37.0),
            'longitude'   => fake()->longitude(9.0, 11.0),
            'par_defaut'  => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'par_defaut' => true,
        ]);
    }
}
