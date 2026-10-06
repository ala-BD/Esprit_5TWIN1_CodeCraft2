@extends('upcycling.layouts.upcycling')

@section('title', 'Ateliers partenaires')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Ateliers</span>
@endsection

@section('upcycling-content')

<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="font-display text-3xl font-extrabold text-gray-900">Ateliers partenaires</h1>
        <p class="text-gray-500 mt-1">Couturiers et créateurs tunisiens qui donnent une seconde vie à vos vêtements</p>
    </div>
    @if(Auth::user()->role === 'ATELIER' && !$monAtelier)
        <a href="{{ route('upcycling.ateliers.create') }}" class="btn-primary"><i class="fas fa-plus"></i> Créer mon profil</a>
    @endif
</div>

{{-- Filtres --}}
<form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 mb-4 flex flex-col sm:flex-row gap-3">
    <div class="relative flex-1">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-search text-sm"></i></span>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Nom de l'atelier ou ville…"
               class="w-full pl-11 pr-4 py-2.5 rounded-xl border-0 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary text-sm">
    </div>
    <input type="hidden" name="specialite" value="{{ request('specialite') }}">
    <button class="px-5 py-2.5 rounded-xl bg-navy text-white text-sm font-semibold hover:bg-navy-light transition-colors">
        <i class="fas fa-search mr-1"></i> Rechercher
    </button>
</form>

<div class="flex gap-2 overflow-x-auto pb-2 mb-6">
    @foreach(['' => ['Toutes', 'fa-border-all']] + collect(\App\Models\Atelier::SPECIALITES)->map(fn ($l, $c) => [$l, \App\Models\Atelier::ICONS[$c]])->all() as $code => [$label, $icone])
        <a href="{{ route('upcycling.ateliers.index', array_filter(['specialite' => $code, 'q' => request('q')])) }}"
           class="flex-shrink-0 text-xs font-semibold px-4 py-2 rounded-full border transition-colors
                  {{ (string) request('specialite') === (string) $code ? 'bg-primary text-white border-primary' : 'bg-white text-gray-600 border-gray-200 hover:border-primary hover:text-primary' }}">
            <i class="fas {{ $icone }} mr-1"></i> {{ $label }}
        </a>
    @endforeach
</div>

@if($ateliers->isEmpty())
    <div class="bg-white rounded-3xl border border-dashed border-gray-200 text-center py-16">
        <i class="fas fa-store-slash text-gray-300 text-3xl mb-3 block"></i>
        <p class="text-gray-500 text-sm">Aucun atelier ne correspond à votre recherche.</p>
    </div>
@else
    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($ateliers as $atelier)
            <a href="{{ route('upcycling.ateliers.show', $atelier) }}"
               class="group bg-white rounded-3xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                <div class="relative h-44 bg-gradient-to-br from-slate-50 to-slate-100 overflow-hidden">
                    <div class="absolute inset-0 p-4 bg-gradient-to-br from-slate-50 to-slate-100 transition-transform duration-500 group-hover:scale-105">
                        @include('upcycling.partials.photo', ['url' => $atelier->couverture_url, 'alt' => $atelier->nom, 'icone' => $atelier->icone])
                    </div>
                    <span class="absolute top-3 left-3 text-[11px] font-bold px-2.5 py-1 rounded-lg bg-white/90 text-primary shadow-sm">
                        <i class="fas {{ $atelier->icone }}"></i> {{ $atelier->specialite_label }}
                    </span>
                    @if($atelier->note_moyenne > 0)
                        <span class="absolute top-3 right-3 text-xs font-bold px-2.5 py-1 rounded-lg bg-white/90 text-amber-600 shadow-sm">
                            <i class="fas fa-star"></i> {{ number_format($atelier->note_moyenne, 1) }}
                        </span>
                    @else
                        <span class="absolute top-3 right-3 text-[11px] font-bold px-2.5 py-1 rounded-lg bg-accent text-navy shadow-sm">Nouveau</span>
                    @endif
                </div>
                <div class="p-5 flex-1 flex flex-col">
                    <h3 class="font-display font-bold text-lg text-gray-900 group-hover:text-primary transition-colors">{{ $atelier->nom }}</h3>
                    <p class="text-sm text-gray-500 line-clamp-2 mt-1 mb-4">{{ $atelier->description ?? 'Atelier partenaire TextileCycle.' }}</p>
                    <div class="mt-auto grid grid-cols-3 gap-2 text-center pt-4 border-t border-gray-100">
                        <div><p class="text-sm font-bold text-gray-900">{{ number_format($atelier->tarif_horaire, 0) }} DT</p><p class="text-[10px] text-gray-400">par heure</p></div>
                        <div><p class="text-sm font-bold text-gray-900">{{ $atelier->projets_termines }}</p><p class="text-[10px] text-gray-400">réalisation(s)</p></div>
                        <div><p class="text-sm font-bold text-gray-900 truncate">{{ \Illuminate\Support\Str::before($atelier->localisation, ',') }}</p><p class="text-[10px] text-gray-400">ville</p></div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    @if($ateliers->hasPages())
        <div class="mt-8">{{ $ateliers->links() }}</div>
    @endif
@endif

@endsection
