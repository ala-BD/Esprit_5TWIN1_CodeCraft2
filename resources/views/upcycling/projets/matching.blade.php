@extends('upcycling.layouts.upcycling')

@section('title', 'Matching atelier')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('upcycling.projets.index') }}" class="hover:text-primary transition-colors">Projets</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('upcycling.projets.show', $projet) }}" class="hover:text-primary transition-colors">Projet #{{ $projet->id }}</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Matching</span>
@endsection

@php
    $m = \App\Services\Upcycling\MatchingAtelierService::class;
    $criteres = [
        'specialite'    => ['Spécialité',    $m::POIDS_SPECIALITE,    'fa-bullseye'],
        'note'          => ['Avis clients',  $m::POIDS_NOTE,          'fa-star'],
        'tarif'         => ['Tarif',         $m::POIDS_TARIF,         'fa-coins'],
        'disponibilite' => ['Disponibilité', $m::POIDS_DISPONIBILITE, 'fa-calendar-check'],
    ];
@endphp

@section('upcycling-content')

{{-- Rappel du projet --}}
<div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5 mb-8 flex flex-col sm:flex-row sm:items-center gap-5">
    <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 p-2 flex-shrink-0">
        @include('upcycling.partials.photo', ['url' => $projet->photo_url, 'alt' => $projet->type_vetement])
    </div>
    <div class="flex-1">
        <p class="text-xs font-semibold uppercase tracking-wider text-primary">Matching atelier</p>
        <h1 class="font-display text-2xl font-extrabold text-gray-900">{{ $projet->produit_final }}</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ \App\Models\Atelier::SPECIALITES[$projet->categorie_produit] ?? 'Catégorie libre' }}
            @if($projet->prix_estime_min) · estimation IA {{ number_format($projet->prix_estime_min, 0) }}–{{ number_format($projet->prix_estime_max, 0) }} DT @endif
        </p>
    </div>
    <div class="hidden md:grid grid-cols-2 gap-2 text-[11px] text-gray-500">
        @foreach($criteres as [$label, $poids, $icone])
            <span><i class="fas {{ $icone }} text-primary w-4"></i> {{ $label }} <strong class="text-gray-700">{{ $poids }} pts</strong></span>
        @endforeach
    </div>
</div>

@if($classement->isEmpty())
    <div class="bg-white rounded-3xl border border-dashed border-gray-200 text-center py-16">
        <i class="fas fa-store-slash text-gray-300 text-3xl mb-3 block"></i>
        <p class="text-gray-500 text-sm">Aucun atelier n'est disponible pour le moment.</p>
    </div>
@else
<div class="space-y-4">
    @foreach($classement as $rang => $resultat)
        @php $atelier = $resultat['atelier']; $estActuel = $projet->atelier_id === $atelier->id; $premier = $rang === 0; @endphp
        <div class="relative bg-white rounded-3xl border-2 overflow-hidden transition-all {{ $premier ? 'border-primary shadow-xl shadow-primary/10' : 'border-gray-100 shadow-sm hover:shadow-md' }}">
            @if($premier)
                <span class="absolute top-0 right-0 text-[11px] font-bold text-white bg-gradient-to-r from-primary to-accent-dark px-4 py-1.5 rounded-bl-2xl">
                    <i class="fas fa-crown"></i> Meilleur choix
                </span>
            @endif

            <div class="flex flex-col md:flex-row">
                {{-- Couverture --}}
                <div class="md:w-44 h-36 md:h-auto bg-gradient-to-br from-slate-50 to-slate-100 p-3 flex-shrink-0">
                    @include('upcycling.partials.photo', ['url' => $atelier->couverture_url, 'alt' => $atelier->nom, 'icone' => $atelier->icone])
                </div>

                <div class="flex-1 p-5 flex flex-col lg:flex-row gap-5">
                    {{-- Infos + critères --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-bold text-gray-400">#{{ $rang + 1 }}</span>
                            <a href="{{ route('upcycling.ateliers.show', $atelier) }}" class="font-display font-bold text-lg text-gray-900 hover:text-primary">{{ $atelier->nom }}</a>
                            @if($atelier->note_moyenne > 0)
                                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full"><i class="fas fa-star"></i> {{ number_format($atelier->note_moyenne, 1) }}</span>
                            @else
                                <span class="text-[10px] font-semibold text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">Nouveau</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas {{ $atelier->icone }} mr-1"></i>{{ $atelier->specialite_label }}
                            · <i class="fas fa-map-marker-alt mr-1"></i>{{ $atelier->localisation }}
                            · {{ number_format($atelier->tarif_horaire, 0) }} DT/h
                            · {{ $atelier->charge_en_cours }} projet(s) en cours
                        </p>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
                            @foreach($criteres as $cle => [$label, $max, $icone])
                                <div>
                                    <div class="flex justify-between text-[11px] text-gray-500 mb-1">
                                        <span><i class="fas {{ $icone }} mr-0.5"></i> {{ $label }}</span>
                                        <span class="font-semibold">{{ $resultat['details'][$cle] }}/{{ $max }}</span>
                                    </div>
                                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full bg-gradient-to-r from-primary to-accent" style="width: {{ $max ? $resultat['details'][$cle] / $max * 100 : 0 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Score + action --}}
                    <div class="flex lg:flex-col items-center justify-between lg:justify-center gap-4 lg:w-44 flex-shrink-0">
                        <div class="relative w-20 h-20">
                            <svg viewBox="0 0 36 36" class="w-20 h-20 -rotate-90">
                                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f1f5f9" stroke-width="3"></circle>
                                <circle cx="18" cy="18" r="15.9" fill="none" stroke="#0d9488" stroke-width="3"
                                        stroke-dasharray="{{ $resultat['score'] }} 100" stroke-linecap="round"></circle>
                            </svg>
                            <span class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-xl font-extrabold text-gray-900 leading-none">{{ $resultat['score'] }}</span>
                                <span class="text-[9px] text-gray-400">/ 100</span>
                            </span>
                        </div>
                        @if($estActuel)
                            <p class="text-sm font-semibold text-primary"><i class="fas fa-check-circle"></i> Atelier actuel</p>
                        @else
                            <form method="POST" action="{{ route('upcycling.projets.atelier', $projet) }}" class="w-full">
                                @csrf @method('PATCH')
                                <input type="hidden" name="atelier_id" value="{{ $atelier->id }}">
                                <button class="w-full inline-flex items-center justify-center gap-2 text-sm font-bold py-2.5 px-4 rounded-xl transition-all
                                               {{ $premier ? 'bg-gradient-to-r from-primary-dark to-primary text-white hover:shadow-lg' : 'border-2 border-gray-200 text-gray-700 hover:border-primary hover:text-primary' }}">
                                    <i class="fas fa-paper-plane"></i> Demander un devis
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif

@endsection
