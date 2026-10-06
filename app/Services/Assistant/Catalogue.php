<?php

namespace App\Services\Assistant;

use App\Models\Mission;
use App\Models\Tournee;
use App\Models\User;

/**
 * Actions que l'assistant vocal peut proposer, selon le rôle de l'utilisateur.
 * Format « tools » des API compatibles OpenAI.
 */
class Catalogue
{
    /** Paramètre technique : la demande n'est pas terminée après cette action */
    public const ENCORE = 'encore';

    /** Outils disponibles pour cet utilisateur (vide si son rôle n'a pas d'assistant) */
    public static function pour(User $user): array
    {
        return match ($user->role) {
            User::ROLE_ADMIN      => self::administration(),
            User::ROLE_COLLECTEUR => self::logistique(),
            default               => [],
        };
    }

    /** Noms des actions autorisées pour cet utilisateur */
    public static function actions(User $user): array
    {
        return array_map(fn (array $outil) => $outil['function']['name'], self::pour($user));
    }

    /*
    |------------------------------------------------------------------
    | Administration — utilisateurs
    |------------------------------------------------------------------
    */
    private static function administration(): array
    {
        // Un utilisateur existant se désigne de préférence par son numéro dans l'annuaire fourni au modèle
        $cible = [
            'utilisateur_id' => ['type' => 'integer', 'description' => "Numéro (#) de l'utilisateur dans l'annuaire"],
            'cible'          => ['type' => 'string', 'description' => "Nom ou e-mail prononcé, seulement si absent de l'annuaire"],
        ];
        $champs = [
            'prenom'    => ['type' => 'string', 'description' => 'Prénom'],
            'nom'       => ['type' => 'string', 'description' => 'Nom de famille'],
            'email'     => ['type' => 'string', 'description' => 'Adresse e-mail'],
            'telephone' => ['type' => 'string', 'description' => 'Chiffres uniquement'],
            'role'      => ['type' => 'string', 'enum' => User::ROLES],
            'actif'     => ['type' => 'boolean', 'description' => 'false pour désactiver le compte'],
        ];

        return [
            self::outil('creer_utilisateur', 'Créer un compte utilisateur. Le mot de passe est généré automatiquement.',
                $champs, ['prenom', 'nom', 'email']),

            self::outil('modifier_utilisateur', "Modifier un utilisateur existant. Ne renseigner que les champs à changer, avec leur nouvelle valeur.",
                $cible + $champs),

            self::outil('supprimer_utilisateur', 'Supprimer définitivement un utilisateur.',
                $cible),

            self::outil('ouvrir_utilisateur', "Afficher la fiche d'un utilisateur.",
                $cible),

            self::outil('rechercher_utilisateurs', 'Afficher la liste des utilisateurs, éventuellement filtrée.', [
                'recherche' => ['type' => 'string', 'description' => 'Texte à chercher dans le nom, le prénom ou l\'e-mail'],
                'role'      => ['type' => 'string', 'enum' => User::ROLES],
                'actif'     => ['type' => 'boolean', 'description' => 'true : comptes actifs seulement, false : comptes désactivés seulement'],
            ]),

            self::outil('ouvrir_page', "Ouvrir une page de l'administration.", [
                'page' => ['type' => 'string', 'enum' => ['utilisateurs', 'nouvel_utilisateur', 'statistiques']],
            ], ['page']),
        ];
    }

    /*
    |------------------------------------------------------------------
    | Logistique — tournées et missions
    |------------------------------------------------------------------
    */
    private static function logistique(): array
    {
        $statutsTournee = array_keys(Tournee::STATUTS);
        $statutsMission = array_keys(Mission::STATUTS);

        // Une tournée existante se désigne par son numéro dans la liste fournie au modèle.
        // (L'exécuteur accepte aussi tournee_date / tournee_zone, non exposés ici pour économiser des jetons.)
        $tournee = [
            'tournee_id' => ['type' => 'integer', 'description' => 'Numéro (#) de la tournée dans la liste ROUNDS'],
        ];

        return [
            self::outil('creer_tournee', 'Planifier une nouvelle tournée.', [
                'date'        => ['type' => 'string', 'description' => 'AAAA-MM-JJ'],
                'zone'        => ['type' => 'string'],
                'vehicule'    => ['type' => 'string'],
                'distance_km' => ['type' => 'number'],
                'statut'      => ['type' => 'string', 'enum' => $statutsTournee],
            ], ['date', 'zone', 'vehicule']),

            self::outil('modifier_tournee', 'Modifier une tournée existante. Ne renseigner que les champs à changer.', $tournee + [
                'nouvelle_date' => ['type' => 'string', 'description' => 'AAAA-MM-JJ'],
                'nouvelle_zone' => ['type' => 'string'],
                'vehicule'      => ['type' => 'string'],
                'distance_km'   => ['type' => 'number'],
                'statut'        => ['type' => 'string', 'enum' => $statutsTournee],
            ]),

            self::outil('supprimer_tournee', 'Supprimer définitivement une tournée et toutes ses missions.', $tournee),

            self::outil('ouvrir_tournee', "Afficher le détail d'une tournée et ses missions.", $tournee),

            self::outil('rechercher_tournees', 'Afficher la liste des tournées, éventuellement filtrée.', [
                'recherche' => ['type' => 'string', 'description' => 'Texte à chercher dans la zone ou le véhicule'],
                'statut'    => ['type' => 'string', 'enum' => $statutsTournee],
            ]),

            self::outil('ajouter_mission', 'Ajouter une collecte ou une livraison à une tournée.', $tournee + [
                'type'         => ['type' => 'string', 'enum' => array_keys(Mission::TYPES)],
                'adresse'      => ['type' => 'string'],
                'heure_prevue' => ['type' => 'string', 'description' => 'HH:MM'],
                'ordre'        => ['type' => 'integer', 'description' => 'Vide = à la fin'],
                'statut'       => ['type' => 'string', 'enum' => $statutsMission],
            ], ['type', 'adresse', 'heure_prevue']),

            self::outil('modifier_mission', "Modifier une mission d'une tournée, désignée par son ordre de passage. Ne renseigner que les champs à changer.", $tournee + [
                'ordre'            => ['type' => 'integer', 'description' => 'Numéro de passage actuel'],
                'nouvel_ordre'     => ['type' => 'integer'],
                'type'             => ['type' => 'string', 'enum' => array_keys(Mission::TYPES)],
                'adresse'          => ['type' => 'string'],
                'heure_prevue'     => ['type' => 'string', 'description' => 'HH:MM'],
                'statut'           => ['type' => 'string', 'enum' => $statutsMission],
                'preuve_livraison' => ['type' => 'string'],
            ], ['ordre']),

            self::outil('supprimer_mission', "Supprimer une mission d'une tournée, désignée par son ordre de passage.", $tournee + [
                'ordre' => ['type' => 'integer', 'description' => 'Numéro de passage'],
            ], ['ordre']),

            self::outil('ouvrir_page', "Ouvrir une page de l'espace logistique.", [
                'page' => ['type' => 'string', 'enum' => ['tournees', 'nouvelle_tournee']],
            ], ['page']),
        ];
    }

    private static function outil(string $nom, string $description, array $proprietes, array $requis = []): array
    {
        // Commande en plusieurs étapes dépendantes (« crée une tournée puis ajoute-lui une collecte ») :
        // le modèle signale qu'il restera quelque chose à faire une fois cette action confirmée.
        if (!str_starts_with($nom, 'ouvrir_') && !str_starts_with($nom, 'rechercher_')) {
            $proprietes[self::ENCORE] = ['type' => 'boolean', 'description' => "true si la demande contient d'autres actions à faire après celle-ci"];
        }

        return [
            'type'     => 'function',
            'function' => [
                'name'        => $nom,
                'description' => $description,
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => (object) $proprietes,
                    'required'   => $requis,
                ],
            ],
        ];
    }
}
