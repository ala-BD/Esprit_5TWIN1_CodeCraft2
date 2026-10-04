<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdresseRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Règles de validation pour les adresses.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'libelle'     => ['nullable', 'string', 'max:100'],
            'rue'         => ['required', 'string', 'max:255'],
            'ville'       => ['required', 'string', 'max:100'],
            'code_postal' => ['required', 'string', 'max:20'],
            'latitude'    => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'   => ['nullable', 'numeric', 'between:-180,180'],
            'par_defaut'  => ['nullable', 'boolean'],
        ];
    }

    /**
     * Prépare les données pour la validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'par_defaut' => $this->boolean('par_defaut'),
        ]);
    }

    /**
     * Messages d'erreur personnalisés en français.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rue.required'         => 'Le nom de la rue est obligatoire.',
            'rue.max'              => 'La rue ne doit pas dépasser 255 caractères.',
            'ville.required'       => 'La ville est obligatoire.',
            'ville.max'            => 'La ville ne doit pas dépasser 100 caractères.',
            'code_postal.required' => 'Le code postal est obligatoire.',
            'code_postal.max'      => 'Le code postal ne doit pas dépasser 20 caractères.',
            'latitude.numeric'     => 'La latitude doit être une valeur numérique.',
            'latitude.between'     => 'La latitude doit être comprise entre -90 et 90.',
            'longitude.numeric'    => 'La longitude doit être une valeur numérique.',
            'longitude.between'    => 'La longitude doit être comprise entre -180 et 180.',
        ];
    }
}
