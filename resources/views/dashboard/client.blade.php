@extends('layouts.app')

@section('title', 'Mon Espace Client')
@section('body-class', 'no-hero')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-green-50/30 py-10">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- ── HEADER ────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10">
        <div>
            <p class="text-sm font-semibold text-primary-DEFAULT uppercase tracking-widest mb-1">Espace Client</p>
            <h1 class="font-display text-3xl font-bold text-gray-900">
                Bonjour, <span class="text-primary-DEFAULT">{{ Auth::user()->prenom ?? Auth::user()->name }}</span> 👋
            </h1>
            <p class="text-gray-500 mt-1 text-sm">Retrouvez vos commandes, favoris et activités récentes.</p>
        </div>
        <a href="{{ route('articles.index') }}"
           class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-dark to-primary-DEFAULT text-white font-semibold px-5 py-3 rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all duration-200 text-sm">
            <i class="fas fa-shopping-bag"></i> Parcourir le catalogue
        </a>
    </div>

    {{-- ── STATS CARDS ────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">

        {{-- Total commandes --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-box text-blue-500"></i>
                </div>
                <span class="text-xs font-semibold text-blue-500 bg-blue-50 px-2 py-0.5 rounded-full">Total</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            <p class="text-gray-500 text-xs mt-0.5">Commandes passées</p>
        </div>

        {{-- En cours --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-clock text-amber-500"></i>
                </div>
                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">Actif</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['en_cours'] }}</p>
            <p class="text-gray-500 text-xs mt-0.5">En cours de livraison</p>
        </div>

        {{-- Livrées --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-check-circle text-green-500"></i>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-0.5 rounded-full">Reçues</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['livrees'] }}</p>
            <p class="text-gray-500 text-xs mt-0.5">Commandes livrées</p>
        </div>

        {{-- Favoris --}}
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-heart text-rose-500"></i>
                </div>
                <span class="text-xs font-semibold text-rose-500 bg-rose-50 px-2 py-0.5 rounded-full">Wishlist</span>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['favoris'] }}</p>
            <p class="text-gray-500 text-xs mt-0.5">Articles en favoris</p>
        </div>
    </div>

    {{-- ── MAIN CONTENT ────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- COMMANDES RÉCENTES (2/3) --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-display font-bold text-gray-900 text-lg flex items-center gap-2">
                        <i class="fas fa-receipt text-primary-DEFAULT text-sm"></i> Mes commandes récentes
                    </h2>
                    <a href="{{ route('commandes.index') }}" class="text-sm text-primary-DEFAULT hover:underline font-medium">
                        Tout voir →
                    </a>
                </div>

                @if($commandes->isEmpty())
                    <div class="py-16 text-center">
                        <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-shopping-bag text-gray-300 text-2xl"></i>
                        </div>
                        <p class="text-gray-500 font-medium">Aucune commande pour l'instant</p>
                        <a href="{{ route('articles.index') }}" class="mt-3 inline-flex items-center gap-1 text-primary-DEFAULT text-sm hover:underline">
                            Découvrir les articles <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                @else
                    <div class="divide-y divide-gray-50">
                        @foreach($commandes as $commande)
                        <div class="px-6 py-4 hover:bg-gray-50/50 transition-colors">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    {{-- Article thumbnail --}}
                                    @php $firstArticle = $commande->lignes->first()?->article; @endphp
                                    @if($firstArticle && $firstArticle->photos?->first())
                                        <img src="{{ asset('storage/' . $firstArticle->photos->first()) }}"
                                             class="w-12 h-12 rounded-xl object-cover flex-shrink-0 border border-gray-100" alt="">
                                    @else
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary-DEFAULT/20 to-primary-DEFAULT/10 flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-tshirt text-primary-DEFAULT text-sm"></i>
                                        </div>
                                    @endif

                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 text-sm truncate">
                                            {{ $commande->numero }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ $commande->date_commande->format('d/m/Y') }}
                                            · {{ $commande->lignes->count() }} article(s)
                                        </p>
                                        <p class="text-xs text-gray-500 mt-0.5 truncate">
                                            @foreach($commande->lignes->take(2) as $ligne)
                                                {{ $ligne->article?->titre }}@if(!$loop->last), @endif
                                            @endforeach
                                            @if($commande->lignes->count() > 2)
                                                <span class="text-gray-400">+{{ $commande->lignes->count() - 2 }} autres</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-col items-end gap-2 flex-shrink-0">
                                    @php
                                        $statutColors = [
                                            'EN_ATTENTE_PAIEMENT' => 'bg-purple-100 text-purple-700',
                                            'EN_ATTENTE'          => 'bg-amber-100 text-amber-700',
                                            'CONFIRMEE'           => 'bg-blue-100 text-blue-700',
                                            'EN_PREPARATION'      => 'bg-orange-100 text-orange-700',
                                            'EXPEDIEE'            => 'bg-indigo-100 text-indigo-700',
                                            'LIVREE'              => 'bg-green-100 text-green-700',
                                            'ANNULEE'             => 'bg-red-100 text-red-700',
                                        ];
                                        $statutIcons = [
                                            'EN_ATTENTE_PAIEMENT' => 'fa-credit-card',
                                            'EN_ATTENTE'          => 'fa-hourglass-half',
                                            'CONFIRMEE'           => 'fa-check',
                                            'EN_PREPARATION'      => 'fa-box-open',
                                            'EXPEDIEE'            => 'fa-truck',
                                            'LIVREE'              => 'fa-check-circle',
                                            'ANNULEE'             => 'fa-times-circle',
                                        ];
                                        $color = $statutColors[$commande->statut] ?? 'bg-gray-100 text-gray-700';
                                        $icon  = $statutIcons[$commande->statut] ?? 'fa-circle';
                                    @endphp
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full {{ $color }}">
                                        <i class="fas {{ $icon }} text-[10px]"></i>
                                        {{ $commande->statutLabel() }}
                                    </span>
                                    <p class="text-sm font-bold text-gray-900">{{ number_format($commande->montant_total, 2) }} DT</p>
                                    <a href="{{ route('commandes.show', $commande) }}"
                                       class="text-xs text-primary-DEFAULT hover:underline">Voir détails →</a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- SIDEBAR (1/3) --}}
        <div class="space-y-5">

            {{-- Accès rapides --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-display font-bold text-gray-900 text-base mb-4 flex items-center gap-2">
                    <i class="fas fa-bolt text-yellow-500 text-sm"></i> Accès rapides
                </h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('articles.index') }}"
                       class="flex flex-col items-center justify-center gap-2 py-4 rounded-xl bg-primary-DEFAULT/5 hover:bg-primary-DEFAULT/10 transition-colors text-center group">
                        <div class="w-9 h-9 bg-primary-DEFAULT rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fas fa-store text-white text-sm"></i>
                        </div>
                        <span class="text-xs font-semibold text-gray-700">Catalogue</span>
                    </a>
                    <a href="{{ route('panier.index') }}"
                       class="flex flex-col items-center justify-center gap-2 py-4 rounded-xl bg-blue-50 hover:bg-blue-100 transition-colors text-center group">
                        <div class="w-9 h-9 bg-blue-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fas fa-shopping-cart text-white text-sm"></i>
                        </div>
                        <span class="text-xs font-semibold text-gray-700">Mon panier</span>
                    </a>
                    <a href="{{ route('favoris.index') }}"
                       class="flex flex-col items-center justify-center gap-2 py-4 rounded-xl bg-rose-50 hover:bg-rose-100 transition-colors text-center group">
                        <div class="w-9 h-9 bg-rose-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fas fa-heart text-white text-sm"></i>
                        </div>
                        <span class="text-xs font-semibold text-gray-700">Mes favoris</span>
                    </a>
                    <a href="{{ route('commandes.index') }}"
                       class="flex flex-col items-center justify-center gap-2 py-4 rounded-xl bg-green-50 hover:bg-green-100 transition-colors text-center group">
                        <div class="w-9 h-9 bg-green-500 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fas fa-box text-white text-sm"></i>
                        </div>
                        <span class="text-xs font-semibold text-gray-700">Commandes</span>
                    </a>
                </div>
            </div>

            {{-- Commande en attente de confirmation livraison --}}
            @if($commandeAConfirmer)
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-5 text-white shadow-lg">
                <div class="flex items-center gap-2 mb-3">
                    <i class="fas fa-truck-fast text-white text-lg animate-bounce"></i>
                    <p class="font-bold text-sm">Colis arrivé ?</p>
                </div>
                <p class="text-white/90 text-xs mb-4">
                    Votre commande <strong>{{ $commandeAConfirmer->numero }}</strong> est expédiée. Confirmez la réception !
                </p>
                <form method="POST" action="{{ route('commandes.livraison', $commandeAConfirmer) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="w-full bg-white text-green-700 font-bold py-2.5 rounded-xl text-sm hover:shadow-md hover:scale-[1.02] transition-all">
                        <i class="fas fa-check mr-1"></i> Confirmer la réception
                    </button>
                </form>
            </div>
            @endif

            {{-- Profil rapide --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-display font-bold text-gray-900 text-base mb-4 flex items-center gap-2">
                    <i class="fas fa-user-circle text-primary-DEFAULT text-sm"></i> Mon profil
                </h3>
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary-DEFAULT to-secondary-dark flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                        {{ Auth::user()->initials }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold text-gray-900 text-sm truncate">{{ Auth::user()->full_name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</p>
                        <span class="inline-block mt-1 text-xs bg-primary-DEFAULT/10 text-primary-DEFAULT font-semibold px-2 py-0.5 rounded-full">
                            {{ Auth::user()->role }}
                        </span>
                    </div>
                </div>
                <a href="{{ route('profile.edit') }}"
                   class="block w-full text-center border border-gray-200 text-gray-700 font-medium py-2 rounded-xl text-sm hover:border-primary-DEFAULT hover:text-primary-DEFAULT transition-colors">
                    <i class="fas fa-edit mr-1"></i> Modifier le profil
                </a>
            </div>

        </div>{{-- /sidebar --}}
    </div>{{-- /grid --}}
</div>
</div>
@endsection
