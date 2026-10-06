@extends('upcycling.layouts.upcycling')

@section('title', 'Dashboard Upcycling')

@php
    $estAtelier = Auth::user()->role === 'ATELIER';
    $photos = $projets->map(fn ($p) => $p->photo_url)->filter()->take(3)->values();
    if ($photos->count() < 3) {
        $photos = collect(['jean-slim.jpg', 'blazer-marine.jpg', 'chemise-bleue.jpg'])->map(fn ($f) => asset('images/upcycling/' . $f));
    }
@endphp

@section('upcycling-content')

{{-- ===== HERO ===== --}}
<section class="relative overflow-hidden rounded-[2rem] hero-gradient text-white mb-8 noise">
    <div class="absolute -right-24 -top-24 w-80 h-80 rounded-full bg-accent/20 blur-3xl"></div>
    <div class="absolute -left-10 -bottom-24 w-72 h-72 rounded-full bg-teal-light/20 blur-3xl"></div>

    <div class="relative grid lg:grid-cols-5 gap-8 items-center p-8 lg:p-10">
        <div class="lg:col-span-3">
            <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest bg-white/10 border border-white/15 px-3 py-1.5 rounded-full">
                <i class="fas fa-wand-magic-sparkles text-accent"></i> Upcycling assisté par IA
            </span>
            <h1 class="font-display text-3xl lg:text-4xl font-extrabold mt-5 leading-tight">
                @if($estAtelier && $atelier)
                    {{ $atelier->nom }}, <span class="text-accent">vos créations</span> donnent une seconde vie au textile.
                @else
                    Bonjour {{ Auth::user()->prenom ?? Auth::user()->name }}, <span class="text-accent">transformez</span> au lieu de jeter.
                @endif
            </h1>
            <p class="text-white/70 mt-4 max-w-xl">
                @if($estAtelier)
                    Chiffrez les demandes, suivez vos projets étape par étape et faites grandir votre réputation.
                @else
                    Prenez votre vêtement en photo : l'IA le reconnaît, imagine 3 transformations et vous trouvez l'atelier idéal pour la réaliser.
                @endif
            </p>
            <div class="flex flex-wrap gap-3 mt-7">
                @if($estAtelier)
                    @if($atelier)
                        <a href="{{ route('upcycling.projets.index', ['statut' => 'ATELIER_CHOISI']) }}" class="btn-primary !py-3">
                            <i class="fas fa-file-invoice-dollar"></i> Demandes à chiffrer
                        </a>
                        <a href="{{ route('upcycling.ateliers.show', $atelier) }}" class="btn-outline-white !py-3">
                            <i class="fas fa-images"></i> Mon portfolio
                        </a>
                    @else
                        <a href="{{ route('upcycling.ateliers.create') }}" class="btn-primary !py-3">
                            <i class="fas fa-id-card"></i> Créer mon profil atelier
                        </a>
                    @endif
                @else
                    <a href="{{ route('upcycling.projets.create') }}" class="btn-primary !py-3">
                        <i class="fas fa-camera"></i> Nouvelle demande
                    </a>
                    <a href="{{ route('upcycling.ateliers.index') }}" class="btn-outline-white !py-3">
                        <i class="fas fa-store"></i> Voir les ateliers
                    </a>
                @endif
            </div>
        </div>

        {{-- Collage photos --}}
        <div class="lg:col-span-2 hidden sm:flex justify-center">
            <div class="relative w-72 h-60">
                @foreach($photos as $i => $photo)
                    <div class="absolute w-40 h-48 bg-white rounded-2xl shadow-2xl p-3 transition-transform duration-500 hover:z-10 hover:scale-105"
                         style="left: {{ [0, 64, 128][$i] }}px; top: {{ [24, 0, 30][$i] }}px; transform: rotate({{ [-8, 2, 9][$i] }}deg)">
                        <img src="{{ $photo }}" alt="" class="w-full h-full object-contain">
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

@if($estAtelier && !$atelier)
    <div class="bg-amber-50 border border-amber-200 rounded-3xl p-6 flex items-start gap-4">
        <div class="w-12 h-12 rounded-2xl bg-amber-100 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-id-card text-amber-500 text-lg"></i>
        </div>
        <div>
            <p class="font-semibold text-amber-800">Votre profil atelier n'existe pas encore</p>
            <p class="text-amber-700 text-sm mt-1">Spécialité, tarif et photo permettent au matching de vous proposer aux clients.</p>
        </div>
    </div>
@else

{{-- ===== STATS ===== --}}
@php
    $cartes = $estAtelier
        ? [
            ['valeur' => $stats['a_chiffrer'],    'label' => 'Demandes à chiffrer', 'icon' => 'fa-file-invoice-dollar', 'teinte' => 'from-amber-400 to-orange-500'],
            ['valeur' => $stats['devis_attente'], 'label' => 'Devis en attente',    'icon' => 'fa-hourglass-half',      'teinte' => 'from-sky-400 to-blue-500'],
            ['valeur' => $stats['en_cours'],      'label' => 'Projets en cours',    'icon' => 'fa-cut',                 'teinte' => 'from-violet-400 to-indigo-500'],
            ['valeur' => number_format($stats['chiffre_affaires'], 0, ',', ' ') . ' DT', 'label' => 'Chiffre d\'affaires', 'icon' => 'fa-coins', 'teinte' => 'from-emerald-400 to-teal-600'],
          ]
        : [
            ['valeur' => $stats['total'],      'label' => 'Projets',       'icon' => 'fa-layer-group',  'teinte' => 'from-slate-500 to-slate-700'],
            ['valeur' => $stats['en_attente'], 'label' => 'En attente',    'icon' => 'fa-clock',        'teinte' => 'from-amber-400 to-orange-500'],
            ['valeur' => $stats['en_cours'],   'label' => 'En confection', 'icon' => 'fa-cut',          'teinte' => 'from-violet-400 to-indigo-500'],
            ['valeur' => $stats['termines'],   'label' => 'Terminés',      'icon' => 'fa-check-double', 'teinte' => 'from-emerald-400 to-teal-600'],
          ];
@endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5 mb-8">
    @foreach($cartes as $carte)
        <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br {{ $carte['teinte'] }} flex items-center justify-center shadow-lg flex-shrink-0">
                <i class="fas {{ $carte['icon'] }} text-white"></i>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-extrabold text-gray-900 truncate">{{ $carte['valeur'] }}</p>
                <p class="text-gray-500 text-xs mt-0.5">{{ $carte['label'] }}</p>
            </div>
        </div>
    @endforeach
</div>

{{-- ===== IMPACT ===== --}}
<div class="grid md:grid-cols-3 gap-4 mb-10">
    <div class="rounded-3xl p-6 bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-100">
        <i class="fas fa-leaf text-emerald-500 text-xl"></i>
        <p class="text-3xl font-extrabold text-emerald-700 mt-3">{{ number_format($impact['co2'], 0, ',', ' ') }} <span class="text-base font-semibold">kg</span></p>
        <p class="text-sm text-emerald-700/70">de CO₂ évités</p>
    </div>
    <div class="rounded-3xl p-6 bg-gradient-to-br from-sky-50 to-blue-50 border border-sky-100">
        <i class="fas fa-tint text-sky-500 text-xl"></i>
        <p class="text-3xl font-extrabold text-sky-700 mt-3">{{ number_format($impact['eau'], 0, ',', ' ') }} <span class="text-base font-semibold">L</span></p>
        <p class="text-sm text-sky-700/70">d'eau économisés</p>
    </div>
    <div class="rounded-3xl p-6 bg-gradient-to-br from-violet-50 to-fuchsia-50 border border-violet-100">
        @if($estAtelier)
            <i class="fas fa-star text-amber-400 text-xl"></i>
            <p class="text-3xl font-extrabold text-violet-700 mt-3">{{ $atelier->note_moyenne > 0 ? number_format($atelier->note_moyenne, 1) : '—' }} <span class="text-base font-semibold">/ 5</span></p>
            <p class="text-sm text-violet-700/70">note moyenne de vos clients</p>
        @else
            <i class="fas fa-recycle text-violet-500 text-xl"></i>
            <p class="text-3xl font-extrabold text-violet-700 mt-3">{{ $impact['nb'] }}</p>
            <p class="text-sm text-violet-700/70">vêtement(s) sauvé(s) de la décharge</p>
        @endif
    </div>
</div>

{{-- ===== PROJETS RÉCENTS ===== --}}
<div class="flex items-end justify-between mb-5">
    <div>
        <h2 class="font-display text-xl font-bold text-gray-900">Activité récente</h2>
        <p class="text-sm text-gray-500">Vos derniers projets mis à jour</p>
    </div>
    <a href="{{ route('upcycling.projets.index') }}" class="text-sm font-semibold text-primary hover:text-primary-dark">
        Tout voir <i class="fas fa-arrow-right text-xs ml-1"></i>
    </a>
</div>

@if($projets->isEmpty())
    <div class="bg-white rounded-3xl border border-dashed border-gray-200 text-center py-16">
        <div class="w-16 h-16 rounded-2xl bg-primary/10 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-cut text-primary text-2xl"></i>
        </div>
        <p class="font-semibold text-gray-800">{{ $estAtelier ? 'Aucun projet reçu pour le moment' : 'Aucun projet pour le moment' }}</p>
        <p class="text-sm text-gray-500 mt-1">
            {{ $estAtelier ? 'Les clients qui vous choisissent via le matching apparaîtront ici.' : 'Commencez par photographier un vêtement que vous ne portez plus.' }}
        </p>
    </div>
@else
    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($projets as $projet)
            @include('upcycling.partials.projet-card', ['projet' => $projet])
        @endforeach
    </div>
@endif
@endif

@endsection
