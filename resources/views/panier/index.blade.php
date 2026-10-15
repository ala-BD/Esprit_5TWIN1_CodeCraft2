@extends('layouts.app')

@section('title', 'Mon Panier — RETISS')

@section('styles')
<style>
    .cart-hero {
        background: linear-gradient(135deg, #111a30 0%, #1a2744 35%, #1B4332 70%, #0d9488 100%);
        position: relative;
        overflow: hidden;
    }
    .cart-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            radial-gradient(ellipse at 80% 50%, rgba(13,148,136,0.25) 0%, transparent 60%),
            radial-gradient(ellipse at 20% 80%, rgba(74,222,128,0.12) 0%, transparent 50%);
        pointer-events: none;
    }
    .cart-grid-pattern {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
        background-size: 36px 36px;
        pointer-events: none;
    }
    .cart-content-wrap {
        position: relative;
        z-index: 10;
    }
    .cart-item-row {
        transition: all 0.25s ease;
    }
    .cart-item-row.updating {
        opacity: 0.45;
        pointer-events: none;
        transform: scale(0.995);
    }
    .cart-item-row.removing {
        opacity: 0;
        transform: translateX(30px);
    }
</style>
@endsection

@section('content')
<div class="min-h-screen bg-slate-50/70 pb-20">

    {{-- ===== HERO BANNER & STEPPER ===== --}}
    <div class="cart-hero py-10 px-4 sm:px-6 lg:px-8 text-white relative">
        <div class="cart-grid-pattern"></div>
        <div class="max-w-7xl mx-auto relative z-10">

            {{-- Fil d'Ariane --}}
            <nav class="flex items-center gap-2 text-xs font-medium text-slate-300/80 mb-4">
                <a href="{{ route('home') }}" class="hover:text-teal-300 transition flex items-center gap-1">
                    <i class="fas fa-home text-[10px]"></i>
                    <span>Accueil</span>
                </a>
                <i class="fas fa-chevron-right text-[9px] text-slate-400"></i>
                <a href="{{ route('articles.index') }}" class="hover:text-teal-300 transition">Marketplace</a>
                <i class="fas fa-chevron-right text-[9px] text-slate-400"></i>
                <span class="text-teal-200 font-semibold">Mon Panier</span>
            </nav>

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/15 text-xs font-semibold text-teal-200 mb-3">
                        <i class="fas fa-recycle text-emerald-400"></i>
                        <span>Économie Circulaire & Mode Solidaire</span>
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white flex items-center gap-3 font-display">
                        <span>Mon Panier</span>
                        <span id="hero-badge-count" class="text-sm font-semibold px-3 py-1 rounded-full bg-teal-500/25 border border-teal-400/30 text-teal-200">
                            {{ $panier->nombreArticles() }} {{ Str::plural('article', $panier->nombreArticles()) }}
                        </span>
                    </h1>
                    <p class="text-sm text-slate-300/90 mt-1.5 max-w-xl">
                        Vérifiez vos pièces sélectionnées, personnalisez vos quantités et finalisez votre commande en toute sécurité.
                    </p>
                </div>

                {{-- Stepper de commande visuel --}}
                <div class="bg-white/10 backdrop-blur-md border border-white/15 rounded-2xl p-3.5 sm:p-4 flex items-center gap-2 sm:gap-4 text-xs self-start lg:self-auto">
                    {{-- Étape 1 : Panier (Active) --}}
                    <div class="flex items-center gap-2 text-white font-bold">
                        <span class="w-6 h-6 rounded-full bg-teal-400 text-slate-900 flex items-center justify-center text-xs font-black shadow-sm">1</span>
                        <span>Panier</span>
                    </div>
                    <i class="fas fa-chevron-right text-[10px] text-slate-400/80"></i>

                    {{-- Étape 2 : Livraison --}}
                    <div class="flex items-center gap-2 text-slate-300/80 font-medium">
                        <span class="w-6 h-6 rounded-full bg-white/10 text-slate-300 flex items-center justify-center text-xs">2</span>
                        <span class="hidden sm:inline">Livraison & Adresse</span>
                    </div>
                    <i class="fas fa-chevron-right text-[10px] text-slate-400/80"></i>

                    {{-- Étape 3 : Paiement --}}
                    <div class="flex items-center gap-2 text-slate-300/80 font-medium">
                        <span class="w-6 h-6 rounded-full bg-white/10 text-slate-300 flex items-center justify-center text-xs">3</span>
                        <span class="hidden sm:inline">Paiement & Validation</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ===== CONTENU PRINCIPAL ===== --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 cart-content-wrap">

        {{-- Bandeau de réassurance clair et moderne --}}
        <div class="mb-8 grid grid-cols-2 md:grid-cols-4 gap-4 p-4 rounded-3xl bg-white border border-slate-200/90 shadow-xs">
            <div class="flex items-center gap-3 px-3 py-1">
                <div class="w-10 h-10 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-shield-alt text-base"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-900">Paiement 100% Sécurisé</p>
                    <p class="text-[11px] text-slate-400">Cryptage & protection SSL</p>
                </div>
            </div>
            <div class="flex items-center gap-3 px-3 py-1">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-truck text-base"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-900">Livraison 48h - 72h</p>
                    <p class="text-[11px] text-slate-400">Expédition rapide en Tunisie</p>
                </div>
            </div>
            <div class="flex items-center gap-3 px-3 py-1">
                <div class="w-10 h-10 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-leaf text-base"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-900">Vêtements 100% Circulaires</p>
                    <p class="text-[11px] text-slate-400">Articles vérifiés & éco-conçus</p>
                </div>
            </div>
            <div class="flex items-center gap-3 px-3 py-1">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-tags text-base"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-900">-10% Ateliers Partenaires</p>
                    <p class="text-[11px] text-slate-400">Remise automatique appliquée</p>
                </div>
            </div>
        </div>

        {{-- Avertissements de synchronisation automatique (stocks changés, articles vendus...) --}}
        @if(!empty($avertissements))
            <div class="mb-6 space-y-2">
                @foreach($avertissements as $avertissement)
                    <div class="flex items-start gap-3 p-4 bg-amber-50/95 border border-amber-200 text-amber-900 rounded-2xl shadow-sm backdrop-blur-xs">
                        <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5 text-lg flex-shrink-0"></i>
                        <div class="text-sm font-medium leading-relaxed">
                            {{ $avertissement }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Messages Flash Session --}}
        @if(session('success'))
            <div class="mb-6 flex items-center gap-3 p-4 bg-emerald-50/95 border border-emerald-200 text-emerald-900 rounded-2xl shadow-sm">
                <i class="fas fa-check-circle text-emerald-500 text-lg flex-shrink-0"></i>
                <div class="text-sm font-medium">{{ session('success') }}</div>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-6 flex items-center gap-3 p-4 bg-rose-50/95 border border-rose-200 text-rose-900 rounded-2xl shadow-sm">
                <i class="fas fa-times-circle text-rose-500 text-lg flex-shrink-0"></i>
                <div class="text-sm font-medium">{{ session('error') }}</div>
            </div>
        @endif

        {{-- Toast notification dynamique --}}
        <div id="cart-toast" class="hidden mb-6 items-center justify-between p-4 rounded-2xl shadow-md border backdrop-blur-md transition-all duration-300 bg-teal-50/95 border-teal-200 text-teal-900">
            <div class="flex items-center gap-3 text-sm font-semibold">
                <i id="cart-toast-icon" class="fas fa-check-circle text-teal-500 text-base"></i>
                <span id="cart-toast-message"></span>
            </div>
            <button onclick="hideToast()" class="text-slate-400 hover:text-slate-600 transition">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>

        {{-- ===== GRILLE PANIER : ARTICLES & RECAPITULATIF ===== --}}
        <div id="cart-active-container" class="{{ $panier->lignes->isNotEmpty() ? '' : 'hidden' }}">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                {{-- COLONNE GAUCHE (8 colonnes) : Liste des articles & Eco-Impact --}}
                <div class="lg:col-span-8 space-y-6">

                    {{-- Card Principale Articles --}}
                    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm overflow-hidden">
                        {{-- Entête de la liste --}}
                        <div class="px-6 py-4.5 bg-slate-50/70 border-b border-slate-200/80 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Articles sélectionnés</span>
                                <span id="items-badge-count" class="text-xs font-extrabold px-2 py-0.5 rounded-full bg-slate-200 text-slate-700">
                                    {{ $panier->lignes->count() }}
                                </span>
                            </div>

                            <button type="button"
                                    onclick="clearWholeCart()"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50 px-3 py-1.5 rounded-xl transition duration-150">
                                <i class="fas fa-trash-alt text-xs"></i>
                                <span>Vider le panier</span>
                            </button>
                        </div>

                        {{-- Liste des Lignes (Rendues directement en Blade pour affichage garanti) --}}
                        <div id="cart-items-list" class="divide-y divide-slate-100">
                            @foreach($panier->lignes as $ligne)
                                @php
                                    $article = $ligne->article;
                                    $imageUrl = $article?->getFirstImageUrl();
                                    $vendeur = $article?->user;
                                    $isAtelier = $vendeur && $vendeur->role === \App\Models\User::ROLE_ATELIER;
                                    $aRemise = $ligne->aRemise();
                                    $remiseMontant = $ligne->montantRemise();
                                @endphp
                                <div id="cart-line-{{ $ligne->id }}"
                                     class="cart-item-row p-6 transition-all duration-200 hover:bg-slate-50/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6"
                                     data-ligne-id="{{ $ligne->id }}"
                                     data-stock="{{ $article?->stock ?? 0 }}">

                                    {{-- Photo + Infos Produit --}}
                                    <div class="flex items-start sm:items-center gap-4.5 flex-1 min-w-0">
                                        {{-- Image avec zoom au survol --}}
                                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-slate-100 border border-slate-200 overflow-hidden flex-shrink-0 flex items-center justify-center relative group">
                                            @if($imageUrl)
                                                <img src="{{ $imageUrl }}" alt="{{ $article?->titre }}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                            @else
                                                <div class="text-slate-400 text-2xl">
                                                    <i class="fas fa-tshirt"></i>
                                                </div>
                                            @endif

                                            {{-- Badge Remise 10% --}}
                                            @if($aRemise)
                                                <span class="absolute top-1.5 left-1.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-[9px] font-black px-1.5 py-0.5 rounded-md shadow-sm">
                                                    -10%
                                                </span>
                                            @endif
                                        </div>

                                        {{-- Métadonnées --}}
                                        <div class="min-w-0 flex-1 space-y-1.5">
                                            <h3 class="text-base font-bold text-slate-900 truncate hover:text-teal-600 transition">
                                                <a href="{{ $article ? route('articles.show', $article) : '#' }}" title="{{ $article?->titre }}">
                                                    {{ $article?->titre ?? 'Article indisponible' }}
                                                </a>
                                            </h3>

                                            {{-- Vendeur & Spécialité --}}
                                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                <span>Vendu par :</span>
                                                <span class="font-semibold text-slate-800">{{ $vendeur?->name ?? 'RETISS' }}</span>

                                                @if($isAtelier)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-200/60">
                                                        <i class="fas fa-cut text-[9px]"></i> Atelier Partenaire
                                                    </span>
                                                @elseif($vendeur?->role)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600">
                                                        {{ $vendeur->role }}
                                                    </span>
                                                @endif

                                                @if($article?->categorie)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-teal-50 text-teal-700">
                                                        {{ $article->categorie }}
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Prix Unitaire --}}
                                            <div class="flex items-baseline gap-2 pt-0.5">
                                                @if($aRemise)
                                                    <div class="flex items-baseline gap-2">
                                                        <span class="text-sm font-bold text-emerald-600">
                                                            {{ number_format((float) $article->prix * 0.90, 2) }} DT
                                                        </span>
                                                        <span class="text-xs text-slate-400 line-through">
                                                            {{ number_format((float) $article->prix, 2) }} DT
                                                        </span>
                                                        <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded">
                                                            Remise Atelier
                                                        </span>
                                                    </div>
                                                @else
                                                    <span class="text-sm font-bold text-slate-700">
                                                        {{ number_format((float) ($article?->prix ?? 0), 2) }} DT
                                                    </span>
                                                @endif
                                                <span class="text-xs text-slate-400">/ pièce</span>
                                            </div>

                                            {{-- Stock restant --}}
                                            @if(($article?->stock ?? 0) <= 3)
                                                <p class="text-[11px] font-bold text-amber-600 flex items-center gap-1">
                                                    <i class="fas fa-bolt text-[10px]"></i>
                                                    Plus que {{ $article?->stock }} disponible(s) en stock !
                                                </p>
                                            @else
                                                <p class="text-[11px] font-medium text-emerald-600 flex items-center gap-1">
                                                    <i class="fas fa-check-circle text-[10px]"></i>
                                                    En stock ({{ $article?->stock }} disponibles)
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Colonne Actions & Prix Ligne --}}
                                    <div class="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto pt-4 sm:pt-0 border-t sm:border-t-0 border-slate-100">

                                        {{-- Stepper Quantité --}}
                                        <div class="flex items-center rounded-xl border border-slate-200/90 bg-slate-50 p-1 shadow-2xs">
                                            <button type="button"
                                                    onclick="changeQuantity({{ $ligne->id }}, -1)"
                                                    id="btn-minus-{{ $ligne->id }}"
                                                    title="Diminuer la quantité"
                                                    class="w-8 h-8 rounded-lg bg-white hover:bg-slate-100 border border-slate-200/80 text-slate-700 disabled:opacity-30 disabled:hover:bg-white flex items-center justify-center transition shadow-2xs text-xs"
                                                    {{ $ligne->quantite <= 1 ? 'disabled' : '' }}>
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <span id="qty-val-{{ $ligne->id }}" class="w-10 text-center text-sm font-extrabold text-slate-900">
                                                {{ $ligne->quantite }}
                                            </span>
                                            <button type="button"
                                                    onclick="changeQuantity({{ $ligne->id }}, 1)"
                                                    id="btn-plus-{{ $ligne->id }}"
                                                    title="Augmenter la quantité"
                                                    class="w-8 h-8 rounded-lg bg-white hover:bg-slate-100 border border-slate-200/80 text-slate-700 disabled:opacity-30 disabled:hover:bg-white flex items-center justify-center transition shadow-2xs text-xs"
                                                    {{ $ligne->quantite >= ($article?->stock ?? 0) ? 'disabled' : '' }}>
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>

                                        {{-- Montant total de la ligne --}}
                                        <div class="text-right min-w-[95px]">
                                            <div id="line-total-{{ $ligne->id }}" class="text-base font-black text-slate-900 font-display">
                                                {{ number_format($ligne->totalLigne(), 2) }} DT
                                            </div>
                                            @if($aRemise && $remiseMontant > 0)
                                                <div id="line-remise-{{ $ligne->id }}" class="text-[11px] font-semibold text-emerald-600">
                                                    Économie : -{{ number_format($remiseMontant, 2) }} DT
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Bouton Supprimer la ligne --}}
                                        <button type="button"
                                                onclick="removeCartLine({{ $ligne->id }}, '{{ addslashes($article?->titre ?? 'cet article') }}')"
                                                title="Retirer cet article"
                                                class="w-9 h-9 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-100 flex items-center justify-center transition">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </button>
                                    </div>

                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- WIDGET CRÉATIF : Bilan Éco-Responsable Interactif --}}
                    <div class="p-6 rounded-3xl bg-gradient-to-br from-emerald-900 via-teal-900 to-slate-900 text-white shadow-md relative overflow-hidden">
                        <div class="absolute -right-8 -bottom-8 w-40 h-40 bg-teal-500/10 rounded-full blur-2xl pointer-events-none"></div>

                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-white/10 relative z-10">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-400/30 flex items-center justify-center text-lg shadow-inner">
                                    <i class="fas fa-seedling"></i>
                                </span>
                                <div>
                                    <h3 class="text-base font-bold font-display text-white">Impact Écologique de votre Panier</h3>
                                    <p class="text-xs text-slate-300">Grâce au réemploi textile circulaire chez RETISS</p>
                                </div>
                            </div>
                            <span class="text-xs font-extrabold px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                100% Circulaire
                            </span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-5 relative z-10">
                            {{-- Eau Économisée --}}
                            <div class="p-4 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-xs">
                                <div class="flex items-center gap-2 text-teal-300 text-xs font-semibold mb-1">
                                    <i class="fas fa-tint"></i>
                                    <span>Eau Préservée</span>
                                </div>
                                <div class="text-xl sm:text-2xl font-black text-white font-display">
                                    ~<span id="eco-water">{{ number_format($panier->nombreArticles() * 2500) }}</span> L
                                </div>
                                <p class="text-[11px] text-slate-300/80 mt-0.5">d'eau douce économisée</p>
                            </div>

                            {{-- Carbone Évité --}}
                            <div class="p-4 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-xs">
                                <div class="flex items-center gap-2 text-emerald-300 text-xs font-semibold mb-1">
                                    <i class="fas fa-smog"></i>
                                    <span>CO₂ Évité</span>
                                </div>
                                <div class="text-xl sm:text-2xl font-black text-white font-display">
                                    ~<span id="eco-co2">{{ number_format($panier->nombreArticles() * 6.5, 1) }}</span> kg
                                </div>
                                <p class="text-[11px] text-slate-300/80 mt-0.5">d'émissions carbone évitées</p>
                            </div>

                            {{-- Déchets Évités --}}
                            <div class="p-4 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-xs">
                                <div class="flex items-center gap-2 text-teal-300 text-xs font-semibold mb-1">
                                    <i class="fas fa-recycle"></i>
                                    <span>Vêtements Sauvés</span>
                                </div>
                                <div class="text-xl sm:text-2xl font-black text-white font-display">
                                    <span id="eco-pieces">{{ $panier->nombreArticles() }}</span> pièce(s)
                                </div>
                                <p class="text-[11px] text-slate-300/80 mt-0.5">détournées des décharges</p>
                            </div>
                        </div>
                    </div>

                    {{-- Garanties et Engagements RETISS --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="p-4.5 rounded-2xl bg-white border border-slate-200/80 flex items-center gap-3.5 shadow-2xs">
                            <span class="w-11 h-11 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg flex-shrink-0">
                                <i class="fas fa-truck-fast"></i>
                            </span>
                            <div>
                                <h4 class="text-xs font-extrabold text-slate-900">Livraison Rapide</h4>
                                <p class="text-[11px] text-slate-500">2 à 4 jours ouvrés en Tunisie</p>
                            </div>
                        </div>
                        <div class="p-4.5 rounded-2xl bg-white border border-slate-200/80 flex items-center gap-3.5 shadow-2xs">
                            <span class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg flex-shrink-0">
                                <i class="fas fa-hand-holding-heart"></i>
                            </span>
                            <div>
                                <h4 class="text-xs font-extrabold text-slate-900">Économie Solidaire</h4>
                                <p class="text-[11px] text-slate-500">Soutient les ateliers locaux</p>
                            </div>
                        </div>
                        <div class="p-4.5 rounded-2xl bg-white border border-slate-200/80 flex items-center gap-3.5 shadow-2xs">
                            <span class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg flex-shrink-0">
                                <i class="fas fa-lock"></i>
                            </span>
                            <div>
                                <h4 class="text-xs font-extrabold text-slate-900">Paiement Garanti</h4>
                                <p class="text-[11px] text-slate-500">En ligne ou à la livraison</p>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- COLONNE DROITE (4 colonnes) : Récapitulatif de Commande Sticky --}}
                <div class="lg:col-span-4">
                    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-6 sticky top-36 space-y-6">

                        {{-- En-tête Récapitulatif --}}
                        <div class="pb-4 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-extrabold text-slate-900 font-display">Récapitulatif</h2>
                                <p class="text-xs text-slate-400">Calcul certifié côté serveur</p>
                            </div>
                            <span class="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-sm">
                                <i class="fas fa-receipt"></i>
                            </span>
                        </div>

                        {{-- Lignes du récapitulatif --}}
                        <div class="space-y-3.5 text-sm">
                            {{-- Sous-total --}}
                            <div class="flex items-center justify-between text-slate-600">
                                <span>Sous-total articles</span>
                                <span id="summary-subtotal" class="font-bold text-slate-800 font-display">
                                    {{ number_format($resume['sous_total'], 2) }} DT
                                </span>
                            </div>

                            {{-- Remise Partenaire si applicable --}}
                            <div id="summary-remise-row" class="flex items-center justify-between text-emerald-700 bg-emerald-50 px-3 py-2.5 rounded-xl border border-emerald-200/60 {{ $resume['remise'] > 0 ? '' : 'hidden' }}">
                                <span class="flex items-center gap-1.5 font-bold text-xs">
                                    <i class="fas fa-tags text-[11px]"></i>
                                    Remise Partenaire Atelier (10%)
                                </span>
                                <span id="summary-remise" class="font-black text-sm">
                                    -{{ number_format($resume['remise'], 2) }} DT
                                </span>
                            </div>

                            {{-- Frais de livraison --}}
                            <div class="flex items-center justify-between text-slate-600">
                                <span class="flex items-center gap-1.5">
                                    <span>Frais de livraison</span>
                                    <i class="fas fa-info-circle text-[11px] text-slate-400" title="Tarif standard RETISS Tunisie"></i>
                                </span>
                                <span id="summary-shipping" class="font-bold text-slate-800">
                                    {{ $resume['frais_livraison'] > 0 ? number_format($resume['frais_livraison'], 2) . ' DT' : 'Gratuit' }}
                                </span>
                            </div>

                            {{-- Séparateur et Total --}}
                            <div class="pt-4 border-t border-slate-100 flex items-baseline justify-between">
                                <div>
                                    <span class="text-base font-extrabold text-slate-900">Total TTC</span>
                                    <span class="block text-[11px] text-slate-400">TVA & livraison incluses</span>
                                </div>
                                <div class="text-right">
                                    <span id="summary-total" class="text-3xl font-black text-teal-700 tracking-tight font-display">
                                        {{ number_format($resume['total'], 2) }} DT
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Bouton Passer la commande via Stripe --}}
                        <div class="pt-2 space-y-3">
                            {{-- Stripe Checkout form --}}
                            <form method="POST" action="{{ route('paiement.checkout') }}" id="stripe-checkout-form">
                                @csrf
                                {{-- Optional: pass preferred address id if user has one --}}
                                @if(auth()->user()->adresseParDefaut()->exists())
                                    <input type="hidden" name="adresse_id" value="{{ auth()->user()->adresseParDefaut()->first()->id }}">
                                @endif
                                <button type="submit"
                                        id="btn-checkout"
                                        class="w-full flex items-center justify-center gap-3 py-4 px-6 rounded-2xl bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 text-white font-extrabold text-base shadow-lg shadow-teal-700/25 transition-all duration-200 transform hover:-translate-y-0.5">
                                    <i class="fab fa-stripe text-xl"></i>
                                    <span>Payer en ligne (Stripe)</span>
                                    <i class="fas fa-lock text-sm"></i>
                                </button>
                            </form>

                            <a href="{{ route('articles.index') }}"
                               class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 hover:text-slate-800 text-xs font-semibold transition"
                               style="text-decoration:none">
                                <i class="fas fa-arrow-left text-[10px]"></i>
                                <span>Continuer mes achats</span>
                            </a>
                        </div>

                        {{-- Badges de paiement sécurisé --}}
                        <div class="pt-2 border-t border-slate-100 text-center space-y-2">
                            <div class="flex items-center justify-center gap-3 text-slate-400 text-lg">
                                <i class="fab fa-cc-visa hover:text-slate-600 transition" title="Visa"></i>
                                <i class="fab fa-cc-mastercard hover:text-slate-600 transition" title="Mastercard"></i>
                                <i class="fab fa-cc-stripe hover:text-slate-600 transition text-indigo-400" title="Stripe Secure"></i>
                                <i class="fas fa-lock hover:text-slate-600 transition" title="SSL sécurisé"></i>
                            </div>
                            <p class="text-[11px] text-slate-400 leading-relaxed">
                                Paiement 100 % sécurisé via Stripe. Vos données de carte ne transitent jamais par nos serveurs.
                            </p>
                        </div>


                    </div>
                </div>

            </div>
        </div>

        {{-- ===== PANIER VIDE (CREATIVE EMPTY STATE) ===== --}}
        <div id="cart-empty-container" class="{{ $panier->lignes->isEmpty() ? '' : 'hidden' }}">
            <div class="max-w-3xl mx-auto my-12 bg-white rounded-3xl border border-slate-200 shadow-sm p-10 sm:p-14 text-center relative overflow-hidden">
                <div class="w-28 h-28 mx-auto rounded-3xl bg-gradient-to-br from-teal-50 to-emerald-50 text-teal-600 border border-teal-100 flex items-center justify-center text-5xl mb-6 shadow-inner relative">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="absolute -top-1 -right-1 w-6 h-6 rounded-full bg-slate-100 border-2 border-white flex items-center justify-center text-slate-400 text-xs font-bold">0</span>
                </div>

                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-bold mb-3">
                    0 article sélectionné
                </span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mb-3 font-display">
                    Votre panier est vide
                </h2>
                <p class="text-sm text-slate-500 max-w-lg mx-auto mb-8 leading-relaxed">
                    Votre panier attend ses premières trouvailles ! Découvrez des vêtements revalorisés, des créations upcyclées uniques réalisées par nos ateliers partenaires et donnez une seconde vie au textile tunisien.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3.5">
                    <a href="{{ route('articles.index') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-7 py-4 rounded-2xl bg-teal-600 hover:bg-teal-700 text-white font-extrabold text-sm shadow-lg shadow-teal-600/25 transition-all transform hover:-translate-y-0.5"
                       style="text-decoration:none">
                        <i class="fas fa-store"></i>
                        <span>Explorer la Marketplace</span>
                    </a>

                    <a href="{{ route('commandes.index') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-4 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm transition"
                       style="text-decoration:none">
                        <i class="fas fa-box-open text-slate-500"></i>
                        <span>Mes Commandes passées</span>
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
let isCartBusy = false;

function showToast(message, type = 'success') {
    const toast = document.getElementById('cart-toast');
    const toastMsg = document.getElementById('cart-toast-message');
    const toastIcon = document.getElementById('cart-toast-icon');
    if (!toast || !toastMsg) return;

    toastMsg.textContent = message;
    if (type === 'error') {
        toast.className = 'flex mb-6 items-center justify-between p-4 rounded-2xl shadow-md border backdrop-blur-md bg-rose-50/95 border-rose-200 text-rose-900';
        toastIcon.className = 'fas fa-exclamation-circle text-rose-500 text-base';
    } else {
        toast.className = 'flex mb-6 items-center justify-between p-4 rounded-2xl shadow-md border backdrop-blur-md bg-teal-50/95 border-teal-200 text-teal-900';
        toastIcon.className = 'fas fa-check-circle text-teal-500 text-base';
    }

    setTimeout(() => {
        hideToast();
    }, 3500);
}

function hideToast() {
    const toast = document.getElementById('cart-toast');
    if (toast) toast.className = 'hidden';
}

function formatMoney(val) {
    return Number(val || 0).toFixed(2);
}

function updateAllBadges(nbArticles) {
    // Badges navbar & sous-header
    document.querySelectorAll('.cart-badge-count').forEach(b => {
        b.textContent = nbArticles > 99 ? '99+' : nbArticles;
        if (nbArticles > 0) {
            b.classList.remove('hidden');
            b.style.display = 'flex';
        } else {
            b.classList.add('hidden');
            b.style.display = 'none';
        }
    });

    // Badge titre hero
    const heroBadge = document.getElementById('hero-badge-count');
    if (heroBadge) {
        heroBadge.textContent = `${nbArticles} ${nbArticles > 1 ? 'articles' : 'article'}`;
    }

    // Impact écologique
    const ecoWater = document.getElementById('eco-water');
    const ecoCo2   = document.getElementById('eco-co2');
    const ecoPieces= document.getElementById('eco-pieces');
    if (ecoWater)  ecoWater.textContent = Number(nbArticles * 2500).toLocaleString();
    if (ecoCo2)    ecoCo2.textContent   = Number(nbArticles * 6.5).toFixed(1);
    if (ecoPieces) ecoPieces.textContent= nbArticles;
}

function applyCartResume(resume) {
    if (!resume) return;

    // Mise à jour de la colonne récapitulatif
    const subtotalEl = document.getElementById('summary-subtotal');
    const remiseEl   = document.getElementById('summary-remise');
    const remiseRow  = document.getElementById('summary-remise-row');
    const shippingEl = document.getElementById('summary-shipping');
    const totalEl    = document.getElementById('summary-total');

    if (subtotalEl) subtotalEl.textContent = formatMoney(resume.sous_total) + ' DT';
    if (shippingEl) shippingEl.textContent = resume.frais_livraison > 0 ? formatMoney(resume.frais_livraison) + ' DT' : 'Gratuit';
    if (totalEl)    totalEl.textContent    = formatMoney(resume.total) + ' DT';

    if (remiseRow && remiseEl) {
        if (resume.remise > 0) {
            remiseEl.textContent = '-' + formatMoney(resume.remise) + ' DT';
            remiseRow.classList.remove('hidden');
        } else {
            remiseRow.classList.add('hidden');
        }
    }

    // Mise à jour des lignes de tableau
    if (resume.lignes && Array.isArray(resume.lignes)) {
        resume.lignes.forEach(l => {
            const qtyVal = document.getElementById(`qty-val-${l.id}`);
            const lineTot = document.getElementById(`line-total-${l.id}`);
            const lineRem = document.getElementById(`line-remise-${l.id}`);
            const btnMinus= document.getElementById(`btn-minus-${l.id}`);
            const btnPlus = document.getElementById(`btn-plus-${l.id}`);

            if (qtyVal) qtyVal.textContent = l.quantite;
            if (lineTot) lineTot.textContent = formatMoney(l.total_ligne) + ' DT';
            if (lineRem && l.a_remise && l.montant_remise > 0) {
                lineRem.textContent = `Économie : -${formatMoney(l.montant_remise)} DT`;
            }
            if (btnMinus) btnMinus.disabled = (l.quantite <= 1);
            if (btnPlus) btnPlus.disabled   = (l.quantite >= l.stock);
        });
    }

    updateAllBadges(resume.nb_articles);

    // Si panier vide
    if (resume.nb_articles === 0) {
        document.getElementById('cart-active-container')?.classList.add('hidden');
        document.getElementById('cart-empty-container')?.classList.remove('hidden');
    }
}

async function changeQuantity(ligneId, delta) {
    if (isCartBusy) return;

    const row = document.getElementById(`cart-line-${ligneId}`);
    const qtySpan = document.getElementById(`qty-val-${ligneId}`);
    if (!row || !qtySpan) return;

    const currentQty = parseInt(qtySpan.textContent) || 1;
    const maxStock = parseInt(row.dataset.stock) || 999;
    const targetQty = currentQty + delta;

    if (targetQty < 1 || targetQty > maxStock) return;

    isCartBusy = true;
    row.classList.add('updating');

    try {
        const response = await axios.patch(`/panier/lignes/${ligneId}`, {
            quantite: targetQty
        }, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });

        if (response.data.success) {
            applyCartResume(response.data.resume);
            showToast('Quantité mise à jour.');
        }
    } catch (err) {
        const msg = err.response?.data?.message || err.response?.data?.error || 'Erreur lors de la mise à jour.';
        showToast(msg, 'error');
    } finally {
        row.classList.remove('updating');
        isCartBusy = false;
    }
}

async function removeCartLine(ligneId, titre) {
    if (isCartBusy) return;

    const confirmed = await retissConfirm({
        title: 'Retirer cet article ?',
        text: `Voulez-vous vraiment retirer « <strong>${titre}</strong> » de votre panier ?`,
        confirmButtonText: 'Oui, retirer',
        cancelButtonText: 'Conserver',
        isDestructive: true
    });
    if (!confirmed) return;

    const row = document.getElementById(`cart-line-${ligneId}`);
    if (!row) return;

    isCartBusy = true;
    row.classList.add('removing');

    try {
        const response = await axios.delete(`/panier/lignes/${ligneId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });

        if (response.data.success) {
            setTimeout(() => {
                row.remove();
                // Compter les lignes restantes
                const itemsList = document.getElementById('cart-items-list');
                const remaining = itemsList ? itemsList.querySelectorAll('.cart-item-row').length : 0;
                const itemsBadge = document.getElementById('items-badge-count');
                if (itemsBadge) itemsBadge.textContent = remaining;

                applyCartResume(response.data.resume);
                showToast(response.data.message || 'Article retiré.');
            }, 250);
        }
    } catch (err) {
        row.classList.remove('removing');
        const msg = err.response?.data?.message || err.response?.data?.error || 'Erreur lors de la suppression.';
        showToast(msg, 'error');
    } finally {
        isCartBusy = false;
    }
}

async function clearWholeCart() {
    if (isCartBusy) return;

    const confirmed = await retissConfirm({
        title: 'Vider tout le panier ?',
        text: 'Êtes-vous sûr de vouloir vider l\'intégralité de votre panier ? Tous vos articles sélectionnés seront retirés.',
        confirmButtonText: 'Oui, tout vider',
        cancelButtonText: 'Annuler',
        isDestructive: true
    });
    if (!confirmed) return;

    isCartBusy = true;

    try {
        const response = await axios.delete('/panier/vider', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });

        if (response.data.success) {
            document.getElementById('cart-items-list').innerHTML = '';
            document.getElementById('cart-active-container')?.classList.add('hidden');
            document.getElementById('cart-empty-container')?.classList.remove('hidden');
            updateAllBadges(0);
            showToast('Votre panier a été vidé.');
        }
    } catch (err) {
        const msg = err.response?.data?.message || err.response?.data?.error || 'Erreur lors de la suppression.';
        showToast(msg, 'error');
    } finally {
        isCartBusy = false;
    }
}
</script>
@endsection
