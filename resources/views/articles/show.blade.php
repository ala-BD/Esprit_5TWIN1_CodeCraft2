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
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-500/20 border border-teal-400/30 text-teal-300 text-xs font-semibold mb-2">
                        <span>{{ $article->categorie }}</span>
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
                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet article ? Cette action est irréversible.');"
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
                            {{-- Buy Form --}}
                            <form action="{{ route('commandes.create') }}" method="GET" class="space-y-4 mb-4">
                                <input type="hidden" name="article_id" value="{{ $article->id }}">

                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Quantité souhaitée</label>
                                    <div class="flex items-center gap-3">
                                        <input type="number" name="quantite" value="1" min="1" max="{{ $article->stock }}"
                                               class="w-24 py-2.5 px-3 border border-slate-200 rounded-xl text-center font-bold text-sm outline-none focus:border-teal-500 bg-slate-50"
                                               required>
                                        <span class="text-xs text-slate-400">max: {{ $article->stock }}</span>
                                    </div>
                                </div>

                                <button type="submit"
                                        class="w-full py-3.5 px-5 rounded-2xl font-display font-bold text-sm text-white bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 shadow-lg shadow-teal-700/25 transition-all duration-200 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                                    <i class="fas fa-shopping-bag"></i>
                                    <span>Commander cet article</span>
                                </button>
                            </form>
                        @endif
                    @else
                        <div class="p-3 bg-amber-50 rounded-2xl border border-amber-100 text-xs text-amber-800 mb-5 flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle text-amber-600"></i>
                            <span>Cet article est actuellement <strong>{{ $article->statut }}</strong></span>
                        </div>
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

    </div>
</div>

<script>
function switchMainImage(url, thumbElement) {
    document.getElementById('main-gallery-img').src = url;
    document.querySelectorAll('.gallery-thumb').forEach(el => el.classList.remove('active'));
    thumbElement.classList.add('active');
}
</script>
@endsection
