@extends('upcycling.layouts.upcycling')

@section('title', 'Demander un upcycling')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('upcycling.projets.index') }}" class="hover:text-primary transition-colors">Projets</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Nouvelle demande</span>
@endsection

@section('upcycling-content')

<div class="max-w-3xl">

    <div class="mb-8">
        <h1 class="font-display text-2xl font-bold text-gray-900">Demander un upcycling</h1>
        <p class="text-gray-500 text-sm mt-1">Décrivez votre vêtement : l'IA vous proposera 3 idées de transformation, puis le matching vous recommandera l'atelier idéal.</p>
    </div>

    <form method="POST" action="{{ route('upcycling.projets.store') }}" class="space-y-6" novalidate
          onsubmit="this.querySelector('[type=submit]').disabled = true; this.querySelector('[data-label]').textContent = 'L\'IA réfléchit…';">
        @csrf

        @include('upcycling.projets._form', ['projet' => new \App\Models\ProjetUpcycling()])

        {{-- Lien avec un don (module Collecte) --}}
        @if($dons->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-900 flex items-center gap-2 mb-1">
                <i class="fas fa-hand-holding-heart text-primary"></i>
                Partir d'un don de la plateforme <span class="text-gray-400 font-normal text-sm">(optionnel)</span>
            </h2>
            <p class="text-gray-500 text-xs mb-4">Vous pouvez faire transformer un vêtement donné et en attente de tri.</p>
            <select name="don_vetement_id"
                    class="w-full px-4 py-3 rounded-xl border {{ $errors->has('don_vetement_id') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} focus:outline-none focus:ring-2 focus:ring-primary text-sm text-gray-700">
                <option value="">— Mon propre vêtement —</option>
                @foreach($dons as $don)
                    <option value="{{ $don->id }}" @selected(old('don_vetement_id') == $don->id)>
                        Don #{{ $don->id }} — {{ $don->type }} en {{ $don->matiere }}, taille {{ $don->taille }} ({{ $don->etat }})
                    </option>
                @endforeach
            </select>
            @error('don_vetement_id')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
        </div>
        @endif

        {{-- Info IA --}}
        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-robot text-blue-500"></i>
            </div>
            <div>
                <p class="font-semibold text-blue-800 text-sm">Génération d'idées par IA</p>
                <p class="text-blue-600 text-xs mt-1 leading-relaxed">
                    Un modèle de langage analyse le type, la matière et l'état du vêtement pour proposer 3 transformations
                    (produit, difficulté, temps estimé, matériaux). La génération peut prendre quelques secondes.
                </p>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit"
                    class="flex-1 bg-gradient-to-r from-primary-dark to-primary text-white font-bold py-3.5 px-6 rounded-xl
                           hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all duration-200 flex items-center justify-center gap-2
                           disabled:opacity-60 disabled:cursor-wait">
                <i class="fas fa-magic"></i>
                <span data-label>Générer les idées</span>
            </button>
            <a href="{{ route('upcycling.projets.index') }}"
               class="px-6 py-3.5 rounded-xl border-2 border-gray-200 text-gray-600 font-semibold hover:bg-gray-50 transition-all flex items-center gap-2">
                <i class="fas fa-times"></i> Annuler
            </a>
        </div>
    </form>
</div>

@endsection
