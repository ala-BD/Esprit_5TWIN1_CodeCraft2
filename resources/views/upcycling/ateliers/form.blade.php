@extends('upcycling.layouts.upcycling')

@php
    $edition = $atelier->exists;
    $champ = fn ($nom) => 'w-full pl-11 pr-4 py-3 rounded-xl border '
        . ($errors->has($nom) ? 'border-red-400 bg-red-50' : 'border-gray-200')
        . ' focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-gray-700';
@endphp

@section('title', $edition ? 'Modifier mon atelier' : 'Créer mon atelier')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('upcycling.ateliers.index') }}" class="hover:text-primary transition-colors">Ateliers</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">{{ $edition ? 'Modifier' : 'Créer' }}</span>
@endsection

@section('upcycling-content')

<div class="max-w-2xl">

    <div class="mb-8">
        <h1 class="font-display text-3xl font-extrabold text-gray-900">{{ $edition ? 'Modifier mon atelier' : 'Créer mon profil atelier' }}</h1>
        <p class="text-gray-500 text-sm mt-1">Ces informations servent au matching : spécialité, tarif et disponibilité déterminent votre classement.</p>
    </div>

    <form method="POST" action="{{ $edition ? route('upcycling.ateliers.update', $atelier) : route('upcycling.ateliers.store') }}"
          enctype="multipart/form-data" class="space-y-6" novalidate>
        @csrf
        @if($edition) @method('PUT') @endif

        @include('upcycling.partials.photo-input', [
            'nom'      => 'photo',
            'actuelle' => $atelier->photo_url,
            'titre'    => 'Photo de couverture',
            'aide'     => "Votre atelier, une création phare ou votre logo (sinon, votre dernière réalisation s'affiche)",
            'hauteur'  => 'h-52',
        ])

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 space-y-5">

            {{-- Nom --}}
            <div>
                <label for="nom" class="block text-sm font-semibold text-gray-700 mb-2">Nom de l'atelier <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-store text-sm"></i></span>
                    <input type="text" id="nom" name="nom" value="{{ old('nom', $atelier->nom) }}" placeholder="ex: Atelier Fil d'Or" class="{{ $champ('nom') }}">
                </div>
                @error('nom')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
            </div>

            {{-- Spécialité --}}
            <div>
                <p class="block text-sm font-semibold text-gray-700 mb-2">Spécialité <span class="text-red-500">*</span></p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach(\App\Models\Atelier::SPECIALITES as $code => $label)
                        <label class="cursor-pointer">
                            <input type="radio" name="specialite" value="{{ $code }}" class="peer sr-only" @checked(old('specialite', $atelier->specialite) === $code)>
                            <span class="flex items-center gap-2 text-sm font-medium px-3 py-2.5 rounded-xl border-2 border-gray-200 text-gray-600
                                         peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary transition-all">
                                <i class="fas {{ \App\Models\Atelier::ICONS[$code] }}"></i> {{ $label }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('specialite')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
            </div>

            <div class="grid sm:grid-cols-2 gap-5">
                {{-- Tarif --}}
                <div>
                    <label for="tarif_horaire" class="block text-sm font-semibold text-gray-700 mb-2">Tarif horaire <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-coins text-sm"></i></span>
                        <input type="number" id="tarif_horaire" name="tarif_horaire" step="0.5" min="1"
                               value="{{ old('tarif_horaire', $atelier->tarif_horaire) }}" placeholder="ex: 15"
                               class="{{ str_replace('pr-4', 'pr-16', $champ('tarif_horaire')) }}">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">DT/h</span>
                    </div>
                    @error('tarif_horaire')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
                </div>

                {{-- Localisation --}}
                <div>
                    <label for="localisation" class="block text-sm font-semibold text-gray-700 mb-2">Localisation <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-map-marker-alt text-sm"></i></span>
                        <input type="text" id="localisation" name="localisation" value="{{ old('localisation', $atelier->localisation) }}"
                               placeholder="ex: La Marsa, Tunis" class="{{ $champ('localisation') }}">
                    </div>
                    @error('localisation')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Portfolio --}}
            <div>
                <label for="portfolio_url" class="block text-sm font-semibold text-gray-700 mb-2">Lien du portfolio <span class="text-gray-400 font-normal">(optionnel)</span></label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-link text-sm"></i></span>
                    <input type="url" id="portfolio_url" name="portfolio_url" value="{{ old('portfolio_url', $atelier->portfolio_url) }}"
                           placeholder="https://instagram.com/mon-atelier" class="{{ $champ('portfolio_url') }}">
                </div>
                @error('portfolio_url')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Présentation</label>
                <textarea id="description" name="description" rows="4" placeholder="Votre savoir-faire, vos réalisations, vos engagements…"
                          class="w-full px-4 py-3 rounded-xl border {{ $errors->has('description') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}
                                 focus:outline-none focus:ring-2 focus:ring-primary text-gray-700 text-sm resize-none">{{ old('description', $atelier->description) }}</textarea>
                @error('description')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
            </div>

            @if($edition)
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="actif" value="1" class="w-4 h-4 rounded text-primary focus:ring-primary" @checked(old('actif', $atelier->actif))>
                    <span class="text-sm text-gray-700">Disponible pour de nouveaux projets <span class="text-gray-400">(décochez pour ne plus apparaître dans le matching)</span></span>
                </label>
            @endif
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="flex-1 bg-gradient-to-r from-primary-dark to-primary text-white font-bold py-3.5 px-6 rounded-xl
                           hover:shadow-lg hover:scale-[1.01] transition-all duration-200 flex items-center justify-center gap-2">
                <i class="fas fa-save"></i> {{ $edition ? 'Enregistrer' : 'Créer mon atelier' }}
            </button>
            <a href="{{ $edition ? route('upcycling.ateliers.show', $atelier) : route('upcycling.dashboard') }}"
               class="px-6 py-3.5 rounded-xl border-2 border-gray-200 text-gray-600 font-semibold hover:bg-gray-50 transition-all flex items-center gap-2">
                <i class="fas fa-times"></i> Annuler
            </a>
        </div>
    </form>
</div>

@endsection
