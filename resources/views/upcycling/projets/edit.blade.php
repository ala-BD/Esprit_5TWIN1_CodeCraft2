@extends('upcycling.layouts.upcycling')

@section('title', 'Modifier la demande')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('upcycling.projets.index') }}" class="hover:text-primary transition-colors">Projets</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('upcycling.projets.show', $projet) }}" class="hover:text-primary transition-colors">Projet #{{ $projet->id }}</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Modifier</span>
@endsection

@section('upcycling-content')

<div class="mb-8">
    <h1 class="font-display text-3xl font-extrabold text-gray-900">Modifier la demande</h1>
    <p class="text-gray-500 mt-1">Une nouvelle photo, ou un autre type, matière ou état : l'IA proposera de nouvelles idées.</p>
</div>

<form method="POST" action="{{ route('upcycling.projets.update', $projet) }}" enctype="multipart/form-data" novalidate
      onsubmit="document.getElementById('attente-ia').classList.remove('hidden')">
    @csrf @method('PUT')

    <div class="grid lg:grid-cols-5 gap-6">
        <div class="lg:col-span-2">
            @include('upcycling.partials.photo-input', [
                'nom'      => 'photo',
                'actuelle' => $projet->photo_url,
                'titre'    => 'Ajoutez une photo du vêtement',
                'aide'     => 'Glissez-déposez ou cliquez pour choisir',
                'hauteur'  => 'h-80',
            ])
        </div>

        <div class="lg:col-span-3 space-y-5">
            @include('upcycling.projets._form')

            <div class="flex gap-3">
                <button type="submit" class="btn-primary flex-1 !py-4 !rounded-2xl">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <a href="{{ route('upcycling.projets.show', $projet) }}"
                   class="px-6 py-4 rounded-2xl border-2 border-gray-200 text-gray-600 font-semibold hover:bg-gray-50 transition-all flex items-center gap-2">
                    Annuler
                </a>
            </div>
        </div>
    </div>
</form>

<div id="attente-ia" class="hidden fixed inset-0 z-[60] bg-navy-dark/80 backdrop-blur-sm flex items-center justify-center p-6">
    <div class="bg-white rounded-3xl shadow-2xl p-10 max-w-sm w-full text-center">
        <i class="fas fa-circle-notch fa-spin text-primary text-4xl mb-5"></i>
        <p class="font-display font-bold text-xl text-gray-900">Enregistrement…</p>
        <p class="text-sm text-gray-500 mt-2">Si le vêtement a changé, l'IA prépare de nouvelles idées.</p>
    </div>
</div>

@endsection
