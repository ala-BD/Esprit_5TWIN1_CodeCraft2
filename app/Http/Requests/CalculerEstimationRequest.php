<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class CalculerEstimationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->isAdmin() || $user->aLeRole(User::ROLE_ATELIER));
    }

    /**
     * Validation rules for calculating costs and price estimate.
     */
    public function rules(): array
    {
        return [
            'titre'          => ['nullable', 'string', 'max:255'],
            'categorie'      => ['nullable', 'string', 'max:100'],
            'matiere'        => ['nullable', 'string', 'max:50'],
            'taille'         => ['nullable', 'string', 'max:20'],
            'etat'           => ['nullable', 'string', 'max:50'],
            'couleur'        => ['nullable', 'string', 'max:50'],
            'poids_kg'       => ['nullable', 'numeric', 'min:0', 'max:100'],
            'cout_achat'     => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'heures_travail' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'cout_materiaux' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'prix'           => ['nullable', 'numeric', 'min:0', 'max:50000'],
            'quantite'       => ['nullable', 'integer', 'min:1', 'max:5000'],
            'article_id'     => ['nullable', 'integer', 'exists:articles,id'],
            'save'           => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'poids_kg.numeric'       => 'Le poids doit être un nombre valide (kg).',
            'cout_achat.numeric'     => 'Le coût d\'achat doit être un montant positif.',
            'heures_travail.numeric' => 'Le nombre d\'heures de travail doit être un nombre.',
            'cout_materiaux.numeric' => 'Le coût des matériaux doit être un montant positif.',
            'prix.numeric'           => 'Le prix doit être un montant positif.',
        ];
    }
}
