@extends('layouts.app')
@section('title', 'Espace Recycleur')
@section('body-class', 'no-hero')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-emerald-50/30 py-10">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-10">
        <div>
            <p class="text-sm font-semibold text-emerald-600 uppercase tracking-widest mb-1">
                <i class="fas fa-recycle mr-1"></i> Espace Recycleur
            </p>
            <h1 class="font-display text-3xl font-bold text-gray-900">
                Bonjour, <span class="text-emerald-600">{{ Auth::user()->prenom ?? Auth::user()->name }}</span> ♻️
            </h1>
            <p class="text-gray-500 mt-1 text-sm">Gérez vos lots textiles et profitez de la remise 10% sur la marketplace.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('recyclage.dashboard') }}"
               class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-700 to-emerald-500 text-white font-semibold px-5 py-3 rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all text-sm">
                <i class="fas fa-recycle"></i> Module recyclage
            </a>
            <a href="{{ route('articles.index') }}"
               class="inline-flex items-center gap-2 border border-gray-200 text-gray-700 font-semibold px-5 py-3 rounded-xl hover:border-emerald-400 hover:text-emerald-600 transition-all text-sm">
                <i class="fas fa-store"></i> Marketplace
            </a>
        </div>
    </div>

    {{-- STATS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-box text-emerald-500"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_commandes'] }}</p>
            <p class="text-gray-500 text-xs mt-0.5">Commandes passées</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-check-circle text-green-500"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['livrees'] }}</p>
            <p class="text-gray-500 text-xs mt-0.5">Commandes livrées</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-heart text-rose-500"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">{{ $stats['favoris'] }}</p>
            <p class="text-gray-500 text-xs mt-0.5">Favoris</p>
        </div>
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-all group">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 rounded-xl bg-yellow-50 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="fas fa-percentage text-yellow-500"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-900">10%</p>
            <p class="text-gray-500 text-xs mt-0.5">Remise atelier</p>
        </div>
    </div>

    {{-- REMISE BANNER --}}
    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-2xl p-5 text-white mb-6 flex items-center gap-4 shadow-md">
        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
            <i class="fas fa-tag text-xl"></i>
        </div>
        <div>
            <p class="font-bold text-lg">Remise automatique de 10 %</p>
            <p class="text-white/80 text-sm">En tant que recycleur, vous bénéficiez d'une remise de 10% sur tous les articles des ateliers. Elle s'applique automatiquement lors du paiement.</p>
        </div>
    </div>

    {{-- GRID --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="font-display font-bold text-gray-900 text-lg">
                        <i class="fas fa-receipt text-emerald-500 text-sm mr-2"></i>Mes commandes récentes
                    </h2>
                    <a href="{{ route('commandes.index') }}" class="text-sm text-emerald-600 hover:underline font-medium">Tout voir →</a>
                </div>
                @if($commandes->isEmpty())
                    <div class="py-14 text-center">
                        <i class="fas fa-box text-gray-200 text-4xl mb-3"></i>
                        <p class="text-gray-400 text-sm">Aucune commande</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-50">
                        @foreach($commandes as $commande)
                        <div class="px-6 py-4 hover:bg-emerald-50/20 transition-colors flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-sm text-gray-900">{{ $commande->numero }}</p>
                                <p class="text-xs text-gray-400">{{ $commande->date_commande->format('d/m/Y') }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold">{{ number_format($commande->montant_total,2) }} DT</span>
                                <a href="{{ route('commandes.show', $commande) }}" class="text-xs text-emerald-600 hover:underline">Voir →</a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <div class="space-y-5">
            @if($commandeAConfirmer)
            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-5 text-white shadow-lg">
                <p class="font-bold text-sm mb-2"><i class="fas fa-truck-fast mr-1 animate-bounce"></i> Colis en route !</p>
                <p class="text-white/90 text-xs mb-4">Commande <strong>{{ $commandeAConfirmer->numero }}</strong> expédiée.</p>
                <form method="POST" action="{{ route('commandes.livraison', $commandeAConfirmer) }}">
                    @csrf @method('PATCH')
                    <button class="w-full bg-white text-emerald-700 font-bold py-2.5 rounded-xl text-sm hover:shadow-md transition-all">
                        <i class="fas fa-check mr-1"></i> Confirmer la réception
                    </button>
                </form>
            </div>
            @endif
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-bold text-gray-900 text-base mb-3"><i class="fas fa-bolt text-yellow-500 mr-2 text-sm"></i>Accès rapides</h3>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('recyclage.dashboard') }}" class="flex flex-col items-center gap-2 py-3 rounded-xl bg-emerald-50 hover:bg-emerald-100 transition-colors text-center">
                        <i class="fas fa-recycle text-emerald-600"></i>
                        <span class="text-xs font-semibold text-gray-700">Recyclage</span>
                    </a>
                    <a href="{{ route('articles.index') }}" class="flex flex-col items-center gap-2 py-3 rounded-xl bg-teal-50 hover:bg-teal-100 transition-colors text-center">
                        <i class="fas fa-store text-teal-600"></i>
                        <span class="text-xs font-semibold text-gray-700">Catalogue</span>
                    </a>
                    <a href="{{ route('favoris.index') }}" class="flex flex-col items-center gap-2 py-3 rounded-xl bg-rose-50 hover:bg-rose-100 transition-colors text-center">
                        <i class="fas fa-heart text-rose-500"></i>
                        <span class="text-xs font-semibold text-gray-700">Favoris</span>
                    </a>
                    <a href="{{ route('commandes.index') }}" class="flex flex-col items-center gap-2 py-3 rounded-xl bg-green-50 hover:bg-green-100 transition-colors text-center">
                        <i class="fas fa-box text-green-500"></i>
                        <span class="text-xs font-semibold text-gray-700">Commandes</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
