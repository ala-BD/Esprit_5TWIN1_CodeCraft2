<?php

namespace App\Http\Requests\Upcycling;

use App\Models\Atelier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation du profil atelier (création et modification).
 */
class AtelierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom'           => ['required', 'string', 'max:100'],
            'specialite'    => ['required', Rule::in(array_keys(Atelier::SPECIALITES))],
            'description'   => ['nullable', 'string', 'max:1000'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
            'tarif_horaire' => ['required', 'numeric', 'min:1', 'max:500'],
            'localisation'  => ['required', 'string', 'max:150'],
            'photo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required'           => "Le nom de l'atelier est obligatoire.",
            'specialite.required'    => 'La spécialité est obligatoire.',
            'specialite.in'          => 'Spécialité invalide.',
            'portfolio_url.url'      => 'Le lien du portfolio doit être une URL valide (https://...).',
            'tarif_horaire.required' => 'Le tarif horaire est obligatoire.',
            'tarif_horaire.numeric'  => 'Le tarif horaire doit être un nombre.',
            'tarif_horaire.min'      => 'Le tarif horaire minimum est 1 DT.',
            'tarif_horaire.max'      => 'Le tarif horaire maximum est 500 DT.',
            'localisation.required'  => 'La localisation est obligatoire.',
            'photo.image'            => 'Le fichier doit être une image.',
            'photo.mimes'            => 'Formats acceptés : JPG, PNG ou WEBP.',
            'photo.max'              => 'La photo ne doit pas dépasser 5 Mo.',
        ];
    }
}
