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
    $criteres = [
        'specialite'    => ['Spécialité',     \App\Services\Upcycling\MatchingAtelierService::POIDS_SPECIALITE],
        'note'          => ['Note clients',   \App\Services\Upcycling\MatchingAtelierService::POIDS_NOTE],
        'tarif'         => ['Tarif',          \App\Services\Upcycling\MatchingAtelierService::POIDS_TARIF],
        'disponibilite' => ['Disponibilité',  \App\Services\Upcycling\MatchingAtelierService::POIDS_DISPONIBILITE],
    ];
@endphp

@section('upcycling-content')

<div class="mb-8">
    <h1 class="font-display text-2xl font-bold text-gray-900">Ateliers recommandés</h1>
    <p class="text-gray-500 text-sm mt-1">
        Pour « <strong class="text-gray-700">{{ $projet->produit_final }}</strong> »
        ({{ \App\Models\Atelier::SPECIALITES[$projet->categorie_produit] ?? 'catégorie libre' }}) —
        classement selon la spécialité, la note des clients, le tarif et la disponibilité.
    </p>
</div>

@if($classement->isEmpty())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 text-center py-16">
        <i class="fas fa-store-slash text-gray-300 text-3xl mb-3 block"></i>
        <p class="text-gray-500 text-sm">Aucun atelier n'est disponible pour le moment.</p>
    </div>
@else
<div class="space-y-4">
    @foreach($classement as $rang => $resultat)
        @php $atelier = $resultat['atelier']; $estActuel = $projet->atelier_id === $atelier->id; @endphp
        <div class="bg-white rounded-2xl shadow-sm border-2 {{ $rang === 0 ? 'border-primary' : 'border-gray-100' }} p-5">
            <div class="flex flex-col md:flex-row md:items-center gap-5">

                {{-- Score --}}
                <div class="flex md:flex-col items-center gap-3 md:w-24 flex-shrink-0">
                    <div class="relative w-16 h-16">
                        <svg viewBox="0 0 36 36" class="w-16 h-16 -rotate-90">
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="#f1f5f9" stroke-width="3"></circle>
                            <circle cx="18" cy="18" r="15.9" fill="none" stroke="#0d9488" stroke-width="3"
                                    stroke-dasharray="{{ $resultat['score'] }} 100" stroke-linecap="round"></circle>
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center font-bold text-gray-900">{{ $resultat['score'] }}</span>
                    </div>
                    @if($rang === 0)
                        <span class="text-[10px] font-bold uppercase tracking-wide text-primary bg-primary/10 px-2 py-0.5 rounded-full">Meilleur choix</span>
                    @endif
                </div>

                {{-- Infos --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <a href="{{ route('upcycling.ateliers.show', $atelier) }}" class="font-semibold text-gray-900 hover:text-primary">{{ $atelier->nom }}</a>
                        @if($atelier->note_moyenne > 0)
                            <span class="text-xs text-gray-500"><i class="fas fa-star text-amber-400"></i> {{ number_format($atelier->note_moyenne, 1) }}</span>
                        @else
                            <span class="text-[10px] text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Nouveau</span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mb-3">
                        <i class="fas {{ $atelier->icone }} mr-1"></i>{{ $atelier->specialite_label }}
                        · <i class="fas fa-map-marker-alt mr-1"></i>{{ $atelier->localisation }}
                        · {{ number_format($atelier->tarif_horaire, 0) }} DT/h
                        · {{ $atelier->charge_en_cours }} projet(s) en cours
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach($criteres as $cle => [$label, $max])
                            <div>
                                <div class="flex justify-between text-[11px] text-gray-500 mb-1">
                                    <span>{{ $label }}</span><span>{{ $resultat['details'][$cle] }}/{{ $max }}</span>
                                </div>
                                <div class="bg-gray-100 rounded-full h-1.5">
                                    <div class="bg-primary h-1.5 rounded-full" style="width: {{ $max ? $resultat['details'][$cle] / $max * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Choix --}}
                <div class="md:w-44 flex-shrink-0">
                    @if($estActuel)
                        <p class="text-center text-sm font-semibold text-primary"><i class="fas fa-check-circle"></i> Atelier actuel</p>
                    @else
                        <form method="POST" action="{{ route('upcycling.projets.atelier', $projet) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="atelier_id" value="{{ $atelier->id }}">
                            <button class="w-full inline-flex items-center justify-center gap-2 text-sm font-semibold py-2.5 rounded-xl transition-all
                                           {{ $rang === 0 ? 'bg-gradient-to-r from-primary-dark to-primary text-white hover:shadow-lg' : 'border-2 border-gray-200 text-gray-700 hover:border-primary hover:text-primary' }}">
                                <i class="fas fa-paper-plane"></i> Demander un devis
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif

@endsection
