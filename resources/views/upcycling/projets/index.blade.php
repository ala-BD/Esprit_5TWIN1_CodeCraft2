@extends('upcycling.layouts.upcycling')

@php $estAtelier = Auth::user()->role === 'ATELIER'; @endphp

@section('title', $estAtelier ? 'Projets reçus' : 'Mes projets')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Projets</span>
@endsection

@section('upcycling-content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="font-display text-3xl font-extrabold text-gray-900">{{ $estAtelier ? 'Projets reçus' : 'Mes projets' }}</h1>
        <p class="text-gray-500 mt-1">
            {{ $estAtelier ? 'Chiffrez les demandes et faites avancer vos créations' : 'Suivez vos transformations, de la photo au produit fini' }}
            · <span class="font-semibold text-gray-700">{{ $projets->total() }}</span> projet(s)
        </p>
    </div>
    @unless($estAtelier)
        <a href="{{ route('upcycling.projets.create') }}" class="btn-primary">
            <i class="fas fa-camera"></i> Nouvelle demande
        </a>
    @endunless
</div>

{{-- Filtres --}}
<form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 mb-4 flex flex-col sm:flex-row gap-3">
    <div class="relative flex-1">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-search text-sm"></i></span>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher un vêtement ou un produit…"
               class="w-full pl-11 pr-4 py-2.5 rounded-xl border-0 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary text-sm">
    </div>
    <input type="hidden" name="statut" value="{{ request('statut') }}">
    <button class="px-5 py-2.5 rounded-xl bg-navy text-white text-sm font-semibold hover:bg-navy-light transition-colors">
        <i class="fas fa-search mr-1"></i> Rechercher
    </button>
</form>

{{-- Statuts en pastilles --}}
<div class="flex gap-2 overflow-x-auto pb-2 mb-6">
    @php
        $pastilles = ['' => 'Tous'] + collect(\App\Models\ProjetUpcycling::ETAPES)->map(fn ($e) => $e['label'])->all() + ['ANNULE' => 'Annulés'];
    @endphp
    @foreach($pastilles as $code => $label)
        <a href="{{ route('upcycling.projets.index', array_filter(['statut' => $code, 'q' => request('q')])) }}"
           class="flex-shrink-0 text-xs font-semibold px-4 py-2 rounded-full border transition-colors
                  {{ (string) request('statut') === (string) $code ? 'bg-primary text-white border-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-primary hover:text-primary' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

@if($projets->isEmpty())
    <div class="bg-white rounded-3xl border border-dashed border-gray-200 text-center py-20">
        <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-primary/10 to-accent/20 flex items-center justify-center mx-auto mb-5">
            <i class="fas fa-tshirt text-primary text-3xl"></i>
        </div>
        <h3 class="font-display font-bold text-gray-800 text-lg">Aucun projet</h3>
        <p class="text-gray-500 text-sm mt-1 mb-6 max-w-sm mx-auto">
            {{ $estAtelier ? 'Les demandes des clients qui vous choisissent apparaîtront ici.' : "Prenez un vêtement en photo et laissez l'IA vous inspirer." }}
        </p>
        @unless($estAtelier)
            <a href="{{ route('upcycling.projets.create') }}" class="btn-primary"><i class="fas fa-camera"></i> Nouvelle demande</a>
        @endunless
    </div>
@else
    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($projets as $projet)
            @include('upcycling.partials.projet-card', ['projet' => $projet])
        @endforeach
    </div>

    @if($projets->hasPages())
        <div class="mt-8">{{ $projets->links() }}</div>
    @endif
@endif

@endsection
