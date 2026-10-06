<?php

namespace Database\Factories;

use App\Models\Atelier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Atelier>
 */
class AtelierFactory extends Factory
{
    const VILLES = ['Tunis', 'La Marsa', 'Ariana', 'Sousse', 'Sfax', 'Nabeul', 'Monastir', 'Bizerte', 'Kairouan'];

    const PRESENTATIONS = [
        'Atelier de couture engagé : chaque vêtement mérite une seconde vie.',
        'Créations uniques à partir de textiles récupérés, cousues à la main.',
        'Retouche créative et transformation sur mesure, fournitures recyclées.',
        'Savoir-faire artisanal tunisien au service de la mode durable.',
    ];

    public function definition(): array
    {
        return [
            'user_id'       => User::factory()->state(['role' => User::ROLE_ATELIER]),
            'nom'           => fake()->randomElement([
                'Dar El Khayata', 'Atelier Yasmine', 'Fil & Aiguille', 'Couture Carthage', 'Atelier Sidi Bou',
                'Les Mains de Nabeul', 'Atelier El Medina', 'Retouche Hammamet', 'Atelier Jasmin', 'Point de Croix Sousse',
            ]),
            'specialite'    => fake()->randomElement(array_keys(Atelier::SPECIALITES)),
            'description'   => fake()->randomElement(self::PRESENTATIONS),
            'portfolio_url' => null,
            'photo'         => null,
            'tarif_horaire' => fake()->numberBetween(10, 30),
            'localisation'  => fake()->randomElement(self::VILLES),
            'note_moyenne'  => 0,
            'actif'         => true,
        ];
    }

    public function specialite(string $specialite): static
    {
        return $this->state(['specialite' => $specialite]);
    }

    public function inactif(): static
    {
        return $this->state(['actif' => false]);
    }
}
