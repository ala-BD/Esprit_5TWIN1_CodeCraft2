<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'prenom'    => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'email'     => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'photo'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.image' => 'La photo doit être une image.',
            'photo.mimes' => 'La photo doit être au format JPG, PNG ou WebP.',
            'photo.max'   => 'La photo ne doit pas dépasser 2 Mo.',
        ];
    }
}
