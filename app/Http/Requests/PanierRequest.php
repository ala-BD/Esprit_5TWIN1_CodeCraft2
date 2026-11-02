<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PanierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'quantite' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantite.required' => 'La quantité est obligatoire.',
            'quantite.integer'  => 'La quantité doit être un nombre entier.',
            'quantite.min'      => 'La quantité doit être au moins 1.',
        ];
    }
}
