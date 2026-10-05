<?php

namespace App\Http\Requests;

use App\Models\Commande;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adresse_id'    => ['nullable', 'integer', 'exists:adresses,id'],
            'mode_paiement' => ['required', 'string', Rule::in(array_keys(Commande::MODES_PAIEMENT))],
            // quantities update (while EN_ATTENTE)
            'lignes'                    => ['nullable', 'array'],
            'lignes.*.id'               => ['required_with:lignes', 'integer', 'exists:ligne_commandes,id'],
            'lignes.*.quantite'         => ['required_with:lignes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'adresse_id.exists'       => 'L\'adresse sélectionnée est introuvable.',
            'mode_paiement.required'  => 'Veuillez choisir un mode de paiement.',
            'mode_paiement.in'        => 'Le mode de paiement sélectionné est invalide.',
            'lignes.*.quantite.min'   => 'La quantité minimale est 1.',
            'lignes.*.quantite.max'   => 'La quantité maximale par article est 100.',
        ];
    }
}
