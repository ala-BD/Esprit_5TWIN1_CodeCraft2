@extends('upcycling.layouts.upcycling')

@section('title', 'Ateliers partenaires')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Ateliers</span>
@endsection

@section('upcycling-content')

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="font-display text-2xl font-bold text-gray-900">Ateliers partenaires</h1>
        <p class="text-gray-500 text-sm mt-1">Couturiers et créateurs qui donnent une seconde vie à vos vêtements</p>
    </div>
    @if(Auth::user()->role === 'ATELIER' && !$monAtelier)
        <a href="{{ route('upcycling.ateliers.create') }}"
           class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-dark to-primary text-white font-semibold px-5 py-2.5 rounded-xl hover:shadow-lg transition-all">
            <i class="fas fa-plus"></i> Créer mon profil
        </a>
    @endif
</div>

{{-- Filtres --}}
<form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-col sm:flex-row gap-3">
    <div class="relative flex-1">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-search text-sm"></i></span>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Nom ou ville…"
               class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary text-sm">
    </div>
    <select name="specialite" class="px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary text-sm text-gray-700">
        <option value="">Toutes les spécialités</option>
        @foreach(\App\Models\Atelier::SPECIALITES as $code => $label)
            <option value="{{ $code }}" @selected(request('specialite') === $code)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary-dark transition-colors">
        <i class="fas fa-filter mr-1"></i> Filtrer
    </button>
</form>

@if($ateliers->isEmpty())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 text-center py-16">
        <i class="fas fa-store-slash text-gray-300 text-3xl mb-3 block"></i>
        <p class="text-gray-500 text-sm">Aucun atelier ne correspond à votre recherche.</p>
    </div>
@else
    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($ateliers as $atelier)
            <a href="{{ route('upcycling.ateliers.show', $atelier) }}"
               class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md hover:-translate-y-0.5 transition-all group">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center">
                        <i class="fas {{ $atelier->icone }} text-primary text-lg"></i>
                    </div>
                    @if($atelier->note_moyenne > 0)
                        <span class="text-sm font-semibold text-gray-700"><i class="fas fa-star text-amber-400"></i> {{ number_format($atelier->note_moyenne, 1) }}</span>
                    @else
                        <span class="text-[10px] text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Nouveau</span>
                    @endif
                </div>
                <h3 class="font-semibold text-gray-900 group-hover:text-primary transition-colors">{{ $atelier->nom }}</h3>
                <p class="text-xs text-primary font-medium mb-2">{{ $atelier->specialite_label }}</p>
                <p class="text-sm text-gray-500 line-clamp-2 mb-4">{{ $atelier->description ?? 'Atelier partenaire TextileCycle.' }}</p>
                <div class="flex items-center justify-between text-xs text-gray-500 pt-4 border-t border-gray-100">
                    <span><i class="fas fa-map-marker-alt mr-1"></i>{{ $atelier->localisation }}</span>
                    <span>{{ number_format($atelier->tarif_horaire, 0) }} DT/h · {{ $atelier->projets_termines }} réalisé(s)</span>
                </div>
            </a>
        @endforeach
    </div>

    @if($ateliers->hasPages())
        <div class="mt-6">{{ $ateliers->links() }}</div>
    @endif
@endif

@endsection
