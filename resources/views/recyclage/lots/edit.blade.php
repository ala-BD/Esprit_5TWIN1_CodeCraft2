@extends('recyclage.layouts.recyclage')

@section('title', 'Modifier le lot ' . $lot->reference)

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('recyclage.lots.index') }}" class="hover:text-primary-DEFAULT transition-colors">Lots</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('recyclage.lots.show', $lot) }}" class="hover:text-primary-DEFAULT transition-colors">{{ $lot->reference }}</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Modifier</span>
@endsection

@section('recyclage-content')

<div class="max-w-2xl">

    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-xl bg-primary-DEFAULT/10 flex items-center justify-center">
                <i class="fas fa-edit text-primary-DEFAULT"></i>
            </div>
            <div>
                <h1 class="font-display text-2xl font-bold text-gray-900">Modifier le lot</h1>
                <p class="text-gray-500 text-sm font-mono">{{ $lot->reference }}</p>
            </div>
        </div>

        {{-- Statut actuel --}}
        @php $badge = $lot->statut_badge; @endphp
        <div class="flex items-center gap-2 mt-3">
            <span class="text-sm text-gray-500">Statut actuel :</span>
            <span class="px-3 py-1 rounded-lg text-xs font-semibold {{ $badge['class'] }}">
                {{ $badge['label'] }}
            </span>
        </div>
    </div>

    {{-- Erreurs --}}
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

    <form method="POST" action="{{ route('recyclage.lots.update', $lot) }}" class="space-y-6" novalidate>
        @csrf
        @method('PUT')

        {{-- Infos non modifiables --}}
        <div class="bg-gray-50 border border-gray-200 rounded-2xl p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                <i class="fas fa-lock text-gray-400"></i>
                Informations non modifiables
            </p>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-500 text-xs mb-1">Référence</p>
                    <p class="font-bold font-mono text-gray-900">{{ $lot->reference }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs mb-1">Poids</p>
                    <p class="font-bold text-gray-900">{{ $lot->poids_kg }} kg</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs mb-1">Filière IA</p>
                    @php $filiere = $lot->filiere_ia_badge; @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-semibold {{ $filiere['class'] }}">
                        <i class="fas {{ $filiere['icon'] }} text-xs"></i> {{ $filiere['label'] }}
                    </span>
                </div>
                <div>
                    <p class="text-gray-500 text-xs mb-1">Enregistré le</p>
                    <p class="text-gray-700">{{ $lot->created_at->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>

        {{-- Champs modifiables --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">

            <h2 class="font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-pen text-primary-DEFAULT"></i>
                Informations modifiables
            </h2>

            {{-- Composition --}}
            <div>
                <label for="composition" class="block text-sm font-semibold text-gray-700 mb-2">
                    Composition textile <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="fas fa-layer-group text-sm"></i>
                    </span>
                    <input type="text" id="composition" name="composition"
                           value="{{ old('composition', $lot->composition) }}"
                           placeholder="ex: 60% coton, 40% polyester"
                           class="w-full pl-11 pr-4 py-3 rounded-xl border
                                  {{ $errors->has('composition') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}
                                  focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                  transition-all duration-200 text-gray-700">
                </div>
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
                           value="{{ old('origine', $lot->origine) }}"
                           placeholder="ex: Collecte Tunis Centre"
                           class="w-full pl-11 pr-4 py-3 rounded-xl border
                                  {{ $errors->has('origine') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}
                                  focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                  transition-all duration-200 text-gray-700">
                </div>
                @error('origine')
                    <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Statut --}}
            @if($lot->statut !== 'CERTIFIE')
            <div>
                <label for="statut" class="block text-sm font-semibold text-gray-700 mb-2">
                    Statut du lot <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="fas fa-tag text-sm"></i>
                    </span>
                    <select id="statut" name="statut"
                            class="w-full pl-11 pr-4 py-3 rounded-xl border
                                   {{ $errors->has('statut') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}
                                   focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                   transition-all duration-200 text-gray-700 bg-white appearance-none">
                        <option value="EN_ATTENTE"    {{ old('statut', $lot->statut) === 'EN_ATTENTE'    ? 'selected' : '' }}>En attente</option>
                        <option value="EN_TRAITEMENT" {{ old('statut', $lot->statut) === 'EN_TRAITEMENT' ? 'selected' : '' }}>En traitement</option>
                        <option value="TRAITE"        {{ old('statut', $lot->statut) === 'TRAITE'        ? 'selected' : '' }}>Traité</option>
                    </select>
                </div>
                @error('statut')
                    <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </p>
                @enderror
            </div>
            @else
                <div class="bg-purple-50 border border-purple-100 rounded-xl p-4 flex items-center gap-3">
                    <i class="fas fa-lock text-purple-400"></i>
                    <p class="text-purple-700 text-sm font-medium">
                        Ce lot est certifié — le statut ne peut plus être modifié.
                    </p>
                </div>
            @endif
        </div>

        {{-- Boutons --}}
        <div class="flex gap-3">
            <button type="submit"
                    class="flex-1 bg-gradient-to-r from-primary-dark to-primary-DEFAULT text-white
                           font-bold py-3.5 px-6 rounded-xl hover:shadow-lg hover:scale-[1.01]
                           active:scale-[0.99] transition-all duration-200
                           flex items-center justify-center gap-2">
                <i class="fas fa-save"></i>
                Enregistrer les modifications
            </button>
            <a href="{{ route('recyclage.lots.show', $lot) }}"
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
