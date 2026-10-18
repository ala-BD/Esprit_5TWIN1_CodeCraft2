<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AvisRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'note_livraison' => ['required', 'integer', 'between:1,5'],
            'note_article'   => ['required', 'integer', 'between:1,5'],
            'note_clarte'    => ['required', 'integer', 'between:1,5'],
            'note_service'   => ['required', 'integer', 'between:1,5'],
            'commentaire'    => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Messages d'erreur personnalisés en français.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note_livraison.required' => 'Veuillez évaluer la livraison (1 à 5 étoiles).',
            'note_livraison.between'  => 'La note de livraison doit être comprise entre 1 et 5.',
            'note_article.required'   => 'Veuillez évaluer l\'article acheté (1 à 5 étoiles).',
            'note_article.between'    => 'La note de l\'article doit être comprise entre 1 et 5.',
            'note_clarte.required'    => 'Veuillez évaluer la clarté de la description (1 à 5 étoiles).',
            'note_clarte.between'     => 'La note de clarté doit être comprise entre 1 et 5.',
            'note_service.required'   => 'Veuillez évaluer le service client (1 à 5 étoiles).',
            'note_service.between'    => 'La note de service doit être comprise entre 1 et 5.',
            'commentaire.max'         => 'Le commentaire ne peut pas dépasser 500 caractères.',
        ];
    }
}
