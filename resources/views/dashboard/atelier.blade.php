@extends('layouts.app')

@section('title', 'Espace Atelier')
@section('body-class', 'no-hero')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-orange-50/30 py-10">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- ── HEADER ────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10">
        <div>
            <p class="text-sm font-semibold text-orange-600 uppercase tracking-widest mb-1">
                <i class="fas fa-store-alt mr-1"></i> Espace Atelier
            </p>
            <h1 class="font-display text-3xl font-bold text-gray-900">
                Bonjour, <span class="text-orange-600">{{ Auth::user()->prenom ?? Auth::user()->name }}</span> 🧵
            </h1>
            <p class="text-gray-500 mt-1 text-sm">Gérez vos articles et confirmez les commandes de vos clients.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('articles.create') }}"
               class="inline-flex items-center gap-2 bg-gradient-to-r from-orange-600 to-orange-500 text-white font-semibold px-5 py-3 rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all duration-200 text-sm">
                <i class="fas fa-plus"></i> Nouvel article
            </a>
            <a href="{{ route('articles.index') }}"
               class="inline-flex items-center gap-2 border border-gray-200 text-gray-700 font-semibold px-5 py-3 rounded-xl hover:border-orange-400 hover:text-orange-600 transition-all duration-200 text-sm">
                <i class="fas fa-eye"></i> Voir le catalogue
            </a>
        </div>
    </div>

    {{-- ── STATS ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-orange-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-tshirt text-orange-500"></i>
                </div>
                <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2 py-0.5 rounded-full">Stock</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_articles'] }}</p>
            <p class="text-gray-500 text-xs mt-0.5">Articles publiés</p>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-bell text-amber-500"></i>
                </div>
                @if($stats['en_attente'] > 0)
                    <span class="text-xs font-semibold text-white bg-amber-500 px-2 py-0.5 rounded-full animate-pulse">
                        {{ $stats['en_attente'] }} nouveau{{ $stats['en_attente'] > 1 ? 'x' : '' }}
                    </span>
                @endif
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['en_attente'] }}</p>
            <p class="text-gray-500 text-xs mt-0.5">Commandes en attente</p>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-box-open text-blue-500"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['en_preparation'] }}</p>
            <p class="text-gray-500 text-xs mt-0.5">En préparation</p>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-coins text-green-500"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['chiffre_affaires'], 0) }} DT</p>
            <p class="text-gray-500 text-xs mt-0.5">Chiffre d'affaires</p>
        </div>
    </div>

    {{-- ── COMMANDES À TRAITER ─────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-5">

            {{-- NOUVELLES COMMANDES (EN_ATTENTE) --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-display font-bold text-gray-900 text-lg flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse inline-block"></span>
                        Nouvelles commandes à confirmer
                        @if($commandesEnAttente->count() > 0)
                            <span class="ml-1 bg-amber-100 text-amber-700 text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ $commandesEnAttente->count() }}
                            </span>
                        @endif
                    </h2>
                </div>

                @if($commandesEnAttente->isEmpty())
                    <div class="py-12 text-center">
                        <i class="fas fa-check-circle text-green-300 text-4xl mb-3"></i>
                        <p class="text-gray-500 font-medium text-sm">Aucune commande en attente</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-50">
                        @foreach($commandesEnAttente as $commande)
                        <div class="px-6 py-4 hover:bg-amber-50/30 transition-colors">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="font-bold text-gray-900 text-sm">{{ $commande->numero }}</p>
                                        <span class="bg-amber-100 text-amber-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                                            En attente
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-400 mb-2">
                                        Client : <strong class="text-gray-700">{{ $commande->user?->full_name }}</strong>
                                        · {{ $commande->date_commande->format('d/m/Y à H:i') }}
                                    </p>
                                    <div class="flex flex-wrap gap-1 mb-3">
                                        @foreach($commande->lignes as $ligne)
                                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                                                {{ $ligne->article?->titre }} (×{{ $ligne->quantite }})
                                            </span>
                                        @endforeach
                                    </div>
                                    <p class="text-sm font-bold text-gray-900">{{ number_format($commande->montant_total, 2) }} DT</p>
                                </div>
                                <div class="flex flex-col gap-2 flex-shrink-0">
                                    {{-- Bouton Confirmer --}}
                                    <form method="POST" action="{{ route('commandes.statut', $commande) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="statut" value="CONFIRMEE">
                                        <input type="hidden" name="commentaire" value="Commande confirmée par l'atelier">
                                        <button type="submit"
                                                class="flex items-center gap-1.5 bg-green-500 hover:bg-green-600 text-white font-semibold px-4 py-2 rounded-xl text-xs transition-all hover:shadow-md whitespace-nowrap">
                                            <i class="fas fa-check"></i> Confirmer
                                        </button>
                                    </form>
                                    {{-- Bouton Refuser --}}
                                    <form method="POST" action="{{ route('commandes.statut', $commande) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="statut" value="ANNULEE">
                                        <input type="hidden" name="commentaire" value="Commande refusée par l'atelier">
                                        <button type="submit"
                                                class="flex items-center gap-1.5 border border-red-200 text-red-500 hover:bg-red-50 font-semibold px-4 py-2 rounded-xl text-xs transition-all whitespace-nowrap"
                                                onclick="return confirm('Refuser cette commande ?')">
                                            <i class="fas fa-times"></i> Refuser
                                        </button>
                                    </form>
                                    <a href="{{ route('commandes.show', $commande) }}"
                                       class="text-center text-xs text-primary-DEFAULT hover:underline">Détails →</a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- COMMANDES CONFIRMÉES — à préparer/expédier --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-display font-bold text-gray-900 text-lg flex items-center gap-2">
                        <i class="fas fa-box-open text-blue-500 text-sm"></i>
                        Commandes confirmées — à expédier
                        @if($commandesConfirmees->count() > 0)
                            <span class="ml-1 bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ $commandesConfirmees->count() }}
                            </span>
                        @endif
                    </h2>
                </div>

                @if($commandesConfirmees->isEmpty())
                    <div class="py-10 text-center">
                        <p class="text-gray-400 text-sm">Aucune commande confirmée en attente d'expédition</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-50">
                        @foreach($commandesConfirmees as $commande)
                        <div class="px-6 py-4 hover:bg-blue-50/20 transition-colors">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="font-bold text-gray-900 text-sm">{{ $commande->numero }}</p>
                                        <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                                            {{ $commande->statutLabel() }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-400 mb-1">
                                        Client : <strong class="text-gray-700">{{ $commande->user?->full_name }}</strong>
                                        · {{ $commande->date_commande->format('d/m/Y') }}
                                    </p>
                                    <p class="text-sm font-bold text-gray-900">{{ number_format($commande->montant_total, 2) }} DT</p>
                                </div>
                                <div class="flex flex-col gap-2 flex-shrink-0">
                                    {{-- Passer en EN_PREPARATION --}}
                                    @if($commande->statut === 'CONFIRMEE')
                                    <form method="POST" action="{{ route('commandes.statut', $commande) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="statut" value="EN_PREPARATION">
                                        <input type="hidden" name="commentaire" value="Article en cours de préparation par l'atelier">
                                        <button type="submit"
                                                class="flex items-center gap-1.5 bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-2 rounded-xl text-xs transition-all hover:shadow-md whitespace-nowrap">
                                            <i class="fas fa-box-open"></i> Préparer
                                        </button>
                                    </form>
                                    @endif
                                    {{-- Passer en EXPEDIEE --}}
                                    @if(in_array($commande->statut, ['CONFIRMEE', 'EN_PREPARATION']))
                                    <form method="POST" action="{{ route('commandes.statut', $commande) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="statut" value="EXPEDIEE">
                                        <input type="hidden" name="commentaire" value="Commande expédiée par l'atelier">
                                        <button type="submit"
                                                class="flex items-center gap-1.5 bg-indigo-500 hover:bg-indigo-600 text-white font-semibold px-4 py-2 rounded-xl text-xs transition-all hover:shadow-md whitespace-nowrap">
                                            <i class="fas fa-truck"></i> Expédier
                                        </button>
                                    </form>
                                    @endif
                                    <a href="{{ route('commandes.show', $commande) }}"
                                       class="text-center text-xs text-primary-DEFAULT hover:underline">Détails →</a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- SIDEBAR --}}
        <div class="space-y-5">

            {{-- Mes articles --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-display font-bold text-gray-900 text-base mb-4 flex items-center gap-2">
                    <i class="fas fa-tshirt text-orange-500 text-sm"></i> Mes articles
                </h3>
                @if($articles->isEmpty())
                    <p class="text-gray-400 text-sm text-center py-4">Aucun article publié</p>
                    <a href="{{ route('articles.create') }}"
                       class="block w-full text-center bg-orange-50 text-orange-600 font-semibold py-2.5 rounded-xl text-sm hover:bg-orange-100 transition-colors">
                        <i class="fas fa-plus mr-1"></i> Créer mon premier article
                    </a>
                @else
                    <div class="space-y-3">
                        @foreach($articles->take(4) as $article)
                        <div class="flex items-center gap-3 group">
                            @if($article->photos?->first())
                                <img src="{{ asset('storage/' . $article->photos->first()) }}"
                                     class="w-10 h-10 rounded-lg object-cover border border-gray-100 flex-shrink-0" alt="">
                            @else
                                <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-tshirt text-orange-400 text-xs"></i>
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ $article->titre }}</p>
                                <p class="text-xs text-gray-400">{{ number_format($article->prix, 2) }} DT · stock : {{ $article->stock }}</p>
                            </div>
                            <a href="{{ route('articles.edit', $article) }}"
                               class="text-gray-300 hover:text-orange-500 transition-colors flex-shrink-0">
                                <i class="fas fa-edit text-xs"></i>
                            </a>
                        </div>
                        @endforeach
                    </div>
                    @if($articles->count() > 4)
                        <a href="{{ route('articles.index') }}"
                           class="block mt-4 text-center text-xs text-orange-600 hover:underline font-medium">
                            Voir tous mes articles ({{ $articles->count() }}) →
                        </a>
                    @endif
                @endif
            </div>

            {{-- Profil rapide --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-display font-bold text-gray-900 text-base mb-4 flex items-center gap-2">
                    <i class="fas fa-store text-orange-500 text-sm"></i> Mon atelier
                </h3>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                        {{ Auth::user()->initials }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold text-gray-900 text-sm truncate">{{ Auth::user()->full_name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</p>
                        <span class="inline-block mt-1 text-xs bg-orange-100 text-orange-700 font-semibold px-2 py-0.5 rounded-full">
                            ATELIER
                        </span>
                    </div>
                </div>
                <a href="{{ route('profile.edit') }}"
                   class="block w-full text-center border border-gray-200 text-gray-700 font-medium py-2 rounded-xl text-sm hover:border-orange-400 hover:text-orange-600 transition-colors">
                    <i class="fas fa-edit mr-1"></i> Modifier le profil
                </a>
            </div>

            {{-- Lien vers toutes les commandes --}}
            <a href="{{ route('commandes.index') }}"
               class="block bg-gradient-to-br from-orange-500 to-amber-500 rounded-2xl p-5 text-white shadow-md hover:shadow-lg hover:scale-[1.01] transition-all">
                <i class="fas fa-list-alt text-2xl mb-2 opacity-80"></i>
                <p class="font-bold">Toutes mes commandes</p>
                <p class="text-white/80 text-xs mt-0.5">Historique complet avec filtres</p>
            </a>
        </div>

    </div>{{-- /grid --}}
</div>
</div>
@endsection
