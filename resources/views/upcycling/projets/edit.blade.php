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

<div class="max-w-3xl">

    <div class="mb-8">
        <h1 class="font-display text-2xl font-bold text-gray-900">Modifier la demande</h1>
        <p class="text-gray-500 text-sm mt-1">Si vous changez le type, la matière ou l'état du vêtement, l'IA proposera de nouvelles idées.</p>
    </div>

    <form method="POST" action="{{ route('upcycling.projets.update', $projet) }}" class="space-y-6" novalidate>
        @csrf @method('PUT')

        @include('upcycling.projets._form')

        <div class="flex gap-3">
            <button type="submit"
                    class="flex-1 bg-gradient-to-r from-primary-dark to-primary text-white font-bold py-3.5 px-6 rounded-xl
                           hover:shadow-lg hover:scale-[1.01] transition-all duration-200 flex items-center justify-center gap-2">
                <i class="fas fa-save"></i> Enregistrer
            </button>
            <a href="{{ route('upcycling.projets.show', $projet) }}"
               class="px-6 py-3.5 rounded-xl border-2 border-gray-200 text-gray-600 font-semibold hover:bg-gray-50 transition-all flex items-center gap-2">
                <i class="fas fa-times"></i> Annuler
            </a>
        </div>
    </form>
</div>

@endsection
