<?php

namespace App\Http\Requests\Upcycling;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation des actions sur un projet (site web et API).
 * Les règles dépendent de la route appelée : une seule classe, pas de validation dans les contrôleurs.
 */
class ActionProjetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $action = str($this->route()->getName())->afterLast('.')->toString();

        return match ($action) {
            'idee'    => ['index' => ['required', 'integer', 'min:0']],
            'idees'   => ['consigne' => ['nullable', 'string', 'max:200']],
            'atelier' => ['atelier_id' => ['required', Rule::exists('ateliers', 'id')->where('actif', true)]],
            'avancer' => ['photo_resultat' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']],
            'noter'   => [
                'note_client'        => ['required', 'integer', 'between:1,5'],
                'commentaire_client' => ['nullable', 'string', 'max:500'],
            ],
            'analyse-photo' => ['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']],
            default   => [],
        };
    }

    public function messages(): array
    {
        return [
            'index.required'       => 'Indiquez le numéro de l\'idée choisie.',
            'consigne.max'         => 'La consigne ne doit pas dépasser 200 caractères.',
            'atelier_id.required'  => 'Veuillez choisir un atelier.',
            'atelier_id.exists'    => 'Cet atelier est indisponible.',
            'note_client.required' => 'Veuillez choisir une note.',
            'note_client.between'  => 'La note doit être comprise entre 1 et 5.',
            'photo.required'       => 'Ajoutez une photo du vêtement.',
            'photo.image'          => 'Le fichier doit être une image.',
            'photo.mimes'          => 'Formats acceptés : JPG, PNG ou WEBP.',
            'photo.max'            => 'La photo ne doit pas dépasser 5 Mo.',
            'photo_resultat.image' => 'Le fichier doit être une image.',
            'photo_resultat.mimes' => 'Formats acceptés : JPG, PNG ou WEBP.',
            'photo_resultat.max'   => 'La photo ne doit pas dépasser 5 Mo.',
        ];
    }
}
