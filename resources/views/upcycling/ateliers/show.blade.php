@extends('upcycling.layouts.upcycling')

@section('title', $atelier->nom)

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('upcycling.ateliers.index') }}" class="hover:text-primary transition-colors">Ateliers</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">{{ $atelier->nom }}</span>
@endsection

@php $estMonAtelier = $atelier->user_id === Auth::id(); @endphp

@section('upcycling-content')

{{-- ===== Bandeau ===== --}}
<section class="relative overflow-hidden rounded-[2rem] hero-gradient text-white mb-8">
    <div class="absolute -right-20 -top-20 w-72 h-72 rounded-full bg-accent/20 blur-3xl"></div>
    <div class="relative flex flex-col md:flex-row gap-6 md:items-center p-8">
        <div class="w-28 h-28 rounded-3xl bg-white p-3 shadow-2xl flex-shrink-0">
            @include('upcycling.partials.photo', ['url' => $atelier->couverture_url, 'alt' => $atelier->nom, 'icone' => $atelier->icone])
        </div>
        <div class="flex-1">
            <span class="text-xs font-semibold uppercase tracking-widest text-accent"><i class="fas {{ $atelier->icone }}"></i> {{ $atelier->specialite_label }}</span>
            <h1 class="font-display text-3xl font-extrabold mt-1">{{ $atelier->nom }}</h1>
            <p class="text-white/70 text-sm mt-2"><i class="fas fa-map-marker-alt mr-1"></i> {{ $atelier->localisation }} · Contact : {{ $atelier->user->full_name }}</p>
            @unless($atelier->actif)
                <span class="inline-block mt-3 text-xs font-semibold bg-white/15 px-3 py-1 rounded-full">Indisponible pour de nouveaux projets</span>
            @endunless
        </div>
        <div class="grid grid-cols-3 gap-3 text-center">
            <div class="bg-white/10 border border-white/15 rounded-2xl px-4 py-3">
                <p class="text-2xl font-extrabold">{{ $atelier->note_moyenne > 0 ? number_format($atelier->note_moyenne, 1) : '—' }}</p>
                <p class="text-[10px] text-white/60 uppercase">note / 5</p>
            </div>
            <div class="bg-white/10 border border-white/15 rounded-2xl px-4 py-3">
                <p class="text-2xl font-extrabold">{{ number_format($atelier->tarif_horaire, 0) }}</p>
                <p class="text-[10px] text-white/60 uppercase">DT / heure</p>
            </div>
            <div class="bg-white/10 border border-white/15 rounded-2xl px-4 py-3">
                <p class="text-2xl font-extrabold">{{ $atelier->charge }}</p>
                <p class="text-[10px] text-white/60 uppercase">en cours</p>
            </div>
        </div>
    </div>
</section>

<div class="grid lg:grid-cols-3 gap-6">

    {{-- Présentation + gestion --}}
    <div class="space-y-5">
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-display font-bold text-gray-900 mb-3">Présentation</h2>
            <p class="text-sm text-gray-600 leading-relaxed">{{ $atelier->description ?? "Cet atelier n'a pas encore rédigé de présentation." }}</p>
            @if($atelier->portfolio_url)
                <a href="{{ $atelier->portfolio_url }}" target="_blank" rel="noopener"
                   class="mt-5 flex items-center justify-center gap-2 w-full border-2 border-gray-200 hover:border-primary hover:text-primary text-gray-700 font-semibold py-2.5 rounded-xl transition-all text-sm">
                    <i class="fas fa-external-link-alt"></i> Portfolio externe
                </a>
            @endif
        </div>

        @if($estMonAtelier)
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-5 space-y-2">
            <h3 class="font-semibold text-gray-900 text-sm mb-1">Gérer mon atelier</h3>
            <a href="{{ route('upcycling.ateliers.edit', $atelier) }}"
               class="flex items-center gap-3 w-full bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold px-4 py-3 rounded-2xl transition-all text-sm">
                <i class="fas fa-pen w-4"></i> Modifier mon profil
            </a>
            <form method="POST" action="{{ route('upcycling.ateliers.destroy', $atelier) }}"
                  onsubmit="return confirm('Supprimer définitivement votre profil atelier ?')">
                @csrf @method('DELETE')
                <button class="flex items-center gap-3 w-full bg-red-50 hover:bg-red-100 text-red-600 font-semibold px-4 py-3 rounded-2xl transition-all text-sm">
                    <i class="fas fa-trash-alt w-4"></i> Supprimer mon profil
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- Réalisations --}}
    <div class="lg:col-span-2">
        <h2 class="font-display text-xl font-bold text-gray-900 mb-4 flex items-center gap-2"><i class="fas fa-images text-primary"></i> Réalisations</h2>

        @if($realisations->isEmpty())
            <div class="bg-white rounded-3xl border border-dashed border-gray-200 text-center py-14">
                <i class="fas fa-cut text-gray-300 text-3xl mb-3 block"></i>
                <p class="text-gray-400 text-sm">Aucune réalisation terminée pour le moment.</p>
            </div>
        @else
            <div class="grid sm:grid-cols-2 gap-5">
                @foreach($realisations as $projet)
                    <article class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="grid {{ $projet->photo_resultat_url ? 'grid-cols-2 divide-x divide-gray-100' : 'grid-cols-1' }} bg-gradient-to-br from-slate-50 to-slate-100">
                            <div class="relative h-44 p-3">
                                @include('upcycling.partials.photo', ['url' => $projet->photo_url, 'alt' => $projet->type_vetement])
                                <span class="absolute bottom-2 left-2 text-[10px] font-bold text-white bg-gray-900/70 px-2 py-0.5 rounded">Avant : {{ $projet->type_vetement }}</span>
                            </div>
                            @if($projet->photo_resultat_url)
                                <div class="relative h-44 p-3">
                                    @include('upcycling.partials.photo', ['url' => $projet->photo_resultat_url, 'alt' => $projet->produit_final])
                                    <span class="absolute bottom-2 left-2 text-[10px] font-bold text-white bg-primary px-2 py-0.5 rounded">Après</span>
                                </div>
                            @endif
                        </div>
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-2">
                                <p class="font-display font-bold text-gray-900 leading-snug"><i class="fas fa-arrow-right text-primary text-xs mr-1"></i>{{ $projet->produit_final }}</p>
                                @if($projet->note_client)
                                    <span class="text-xs font-bold text-amber-600 flex-shrink-0"><i class="fas fa-star"></i> {{ $projet->note_client }}/5</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Livré le {{ $projet->date_fin?->format('d/m/Y') }} · ≈ {{ number_format($projet->co2_evite_kg ?? 0, 0) }} kg CO₂ évités</p>
                            @if($projet->commentaire_client)
                                <p class="text-sm text-gray-600 italic mt-3">« {{ $projet->commentaire_client }} »
                                    <span class="not-italic text-gray-400">— {{ $projet->client->prenom ?? $projet->client->name }}</span></p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</div>

@endsection
