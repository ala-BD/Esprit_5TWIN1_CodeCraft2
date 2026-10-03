@extends('recyclage.layouts.recyclage')

@section('title', 'Enregistrer un lot')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('recyclage.lots.index') }}" class="hover:text-primary-DEFAULT transition-colors">Lots</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Enregistrer</span>
@endsection

@section('recyclage-content')

<div class="max-w-2xl">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="font-display text-2xl font-bold text-gray-900">Enregistrer un lot textile</h1>
        <p class="text-gray-500 text-sm mt-1">
            L'IA analysera la composition pour recommander la meilleure filière de traitement.
        </p>
    </div>

    {{-- Erreurs globales --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex items-start gap-3">
            <i class="fas fa-exclamation-circle text-red-500 mt-0.5 flex-shrink-0"></i>
            <div>
                @foreach($errors->all() as $error)
                    <p class="text-red-600 text-sm">{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Formulaire --}}
    <form method="POST" action="{{ route('recyclage.lots.store') }}" class="space-y-6" novalidate>
        @csrf

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">

            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-info-circle text-primary-DEFAULT"></i>
                Informations du lot
            </h2>

            {{-- Référence --}}
            <div>
                <label for="reference" class="block text-sm font-semibold text-gray-700 mb-2">
                    Référence du lot <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="fas fa-hashtag text-sm"></i>
                    </span>
                    <input type="text" id="reference" name="reference"
                           value="{{ old('reference', 'LOT-' . date('Y') . '-') }}"
                           placeholder="LOT-2026-001"
                           class="w-full pl-11 pr-4 py-3 rounded-xl border {{ $errors->has('reference') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}
                                  focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                  transition-all duration-200 text-gray-700 font-mono uppercase">
                </div>
                @error('reference')
                    <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Poids --}}
            <div>
                <label for="poids_kg" class="block text-sm font-semibold text-gray-700 mb-2">
                    Poids (kg) <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="fas fa-weight text-sm"></i>
                    </span>
                    <input type="number" id="poids_kg" name="poids_kg"
                           value="{{ old('poids_kg') }}"
                           placeholder="ex: 25.5"
                           step="0.1" min="0.1"
                           class="w-full pl-11 pr-16 py-3 rounded-xl border {{ $errors->has('poids_kg') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}
                                  focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                  transition-all duration-200 text-gray-700">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">kg</span>
                </div>
                @error('poids_kg')
                    <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Composition --}}
            <div>
                <label for="composition" class="block text-sm font-semibold text-gray-700 mb-2">
                    Composition textile <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-3.5 text-gray-400">
                        <i class="fas fa-layer-group text-sm"></i>
                    </span>
                    <input type="text" id="composition" name="composition"
                           value="{{ old('composition') }}"
                           placeholder="ex: 60% coton, 40% polyester"
                           class="w-full pl-11 pr-4 py-3 rounded-xl border {{ $errors->has('composition') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}
                                  focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                  transition-all duration-200 text-gray-700">
                </div>
                <p class="mt-1.5 text-xs text-gray-400">
                    <i class="fas fa-robot mr-1 text-blue-400"></i>
                    L'IA se base sur la composition pour recommander la filière
                </p>
                @error('composition')
                    <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Origine --}}
            <div>
                <label for="origine" class="block text-sm font-semibold text-gray-700 mb-2">
                    Origine / Provenance <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="fas fa-map-marker-alt text-sm"></i>
                    </span>
                    <input type="text" id="origine" name="origine"
                           value="{{ old('origine') }}"
                           placeholder="ex: Collecte Tunis Centre, Point Ariana"
                           class="w-full pl-11 pr-4 py-3 rounded-xl border {{ $errors->has('origine') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}
                                  focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                  transition-all duration-200 text-gray-700">
                </div>
                @error('origine')
                    <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </p>
                @enderror
            </div>
        </div>

        {{-- Info IA --}}
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-robot text-blue-500"></i>
            </div>
            <div>
                <p class="font-semibold text-blue-800 text-sm">Recommandation IA automatique</p>
                <p class="text-blue-600 text-xs mt-1 leading-relaxed">
                    Après enregistrement, l'IA analysera la composition du lot et recommandera automatiquement la filière optimale :
                    <strong>Revente</strong>, <strong>Upcycling</strong>, <strong>Recyclage fibre</strong> ou <strong>Recyclage énergie</strong>.
                </p>
            </div>
        </div>

        {{-- Boutons --}}
        <div class="flex gap-3">
            <button type="submit"
                    class="flex-1 bg-gradient-to-r from-primary-dark to-primary-DEFAULT text-white
                           font-bold py-3.5 px-6 rounded-xl hover:shadow-lg hover:scale-[1.01]
                           active:scale-[0.99] transition-all duration-200
                           flex items-center justify-center gap-2">
                <i class="fas fa-plus-circle"></i>
                Enregistrer le lot
            </button>
            <a href="{{ route('recyclage.lots.index') }}"
               class="px-6 py-3.5 rounded-xl border-2 border-gray-200 text-gray-600 font-semibold
                      hover:border-gray-300 hover:bg-gray-50 transition-all duration-200
                      flex items-center gap-2">
                <i class="fas fa-times"></i>
                Annuler
            </a>
        </div>
    </form>
</div>

@endsection
