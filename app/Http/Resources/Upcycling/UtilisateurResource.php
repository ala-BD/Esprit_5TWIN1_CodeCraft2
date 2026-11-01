<?php

namespace App\Http\Resources\Upcycling;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Profil public d'un utilisateur : jamais de mot de passe ni de jeton.
 * L'e-mail n'est exposé qu'à l'utilisateur lui-même.
 *
 * @mixin \App\Models\User
 */
class UtilisateurResource extends JsonResource
{
    private bool $avecEmail = false;

    /** Force l'affichage de l'e-mail (ex. réponse de connexion, adressée à l'utilisateur lui-même) */
    public function avecEmail(): static
    {
        $this->avecEmail = true;

        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'prenom'     => $this->prenom,
            'nom'        => $this->name,
            'nom_complet'=> $this->full_name,
            'role'       => $this->role,
            'email'      => $this->when($this->avecEmail || $request->user()?->id === $this->id, $this->email),
        ];
    }
}
