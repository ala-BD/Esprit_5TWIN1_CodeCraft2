<?php

namespace App\Http\Requests\Upcycling;

use App\Models\DonVetement;
use App\Models\ProjetUpcycling;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation d'une demande d'upcycling (création et modification).
 * Les droits d'accès sont vérifiés dans le contrôleur.
 */
class ProjetUpcyclingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $regles = [
            'type_vetement' => ['required', 'string', 'max:100'],
            'matiere'       => ['required', 'string', 'max:100'],
            'couleur'       => ['nullable', 'string', 'max:50'],
            'etat'          => ['required', Rule::in(array_keys(ProjetUpcycling::ETATS))],
            'description'   => ['required', 'string', 'min:10', 'max:1000'],
            'budget_max'    => ['nullable', 'numeric', 'min:1', 'max:10000'],
            'photo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];

        // Le don d'origine ne se choisit qu'à la création
        if ($this->isMethod('post')) {
            $regles['don_vetement_id'] = ['nullable', Rule::in(self::donsDisponibles()->pluck('id')->all())];
        }

        return $regles;
    }

    public function messages(): array
    {
        return [
            'type_vetement.required' => 'Le type de vêtement est obligatoire.',
            'matiere.required'       => 'La matière est obligatoire.',
            'etat.required'          => "L'état du vêtement est obligatoire.",
            'etat.in'                => 'État invalide.',
            'description.required'   => 'Décrivez votre vêtement et vos envies.',
            'description.min'        => 'La description doit contenir au moins 10 caractères.',
            'budget_max.numeric'     => 'Le budget doit être un nombre.',
            'budget_max.min'         => 'Le budget minimum est 1 DT.',
            'photo.image'            => 'Le fichier doit être une image.',
            'photo.mimes'            => 'Formats acceptés : JPG, PNG ou WEBP.',
            'photo.max'              => 'La photo ne doit pas dépasser 5 Mo.',
            'don_vetement_id.in'     => "Ce don n'est plus disponible pour l'upcycling.",
        ];
    }

    /** Dons déposés / en tri qui ne sont pas déjà rattachés à un projet */
    public static function donsDisponibles()
    {
        return DonVetement::whereIn('statut', ['DEPOSE', 'EN_TRI'])
            ->whereNotIn('id', ProjetUpcycling::whereNotNull('don_vetement_id')->select('don_vetement_id'))
            ->latest()
            ->get();
    }
}
