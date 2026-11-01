<?php

namespace App\Policies;

use App\Models\Atelier;
use App\Models\ProjetUpcycling;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Autorisations d'un projet d'upcycling (site web et API).
 */
class ProjetUpcyclingPolicy
{
    /** Rôles autorisés à demander un upcycling */
    const ROLES_CLIENT = [User::ROLE_CLIENT, User::ROLE_DONATEUR];

    public function create(User $user): Response
    {
        return in_array($user->role, self::ROLES_CLIENT, true)
            ? Response::allow()
            : Response::deny('Réservé aux clients.');
    }

    /** Client du projet, atelier assigné ou administrateur */
    public function view(User $user, ProjetUpcycling $projet): Response
    {
        return $this->estClient($user, $projet) || $this->estAtelier($user, $projet) || $user->isAdmin()
            ? Response::allow()
            : Response::deny('Accès non autorisé.');
    }

    /** Actions du client : modifier, choisir l'idée et l'atelier, répondre aux devis, noter... */
    public function gererClient(User $user, ProjetUpcycling $projet): Response
    {
        return $this->estClient($user, $projet)
            ? Response::allow()
            : Response::deny('Seul le client du projet peut effectuer cette action.');
    }

    /** Actions de l'atelier : devis, avancement des étapes, contrôle qualité */
    public function gererAtelier(User $user, ProjetUpcycling $projet): Response
    {
        return $this->estAtelier($user, $projet)
            ? Response::allow()
            : Response::deny("Seul l'atelier du projet peut effectuer cette action.");
    }

    /** Écrire dans la discussion du projet */
    public function discuter(User $user, ProjetUpcycling $projet): Response
    {
        return $this->estClient($user, $projet) || $this->estAtelier($user, $projet)
            ? Response::allow()
            : Response::deny("Seuls le client et l'atelier du projet peuvent écrire ici.");
    }

    public function estClient(User $user, ProjetUpcycling $projet): bool
    {
        return $projet->client_id === $user->id;
    }

    public function estAtelier(User $user, ProjetUpcycling $projet): bool
    {
        return $projet->atelier_id !== null
            && Atelier::whereKey($projet->atelier_id)->where('user_id', $user->id)->exists();
    }
}
