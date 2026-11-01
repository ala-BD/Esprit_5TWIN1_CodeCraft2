<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Demande d'un token d'accès à l'API (Sanctum).
 */
class TokenRequest extends FormRequest
{
    /** Permissions qu'un token peut recevoir */
    const ABILITIES = [
        'upcycling:lire'   => 'Lire les projets, ateliers, devis et messages',
        'upcycling:ecrire' => 'Créer et modifier des projets, devis et messages',
        'upcycling:ia'     => "Utiliser l'IA (analyse de photo, idées, styliste)",
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string'],
            'nom_appareil'=> ['required', 'string', 'max:60'],
            'abilities'   => ['sometimes', 'array'],
            'abilities.*' => ['string', Rule::in(array_keys(self::ABILITIES))],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'        => "L'e-mail est obligatoire.",
            'email.email'           => "L'e-mail n'est pas valide.",
            'password.required'     => 'Le mot de passe est obligatoire.',
            'nom_appareil.required' => "Donnez un nom à l'appareil ou à l'application (ex. postman).",
            'abilities.*.in'        => 'Permission inconnue. Valeurs possibles : ' . implode(', ', array_keys(self::ABILITIES)) . '.',
        ];
    }

    /** Permissions demandées (toutes par défaut) */
    public function abilities(): array
    {
        return $this->validated('abilities') ?: array_keys(self::ABILITIES);
    }
}
