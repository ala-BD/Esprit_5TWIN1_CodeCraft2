<?php

namespace App\Http\Controllers\Upcycling\Concerns;

use App\Models\Atelier;
use App\Models\ProjetUpcycling;
use App\Models\User;
use App\Policies\ProjetUpcyclingPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Raccourcis d'accès du module Upcycling (site web et API).
 * Les règles elles-mêmes sont dans App\Policies\ProjetUpcyclingPolicy.
 */
trait AccesUpcycling
{
    protected function estClient(): bool
    {
        return in_array(Auth::user()->role, ProjetUpcyclingPolicy::ROLES_CLIENT, true);
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
        return Gate::allows('gererClient', $projet);
    }

    protected function estAtelierDuProjet(ProjetUpcycling $projet): bool
    {
        return Gate::allows('gererAtelier', $projet);
    }

    /** Client propriétaire, atelier assigné ou admin */
    protected function autoriserLecture(ProjetUpcycling $projet): void
    {
        Gate::authorize('view', $projet);
    }

    protected function autoriserClient(ProjetUpcycling $projet): void
    {
        Gate::authorize('gererClient', $projet);
    }

    protected function autoriserAtelier(ProjetUpcycling $projet): void
    {
        Gate::authorize('gererAtelier', $projet);
    }
}
