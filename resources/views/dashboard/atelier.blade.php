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
            <a href="{{ route('atelier.dons') }}"
               class="inline-flex items-center gap-2 border border-emerald-200 bg-emerald-50/80 text-emerald-800 font-semibold px-4 py-3 rounded-xl hover:bg-emerald-100 transition-all text-sm shadow-sm">
                <i class="fas fa-gift text-emerald-600"></i> Gisement de Dons ({{ $stats['nb_dons_disponibles'] ?? 0 }})
            </a>
            <a href="{{ route('atelier.estimations.historique') }}"
               class="inline-flex items-center gap-2 border border-purple-200 bg-purple-50/70 text-purple-700 font-semibold px-4 py-3 rounded-xl hover:bg-purple-100 transition-all text-sm shadow-sm">
                <i class="fas fa-sparkles text-purple-600"></i> Historique IA
            </a>
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

    {{-- ── AIDE À LA DÉCISION IA & RENTABILITÉ ───────────────────── --}}
    <div class="mb-10 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-40 top-0 w-48 h-48 bg-purple-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 relative z-10 mb-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-purple-500/20 text-purple-300 border border-purple-500/30 mb-2">
                    <i class="fas fa-sparkles"></i> IA Atelier · Pilotage de la Rentabilité
                </span>
                <h2 class="text-xl sm:text-2xl font-bold font-display">Performance économique & Alertes</h2>
                <p class="text-slate-300 text-sm mt-1 max-w-2xl">
                    Estimation en temps réel des charges d'upcycling, valorisation matière et détection des marges critiques pour chacun de vos articles.
                </p>
            </div>
            <a href="{{ route('atelier.estimations.historique') }}"
               class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/20 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition-all self-start lg:self-center">
                <span>Voir toutes les estimations</span>
                <i class="fas fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        {{-- 4 Mini KPI Rentabilité --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 relative z-10">
            <div class="bg-white/5 border border-white/10 rounded-2xl p-4 backdrop-blur-sm">
                <p class="text-xs text-slate-400 font-medium">Marge nette totale</p>
                <p class="text-2xl font-extrabold text-emerald-400 mt-1">
                    {{ number_format($stats['marge_totale_estimee'], 2) }} <span class="text-xs font-normal text-slate-400">DT</span>
                </p>
                <p class="text-[11px] text-slate-400 mt-0.5">Sur articles estimés</p>
            </div>

            <div class="bg-white/5 border border-white/10 rounded-2xl p-4 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-slate-400 font-medium">Articles gagnants</p>
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 shadow-sm shadow-emerald-400/50"></span>
                </div>
                <p class="text-2xl font-extrabold text-emerald-300 mt-1">{{ $stats['nb_gagnants'] }}</p>
                <p class="text-[11px] text-emerald-400/80 mt-0.5">Marge &ge; 20% (Alerte verte)</p>
            </div>

            <div class="bg-white/5 border border-white/10 rounded-2xl p-4 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-slate-400 font-medium">À surveiller</p>
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"></span>
                </div>
                <p class="text-2xl font-extrabold text-amber-300 mt-1">{{ $stats['nb_a_surveiller'] }}</p>
                <p class="text-[11px] text-amber-400/80 mt-0.5">Marge 0% à 20% (Alerte orange)</p>
            </div>

            <div class="bg-white/5 border border-white/10 rounded-2xl p-4 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-slate-400 font-medium">Articles à corriger</p>
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-400 shadow-sm shadow-rose-400/50"></span>
                </div>
                <p class="text-2xl font-extrabold text-rose-300 mt-1">{{ $stats['nb_perdants'] }}</p>
                <p class="text-[11px] text-rose-400/80 mt-0.5">Vente à perte (Alerte rouge)</p>
            </div>
        </div>

        @if($stats['nb_a_corriger'] > 0)
            <div class="mt-5 p-4 rounded-2xl bg-rose-500/15 border border-rose-500/30 flex items-start gap-3">
                <i class="fas fa-triangle-exclamation text-rose-400 text-lg mt-0.5 flex-shrink-0"></i>
                <div class="text-xs">
                    <p class="font-bold text-rose-200">Attention : {{ $stats['nb_a_corriger'] }} article(s) nécessitent un ajustement de prix ou de filière.</p>
                    <p class="text-slate-300 mt-0.5">Ces pièces présentent une marge trop faible ou déficitaire par rapport à vos coûts de main-d'œuvre et matériaux. Ouvrez l'article pour réajuster le prix conseillé ou le basculer en recyclage.</p>
                </div>
            </div>
        @endif
    </div>

    {{-- ── GISEMENT DE DONS TEXTILES DISPONIBLES ─────────────────── --}}
    @if(isset($donsDisponibles) && $donsDisponibles->count() > 0)
        <div class="mb-10 bg-white rounded-3xl border border-emerald-200 p-6 sm:p-7 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            <i class="fas fa-gift text-emerald-600"></i> Dons textiles publiés
                        </span>
                        <span class="text-xs text-slate-400 font-semibold">• Coût d'achat : 0.00 DT</span>
                    </div>
                    <h2 class="font-display font-bold text-gray-900 text-xl">
                        Matières & Vêtements Donnés à Transformer
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        L'IA analyse le potentiel économique de chaque don textile et estime votre bénéfice par pièce et par lot.
                    </p>
                </div>
                <a href="{{ route('atelier.dons') }}"
                   class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-4 py-2.5 rounded-xl transition">
                    <span>Voir tout le gisement ({{ $stats['nb_dons_disponibles'] ?? $donsDisponibles->count() }})</span>
                    <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($donsDisponibles as $don)
                    <div class="border border-slate-200/80 rounded-2xl p-4 hover:border-emerald-400 hover:shadow-md transition-all flex flex-col justify-between group bg-slate-50/50">
                        <div>
                            <div class="relative h-32 rounded-xl bg-slate-100 overflow-hidden mb-3">
                                @if($don->getFirstImageUrl())
                                    <img src="{{ $don->getFirstImageUrl() }}" alt="{{ $don->titre }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-300 text-2xl"><i class="fas fa-shirt"></i></div>
                                @endif
                                <span class="absolute top-2 left-2 bg-emerald-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-xs">
                                    0 DT (Don)
                                </span>
                                <span class="absolute top-2 right-2 bg-slate-900/80 text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-xs">
                                    {{ $don->stock }} {{ $don->stock > 1 ? 'pcs' : 'pc' }}
                                </span>
                            </div>

                            <h3 class="font-bold text-slate-900 text-xs line-clamp-1 group-hover:text-emerald-700 transition mb-1">
                                {{ $don->titre }}
                            </h3>
                            <div class="flex flex-wrap gap-1 text-[10px] text-slate-500 mb-2">
                                @if($don->matiere)<span class="px-1.5 py-0.5 rounded bg-white border border-slate-200 font-semibold">{{ $don->matiere }}</span>@endif
                                @if($don->etat)<span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-semibold">{{ $don->etat }}</span>@endif
                                @if($don->poids_kg)<span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">{{ $don->poids_kg }} kg</span>@endif
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-200/60 flex items-center justify-between">
                            <span class="text-[11px] font-bold text-emerald-700 flex items-center gap-1">
                                <i class="fas fa-circle-check text-emerald-500"></i> Potentiel IA
                            </span>
                            <a href="{{ route('articles.show', $don) }}"
                               class="text-xs font-bold text-white bg-slate-900 hover:bg-emerald-600 px-3 py-1.5 rounded-lg transition flex items-center gap-1 shadow-xs">
                                <span>Évaluer</span>
                                <i class="fas fa-arrow-right text-[9px]"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

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
                        @foreach($articles->take(6) as $article)
                        @php
                            $img = !empty($article->images) && is_array($article->images) ? $article->images[0] : null;
                            $imgUrl = $img ? (str_starts_with($img, 'http') ? $img : asset('storage/' . $img)) : null;
                            $est = $article->derniereEstimation;
                        @endphp
                        <div class="flex items-center gap-3 p-2 rounded-xl hover:bg-slate-50 transition-colors group">
                            @if($imgUrl)
                                <img src="{{ $imgUrl }}"
                                     class="w-11 h-11 rounded-lg object-cover border border-gray-100 flex-shrink-0" alt="">
                            @else
                                <div class="w-11 h-11 rounded-lg bg-orange-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-tshirt text-orange-400 text-xs"></i>
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-gray-800 truncate">{{ $article->titre }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs font-semibold text-gray-700">{{ number_format($article->prix, 2) }} DT</span>
                                    @if($est)
                                        @if($est->niveau_alerte === 'vert')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                🟢 +{{ number_format($est->marge_pourcent, 0) }}%
                                            </span>
                                        @elseif($est->niveau_alerte === 'orange')
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                                🟠 {{ number_format($est->marge_pourcent, 0) }}%
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 animate-pulse">
                                                🔴 {{ number_format($est->marge_pourcent, 0) }}%
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-[10px] text-gray-400">⚪ Non estimé</span>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('articles.edit', $article) }}"
                               class="w-7 h-7 rounded-lg bg-gray-50 group-hover:bg-orange-50 text-gray-400 group-hover:text-orange-600 flex items-center justify-center transition-colors flex-shrink-0"
                               title="Estimer / Modifier le prix">
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

            {{-- Estimations IA récentes --}}
            @if(isset($dernieresEstimations) && $dernieresEstimations->count() > 0)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-display font-bold text-gray-900 text-sm flex items-center gap-2">
                        <i class="fas fa-sparkles text-purple-600"></i> Derniers calculs IA
                    </h3>
                    <a href="{{ route('atelier.estimations.historique') }}" class="text-[11px] text-purple-600 font-bold hover:underline">
                        Tout voir →
                    </a>
                </div>
                <div class="space-y-2.5">
                    @foreach($dernieresEstimations as $est)
                    <div class="p-2.5 rounded-xl border border-gray-100 hover:border-purple-200 transition-colors bg-gray-50/50">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-800 truncate max-w-[130px]">
                                {{ $est->article?->titre ?? 'Article sans titre' }}
                            </span>
                            @if($est->niveau_alerte === 'vert')
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800">
                                    🟢 +{{ number_format($est->marge_pourcent, 0) }}%
                                </span>
                            @elseif($est->niveau_alerte === 'orange')
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">
                                    🟠 {{ number_format($est->marge_pourcent, 0) }}%
                                </span>
                            @else
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-rose-100 text-rose-800">
                                    🔴 {{ number_format($est->marge_pourcent, 0) }}%
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-gray-400 mt-1">
                            <span>Conseillé : <strong class="text-gray-700">{{ number_format($est->prix_conseille, 2) }} DT</strong></span>
                            <span>{{ $est->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

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
