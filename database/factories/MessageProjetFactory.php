<?php

namespace Database\Factories;

use App\Models\MessageProjet;
use App\Models\ProjetUpcycling;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageProjet>
 */
class MessageProjetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'projet_upcycling_id' => ProjetUpcycling::factory(),
            'user_id'             => null,
            'auteur'              => MessageProjet::AUTEUR_CLIENT,
            'contenu'             => fake()->randomElement([
                'Bonjour, est-ce possible de garder les poches d\'origine ?',
                'Je préfère une doublure de couleur claire si possible.',
                'Merci, j\'ai hâte de voir le résultat !',
            ]),
        ];
    }

    /** L'auteur humain est déduit du projet : son client ou son atelier */
    public function configure(): static
    {
        return $this->afterMaking(function (MessageProjet $message) {
            if ($message->user_id || $message->auteur === MessageProjet::AUTEUR_IA) return;

            $projet = $message->projetUpcycling;
            $message->user_id = $message->auteur === MessageProjet::AUTEUR_ATELIER
                ? $projet?->atelier?->user_id
                : $projet?->client_id;
        });
    }

    /** Message écrit par l'atelier du projet */
    public function parAtelier(): static
    {
        return $this->state([
            'auteur'  => MessageProjet::AUTEUR_ATELIER,
            'contenu' => fake()->randomElement([
                'Bonjour, oui c\'est tout à fait possible, je les intègre au modèle.',
                'Le tissu est en bon état, la découpe commence demain.',
                'Je vous envoie une photo dès que l\'assemblage est terminé.',
            ]),
        ]);
    }

    /** Réponse du styliste IA */
    public function parIa(): static
    {
        return $this->state([
            'auteur'  => MessageProjet::AUTEUR_IA,
            'user_id' => null,
            'contenu' => "Bonne idée ! Pour ce type de projet :\n- privilégiez une doublure en coton lavable\n- renforcez les coutures des anses\n- lavez à 30 °C pour préserver la couleur",
        ]);
    }
}
