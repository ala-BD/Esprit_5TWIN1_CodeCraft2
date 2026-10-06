<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Vêtements Homme',
            'Vêtements Femme',
            'Vêtements Enfant',
            'Accessoires & Sacs',
            'Linge de maison',
            'Textile Upcyclé',
        ];

        $prix = fake()->randomFloat(2, 5, 200);

        return [
            'user_id'         => User::factory(),
            'don_vetement_id' => null,
            'titre'           => fake()->sentence(3),
            'description'     => fake()->paragraph(),
            'prix'            => $prix,
            'prix_estime_ia'  => round($prix * fake()->randomFloat(2, 0.8, 1.2), 2),
            'stock'           => fake()->numberBetween(1, 10),
            'categorie'       => fake()->randomElement($categories),
            'statut'          => 'DISPONIBLE',
        ];
    }

    /**
     * Indique que l'article est vendu.
     */
    public function vendu(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'VENDU',
            'stock'  => 0,
        ]);
    }
}
