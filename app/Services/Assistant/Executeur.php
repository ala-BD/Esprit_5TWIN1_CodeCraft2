<?php

namespace App\Services\Assistant;

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Logistique\MissionController;
use App\Http\Controllers\Logistique\TourneeController;
use App\Models\Mission;
use App\Models\Tournee;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Prépare puis exécute les actions proposées par l'assistant vocal.
 *
 * Les arguments viennent d'un modèle de langage puis du navigateur : ils ne sont
 * jamais considérés comme fiables. Chaque action repasse par les règles de
 * validation des formulaires et par les mêmes contrôles d'accès.
 *
 * - preparer() : lecture seule. Renvoie une navigation, ou une demande de confirmation.
 * - executer() : effectue réellement l'action confirmée.
 *
 * Toutes les réponses sont rédigées dans la langue dictée (français ou anglais).
 */
class Executeur
{
    private bool $en = false;

    private Carbon $aujourdhui;

    public function preparer(User $user, string $action, array $arguments, string $langue = 'fr', ?string $aujourdhui = null): array
    {
        return $this->lancer($user, $action, $arguments, false, $langue, $aujourdhui);
    }

    public function executer(User $user, string $action, array $arguments, string $langue = 'fr', ?string $aujourdhui = null): array
    {
        return $this->lancer($user, $action, $arguments, true, $langue, $aujourdhui);
    }

    private function lancer(User $user, string $action, array $arguments, bool $executer, string $langue, ?string $aujourdhui): array
    {
        $this->en = $langue === 'en';
        // Date du jour vue par le navigateur : le serveur peut être sur un autre fuseau
        $this->aujourdhui = $aujourdhui ? Carbon::parse($aujourdhui)->startOfDay() : today();

        if (!in_array($action, Catalogue::actions($user), true)) {
            return $this->message($this->t("Cette action n'est pas disponible dans votre espace.", 'This action is not available in your workspace.'));
        }

        try {
            return $this->{Str::camel($action)}($user, $arguments, $executer);
        } catch (Interruption $e) {
            return $this->message($e->getMessage());
        }
    }

    /*
    |------------------------------------------------------------------
    | Administration — utilisateurs
    |------------------------------------------------------------------
    */

    private function creerUtilisateur(User $user, array $a, bool $executer): array
    {
        $donnees = [
            'prenom'    => $this->texte($a, 'prenom'),
            'name'      => $this->texte($a, 'nom'),
            'email'     => $this->email($a),
            'telephone' => $this->telephone($a),
            'role'      => strtoupper($this->texte($a, 'role') ?? User::ROLE_DONATEUR),
        ];
        $actif = $this->booleen($a, 'actif') ?? true;

        $this->valider($donnees, Arr::except(UserController::regles(), 'password'), UserController::MESSAGES);

        $nom = "{$donnees['prenom']} {$donnees['name']}";

        if (!$executer) {
            return $this->confirmation(
                $this->t("Créer l'utilisateur {$nom} ?", "Create the user {$nom}?"),
                [
                    [$this->t('E-mail', 'Email'), $donnees['email']],
                    [$this->t('Téléphone', 'Phone'), $donnees['telephone'] ?? '—'],
                    [$this->t('Rôle', 'Role'), $this->libelleRole($donnees['role'])],
                    [$this->t('Compte', 'Account'), $this->libelleActif($actif)],
                ],
                'creer_utilisateur',
                ['prenom' => $donnees['prenom'], 'nom' => $donnees['name'], 'email' => $donnees['email'],
                 'telephone' => $donnees['telephone'], 'role' => $donnees['role'], 'actif' => $actif],
            );
        }

        // Dicter un mot de passe n'a pas de sens : on en génère un, affiché une seule fois
        $motDePasse = Str::password(12, symbols: false);

        $nouveau = User::create([...$donnees, 'actif' => $actif, 'password' => Hash::make($motDePasse)]);
        $nouveau->forceFill(['email_verified_at' => now()])->save();

        return $this->succes(
            $this->t("Utilisateur {$nouveau->full_name} créé.", "User {$nouveau->full_name} created."),
            route('admin.users.show', $nouveau),
            ['label' => $this->t('Mot de passe temporaire', 'Temporary password'), 'valeur' => $motDePasse],
        );
    }

    private function modifierUtilisateur(User $user, array $a, bool $executer): array
    {
        $cible = $this->trouverUtilisateur($a);

        $changements = array_filter([
            'prenom'    => $this->texte($a, 'prenom'),
            'name'      => $this->texte($a, 'nom'),
            'email'     => $this->email($a),
            'telephone' => $this->telephone($a),
            'role'      => ($role = $this->texte($a, 'role')) ? strtoupper($role) : null,
            'actif'     => $this->booleen($a, 'actif'),
        ], fn ($valeur) => $valeur !== null);

        // On ne garde que ce qui change réellement
        $changements = array_filter($changements, fn ($valeur, $champ) => $cible->{$champ} !== $valeur, ARRAY_FILTER_USE_BOTH);

        if (!$changements) {
            throw new Interruption($this->t(
                "Rien à changer : {$cible->full_name} a déjà ces valeurs. Que faut-il modifier ?",
                "Nothing to change: {$cible->full_name} already has these values. What should I update?"
            ));
        }

        $donnees = [...$cible->only(['prenom', 'name', 'email', 'telephone', 'role', 'actif']), ...$changements];

        // Même garde-fou que le formulaire : un admin ne se rétrograde ni ne se désactive lui-même
        if ($cible->is($user) && ($donnees['role'] !== User::ROLE_ADMIN || !$donnees['actif'])) {
            throw new Interruption($this->t(
                'Vous ne pouvez pas modifier votre propre rôle ni désactiver votre compte.',
                'You cannot change your own role or deactivate your own account.'
            ));
        }

        $this->valider(Arr::except($donnees, 'actif'), Arr::except(UserController::regles($cible), 'password'), UserController::MESSAGES);

        if (!$executer) {
            $libelles = [
                'prenom' => $this->t('Prénom', 'First name'), 'name' => $this->t('Nom', 'Last name'), 'email' => $this->t('E-mail', 'Email'),
                'telephone' => $this->t('Téléphone', 'Phone'), 'role' => $this->t('Rôle', 'Role'), 'actif' => $this->t('Compte', 'Account'),
            ];
            $afficher = fn (string $champ, $valeur) => match ($champ) {
                'role'  => $this->libelleRole((string) $valeur),
                'actif' => $this->libelleActif((bool) $valeur),
                default => $valeur ?: '—',
            };

            return $this->confirmation(
                $this->t("Modifier {$cible->full_name} ?", "Update {$cible->full_name}?"),
                collect($changements)->map(fn ($valeur, $champ) => [
                    $libelles[$champ],
                    $afficher($champ, $cible->{$champ}) . ' → ' . $afficher($champ, $valeur),
                ])->values()->all(),
                'modifier_utilisateur',
                ['utilisateur_id' => $cible->id, ...Arr::except($changements, 'name'), ...(isset($changements['name']) ? ['nom' => $changements['name']] : [])],
            );
        }

        $cible->update($changements);

        return $this->succes(
            $this->t("Utilisateur {$cible->full_name} mis à jour.", "User {$cible->full_name} updated."),
            route('admin.users.show', $cible),
        );
    }

    private function supprimerUtilisateur(User $user, array $a, bool $executer): array
    {
        $cible = $this->trouverUtilisateur($a);

        if ($cible->is($user)) {
            throw new Interruption($this->t('Vous ne pouvez pas supprimer votre propre compte.', 'You cannot delete your own account.'));
        }

        if (!$executer) {
            return $this->confirmation(
                $this->t("Supprimer l'utilisateur {$cible->full_name} ?", "Delete the user {$cible->full_name}?"),
                [[$this->t('E-mail', 'Email'), $cible->email], [$this->t('Rôle', 'Role'), $this->libelleRole($cible->role)]],
                'supprimer_utilisateur',
                ['utilisateur_id' => $cible->id],
                danger: true,
            );
        }

        $nom = $cible->full_name;
        $cible->delete();

        return $this->succes($this->t("Utilisateur {$nom} supprimé.", "User {$nom} deleted."), route('admin.users.index'));
    }

    private function ouvrirUtilisateur(User $user, array $a, bool $executer): array
    {
        $cible = $this->trouverUtilisateur($a);

        return $this->navigation(
            route('admin.users.show', $cible),
            $this->t("Voici la fiche de {$cible->full_name}.", "Here is {$cible->full_name}'s profile."),
        );
    }

    private function rechercherUtilisateurs(User $user, array $a, bool $executer): array
    {
        $role  = strtoupper($this->texte($a, 'role') ?? '');
        $actif = $this->booleen($a, 'actif');

        $filtres = array_filter([
            'q'     => $this->texte($a, 'recherche'),
            'role'  => in_array($role, User::ROLES, true) ? $role : null,
            'actif' => $actif === null ? null : ($actif ? '1' : '0'),
        ], fn ($valeur) => $valeur !== null);

        return $this->navigation(route('admin.users.index', $filtres), $filtres
            ? $this->t('Voici les utilisateurs correspondants.', 'Here are the matching users.')
            : $this->t('Voici tous les utilisateurs.', 'Here are all the users.'));
    }

    /** Retrouve l'utilisateur désigné par son identifiant, son e-mail ou son nom */
    private function trouverUtilisateur(array $a): User
    {
        if (is_numeric($a['utilisateur_id'] ?? null)) {
            return User::find((int) $a['utilisateur_id'])
                ?? throw new Interruption($this->t("Cet utilisateur n'existe plus.", 'This user no longer exists.'));
        }

        $cible = $this->texte($a, 'cible');

        if (!$cible) {
            throw new Interruption($this->t(
                'De quel utilisateur parlez-vous ? Donnez son nom ou son e-mail.',
                'Which user do you mean? Give me their name or email.'
            ));
        }

        if (str_contains($cible, '@')) {
            $trouves = User::where('email', Str::lower(str_replace(' ', '', $cible)))->get();
        } else {
            $requete = User::query();
            foreach (preg_split('/\s+/', $cible, -1, PREG_SPLIT_NO_EMPTY) as $mot) {
                $requete->where(fn ($q) => $q->where('prenom', 'like', "%{$mot}%")->orWhere('name', 'like', "%{$mot}%"));
            }
            $trouves = $requete->limit(6)->get();

            // La dictée déforme souvent les noms propres : on tolère une orthographe approchante
            if ($trouves->isEmpty()) {
                $trouves = $this->utilisateursApprochants($cible);
            }
        }

        if ($trouves->isEmpty()) {
            throw new Interruption($this->t("Aucun utilisateur ne correspond à « {$cible} ».", "No user matches \"{$cible}\"."));
        }

        if ($trouves->count() > 1) {
            $liste = $trouves->map(fn (User $u) => "{$u->full_name} ({$u->email})")->join(', ');
            throw new Interruption($this->t(
                "Plusieurs utilisateurs correspondent à « {$cible} » : {$liste}. Lequel ?",
                "Several users match \"{$cible}\": {$liste}. Which one?"
            ));
        }

        return $trouves->first();
    }

    /** Utilisateurs dont le nom complet ressemble à ce qui a été dicté */
    private function utilisateursApprochants(string $cible)
    {
        $dicte = Str::lower(Str::ascii($cible));

        $scores = User::query()->limit(500)->get()->map(function (User $u) use ($dicte) {
            $nom = Str::lower(Str::ascii($u->full_name));
            $inverse = Str::lower(Str::ascii(trim("{$u->name} {$u->prenom}")));

            return ['user' => $u, 'distance' => min(levenshtein($dicte, $nom), levenshtein($dicte, $inverse))];
        })->sortBy('distance')->values();

        // On accepte environ une faute pour quatre lettres, et on garde les ex æquo
        $seuil = max(1, intdiv(strlen($dicte), 4));
        $meilleure = $scores->first()['distance'] ?? PHP_INT_MAX;

        return $meilleure <= $seuil
            ? $scores->where('distance', $meilleure)->pluck('user')
            : collect();
    }

    /*
    |------------------------------------------------------------------
    | Logistique — tournées
    |------------------------------------------------------------------
    */

    private function creerTournee(User $user, array $a, bool $executer): array
    {
        $donnees = [
            'date'        => $this->texte($a, 'date'),
            'zone'        => $this->texte($a, 'zone'),
            'vehicule'    => $this->texte($a, 'vehicule'),
            'distance_km' => $this->nombre($a, 'distance_km'),
            'statut'      => strtoupper($this->texte($a, 'statut') ?? Tournee::STATUT_PLANIFIEE),
        ];

        $this->valider($donnees, TourneeController::regles(), TourneeController::MESSAGES);

        if (!$executer) {
            return $this->confirmation(
                $this->t(
                    "Planifier une tournée à {$donnees['zone']} le {$this->date($donnees['date'])} ?",
                    "Plan a round in {$donnees['zone']} on {$this->date($donnees['date'])}?"
                ),
                [
                    [$this->t('Véhicule', 'Vehicle'), $donnees['vehicule']],
                    ['Distance', $donnees['distance_km'] !== null ? $donnees['distance_km'] . ' km' : '—'],
                    [$this->t('Statut', 'Status'), $this->libelleStatutTournee($donnees['statut'])],
                ],
                'creer_tournee',
                $donnees,
            );
        }

        $tournee = Tournee::create([...$donnees, 'user_id' => $user->id]);

        return $this->succes(
            $this->t("Tournée {$this->nomTournee($tournee)} enregistrée.", "Round {$this->nomTournee($tournee)} saved."),
            route('logistique.tournees.show', $tournee),
        );
    }

    private function modifierTournee(User $user, array $a, bool $executer): array
    {
        $tournee = $this->trouverTournee($user, $a);

        $changements = array_filter([
            'date'        => $this->texte($a, 'nouvelle_date'),
            'zone'        => $this->texte($a, 'nouvelle_zone'),
            'vehicule'    => $this->texte($a, 'vehicule'),
            'distance_km' => $this->nombre($a, 'distance_km'),
            'statut'      => ($statut = $this->texte($a, 'statut')) ? strtoupper($statut) : null,
        ], fn ($valeur) => $valeur !== null);

        if (!$changements) {
            throw new Interruption($this->t(
                "Que faut-il modifier pour la tournée {$this->nomTournee($tournee)} ?",
                "What should I change for the round {$this->nomTournee($tournee)}?"
            ));
        }

        $donnees = [
            'date' => $tournee->date->format('Y-m-d'),
            ...$tournee->only(['zone', 'vehicule', 'distance_km', 'statut']),
            ...$changements,
        ];

        $this->valider($donnees, TourneeController::regles(), TourneeController::MESSAGES);

        if (!$executer) {
            $libelles = ['date' => 'Date', 'zone' => 'Zone', 'vehicule' => $this->t('Véhicule', 'Vehicle'), 'distance_km' => 'Distance', 'statut' => $this->t('Statut', 'Status')];
            $afficher = fn (string $champ, $valeur) => match ($champ) {
                'date'        => $this->date((string) $valeur),
                'statut'      => $this->libelleStatutTournee((string) $valeur),
                'distance_km' => $valeur . ' km',
                default       => $valeur,
            };

            return $this->confirmation(
                $this->t("Modifier la tournée {$this->nomTournee($tournee)} ?", "Update the round {$this->nomTournee($tournee)}?"),
                collect($changements)->map(fn ($valeur, $champ) => [$libelles[$champ], $afficher($champ, $valeur)])->values()->all(),
                'modifier_tournee',
                [
                    'tournee_id' => $tournee->id,
                    ...Arr::only($changements, ['vehicule', 'distance_km', 'statut']),
                    ...(isset($changements['date']) ? ['nouvelle_date' => $changements['date']] : []),
                    ...(isset($changements['zone']) ? ['nouvelle_zone' => $changements['zone']] : []),
                ],
            );
        }

        $tournee->update($changements);

        return $this->succes(
            $this->t("Tournée {$this->nomTournee($tournee)} mise à jour.", "Round {$this->nomTournee($tournee)} updated."),
            route('logistique.tournees.show', $tournee),
        );
    }

    private function supprimerTournee(User $user, array $a, bool $executer): array
    {
        $tournee = $this->trouverTournee($user, $a);

        if (!$executer) {
            return $this->confirmation(
                $this->t("Supprimer la tournée {$this->nomTournee($tournee)} ?", "Delete the round {$this->nomTournee($tournee)}?"),
                [[$this->t('Missions supprimées avec elle', 'Missions deleted with it'), (string) $tournee->missions()->count()]],
                'supprimer_tournee',
                ['tournee_id' => $tournee->id],
                danger: true,
            );
        }

        $nom = $this->nomTournee($tournee);
        $tournee->delete();

        return $this->succes($this->t("Tournée {$nom} supprimée.", "Round {$nom} deleted."), route('logistique.tournees.index'));
    }

    private function ouvrirTournee(User $user, array $a, bool $executer): array
    {
        $tournee = $this->trouverTournee($user, $a);

        return $this->navigation(
            route('logistique.tournees.show', $tournee),
            $this->t("Voici la tournée {$this->nomTournee($tournee)}.", "Here is the round {$this->nomTournee($tournee)}."),
        );
    }

    private function rechercherTournees(User $user, array $a, bool $executer): array
    {
        $statut = strtoupper($this->texte($a, 'statut') ?? '');

        $filtres = array_filter([
            'q'      => $this->texte($a, 'recherche'),
            'statut' => array_key_exists($statut, Tournee::STATUTS) ? $statut : null,
        ]);

        return $this->navigation(route('logistique.tournees.index', $filtres), $filtres
            ? $this->t('Voici les tournées correspondantes.', 'Here are the matching rounds.')
            : $this->t('Voici toutes vos tournées.', 'Here are all your rounds.'));
    }

    /** Retrouve une tournée du collecteur par son identifiant, ou par sa date et/ou sa zone */
    private function trouverTournee(User $user, array $a): Tournee
    {
        $requete = Tournee::where('user_id', $user->id);

        if (is_numeric($a['tournee_id'] ?? null)) {
            return $requete->find((int) $a['tournee_id'])
                ?? throw new Interruption($this->t("Cette tournée n'existe pas dans votre espace.", 'This round does not exist in your workspace.'));
        }

        $date = $this->texte($a, 'tournee_date');
        $zone = $this->texte($a, 'tournee_zone');

        if ($date) {
            if (Validator::make(['date' => $date], ['date' => 'date'])->fails()) {
                throw new Interruption($this->t("Je n'ai pas compris la date de la tournée.", "I didn't understand the round's date."));
            }
            $requete->whereDate('date', Carbon::parse($date));
        }

        if ($zone) {
            $requete->where('zone', 'like', "%{$zone}%");
        }

        // Sans précision, on suppose qu'il s'agit de la tournée du jour
        if (!$date && !$zone) {
            $requete->whereDate('date', $this->aujourdhui);
        }

        $trouvees = $requete->orderBy('date')->limit(6)->get();

        if ($trouvees->isEmpty()) {
            throw new Interruption($date || $zone
                ? $this->t('Aucune de vos tournées ne correspond.', 'None of your rounds match.')
                : $this->t('De quelle tournée parlez-vous ? Donnez sa date ou sa zone.', 'Which round do you mean? Give me its date or zone.'));
        }

        if ($trouvees->count() > 1) {
            $liste = $trouvees->map(fn (Tournee $t) => $this->nomTournee($t))->join(', ');
            throw new Interruption($this->t(
                "Plusieurs tournées correspondent : {$liste}. Laquelle ?",
                "Several rounds match: {$liste}. Which one?"
            ));
        }

        return $trouvees->first();
    }

    /*
    |------------------------------------------------------------------
    | Logistique — missions
    |------------------------------------------------------------------
    */

    private function ajouterMission(User $user, array $a, bool $executer): array
    {
        $tournee = $this->trouverTournee($user, $a);

        $donnees = [
            'type'         => strtoupper($this->texte($a, 'type') ?? ''),
            'adresse'      => $this->texte($a, 'adresse'),
            'heure_prevue' => $this->heure($a),
            'ordre'        => $this->nombre($a, 'ordre') ?? ((int) $tournee->missions()->max('ordre') + 1),
            'statut'       => strtoupper($this->texte($a, 'statut') ?? Mission::STATUT_A_FAIRE),
        ];

        $this->valider($donnees, MissionController::regles(), MissionController::MESSAGES);

        $type = Str::lower($this->libelleType($donnees['type']));

        if (!$executer) {
            return $this->confirmation(
                $this->t(
                    "Ajouter une {$type} à la tournée {$this->nomTournee($tournee)} ?",
                    "Add a {$type} to the round {$this->nomTournee($tournee)}?"
                ),
                [
                    [$this->t('Adresse', 'Address'), $donnees['adresse']],
                    [$this->t('Heure prévue', 'Planned time'), $donnees['heure_prevue']],
                    [$this->t('Ordre de passage', 'Stop number'), (string) $donnees['ordre']],
                    [$this->t('Statut', 'Status'), $this->libelleStatutMission($donnees['statut'])],
                ],
                'ajouter_mission',
                ['tournee_id' => $tournee->id, ...$donnees],
            );
        }

        $tournee->missions()->create($donnees);

        return $this->succes(
            $this->t("Mission {$donnees['ordre']} ajoutée à la tournée {$this->nomTournee($tournee)}.", "Mission {$donnees['ordre']} added to the round {$this->nomTournee($tournee)}."),
            route('logistique.tournees.show', $tournee),
        );
    }

    private function modifierMission(User $user, array $a, bool $executer): array
    {
        [$tournee, $mission] = $this->trouverMission($user, $a);

        $changements = array_filter([
            'ordre'            => $this->nombre($a, 'nouvel_ordre'),
            'type'             => ($type = $this->texte($a, 'type')) ? strtoupper($type) : null,
            'adresse'          => $this->texte($a, 'adresse'),
            'heure_prevue'     => $this->heure($a),
            'statut'           => ($statut = $this->texte($a, 'statut')) ? strtoupper($statut) : null,
            'preuve_livraison' => $this->texte($a, 'preuve_livraison'),
        ], fn ($valeur) => $valeur !== null);

        if (!$changements) {
            throw new Interruption($this->t(
                "Que faut-il modifier pour la mission {$mission->ordre} ?",
                "What should I change for mission {$mission->ordre}?"
            ));
        }

        $donnees = [
            ...$mission->only(['type', 'adresse', 'ordre', 'statut', 'preuve_livraison', 'don_vetement_id']),
            'heure_prevue' => $mission->heure,
            ...$changements,
        ];

        $this->valider($donnees, MissionController::regles(), MissionController::MESSAGES);

        if (!$executer) {
            $libelles = [
                'ordre' => $this->t('Ordre de passage', 'Stop number'), 'type' => 'Type', 'adresse' => $this->t('Adresse', 'Address'),
                'heure_prevue' => $this->t('Heure prévue', 'Planned time'), 'statut' => $this->t('Statut', 'Status'),
                'preuve_livraison' => $this->t('Preuve de livraison', 'Proof of delivery'),
            ];
            $afficher = fn (string $champ, $valeur) => match ($champ) {
                'type'   => $this->libelleType((string) $valeur),
                'statut' => $this->libelleStatutMission((string) $valeur),
                default  => (string) $valeur,
            };

            return $this->confirmation(
                $this->t(
                    "Modifier la mission {$mission->ordre} ({$mission->adresse}) de la tournée {$this->nomTournee($tournee)} ?",
                    "Update mission {$mission->ordre} ({$mission->adresse}) of the round {$this->nomTournee($tournee)}?"
                ),
                collect($changements)->map(fn ($valeur, $champ) => [$libelles[$champ], $afficher($champ, $valeur)])->values()->all(),
                'modifier_mission',
                [
                    'mission_id' => $mission->id,
                    ...Arr::except($changements, 'ordre'),
                    ...(isset($changements['ordre']) ? ['nouvel_ordre' => $changements['ordre']] : []),
                ],
            );
        }

        $mission->update($changements);

        return $this->succes(
            $this->t("Mission {$mission->ordre} mise à jour.", "Mission {$mission->ordre} updated."),
            route('logistique.tournees.show', $tournee),
        );
    }

    private function supprimerMission(User $user, array $a, bool $executer): array
    {
        [$tournee, $mission] = $this->trouverMission($user, $a);

        if (!$executer) {
            return $this->confirmation(
                $this->t(
                    "Supprimer la mission {$mission->ordre} de la tournée {$this->nomTournee($tournee)} ?",
                    "Delete mission {$mission->ordre} of the round {$this->nomTournee($tournee)}?"
                ),
                [
                    ['Type', $this->libelleType($mission->type)],
                    [$this->t('Adresse', 'Address'), $mission->adresse],
                    [$this->t('Heure prévue', 'Planned time'), $mission->heure],
                ],
                'supprimer_mission',
                ['mission_id' => $mission->id],
                danger: true,
            );
        }

        $ordre = $mission->ordre;
        $mission->delete();

        return $this->succes($this->t("Mission {$ordre} supprimée.", "Mission {$ordre} deleted."), route('logistique.tournees.show', $tournee));
    }

    /**
     * Retrouve une mission par son identifiant, ou par son ordre dans une tournée.
     *
     * @return array{0: Tournee, 1: Mission}
     */
    private function trouverMission(User $user, array $a): array
    {
        if (is_numeric($a['mission_id'] ?? null)) {
            $mission = Mission::with('tournee')->find((int) $a['mission_id']);

            if (!$mission || $mission->tournee->user_id !== $user->id) {
                throw new Interruption($this->t("Cette mission n'existe pas dans votre espace.", 'This mission does not exist in your workspace.'));
            }

            return [$mission->tournee, $mission];
        }

        $tournee = $this->trouverTournee($user, $a);
        $ordre   = $this->nombre($a, 'ordre');

        if ($ordre === null) {
            throw new Interruption($this->t(
                'De quelle mission parlez-vous ? Donnez son numéro de passage.',
                'Which mission do you mean? Give me its stop number.'
            ));
        }

        $missions = $tournee->missions()->where('ordre', (int) $ordre)->get();

        if ($missions->isEmpty()) {
            throw new Interruption($this->t(
                "La tournée {$this->nomTournee($tournee)} n'a pas de mission numéro {$ordre}.",
                "The round {$this->nomTournee($tournee)} has no mission number {$ordre}."
            ));
        }

        if ($missions->count() > 1) {
            throw new Interruption($this->t(
                "Plusieurs missions portent le numéro {$ordre} dans cette tournée. Modifiez-les depuis la page de la tournée.",
                "Several missions share number {$ordre} in this round. Edit them from the round's page."
            ));
        }

        return [$tournee, $missions->first()];
    }

    /*
    |------------------------------------------------------------------
    | Navigation
    |------------------------------------------------------------------
    */

    private function ouvrirPage(User $user, array $a, bool $executer): array
    {
        $pages = $user->isAdmin()
            ? [
                'utilisateurs'       => [route('admin.users.index'), $this->t('Voici les utilisateurs.', 'Here are the users.')],
                'nouvel_utilisateur' => [route('admin.users.create'), $this->t("Voici le formulaire d'ajout.", 'Here is the new user form.')],
                'statistiques'       => [route('admin.statistiques'), $this->t('Voici les statistiques.', 'Here are the statistics.')],
            ]
            : [
                'tournees'         => [route('logistique.tournees.index'), $this->t('Voici vos tournées.', 'Here are your rounds.')],
                'nouvelle_tournee' => [route('logistique.tournees.create'), $this->t('Voici le formulaire de tournée.', 'Here is the new round form.')],
            ];

        $page = $pages[$this->texte($a, 'page') ?? '']
            ?? throw new Interruption($this->t('Je ne connais pas cette page.', "I don't know that page."));

        return $this->navigation(...$page);
    }

    /*
    |------------------------------------------------------------------
    | Lecture des arguments
    |------------------------------------------------------------------
    */

    private function texte(array $a, string $cle): ?string
    {
        $valeur = $a[$cle] ?? null;

        if (!is_scalar($valeur) || is_bool($valeur)) {
            return null;
        }

        return ($valeur = trim((string) $valeur)) === '' ? null : $valeur;
    }

    private function nombre(array $a, string $cle): int|float|null
    {
        $valeur = $a[$cle] ?? null;

        return is_numeric($valeur) ? $valeur + 0 : null;
    }

    private function booleen(array $a, string $cle): ?bool
    {
        return isset($a[$cle]) ? filter_var($a[$cle], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : null;
    }

    /** E-mail dicté : minuscules, sans espace */
    private function email(array $a): ?string
    {
        $email = $this->texte($a, 'email');

        return $email ? Str::lower(str_replace(' ', '', $email)) : null;
    }

    /** Téléphone dicté : on ne garde que les chiffres et un éventuel + initial */
    private function telephone(array $a): ?string
    {
        $telephone = $this->texte($a, 'telephone');

        return $telephone ? (preg_replace('/(?!^\+)\D/', '', $telephone) ?: null) : null;
    }

    /** Heure dictée (« 9h30 », « 9:30 », « 14 h », « 2 pm ») ramenée à HH:MM */
    private function heure(array $a): ?string
    {
        $heure = $this->texte($a, 'heure_prevue');

        if ($heure && preg_match('/^(\d{1,2})\s*(?:[:hH.]\s*(\d{2}))?\s*(am|pm|a\.m\.|p\.m\.)?/i', $heure, $m) && (isset($m[2]) || isset($m[3]) || preg_match('/[hH]/', $heure) || ctype_digit($heure))) {
            $h = (int) $m[1];
            $suffixe = Str::lower(str_replace('.', '', $m[3] ?? ''));
            if ($suffixe === 'pm' && $h < 12) $h += 12;
            if ($suffixe === 'am' && $h === 12) $h = 0;

            return sprintf('%02d:%02d', $h, (int) ($m[2] ?? 0));
        }

        return $heure;
    }

    private function valider(array $donnees, array $regles, array $messagesFr): void
    {
        // En anglais : messages standard de Laravel, avec des noms de champs lisibles
        $validation = $this->en
            ? Validator::make($donnees, $regles, [], [
                'prenom' => 'first name', 'name' => 'last name', 'telephone' => 'phone', 'role' => 'role',
                'vehicule' => 'vehicle', 'distance_km' => 'distance', 'statut' => 'status', 'adresse' => 'address',
                'ordre' => 'stop number', 'heure_prevue' => 'planned time', 'preuve_livraison' => 'proof of delivery',
            ])
            : Validator::make($donnees, $regles, $messagesFr);

        if ($this->en) {
            $validation->setTranslator(tap(clone app('translator'), fn ($traducteur) => $traducteur->setLocale('en')));
        }

        if ($validation->fails()) {
            throw new Interruption($validation->errors()->first());
        }
    }

    /*
    |------------------------------------------------------------------
    | Mise en forme
    |------------------------------------------------------------------
    */

    /** Choisit le texte dans la langue dictée */
    private function t(string $fr, string $en): string
    {
        return $this->en ? $en : $fr;
    }

    private function libelleRole(string $role): string
    {
        $anglais = ['DONATEUR' => 'Donor', 'CLIENT' => 'Customer', 'COLLECTEUR' => 'Collector', 'ATELIER' => 'Workshop', 'RECYCLEUR' => 'Recycler', 'ADMIN' => 'Admin'];

        return $this->en ? ($anglais[$role] ?? $role) : ucfirst(strtolower($role));
    }

    private function libelleActif(bool $actif): string
    {
        return $actif ? $this->t('Actif', 'Active') : $this->t('Désactivé', 'Deactivated');
    }

    private function libelleStatutTournee(string $statut): string
    {
        $anglais = ['PLANIFIEE' => 'Planned', 'EN_COURS' => 'In progress', 'TERMINEE' => 'Completed', 'ANNULEE' => 'Cancelled'];

        return $this->en ? ($anglais[$statut] ?? $statut) : (Tournee::STATUTS[$statut]['label'] ?? $statut);
    }

    private function libelleStatutMission(string $statut): string
    {
        $anglais = ['A_FAIRE' => 'To do', 'EN_COURS' => 'In progress', 'TERMINEE' => 'Done', 'ECHOUEE' => 'Failed'];

        return $this->en ? ($anglais[$statut] ?? $statut) : (Mission::STATUTS[$statut]['label'] ?? $statut);
    }

    private function libelleType(string $type): string
    {
        $anglais = ['COLLECTE' => 'Pickup', 'LIVRAISON' => 'Delivery'];

        return $this->en ? ($anglais[$type] ?? $type) : (Mission::TYPES[$type] ?? $type);
    }

    private function date(string $date): string
    {
        return Carbon::parse($date)->format('d/m/Y');
    }

    private function nomTournee(Tournee $tournee): string
    {
        return $tournee->zone . $this->t(' du ', ' of ') . $tournee->date->format('d/m/Y');
    }

    /*
    |------------------------------------------------------------------
    | Réponses
    |------------------------------------------------------------------
    */

    private function message(string $message): array
    {
        return ['type' => 'message', 'message' => $message, 'voix' => $this->en ? 'en' : 'fr'];
    }

    private function navigation(string $url, string $message): array
    {
        return ['type' => 'navigation', 'url' => $url, 'message' => $message, 'voix' => $this->en ? 'en' : 'fr'];
    }

    private function succes(string $message, string $url, ?array $secret = null): array
    {
        return ['type' => 'succes', 'message' => $message, 'url' => $url, 'secret' => $secret, 'voix' => $this->en ? 'en' : 'fr'];
    }

    /** @param array<int, array{0: string, 1: string}> $details */
    private function confirmation(string $resume, array $details, string $action, array $arguments, bool $danger = false): array
    {
        return [
            'type'      => 'confirmation',
            'resume'    => $resume,
            'details'   => array_map(fn (array $detail) => ['label' => $detail[0], 'valeur' => (string) $detail[1]], $details),
            'danger'    => $danger,
            'action'    => $action,
            'arguments' => $arguments,
            'voix'      => $this->en ? 'en' : 'fr',
        ];
    }
}
