<?php

namespace Database\Factories;

use App\Models\DonVetement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DonVetement>
 */
class DonVetementFactory extends Factory
{
    protected $model = DonVetement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'            => User::factory(),
            'type'               => fake()->randomElement(['Chemise', 'Pantalon', 'Veste', 'Robe', 'T-shirt']),
            'matiere'            => fake()->randomElement(['Coton', 'Laine', 'Lin', 'Polyester', 'Soie']),
            'taille'             => fake()->randomElement(['S', 'M', 'L', 'XL', 'Unique']),
            'etat'               => fake()->randomElement(['NEUF', 'BON_ETAT', 'USAGE', 'A_RECYCLER']),
            'photo_url'          => null,
            'qr_code'            => 'DON-' . strtoupper(Str::random(10)),
            'statut'             => 'DEPOSE',
            'date_depot'         => now()->subDays(fake()->numberBetween(1, 30)),
            'categorie_ia'       => 'Vêtements',
            'score_confiance_ia' => 0.95,
        ];
    }
}
