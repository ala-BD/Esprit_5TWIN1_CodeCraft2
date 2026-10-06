<?php

namespace Database\Factories;

use App\Models\Atelier;
use App\Models\ProjetUpcycling;
use App\Models\User;
use App\Services\Upcycling\IdeeUpcyclingService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjetUpcycling>
 */
class ProjetUpcyclingFactory extends Factory
{
    /**
     * Vêtements de démonstration (photos versionnées dans public/images/upcycling).
     * [photo, type, matière, couleur, état, description]
     */
    const VETEMENTS = [
        ['jean-slim.jpg',        'Jean',     'Denim',     'Bleu foncé',  'USE',   "Jean slim porté souvent, un peu délavé aux genoux."],
        ['jean-droit.jpg',       'Jean',     'Denim',     'Bleu clair',  'BON',   "Jean droit devenu trop grand, encore en très bon état."],
        ['jean-flare.jpg',       'Jean',     'Denim',     'Bleu délavé', 'USE',   "Jean flare que je ne porte plus, ourlet abîmé."],
        ['short-jean.jpg',       'Short',    'Denim',     'Bleu',        'USE',   "Short en jean avec quelques effilochures, j'aimerais un accessoire."],
        ['pantalon-bleu.jpg',    'Pantalon', 'Coton',     'Bleu pétrole','USE',   "Pantalon à taille élastique, tissu encore solide."],
        ['chemise-bleue.jpg',    'Chemise',  'Coton',     'Bleu',        'BON',   "Chemise à col contrasté, je veux la garder sous une autre forme."],
        ['tshirt-turquoise.jpg', 'T-shirt',  'Coton',     'Turquoise',   'ABIME', "T-shirt taché sur le devant, couleur que j'adore."],
        ['blazer-marine.jpg',    'Blazer',   'Laine',     'Bleu marine', 'BON',   "Blazer homme classique jamais porté depuis le mariage."],
        ['blazer-noir.jpg',      'Blazer',   'Polyester', 'Noir',        'USE',   "Blazer femme cintré, doublure un peu usée."],
        ['veste-camel.jpg',      'Veste',    'Laine',     'Camel',       'BON',   "Veste de costume camel, coupe un peu démodée."],
    ];

    public function definition(): array
    {
        return $this->attributsVetement(fake()->numberBetween(0, count(self::VETEMENTS) - 1)) + [
            'client_id'  => User::factory()->state(['role' => User::ROLE_CLIENT]),
            'atelier_id' => null,
            'budget_max' => fake()->optional(0.7)->numberBetween(30, 150),
            'statut'     => ProjetUpcycling::STATUT_DEMANDE,
        ];
    }

    /** Choisit un vêtement précis du catalogue de démonstration */
    public function vetement(int $index): static
    {
        return $this->state(fn () => $this->attributsVetement($index));
    }

    /** Le client a retenu une idée (la première, ou celle de la catégorie donnée) */
    public function avecIdee(?string $categorie = null): static
    {
        return $this->state(function (array $attributs) use ($categorie) {
            $idees = collect($attributs['idee_generee_ia']);
            $idee  = ($categorie ? $idees->firstWhere('categorie', $categorie) : null) ?? $idees->first();

            return [
                'produit_final'     => $idee['titre'],
                'categorie_produit' => $idee['categorie'],
                'prix_estime_min'   => $idee['prix_min'],
                'prix_estime_max'   => $idee['prix_max'],
            ];
        });
    }

    /** Un atelier a été choisi, en attente de devis */
    public function pourAtelier(Atelier $atelier): static
    {
        return $this->avecIdee($atelier->specialite)->state([
            'atelier_id' => $atelier->id,
            'statut'     => ProjetUpcycling::STATUT_ATELIER_CHOISI,
        ]);
    }

    /** Travaux en cours (CONCEPTION, CONFECTION ou FINITION) */
    public function enCours(string $statut = ProjetUpcycling::STATUT_CONFECTION): static
    {
        return $this->state([
            'statut'     => $statut,
            'date_debut' => now()->subDays(fake()->numberBetween(3, 10)),
        ]);
    }

    /** Projet terminé et noté par le client */
    public function termine(?int $note = null, ?string $commentaire = null): static
    {
        return $this->state(function () use ($note, $commentaire) {
            $fin = now()->subDays(fake()->numberBetween(2, 40));

            return [
                'statut'             => ProjetUpcycling::STATUT_TERMINE,
                'date_debut'         => $fin->copy()->subDays(fake()->numberBetween(5, 15)),
                'date_fin'           => $fin,
                'note_client'        => $note ?? fake()->numberBetween(3, 5),
                'commentaire_client' => $commentaire ?? fake()->randomElement([
                    'Travail soigné, je recommande !',
                    'Résultat au-delà de mes attentes.',
                    'Très belle transformation, merci.',
                    'Bon travail, livré dans les temps.',
                ]),
            ];
        });
    }

    private function attributsVetement(int $index): array
    {
        [$photo, $type, $matiere, $couleur, $etat, $description] = self::VETEMENTS[$index];
        $ia = app(IdeeUpcyclingService::class);

        return [
            'photo'           => 'images/upcycling/' . $photo,
            'type_vetement'   => $type,
            'matiere'         => $matiere,
            'couleur'         => $couleur,
            'etat'            => $etat,
            'description'     => $description,
            'idee_generee_ia' => $ia->genererLocalement($type, $matiere, $etat),
            'source_ia'       => IdeeUpcyclingService::SOURCE_LOCAL,
            'analyse_ia'      => ['defauts' => []],
        ] + $ia->impact($type);
    }
}
