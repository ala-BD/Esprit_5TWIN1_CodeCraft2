<?php

namespace App\Http\Requests\Upcycling;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Message dans la discussion d'un projet (site web et API).
 */
class MessageProjetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contenu' => ['required', 'string', 'min:2', 'max:1000'],
            'ia'      => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'contenu.required' => 'Écrivez votre message.',
            'contenu.min'      => 'Le message est trop court.',
            'contenu.max'      => 'Le message ne doit pas dépasser 1000 caractères.',
            'ia.boolean'       => 'Le champ ia doit valoir true ou false.',
        ];
    }
}
