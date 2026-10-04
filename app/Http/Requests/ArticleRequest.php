<?php

namespace App\Http\Requests;

use App\Models\Article;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titre'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:5000'],
            'prix'            => ['required', 'numeric', 'gt:0'],
            'stock'           => ['required', 'integer', 'min:0'],
            'categorie'       => ['required', 'string', 'max:100'],
            'statut'          => ['nullable', 'string', Rule::in(Article::STATUTS)],
            'don_vetement_id' => ['nullable', 'integer', 'exists:don_vetements,id'],
            'images'          => ['nullable', 'array'],
            'images.*'        => ['image', 'mimes:jpeg,png,jpg,webp,gif', 'max:4096'],
            'remove_images'   => ['nullable', 'array'],
        ];
    }

    /**
     * Messages de validation personnalisés en français.
     */
    public function messages(): array
    {
        return [
            'titre.required'     => 'Le titre de l\'article est obligatoire.',
            'titre.max'          => 'Le titre ne peut pas dépasser 255 caractères.',
            'prix.required'      => 'Le prix de vente est obligatoire.',
            'prix.numeric'       => 'Le prix doit être un nombre valide.',
            'prix.gt'            => 'Le prix doit être strictement supérieur à zéro (positif).',
            'stock.required'     => 'La quantité en stock est obligatoire.',
            'stock.integer'      => 'Le stock doit être un nombre entier.',
            'stock.min'          => 'Le stock ne peut pas être négatif.',
            'categorie.required' => 'La catégorie est obligatoire.',
            'statut.in'          => 'Le statut sélectionné est invalide.',
            'don_vetement_id.exists' => 'Le don sélectionné est introuvable.',
            'images.*.image'     => 'Chaque fichier doit être une image valide.',
            'images.*.mimes'     => 'Formats acceptés : jpeg, png, jpg, webp, gif.',
            'images.*.max'       => 'La taille maximale par image est de 4 Mo.',
        ];
    }
}
