@extends('layouts.app')

@section('title', $article->titre . ' — Marketplace RETISS')

@section('styles')
<style>
    .article-show-page {
        min-height: 100vh;
        background: linear-gradient(160deg, #f0fdf9 0%, #f8fafc 40%, #f0f4ff 100%);
        padding-top: 72px;
    }

    .article-hero {
        background: linear-gradient(135deg, #111a30 0%, #1a2744 35%, #1B4332 70%, #0d9488 100%);
        position: relative;
        overflow: hidden;
        padding: 2.5rem 0 5rem;
    }
    .article-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            radial-gradient(ellipse at 80% 50%, rgba(13,148,136,0.25) 0%, transparent 60%),
            radial-gradient(ellipse at 20% 80%, rgba(74,222,128,0.1) 0%, transparent 50%);
        pointer-events: none;
    }
    .hero-grid {
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
        background-size: 40px 40px;
        pointer-events: none;
    }

    .article-content-wrap {
        margin-top: -3.5rem;
        position: relative;
        z-index: 10;
    }

    .badge-statut-disponible { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .badge-statut-reserve    { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .badge-statut-vendu      { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .badge-statut-archive    { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    .gallery-thumb {
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
    }
    .gallery-thumb:hover, .gallery-thumb.active {
        border-color: #0d9488;
        transform: scale(1.04);
    }
</style>
@endsection

@section('content')
<div class="article-show-page">

    {{-- ===== HERO ===== --}}
    <div class="article-hero">
        <div class="hero-grid"></div>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">

            {{-- Breadcrumb --}}
            <div class="flex items-center gap-2 mb-3">
                <a href="{{ route('home') }}" class="text-white/50 hover:text-white/80 text-sm transition-colors" style="text-decoration:none">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <a href="{{ route('articles.index') }}" class="text-white/50 hover:text-white/80 text-sm transition-colors" style="text-decoration:none">
                    Marketplace
                </a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <span class="text-white/80 text-sm font-medium truncate max-w-xs">{{ $article->titre }}</span>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center flex-wrap gap-2 mb-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-500/20 border border-teal-400/30 text-teal-300 text-xs font-semibold">
                            <span>{{ $article->categorie }}</span>
                        </div>
                        @if($article->nbAvis() > 0)
                            <a href="#section-avis" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-400/20 border border-amber-300/30 text-amber-300 text-xs font-bold hover:bg-amber-400/30 transition-colors" style="text-decoration:none">
                                <i class="fas fa-star text-amber-300 text-[10px]"></i>
                                <span>{{ number_format($article->noteMoyenne(), 1) }} / 5</span>
                                <span class="text-white/60 font-normal">({{ $article->nbAvis() }} avis)</span>
                            </a>
                        @endif
                    </div>
                    <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        {{ $article->titre }}
                    </h1>
                </div>

                {{-- Owner / Admin Action Buttons --}}
                @can('update', $article)
                    <div class="flex items-center gap-2">
                        <a href="{{ route('articles.edit', $article) }}"
                           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white hover:bg-teal-50 text-teal-800 text-xs font-bold transition-all shadow-md"
                           style="text-decoration:none">
                            <i class="fas fa-edit"></i>
                            <span>Modifier</span>
                        </a>

                        <form method="POST"
                              action="{{ route('articles.destroy', $article) }}"
                              data-confirm="Êtes-vous sûr de vouloir supprimer l'article « {{ addslashes($article->titre) }} » ? Cette action est irréversible."
                              data-confirm-title="Supprimer cet article"
                              data-confirm-button="Oui, supprimer"
                              class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-500/80 hover:bg-red-600 text-white text-xs font-bold transition-all shadow-md">
                                <i class="fas fa-trash-alt"></i>
                                <span>Supprimer</span>
                            </button>
                        </form>
                    </div>
                @endcan
            </div>

        </div>
    </div>

    {{-- ===== MAIN CONTENT ===== --}}
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 article-content-wrap pb-16">

        {{-- Flash message --}}
        @if(session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-medium flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-sm"></i>
                    </div>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- ===== MAIN DETAILS (2 cols) ===== --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Primary Showcase Card --}}
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 sm:p-8">

                    {{-- Image Gallery (if images exist) --}}
                    @php
                        $imageUrls = $article->getImageUrls();
                    @endphp

                    @if(count($imageUrls) > 0)
                        <div class="mb-6">
                            {{-- Main Displayed Image --}}
                            <div class="w-full h-80 sm:h-96 rounded-2xl overflow-hidden bg-slate-900 border border-slate-200 shadow-inner flex items-center justify-center relative">
                                <img id="main-gallery-img"
                                     src="{{ $imageUrls[0] }}"
                                     alt="{{ $article->titre }}"
                                     class="w-full h-full object-contain">
                            </div>

                            {{-- Thumbnails Carousel (if multiple images) --}}
                            @if(count($imageUrls) > 1)
                                <div class="flex items-center gap-2.5 mt-3 overflow-x-auto pb-2">
                                    @foreach($imageUrls as $idx => $url)
                                        <div class="w-16 h-16 rounded-xl overflow-hidden bg-slate-100 gallery-thumb {{ $idx === 0 ? 'active' : '' }} flex-shrink-0"
                                             onclick="switchMainImage('{{ $url }}', this)">
                                            <img src="{{ $url }}" class="w-full h-full object-cover" alt="Vignette {{ $idx + 1 }}">
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Header with Badges --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-6 border-b border-slate-100">
                        <div class="flex flex-wrap items-center gap-2">
                            @php
                                $statutClass = match($article->statut) {
                                    'DISPONIBLE' => 'badge-statut-disponible',
                                    'RESERVE'    => 'badge-statut-reserve',
                                    'VENDU'      => 'badge-statut-vendu',
                                    default      => 'badge-statut-archive',
                                };
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $statutClass }}">
                                {{ $article->statut }}
                            </span>

                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                <i class="fas fa-tag mr-1.5 text-slate-400"></i> {{ $article->categorie }}
                            </span>

                            @if($article->don_vetement_id)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                    <i class="fas fa-hand-holding-heart text-emerald-600"></i> Issu d'un don
                                </span>
                            @endif
                        </div>

                        <span class="text-xs text-slate-400">
                            Publié {{ $article->created_at->diffForHumans() }}
                        </span>
                    </div>

                    {{-- Title & Description --}}
                    <div class="py-6">
                        <h2 class="font-display text-2xl font-bold text-slate-900 mb-4">
                            {{ $article->titre }}
                        </h2>

                        <div class="prose prose-slate max-w-none text-slate-600 text-sm leading-relaxed whitespace-pre-line">
                            {{ $article->description ?: 'Aucune description détaillée n\'a été ajoutée pour cet article.' }}
                        </div>
                    </div>

                    {{-- Stock & Availability Banner --}}
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center font-bold">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800 uppercase tracking-wider">Quantité disponible</p>
                                <p class="text-sm font-semibold text-slate-600">
                                    {{ $article->stock }} unité(s) en stock
                                </p>
                            </div>
                        </div>

                        @if($article->isDisponible())
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                <i class="fas fa-check-circle mr-1"></i> Immédiatement disponible
                            </span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                                <i class="fas fa-clock mr-1"></i> {{ $article->statut }}
                            </span>
                        @endif
                    </div>

                </div>

                {{-- Linked Donation Details Card (if applicable) --}}
                @if($article->donVetement)
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold">
                                <i class="fas fa-history"></i>
                            </div>
                            <div>
                                <h3 class="font-display font-bold text-slate-900 text-sm">Traçabilité du don d'origine</h3>
                                <p class="text-xs text-slate-400">Cet article provient d'un vêtement donné et réintégré dans le circuit</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="text-slate-400 block text-[10px] uppercase font-semibold">Type de vêtement</span>
                                <span class="font-bold text-slate-800">{{ $article->donVetement->type }}</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="text-slate-400 block text-[10px] uppercase font-semibold">Matière</span>
                                <span class="font-bold text-slate-800">{{ $article->donVetement->matiere }}</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="text-slate-400 block text-[10px] uppercase font-semibold">Taille</span>
                                <span class="font-bold text-slate-800">{{ $article->donVetement->taille }}</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="text-slate-400 block text-[10px] uppercase font-semibold">État initial</span>
                                <span class="font-bold text-slate-800">{{ $article->donVetement->etat }}</span>
                            </div>
                        </div>
                    </div>
                @endif

            </div>

            {{-- ===== SIDEBAR (1 col) ===== --}}
            <div class="space-y-6">

                {{-- Price & Buy Card --}}
                <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
                    @php
                        $user = auth()->user();
                        $sellerIsAtelier = $article->user && $article->user->role === \App\Models\User::ROLE_ATELIER;
                        $hasRemise = $user && $user->getsRemise() && $sellerIsAtelier;
                        $remiseAmount = $hasRemise ? round($article->prix * 0.10, 2) : 0;
                        $discountedPrice = round($article->prix - $remiseAmount, 2);
                        $isOwner = $user && ($user->id === $article->user_id);
                    @endphp

                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Prix de vente</span>
                    <div class="flex items-baseline gap-2 mb-3">
                        @if($hasRemise && $article->prix > 0)
                            <span class="line-through text-slate-400 font-bold text-lg">
                                {{ number_format($article->prix, 2) }} DT
                            </span>
                            <span class="font-display font-black text-3xl text-emerald-700">
                                {{ number_format($discountedPrice, 2) }}
                            </span>
                            <span class="font-display font-bold text-lg text-emerald-600">DT</span>
                        @elseif($article->prix == 0)
                            <span class="font-display font-black text-3xl text-emerald-600">
                                Gratuit (Don)
                            </span>
                        @else
                            <span class="font-display font-black text-3xl text-teal-700">
                                {{ number_format($article->prix, 2) }}
                            </span>
                            <span class="font-display font-bold text-lg text-slate-500">DT</span>
                        @endif
                    </div>

                    @if($hasRemise && $article->prix > 0)
                        <div class="p-3 bg-emerald-50 rounded-2xl border border-emerald-200 text-xs text-emerald-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-percent text-emerald-600"></i>
                            <span>Avantage <strong>{{ $user->role }}</strong> : 10% de remise automatique appliquée !</span>
                        </div>
                    @endif

                    @if($article->isDisponible())
                        <div class="p-3 bg-teal-50 rounded-2xl border border-teal-100 text-xs text-teal-800 mb-5 flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-teal-600"></i>
                                <span>En stock ({{ $article->stock }})</span>
                            </span>
                            <span class="font-semibold text-[11px] text-teal-700">Prêt à être expédié</span>
                        </div>

                        @if($isOwner)
                            <div class="p-4 bg-amber-50 rounded-2xl border border-amber-200 text-center mb-4">
                                <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center mx-auto mb-2 text-base">
                                    <i class="fas fa-handshake-slash"></i>
                                </div>
                                <p class="text-xs font-bold text-amber-900 mb-1">
                                    Achat non autorisé (Votre article)
                                </p>
                                <p class="text-xs text-amber-800 leading-relaxed mb-3">
                                    En tant qu'atelier créateur de cette annonce, vous ne pouvez pas acheter votre propre article.
                                </p>
                                <a href="{{ route('articles.edit', $article) }}"
                                   class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-sm transition-all"
                                   style="text-decoration:none">
                                    <i class="fas fa-edit"></i> Modifier mon article
                                </a>
                            </div>
                        @elseif(!auth()->check())
                            <a href="{{ route('login') }}"
                               class="w-full py-3.5 px-5 rounded-2xl font-display font-bold text-sm text-white bg-slate-800 hover:bg-slate-900 shadow-md transition-all flex items-center justify-center gap-2 mb-4"
                               style="text-decoration:none">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Se connecter pour commander</span>
                            </a>
                        @else
                            {{-- Add to Cart Form --}}
                            <form id="show-add-to-cart-form" action="{{ route('panier.ajouter', $article) }}" method="POST" class="space-y-4 mb-4">
                                @csrf
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Quantité souhaitée</label>
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center rounded-xl border border-slate-200 bg-slate-50 p-1">
                                            <button type="button" id="qty-minus-btn"
                                                    class="w-8 h-8 rounded-lg bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 disabled:opacity-40 flex items-center justify-center transition shadow-xs text-xs">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" id="quantite-input" name="quantite" value="1" min="1" max="{{ $article->stock }}"
                                                   class="w-14 text-center font-bold text-sm bg-transparent border-none outline-none focus:ring-0"
                                                   required>
                                            <button type="button" id="qty-plus-btn"
                                                    class="w-8 h-8 rounded-lg bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 disabled:opacity-40 flex items-center justify-center transition shadow-xs text-xs">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <span class="text-xs text-slate-400">sur {{ $article->stock }} dispo</span>
                                    </div>
                                </div>

                                {{-- Bouton Ajouter au Panier (Action Principale) --}}
                                <button type="submit" id="add-to-cart-submit-btn"
                                        class="w-full py-3.5 px-5 rounded-2xl font-display font-bold text-sm text-white bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 shadow-lg shadow-teal-700/25 transition-all duration-200 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                                    <i class="fas fa-cart-plus"></i>
                                    <span>Ajouter au panier</span>
                                </button>

                                {{-- Bouton Acheter Immédiatement (Action Directe) --}}
                                <a href="{{ route('commandes.create', ['article_id' => $article->id]) }}"
                                   id="buy-now-link"
                                   class="w-full py-2.5 px-4 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs transition-colors flex items-center justify-center gap-2"
                                   style="text-decoration:none">
                                    <i class="fas fa-bolt text-amber-500"></i>
                                    <span>Commander directement</span>
                                </a>

                                {{-- Bouton Liste de souhaits (Favoris) --}}
                                @auth
                                    @if(!$isOwner)
                                        <button type="button"
                                                id="btn-toggle-favori-show"
                                                onclick="toggleFavoriShow({{ $article->id }})"
                                                class="w-full py-2.5 px-4 rounded-xl border {{ $isFavori ? 'border-rose-300 bg-rose-50 text-rose-600' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-700' }} font-semibold text-xs transition-colors flex items-center justify-center gap-2">
                                            <i class="{{ $isFavori ? 'fas' : 'far' }} fa-heart text-rose-500 text-sm"></i>
                                            <span id="favori-show-text">{{ $isFavori ? 'Dans votre liste de souhaits' : 'Ajouter à ma liste de souhaits' }}</span>
                                        </button>
                                    @endif
                                @endauth
                            </form>
                        @endif
                    @else
                        <div class="p-3 bg-amber-50 rounded-2xl border border-amber-100 text-xs text-amber-800 mb-5 flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle text-amber-600"></i>
                            <span>Cet article est actuellement <strong>{{ $article->statut }}</strong></span>
                        </div>

                        {{-- Permettre de mettre en favori même si hors stock ou réservé pour être prévenu au retour ! --}}
                        @auth
                            @if(!$isOwner)
                                <button type="button"
                                        id="btn-toggle-favori-show"
                                        onclick="toggleFavoriShow({{ $article->id }})"
                                        class="w-full py-2.5 px-4 rounded-xl border {{ $isFavori ? 'border-rose-300 bg-rose-50 text-rose-600' : 'border-slate-200 bg-white hover:bg-slate-50 text-slate-700' }} font-semibold text-xs transition-colors flex items-center justify-center gap-2 mb-4">
                                    <i class="{{ $isFavori ? 'fas' : 'far' }} fa-heart text-rose-500 text-sm"></i>
                                    <span id="favori-show-text">{{ $isFavori ? 'Alerte active (En favoris)' : 'M\'alerter du retour en stock' }}</span>
                                </button>
                            @endif
                        @endauth
                    @endif

                    <div class="space-y-2.5 pt-2 border-t border-slate-100">
                        <a href="{{ route('articles.index') }}"
                           class="w-full py-2.5 px-4 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-semibold transition-colors flex items-center justify-center gap-2"
                           style="text-decoration:none">
                            <i class="fas fa-arrow-left"></i>
                            <span>Retour à la marketplace</span>
                        </a>
                    </div>
                </div>

                {{-- Seller Information Card --}}
                <div class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
                    <h3 class="font-display font-bold text-slate-800 text-xs uppercase tracking-wider mb-4">
                        Vendeur / Propriétaire
                    </h3>

                    <div class="flex items-center gap-3.5 mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-teal-100 text-teal-700 font-bold flex items-center justify-center text-sm shadow-sm flex-shrink-0">
                            {{ $article->user->initials }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-slate-800 text-sm truncate">
                                {{ $article->user->full_name ?: $article->user->name }}
                            </p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                {{ $article->user->role }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-2 text-xs text-slate-600 pt-3 border-t border-slate-100">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-envelope text-slate-400 w-4"></i>
                            <span class="truncate">{{ $article->user->email }}</span>
                        </div>
                        @if($article->user->telephone)
                            <div class="flex items-center gap-2">
                                <i class="fas fa-phone text-slate-400 w-4"></i>
                                <span>{{ $article->user->telephone }}</span>
                            </div>
                        @endif

                        @if($article->user->nbAvisRecus() > 0)
                            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                <span class="text-slate-500 font-medium">Note vendeur :</span>
                                <span class="inline-flex items-center gap-1 font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-200">
                                    <i class="fas fa-star text-amber-500 text-[10px]"></i>
                                    <span>{{ number_format($article->user->noteMoyenne(), 1) }} / 5</span>
                                    <span class="text-slate-400 font-normal">({{ $article->user->nbAvisRecus() }})</span>
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Circular Economy Guarantee --}}
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 text-white rounded-3xl p-6 shadow-md">
                    <div class="w-10 h-10 rounded-xl bg-emerald-400/20 text-emerald-400 flex items-center justify-center text-lg mb-3">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4 class="font-display font-bold text-sm mb-1">Impact Positif Garanti</h4>
                    <p class="text-xs text-slate-300 leading-relaxed mb-4">
                        Chaque achat ou vente sur RETISS prolonge la durée de vie des textiles et réduit les déchets vestimentaires en Tunisie.
                    </p>
                    <div class="space-y-2 text-xs text-slate-300">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check text-emerald-400"></i>
                            <span>Traçabilité transparente</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check text-emerald-400"></i>
                            <span>Gestion des articles par le propriétaire</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        {{-- ========================================== --}}
        {{-- ===== SECTION AVIS CLIENTS & ÉVALUATIONS ===== --}}
        {{-- ========================================== --}}
        <div id="section-avis" class="mt-12 bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-10">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl shadow-xs">
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <h2 class="font-display text-xl sm:text-2xl font-bold text-slate-900">
                            Avis clients vérifiés
                        </h2>
                        <p class="text-xs text-slate-500">
                            Évaluations authentiques déposées par des acheteurs ayant reçu cet article
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <i class="fas fa-shield-alt text-emerald-600"></i>
                        <span>Avis 100% vérifiés</span>
                    </span>
                </div>
            </div>

            @if($article->nbAvis() > 0)
                {{-- Global Rating Breakdown Card --}}
                <div class="my-8 p-6 sm:p-8 rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100/60 border border-slate-200">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                        {{-- Overall Score --}}
                        <div class="md:col-span-4 text-center md:border-r md:border-slate-200 md:pr-8">
                            <div class="font-display font-black text-5xl text-slate-900 tracking-tight mb-2">
                                {{ number_format($article->noteMoyenne(), 1) }}
                                <span class="text-xl font-bold text-slate-400">/ 5</span>
                            </div>
                            <div class="flex items-center justify-center gap-1 text-amber-400 text-lg mb-2">
                                @php
                                    $rating = $article->noteMoyenne();
                                @endphp
                                @for($i = 1; $i <= 5; $i++)
                                    @if($rating >= $i)
                                        <i class="fas fa-star"></i>
                                    @elseif($rating >= $i - 0.5)
                                        <i class="fas fa-star-half-alt"></i>
                                    @else
                                        <i class="far fa-star text-slate-300"></i>
                                    @endif
                                @endfor
                            </div>
                            <p class="text-xs text-slate-500 font-medium">
                                Basé sur <strong>{{ $article->nbAvis() }}</strong> évaluation{{ $article->nbAvis() > 1 ? 's' : '' }}
                            </p>
                        </div>

                        {{-- 4 Criteria Progress Bars --}}
                        <div class="md:col-span-8 space-y-3.5">
                            @php
                                $critereConfigs = [
                                    'note_livraison' => ['label' => 'Livraison & Expédition', 'icon' => 'fa-truck-fast', 'color' => 'bg-teal-500'],
                                    'note_article'   => ['label' => 'Article & Conformité', 'icon' => 'fa-box-open', 'color' => 'bg-emerald-500'],
                                    'note_clarte'    => ['label' => 'Clarté de la description', 'icon' => 'fa-file-lines', 'color' => 'bg-indigo-500'],
                                    'note_service'   => ['label' => 'Service & Communication', 'icon' => 'fa-headset', 'color' => 'bg-amber-500'],
                                ];
                            @endphp

                            @foreach($critereConfigs as $critereKey => $critConf)
                                @php
                                    $critScore = $article->noteMoyenneParCritere($critereKey);
                                    $critPct = round(($critScore / 5) * 100);
                                @endphp
                                <div>
                                    <div class="flex items-center justify-between text-xs font-semibold text-slate-700 mb-1.5">
                                        <span class="flex items-center gap-2">
                                            <i class="fas {{ $critConf['icon'] }} text-slate-400 text-[11px] w-4"></i>
                                            <span>{{ $critConf['label'] }}</span>
                                        </span>
                                        <span class="font-bold text-slate-900">{{ number_format($critScore, 1) }} / 5</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-slate-200 overflow-hidden">
                                        <div class="h-full rounded-full {{ $critConf['color'] }} transition-all duration-500" style="width: {{ $critPct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Sorting Bar --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 pb-4 border-b border-slate-100">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                        {{ $avisList->count() }} avis affiché{{ $avisList->count() > 1 ? 's' : '' }}
                    </span>

                    <div class="flex items-center flex-wrap gap-2 text-xs">
                        <span class="text-slate-400 mr-1 font-medium">Trier par :</span>
                        @php
                            $triOptions = [
                                'utiles'    => ['label' => 'Plus utiles', 'icon' => 'fa-thumbs-up'],
                                'recent'    => ['label' => 'Plus récents', 'icon' => 'fa-clock'],
                                'note_desc' => ['label' => 'Meilleures notes', 'icon' => 'fa-arrow-trend-up'],
                                'note_asc'  => ['label' => 'Moins bonnes notes', 'icon' => 'fa-arrow-trend-down'],
                            ];
                        @endphp
                        @foreach($triOptions as $triKey => $triData)
                            <a href="{{ route('articles.show', array_merge(request()->except(['avis_tri']), ['avis_tri' => $triKey])) }}#section-avis"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-semibold transition-all {{ $currentAvisTri === $triKey ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600' }}"
                               style="text-decoration:none">
                                <i class="fas {{ $triData['icon'] }} text-[10px]"></i>
                                <span>{{ $triData['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Reviews List --}}
                <div class="space-y-6">
                    @foreach($avisList as $avis)
                        <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-slate-300 transition-all">
                            {{-- Review Author & Date --}}
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-teal-100 text-teal-700 font-bold flex items-center justify-center text-xs flex-shrink-0">
                                        {{ $avis->user->initials }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-bold text-slate-900 text-sm">
                                                {{ $avis->user->full_name ?: $avis->user->name }}
                                            </h4>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Achat vérifié dans une commande livrée">
                                                <i class="fas fa-check-circle text-emerald-600"></i>
                                                <span>Achat vérifié</span>
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-400">
                                            Évalué {{ $avis->created_at->diffForHumans() }}
                                            @if($avis->updated_at->gt($avis->created_at))
                                                <span class="text-slate-400 italic">(modifié)</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                {{-- Stars & Overall Note --}}
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1 text-amber-400 text-sm">
                                        @for($s = 1; $s <= 5; $s++)
                                            @if($avis->note_globale >= $s)
                                                <i class="fas fa-star"></i>
                                            @elseif($avis->note_globale >= $s - 0.5)
                                                <i class="fas fa-star-half-alt"></i>
                                            @else
                                                <i class="far fa-star text-slate-200"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <span class="px-2.5 py-1 rounded-xl bg-amber-50 text-amber-800 border border-amber-200 text-xs font-black">
                                        {{ number_format($avis->note_globale, 2) }} / 5
                                    </span>
                                </div>
                            </div>

                            {{-- 4 Criteria Badges --}}
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-4 p-3 bg-slate-50 rounded-xl border border-slate-100 text-[11px]">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500"><i class="fas fa-truck-fast text-slate-400 mr-1"></i> Livraison :</span>
                                    <span class="font-bold text-slate-800">{{ $avis->note_livraison }}/5</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500"><i class="fas fa-box-open text-slate-400 mr-1"></i> Article :</span>
                                    <span class="font-bold text-slate-800">{{ $avis->note_article }}/5</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500"><i class="fas fa-file-lines text-slate-400 mr-1"></i> Clarté :</span>
                                    <span class="font-bold text-slate-800">{{ $avis->note_clarte }}/5</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-500"><i class="fas fa-headset text-slate-400 mr-1"></i> Service :</span>
                                    <span class="font-bold text-slate-800">{{ $avis->note_service }}/5</span>
                                </div>
                            </div>

                            {{-- Comment Content --}}
                            @if($avis->commentaire)
                                <div class="mb-4 text-slate-700 text-sm leading-relaxed p-3.5 bg-slate-50/50 rounded-xl border-l-4 border-teal-500">
                                    <p class="whitespace-pre-line">{{ $avis->commentaire }}</p>
                                </div>
                            @endif

                            {{-- Bottom Row: Likes/Dislikes & Author/Admin Actions --}}
                            <div class="flex items-center justify-between gap-3 pt-3 border-t border-slate-100">
                                {{-- Reaction Buttons (Alpine.js + Axios) --}}
                                <div x-data="{
                                    avisId: {{ $avis->id }},
                                    likes: {{ $avis->likesCount() }},
                                    dislikes: {{ $avis->dislikesCount() }},
                                    userVote: '{{ $avis->getUserVote(auth()->id()) ?? '' }}',
                                    isLoggedIn: {{ auth()->check() ? 'true' : 'false' }},
                                    isAuthor: {{ auth()->id() === $avis->user_id ? 'true' : 'false' }},
                                    loading: false,
                                    async vote(type) {
                                        if (!this.isLoggedIn) {
                                            retissAlert({ title: 'Connexion requise', text: 'Veuillez vous connecter pour réagir à un avis.', icon: 'info' });
                                            return;
                                        }
                                        if (this.isAuthor) {
                                            retissAlert({ title: 'Non autorisé', text: 'Vous ne pouvez pas voter sur votre propre avis.', icon: 'warning' });
                                            return;
                                        }
                                        if (this.loading) return;
                                        this.loading = true;
                                        try {
                                            const res = await axios.post(`/avis/${this.avisId}/vote`, { type: type });
                                            if (res.data.success) {
                                                this.userVote = res.data.user_vote || '';
                                                this.likes = res.data.likes_count;
                                                this.dislikes = res.data.dislikes_count;
                                            }
                                        } catch (err) {
                                            const msg = err.response?.data?.message || 'Erreur lors de l\'enregistrement du vote.';
                                            retissAlert({ title: 'Erreur', text: msg, icon: 'error' });
                                        } finally {
                                            this.loading = false;
                                        }
                                    }
                                }" class="flex items-center gap-2">
                                    <span class="text-xs text-slate-400 mr-1 hidden sm:inline">Cet avis vous a-t-il été utile ?</span>

                                    {{-- Like button --}}
                                    <button type="button"
                                            @click="vote('like')"
                                            :disabled="loading || isAuthor"
                                            :class="userVote === 'like' ? 'bg-emerald-100 text-emerald-800 border-emerald-300 ring-2 ring-emerald-400/30 font-bold' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border-slate-200'"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all disabled:opacity-50"
                                            :title="isAuthor ? 'Vous êtes l\'auteur de cet avis' : 'Avis utile (J\'aime)'">
                                        <i class="fas fa-thumbs-up" :class="userVote === 'like' ? 'text-emerald-600' : 'text-slate-400'"></i>
                                        <span x-text="likes"></span>
                                    </button>

                                    {{-- Dislike button --}}
                                    <button type="button"
                                            @click="vote('dislike')"
                                            :disabled="loading || isAuthor"
                                            :class="userVote === 'dislike' ? 'bg-rose-100 text-rose-800 border-rose-300 ring-2 ring-rose-400/30 font-bold' : 'bg-slate-50 hover:bg-slate-100 text-slate-600 border-slate-200'"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold transition-all disabled:opacity-50"
                                            :title="isAuthor ? 'Vous êtes l\'auteur de cet avis' : 'Pas utile (Je n\'aime pas)'">
                                        <i class="fas fa-thumbs-down" :class="userVote === 'dislike' ? 'text-rose-600' : 'text-slate-400'"></i>
                                        <span x-text="dislikes"></span>
                                    </button>

                                    @if(auth()->id() === $avis->user_id)
                                        <span class="text-[10px] text-slate-400 italic ml-1">(Votre avis)</span>
                                    @endif
                                </div>

                                {{-- Edit / Delete actions --}}
                                <div class="flex items-center gap-2">
                                    @can('update', $avis)
                                        <a href="{{ route('avis.edit', $avis) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1 rounded-xl bg-slate-100 hover:bg-teal-50 text-slate-600 hover:text-teal-700 text-xs font-semibold transition-colors"
                                           style="text-decoration:none">
                                            <i class="fas fa-pen-to-square text-[10px]"></i>
                                            <span>Modifier</span>
                                        </a>
                                    @endcan

                                    @can('delete', $avis)
                                        <form method="POST"
                                              action="{{ route('avis.destroy', $avis) }}"
                                              data-confirm="Êtes-vous sûr de vouloir supprimer cet avis ? Cette action est irréversible."
                                              data-confirm-title="Supprimer l'avis"
                                              data-confirm-button="Oui, supprimer"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 px-3 py-1 rounded-xl bg-slate-100 hover:bg-red-50 text-slate-600 hover:text-red-700 text-xs font-semibold transition-colors"
                                                    title="Supprimer cet avis">
                                                <i class="fas fa-trash-alt text-[10px]"></i>
                                                <span>Supprimer</span>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Empty State for Reviews --}}
                <div class="py-12 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center text-2xl mx-auto mb-4">
                        <i class="far fa-star"></i>
                    </div>
                    <h3 class="font-display font-bold text-slate-800 text-lg mb-1">
                        Aucun avis pour le moment
                    </h3>
                    <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed mb-2">
                        Les évaluations sont réservées aux clients ayant commandé cet article et confirmé sa bonne réception. Soyez le premier à commander pour partager votre expérience !
                    </p>
                </div>
            @endif
        </div>

        {{-- Related Articles Section --}}
        @if(isset($relatedArticles) && $relatedArticles->count() > 0)
            <div class="mt-12">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="font-display text-xl font-bold text-slate-900">Articles similaires dans la même catégorie</h3>
                        <p class="text-xs text-slate-500">D'autres créations textiles éco-responsables à découvrir</p>
                    </div>
                    <a href="{{ route('articles.index', ['categorie' => $article->categorie]) }}" class="text-xs font-bold text-teal-600 hover:text-teal-700 flex items-center gap-1" style="text-decoration:none">
                        <span>Voir tout</span>
                        <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($relatedArticles as $rel)
                        <div class="bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-md transition-all flex flex-col justify-between">
                            <div>
                                <div class="h-44 bg-slate-900 overflow-hidden relative">
                                    @if($rel->getFirstImageUrl())
                                        <img src="{{ $rel->getFirstImageUrl() }}" alt="{{ $rel->titre }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-white/30 text-4xl">
                                            <i class="fas fa-tshirt"></i>
                                        </div>
                                    @endif
                                    <div class="absolute top-3 right-3">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-white/90 backdrop-blur-sm text-slate-800">
                                            {{ $rel->categorie }}
                                        </span>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <h4 class="font-bold text-slate-900 text-sm mb-1 truncate">
                                        <a href="{{ route('articles.show', $rel) }}" class="hover:text-teal-600 transition-colors" style="text-decoration:none; color:inherit;">
                                            {{ $rel->titre }}
                                        </a>
                                    </h4>
                                    @if($rel->nbAvis() > 0)
                                        <div class="flex items-center gap-1 text-[11px] text-amber-600 font-bold mb-2">
                                            <i class="fas fa-star text-amber-500 text-[10px]"></i>
                                            <span>{{ number_format($rel->noteMoyenne(), 1) }}</span>
                                            <span class="text-slate-400 font-normal">({{ $rel->nbAvis() }})</span>
                                        </div>
                                    @endif
                                    <div class="font-display font-extrabold text-base text-teal-700">
                                        {{ number_format($rel->prix, 2) }} DT
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 bg-slate-50 border-t border-slate-100">
                                <a href="{{ route('articles.show', $rel) }}" class="block text-center py-2 px-3 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700 transition-colors" style="text-decoration:none">
                                    Voir l'article
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>

<script>
function switchMainImage(url, thumbElement) {
    document.getElementById('main-gallery-img').src = url;
    document.querySelectorAll('.gallery-thumb').forEach(el => el.classList.remove('active'));
    thumbElement.classList.add('active');
}

document.addEventListener('DOMContentLoaded', function() {
    const qtyInput = document.getElementById('quantite-input');
    const minusBtn = document.getElementById('qty-minus-btn');
    const plusBtn  = document.getElementById('qty-plus-btn');
    const buyLink  = document.getElementById('buy-now-link');
    const cartForm = document.getElementById('show-add-to-cart-form');
    const maxStock = {{ (int) $article->stock }};

    if (qtyInput && minusBtn && plusBtn) {
        minusBtn.addEventListener('click', function() {
            let val = parseInt(qtyInput.value) || 1;
            if (val > 1) {
                qtyInput.value = val - 1;
                updateBuyNowHref(val - 1);
            }
        });

        plusBtn.addEventListener('click', function() {
            let val = parseInt(qtyInput.value) || 1;
            if (val < maxStock) {
                qtyInput.value = val + 1;
                updateBuyNowHref(val + 1);
            }
        });

        qtyInput.addEventListener('change', function() {
            let val = parseInt(qtyInput.value) || 1;
            if (val < 1) val = 1;
            if (val > maxStock) val = maxStock;
            qtyInput.value = val;
            updateBuyNowHref(val);
        });

        function updateBuyNowHref(qty) {
            if (buyLink) {
                buyLink.href = "{{ route('commandes.create', ['article_id' => $article->id]) }}&quantite=" + qty;
            }
        }
    }

    if (cartForm) {
        cartForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('add-to-cart-submit-btn');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i><span>Ajout en cours...</span>';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const quantite = parseInt(qtyInput?.value) || 1;

            try {
                const response = await fetch(cartForm.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ quantite: quantite }),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    btn.innerHTML = '<i class="fas fa-check"></i><span>Ajouté au panier !</span>';
                    btn.classList.remove('from-teal-600', 'to-teal-700');
                    btn.classList.add('from-emerald-600', 'to-emerald-700');

                    // Update all navbar cart badges
                    if (data.resume && typeof data.resume.nb_articles !== 'undefined') {
                        document.querySelectorAll('.cart-badge-count').forEach(badge => {
                            badge.textContent = data.resume.nb_articles > 99 ? '99+' : data.resume.nb_articles;
                            badge.classList.remove('hidden');
                            badge.style.display = data.resume.nb_articles > 0 ? 'flex' : 'none';
                        });
                    }

                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.classList.remove('from-emerald-600', 'to-emerald-700');
                        btn.classList.add('from-teal-600', 'to-teal-700');
                        btn.disabled = false;
                    }, 2000);
                } else {
                    retissAlert({
                        title: 'Attention',
                        text: data.message || data.error || 'Erreur lors de l\'ajout au panier.',
                        icon: 'warning'
                    });
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            } catch (err) {
                retissAlert({
                    title: 'Erreur',
                    text: 'Une erreur réseau est survenue lors de l\'ajout au panier.',
                    icon: 'error'
                });
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        });
    }
});

// ===== Toggle Favori sur la page de détail =====
async function toggleFavoriShow(articleId) {
    const btn = document.getElementById('btn-toggle-favori-show');
    const textSpan = document.getElementById('favori-show-text');
    if (!btn) return;
    const icon = btn.querySelector('i');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    try {
        const res = await fetch(`/favoris/toggle/${articleId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        const data = await res.json();
        if (res.ok) {
            if (data.is_favori) {
                btn.className = 'w-full py-2.5 px-4 rounded-xl border border-rose-300 bg-rose-50 text-rose-600 font-semibold text-xs transition-colors flex items-center justify-center gap-2 mb-2';
                icon.className = 'fas fa-heart text-rose-500 text-sm';
                textSpan.textContent = 'Dans votre liste de souhaits';
            } else {
                btn.className = 'w-full py-2.5 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition-colors flex items-center justify-center gap-2 mb-2';
                icon.className = 'far fa-heart text-rose-500 text-sm';
                textSpan.textContent = 'Ajouter à ma liste de souhaits';
            }

            document.querySelectorAll('.favoris-badge-count').forEach(el => {
                el.textContent = data.favori_count > 99 ? '99+' : data.favori_count;
                el.style.display = data.favori_count > 0 ? 'flex' : 'none';
            });

            if (typeof retissToast === 'function') {
                retissToast({
                    title: data.is_favori ? 'Ajouté aux favoris' : 'Retiré des favoris',
                    text: data.message,
                    icon: data.is_favori ? 'success' : 'info'
                });
            }
        }
    } catch(e) {
        console.error(e);
    }
}
</script>
@endsection
