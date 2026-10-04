<?php

namespace App\Http\Controllers\Upcycling\Concerns;

use App\Models\Atelier;
use App\Models\ProjetUpcycling;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Contrôles d'accès communs au module Upcycling.
 */
trait AccesUpcycling
{
    /** Rôles autorisés à demander un upcycling */
    protected array $rolesClient = [User::ROLE_CLIENT, User::ROLE_DONATEUR];

    protected function estClient(): bool
    {
        return in_array(Auth::user()->role, $this->rolesClient, true);
    }

    protected function estAtelier(): bool
    {
        return Auth::user()->role === User::ROLE_ATELIER;
    }

    /** Profil atelier de l'utilisateur connecté (null si pas encore créé) */
    protected function atelierConnecte(): ?Atelier
    {
        return Atelier::where('user_id', Auth::id())->first();
    }

    protected function estProprietaire(ProjetUpcycling $projet): bool
    {
        return $projet->client_id === Auth::id();
    }

    protected function estAtelierDuProjet(ProjetUpcycling $projet): bool
    {
        $atelier = $this->atelierConnecte();
        return $atelier !== null && $projet->atelier_id === $atelier->id;
    }

    /** Client propriétaire, atelier assigné ou admin */
    protected function autoriserLecture(ProjetUpcycling $projet): void
    {
        abort_unless(
            $this->estProprietaire($projet) || $this->estAtelierDuProjet($projet) || Auth::user()->isAdmin(),
            403,
            'Accès non autorisé.'
        );
    }

    protected function autoriserClient(ProjetUpcycling $projet): void
    {
        abort_unless($this->estProprietaire($projet), 403, 'Seul le client du projet peut effectuer cette action.');
    }

    protected function autoriserAtelier(ProjetUpcycling $projet): void
    {
        abort_unless($this->estAtelierDuProjet($projet), 403, "Seul l'atelier du projet peut effectuer cette action.");
    }
}
