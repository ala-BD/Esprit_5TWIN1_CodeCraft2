<?php

namespace App\Http\Requests\Upcycling;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation d'un devis envoyé par un atelier.
 */
class DevisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'montant'     => ['required', 'numeric', 'min:1', 'max:100000'],
            'delai_jours' => ['required', 'integer', 'min:1', 'max:180'],
            'message'     => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'montant.required'     => 'Le montant est obligatoire.',
            'montant.numeric'      => 'Le montant doit être un nombre.',
            'montant.min'          => 'Le montant minimum est 1 DT.',
            'delai_jours.required' => 'Le délai est obligatoire.',
            'delai_jours.integer'  => 'Le délai doit être un nombre entier de jours.',
            'delai_jours.min'      => 'Le délai minimum est 1 jour.',
            'delai_jours.max'      => 'Le délai maximum est 180 jours.',
        ];
    }
}
