@extends('layouts.app-auth')

@section('title', 'Dashboard')

@section('content')
@php $role = Auth::user()->role; @endphp

{{-- Redirection automatique vers les espaces dédiés --}}
@if($role === 'RECYCLEUR')
    <script>window.location.replace("{{ route('recyclage.dashboard') }}");</script>
@elseif($role === 'ADMIN')
    <script>window.location.replace("{{ route('admin.users.index') }}");</script>
@elseif($role === 'COLLECTEUR')
    <script>window.location.replace("{{ route('logistique.tournees.index') }}");</script>
@elseif($role === 'ATELIER')
    <script>window.location.replace("{{ route('upcycling.dashboard') }}");</script>
@else

@php
$isDonateur = $role === 'DONATEUR';
$isClient   = $role === 'CLIENT';
$lien = fn($actif) => 'flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 '
    . ($actif ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white');
@endphp

<div class="min-h-[calc(100vh-56px)] bg-gray-50 flex">

    {{-- ===== SIDEBAR ===== --}}
    <aside class="hidden lg:flex flex-col w-64 flex-shrink-0 text-white z-30"
           style="background: linear-gradient(to bottom, #1a2744, #1B4332, #0d9488);
                  position: sticky; top: 56px; height: calc(100vh - 56px); overflow-y: auto;">

        {{-- En-tête module --}}
        <div class="px-6 pt-6 pb-5">
            <div class="flex items-center gap-2.5 mb-1">
                <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center flex-shrink-0">
                    @if($isDonateur)
                        <i class="fas fa-hand-holding-heart text-secondary-DEFAULT text-sm"></i>
                    @else
                        <i class="fas fa-user text-secondary-DEFAULT text-sm"></i>
                    @endif
                </div>
                <span class="font-bold text-white text-sm">
                    {{ $isDonateur ? 'Espace Donateur' : 'Espace Client' }}
                </span>
            </div>
            <p class="text-white/40 text-xs ml-10">RETISS — Textile Circulaire</p>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 space-y-0.5">

            {{-- Tableau de bord --}}
            <a href="{{ route('dashboard') }}" class="{{ $lien(request()->routeIs('dashboard')) }}">
                <i class="fas fa-th-large w-4 text-center"></i>
                Tableau de bord
            </a>

            @if($isDonateur)
            {{-- ---- DONATEUR ---- --}}
            <div class="pt-4 pb-1.5 px-4">
                <p class="text-white/30 text-xs uppercase tracking-wider font-semibold">Mes dons</p>
            </div>

            <a href="{{ route('collecte.dons.create') }}" class="{{ $lien(request()->routeIs('collecte.dons.create')) }}">
                <i class="fas fa-plus-circle w-4 text-center"></i>
                Faire un don
            </a>

            <a href="{{ route('collecte.dons.index') }}" class="{{ $lien(request()->routeIs('collecte.dons.index') || request()->routeIs('collecte.dons.show')) }}">
                <i class="fas fa-list w-4 text-center"></i>
                Historique des dons
            </a>

            <a href="{{ route('collecte.dashboard') }}" class="{{ $lien(request()->routeIs('collecte.dashboard') || request()->routeIs('collecte.points.*')) }}">
                <i class="fas fa-map-marker-alt w-4 text-center"></i>
                Points de collecte
            </a>
            @endif

            @if($isClient)
            {{-- ---- CLIENT ---- --}}
            <div class="pt-4 pb-1.5 px-4">
                <p class="text-white/30 text-xs uppercase tracking-wider font-semibold">Achats</p>
            </div>

            <a href="{{ route('articles.index') }}" class="{{ $lien(request()->routeIs('articles.index') || request()->routeIs('articles.show')) }}">
                <i class="fas fa-store w-4 text-center"></i>
                Marketplace
            </a>

            <a href="{{ route('commandes.index') }}" class="{{ $lien(request()->routeIs('commandes.*')) }}">
                <i class="fas fa-shopping-bag w-4 text-center"></i>
                Mes commandes
            </a>

            <div class="pt-4 pb-1.5 px-4">
                <p class="text-white/30 text-xs uppercase tracking-wider font-semibold">Upcycling</p>
            </div>

            <a href="{{ route('upcycling.dashboard') }}" class="{{ $lien(request()->routeIs('upcycling.*')) }}">
                <i class="fas fa-cut w-4 text-center"></i>
                Mes projets
            </a>
            @endif

            {{-- ---- COMMUN : MARKETPLACE POUR DONATEUR ---- --}}
            @if($isDonateur)
            <div class="pt-4 pb-1.5 px-4">
                <p class="text-white/30 text-xs uppercase tracking-wider font-semibold">Marketplace</p>
            </div>

            <a href="{{ route('articles.index') }}" class="{{ $lien(request()->routeIs('articles.index') || request()->routeIs('articles.show')) }}">
                <i class="fas fa-store w-4 text-center"></i>
                Parcourir les articles
            </a>

            <a href="{{ route('articles.create') }}" class="{{ $lien(request()->routeIs('articles.create')) }}">
                <i class="fas fa-tag w-4 text-center"></i>
                Publier un article
            </a>
            @endif

            {{-- ---- COMMUN ---- --}}
            <div class="pt-4 pb-1.5 px-4">
                <p class="text-white/30 text-xs uppercase tracking-wider font-semibold">Mon compte</p>
            </div>

            <a href="{{ route('adresses.index') }}" class="{{ $lien(request()->routeIs('adresses.*')) }}">
                <i class="fas fa-map-pin w-4 text-center"></i>
                Mes adresses
            </a>

            <a href="{{ route('profile.edit') }}" class="{{ $lien(request()->routeIs('profile.*')) }}">
                <i class="fas fa-user-circle w-4 text-center"></i>
                Mon profil
            </a>

        </nav>

        {{-- Profil bas --}}
        <div class="px-4 pb-5 mt-4">
            <div class="bg-white/10 rounded-xl p-3 flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                    <span class="text-white font-bold text-sm">{{ Auth::user()->initials ?? '?' }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-white text-xs font-semibold truncate">{{ Auth::user()->full_name }}</p>
                    <p class="text-white/50 text-xs">{{ ucfirst(strtolower($role)) }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-7 h-7 rounded-lg flex items-center justify-center text-white/50 hover:text-white hover:bg-white/10 transition-all"
                            title="Se déconnecter">
                        <i class="fas fa-sign-out-alt text-xs"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ===== CONTENU PRINCIPAL ===== --}}
    <main class="flex-1 min-w-0 p-6 lg:p-8">

        {{-- Flash success --}}
        @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 flex items-center gap-3">
            <i class="fas fa-check-circle text-green-500"></i>
            <p class="text-green-700 text-sm font-medium">{{ session('success') }}</p>
            <button onclick="this.parentElement.remove()" class="ml-auto text-green-400 hover:text-green-600">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
        @endif

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="font-display text-2xl font-bold text-gray-900">
                Bonjour, <span class="text-teal-DEFAULT">{{ Auth::user()->prenom ?? Auth::user()->name }}</span>
            </h1>
            <p class="text-gray-500 text-sm mt-0.5">
                Bienvenue dans votre espace
                <span class="font-semibold text-gray-700">{{ $isDonateur ? 'Donateur' : 'Client' }}</span>
            </p>
        </div>

        {{-- KPI cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            @if($isDonateur)
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-teal-50 flex items-center justify-center mb-3">
                    <i class="fas fa-box-open text-teal-600"></i>
                </div>
                @php $nbDons = Auth::user()->dons()->count(); @endphp
                <p class="text-2xl font-bold text-gray-900">{{ $nbDons }}</p>
                <p class="text-gray-500 text-xs mt-0.5">Don(s) effectué(s)</p>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-3">
                    <i class="fas fa-check-circle text-blue-600"></i>
                </div>
                @php $donsTraites = Auth::user()->dons()->whereIn('statut', ['VENDU','UPCYCLING','RECYCLE'])->count(); @endphp
                <p class="text-2xl font-bold text-gray-900">{{ $donsTraites }}</p>
                <p class="text-gray-500 text-xs mt-0.5">Don(s) traité(s)</p>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mb-3">
                    <i class="fas fa-tag text-emerald-600"></i>
                </div>
                @php $nbArticles = Auth::user()->articles()->count(); @endphp
                <p class="text-2xl font-bold text-gray-900">{{ $nbArticles }}</p>
                <p class="text-gray-500 text-xs mt-0.5">Article(s) publiés</p>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center mb-3">
                    <i class="fas fa-leaf text-green-600"></i>
                </div>
                @php $co2 = round(Auth::user()->dons()->count() * 2.4, 1); @endphp
                <p class="text-2xl font-bold text-gray-900">{{ $co2 }}</p>
                <p class="text-gray-500 text-xs mt-0.5">kg CO₂ évité(s)</p>
            </div>
            @endif

            @if($isClient)
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center mb-3">
                    <i class="fas fa-shopping-bag text-amber-600"></i>
                </div>
                @php $nbCommandes = Auth::user()->commandes()->count(); @endphp
                <p class="text-2xl font-bold text-gray-900">{{ $nbCommandes }}</p>
                <p class="text-gray-500 text-xs mt-0.5">Commande(s)</p>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mb-3">
                    <i class="fas fa-store text-emerald-600"></i>
                </div>
                @php $nbArticles = \App\Models\Article::where('statut', 'DISPONIBLE')->count(); @endphp
                <p class="text-2xl font-bold text-gray-900">{{ $nbArticles }}</p>
                <p class="text-gray-500 text-xs mt-0.5">Articles disponibles</p>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center mb-3">
                    <i class="fas fa-cut text-purple-600"></i>
                </div>
                @php $nbProjets = \App\Models\ProjetUpcycling::where('client_id', Auth::id())->count(); @endphp
                <p class="text-2xl font-bold text-gray-900">{{ $nbProjets }}</p>
                <p class="text-gray-500 text-xs mt-0.5">Projet(s) upcycling</p>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-3">
                    <i class="fas fa-map-pin text-blue-600"></i>
                </div>
                @php $nbAdresses = Auth::user()->adresses()->count(); @endphp
                <p class="text-2xl font-bold text-gray-900">{{ $nbAdresses }}</p>
                <p class="text-gray-500 text-xs mt-0.5">Adresse(s) enregistrée(s)</p>
            </div>
            @endif
        </div>

        {{-- Actions rapides --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-8">
            <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <i class="fas fa-bolt text-amber-500 text-sm"></i>
                Actions rapides
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">

                @if($isDonateur)
                <a href="{{ route('collecte.dons.create') }}"
                   class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-100 hover:border-teal-200 hover:bg-teal-50 transition-all text-center group">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 group-hover:bg-teal-100 flex items-center justify-center transition-colors">
                        <i class="fas fa-plus text-teal-600 text-sm"></i>
                    </div>
                    <span class="text-xs font-semibold text-gray-700">Nouveau don</span>
                </a>

                <a href="{{ route('collecte.dons.index') }}"
                   class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50 transition-all text-center group">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 group-hover:bg-blue-100 flex items-center justify-center transition-colors">
                        <i class="fas fa-list text-blue-600 text-sm"></i>
                    </div>
                    <span class="text-xs font-semibold text-gray-700">Mes dons</span>
                </a>

                <a href="{{ route('articles.create') }}"
                   class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-100 hover:border-emerald-200 hover:bg-emerald-50 transition-all text-center group">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 group-hover:bg-emerald-100 flex items-center justify-center transition-colors">
                        <i class="fas fa-tag text-emerald-600 text-sm"></i>
                    </div>
                    <span class="text-xs font-semibold text-gray-700">Publier article</span>
                </a>

                <a href="{{ route('collecte.dashboard') }}"
                   class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-100 hover:border-green-200 hover:bg-green-50 transition-all text-center group">
                    <div class="w-10 h-10 rounded-xl bg-green-50 group-hover:bg-green-100 flex items-center justify-center transition-colors">
                        <i class="fas fa-map-marker-alt text-green-600 text-sm"></i>
                    </div>
                    <span class="text-xs font-semibold text-gray-700">Points collecte</span>
                </a>
                @endif

                @if($isClient)
                <a href="{{ route('articles.index') }}"
                   class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-100 hover:border-emerald-200 hover:bg-emerald-50 transition-all text-center group">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 group-hover:bg-emerald-100 flex items-center justify-center transition-colors">
                        <i class="fas fa-store text-emerald-600 text-sm"></i>
                    </div>
                    <span class="text-xs font-semibold text-gray-700">Marketplace</span>
                </a>

                <a href="{{ route('commandes.index') }}"
                   class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-100 hover:border-amber-200 hover:bg-amber-50 transition-all text-center group">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 group-hover:bg-amber-100 flex items-center justify-center transition-colors">
                        <i class="fas fa-shopping-bag text-amber-600 text-sm"></i>
                    </div>
                    <span class="text-xs font-semibold text-gray-700">Commandes</span>
                </a>

                <a href="{{ route('upcycling.projets.create') }}"
                   class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-100 hover:border-purple-200 hover:bg-purple-50 transition-all text-center group">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 group-hover:bg-purple-100 flex items-center justify-center transition-colors">
                        <i class="fas fa-cut text-purple-600 text-sm"></i>
                    </div>
                    <span class="text-xs font-semibold text-gray-700">Upcycling</span>
                </a>

                <a href="{{ route('adresses.create') }}"
                   class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-100 hover:border-rose-200 hover:bg-rose-50 transition-all text-center group">
                    <div class="w-10 h-10 rounded-xl bg-rose-50 group-hover:bg-rose-100 flex items-center justify-center transition-colors">
                        <i class="fas fa-map-pin text-rose-600 text-sm"></i>
                    </div>
                    <span class="text-xs font-semibold text-gray-700">Adresse</span>
                </a>
                @endif
            </div>
        </div>

        {{-- Bloc impact écologique --}}
        <div class="bg-gradient-to-r from-[#1B4332] to-[#0d9488] rounded-2xl p-6 text-white">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center">
                    <i class="fas fa-leaf text-white text-sm"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-sm">Impact écologique RETISS</h3>
                    <p class="text-white/60 text-xs">Chaque vêtement recyclé compte</p>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-white/10 rounded-xl p-3 text-center">
                    <p class="text-xl font-bold">2.4 kg</p>
                    <p class="text-white/60 text-xs mt-0.5">CO₂ / kg textile</p>
                </div>
                <div class="bg-white/10 rounded-xl p-3 text-center">
                    <p class="text-xl font-bold">3 000 L</p>
                    <p class="text-white/60 text-xs mt-0.5">eau / kg économisée</p>
                </div>
                <div class="bg-white/10 rounded-xl p-3 text-center">
                    <p class="text-xl font-bold">100%</p>
                    <p class="text-white/60 text-xs mt-0.5">traçabilité certifiée</p>
                </div>
            </div>
        </div>

    </main>
</div>
@endif
@endsection
