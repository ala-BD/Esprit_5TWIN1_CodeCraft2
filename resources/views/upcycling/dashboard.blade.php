@extends('upcycling.layouts.upcycling')

@section('title', 'Dashboard Upcycling')

@php $estAtelier = Auth::user()->role === 'ATELIER'; @endphp

@section('upcycling-content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div>
        <h1 class="font-display text-2xl font-bold text-gray-900">
            Bonjour, <span class="text-primary">{{ Auth::user()->prenom ?? Auth::user()->name }}</span> 👋
        </h1>
        <p class="text-gray-500 text-sm mt-1">
            Tableau de bord — Module Upcycling M3
            @if($atelier) · <span class="font-medium text-gray-700">{{ $atelier->nom }}</span> @endif
        </p>
    </div>
    @unless($estAtelier)
        <a href="{{ route('upcycling.projets.create') }}"
           class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-dark to-primary text-white font-semibold px-5 py-2.5 rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
            <i class="fas fa-magic"></i> Demander un upcycling
        </a>
    @endunless
</div>

@if($estAtelier && !$atelier)
    {{-- Atelier sans profil --}}
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
            <i class="fas fa-id-card text-amber-500 text-lg"></i>
        </div>
        <div class="flex-1">
            <p class="font-semibold text-amber-800">Créez votre profil atelier</p>
            <p class="text-amber-600 text-sm mt-1">Votre spécialité, votre tarif et votre portfolio permettent au matching de vous proposer aux clients.</p>
        </div>
        <a href="{{ route('upcycling.ateliers.create') }}"
           class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors">
            <i class="fas fa-plus"></i> Créer mon profil
        </a>
    </div>
@else

{{-- Stats --}}
@php
    $cartes = $estAtelier
        ? [
            ['valeur' => $stats['a_chiffrer'],    'label' => 'Demandes à chiffrer', 'icon' => 'fa-file-invoice-dollar', 'couleur' => 'yellow'],
            ['valeur' => $stats['devis_attente'], 'label' => 'Devis en attente',    'icon' => 'fa-hourglass-half',      'couleur' => 'sky'],
            ['valeur' => $stats['en_cours'],      'label' => 'Projets en cours',    'icon' => 'fa-cut',                 'couleur' => 'blue'],
            ['valeur' => number_format($stats['chiffre_affaires'], 0, ',', ' ') . ' DT', 'label' => 'CA projets terminés', 'icon' => 'fa-coins', 'couleur' => 'green'],
          ]
        : [
            ['valeur' => $stats['total'],      'label' => 'Projets',             'icon' => 'fa-layer-group', 'couleur' => 'gray'],
            ['valeur' => $stats['en_attente'], 'label' => 'En attente',          'icon' => 'fa-clock',       'couleur' => 'yellow'],
            ['valeur' => $stats['en_cours'],   'label' => 'En confection',       'icon' => 'fa-cut',         'couleur' => 'blue'],
            ['valeur' => $stats['termines'],   'label' => 'Terminés',            'icon' => 'fa-check-double','couleur' => 'green'],
          ];
    $teintes = [
        'gray'   => ['bg-gray-100',  'text-gray-600',  'text-gray-900'],
        'yellow' => ['bg-yellow-50', 'text-yellow-500','text-yellow-600'],
        'sky'    => ['bg-sky-50',    'text-sky-500',   'text-sky-600'],
        'blue'   => ['bg-blue-50',   'text-blue-500',  'text-blue-600'],
        'green'  => ['bg-green-50',  'text-green-500', 'text-green-600'],
    ];
@endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    @foreach($cartes as $carte)
        @php [$fond, $icone, $texte] = $teintes[$carte['couleur']]; @endphp
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="w-11 h-11 rounded-xl {{ $fond }} flex items-center justify-center mb-4">
                <i class="fas {{ $carte['icon'] }} {{ $icone }}"></i>
            </div>
            <p class="text-2xl lg:text-3xl font-bold {{ $texte }}">{{ $carte['valeur'] }}</p>
            <p class="text-gray-500 text-sm mt-1">{{ $carte['label'] }}</p>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

    {{-- Bandeau --}}
    <div class="lg:col-span-2 bg-gradient-to-br from-primary-dark to-primary rounded-2xl p-6 text-white">
        @if($estAtelier)
            <div class="flex items-center gap-2 mb-5">
                <i class="fas fa-star text-accent"></i>
                <h3 class="font-semibold">Ma réputation</h3>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white/10 rounded-xl p-4 text-center border border-white/15">
                    <p class="text-2xl font-bold">{{ $atelier->note_moyenne > 0 ? number_format($atelier->note_moyenne, 1) : '—' }}</p>
                    <p class="text-white/70 text-xs mt-1">note moyenne / 5</p>
                </div>
                <div class="bg-white/10 rounded-xl p-4 text-center border border-white/15">
                    <p class="text-2xl font-bold">{{ $stats['termines'] }}</p>
                    <p class="text-white/70 text-xs mt-1">projets livrés</p>
                </div>
                <div class="bg-white/10 rounded-xl p-4 text-center border border-white/15">
                    <p class="text-2xl font-bold">{{ number_format($atelier->tarif_horaire, 0) }} DT</p>
                    <p class="text-white/70 text-xs mt-1">tarif horaire</p>
                </div>
            </div>
        @else
            <div class="flex items-center gap-2 mb-3">
                <i class="fas fa-robot text-accent"></i>
                <h3 class="font-semibold">Comment ça marche ?</h3>
            </div>
            <ol class="grid sm:grid-cols-4 gap-3 text-sm">
                @foreach([
                    ['fa-tshirt', 'Décrivez votre vêtement'],
                    ['fa-lightbulb', "L'IA propose 3 idées"],
                    ['fa-store', 'Le matching trouve l\'atelier'],
                    ['fa-file-signature', 'Validez le devis et suivez'],
                ] as $i => [$icone, $texte])
                    <li class="bg-white/10 rounded-xl p-3 border border-white/15">
                        <span class="text-white/50 text-xs">Étape {{ $i + 1 }}</span>
                        <p class="font-medium mt-1"><i class="fas {{ $icone }} mr-1 text-accent"></i> {{ $texte }}</p>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

    {{-- Actions rapides --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Actions rapides</h3>
        <div class="space-y-3">
            @if($estAtelier)
                <a href="{{ route('upcycling.projets.index', ['statut' => 'ATELIER_CHOISI']) }}"
                   class="flex items-center gap-3 p-3 bg-primary/5 hover:bg-primary/10 rounded-xl transition-colors">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center"><i class="fas fa-file-invoice-dollar text-primary text-sm"></i></div>
                    <div><p class="text-sm font-semibold text-gray-800">Chiffrer les demandes</p><p class="text-xs text-gray-500">Envoyer vos devis</p></div>
                </a>
                <a href="{{ route('upcycling.ateliers.edit', $atelier) }}"
                   class="flex items-center gap-3 p-3 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center"><i class="fas fa-images text-gray-500 text-sm"></i></div>
                    <div><p class="text-sm font-semibold text-gray-800">Mon portfolio</p><p class="text-xs text-gray-500">Mettre à jour mon profil</p></div>
                </a>
            @else
                <a href="{{ route('upcycling.projets.create') }}"
                   class="flex items-center gap-3 p-3 bg-primary/5 hover:bg-primary/10 rounded-xl transition-colors">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center"><i class="fas fa-plus text-primary text-sm"></i></div>
                    <div><p class="text-sm font-semibold text-gray-800">Nouvelle demande</p><p class="text-xs text-gray-500">Idées générées par l'IA</p></div>
                </a>
                <a href="{{ route('upcycling.ateliers.index') }}"
                   class="flex items-center gap-3 p-3 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors">
                    <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center"><i class="fas fa-store text-gray-500 text-sm"></i></div>
                    <div><p class="text-sm font-semibold text-gray-800">Ateliers partenaires</p><p class="text-xs text-gray-500">Voir les portfolios</p></div>
                </a>
            @endif
        </div>
    </div>
</div>

{{-- Derniers projets --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-900">Activité récente</h3>
        <a href="{{ route('upcycling.projets.index') }}" class="text-sm text-primary hover:text-primary-dark font-medium">
            Voir tout <i class="fas fa-arrow-right text-xs ml-1"></i>
        </a>
    </div>

    @forelse($projets as $projet)
        <a href="{{ route('upcycling.projets.show', $projet) }}"
           class="px-6 py-4 flex items-center justify-between gap-4 hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-cut text-primary text-sm"></i>
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-gray-900 text-sm truncate">{{ $projet->produit_final ?? ucfirst($projet->type_vetement) . ' — idée à choisir' }}</p>
                    <p class="text-gray-400 text-xs truncate">
                        {{ ucfirst($projet->type_vetement) }} en {{ $projet->matiere }}
                        · {{ $estAtelier ? $projet->client->full_name : ($projet->atelier->nom ?? 'aucun atelier') }}
                    </p>
                </div>
            </div>
            @php $badge = $projet->statut_badge; @endphp
            <span class="px-2.5 py-1 rounded-lg text-xs font-semibold flex-shrink-0 {{ $badge['class'] }}">{{ $badge['label'] }}</span>
        </a>
    @empty
        <div class="text-center py-12">
            <i class="fas fa-cut text-gray-300 text-3xl mb-3 block"></i>
            <p class="text-gray-400 text-sm">
                {{ $estAtelier ? 'Aucun projet reçu pour le moment.' : "Vous n'avez encore aucun projet d'upcycling." }}
            </p>
        </div>
    @endforelse
</div>
@endif

@endsection
