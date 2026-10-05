<?php

namespace App\Http\Requests;

use App\Models\Commande;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adresse_id'     => ['nullable', 'integer', 'exists:adresses,id'],
            'mode_paiement'  => ['required', 'string', Rule::in(array_keys(Commande::MODES_PAIEMENT))],
            'articles'       => ['required', 'array', 'min:1'],
            'articles.*.id'  => ['required', 'integer', 'exists:articles,id'],
            'articles.*.quantite' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'adresse_id.exists'           => 'L\'adresse sélectionnée est introuvable.',
            'mode_paiement.required'      => 'Veuillez choisir un mode de paiement.',
            'mode_paiement.in'            => 'Le mode de paiement sélectionné est invalide.',
            'articles.required'           => 'Votre panier est vide.',
            'articles.min'                => 'Vous devez commander au moins un article.',
            'articles.*.id.exists'        => 'Un article sélectionné est introuvable.',
            'articles.*.quantite.min'     => 'La quantité minimale est 1.',
            'articles.*.quantite.max'     => 'La quantité maximale par article est 100.',
        ];
    }

    /**
     * Règle de validation métier : Un atelier (ou tout vendeur) ne peut pas acheter son propre article.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            if (! $user) {
                return;
            }

            $articlesInput = $this->input('articles', []);
            if (! is_array($articlesInput)) {
                return;
            }

            foreach ($articlesInput as $index => $item) {
                if (isset($item['id'])) {
                    $article = \App\Models\Article::find($item['id']);
                    if ($article && $article->user_id === $user->id) {
                        $validator->errors()->add(
                            "articles",
                            "Un atelier ne peut pas acheter son propre article (« {$article->titre} »)."
                        );
                    }
                }
            }
        });
    }
}
