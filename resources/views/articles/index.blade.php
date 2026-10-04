@extends('layouts.app')

@section('title', 'Marketplace Textile — RETISS')

@section('styles')
<style>
    .marketplace-page {
        min-height: 100vh;
        background: linear-gradient(160deg, #f0fdf9 0%, #f8fafc 40%, #f0f4ff 100%);
        padding-top: 72px;
    }

    .marketplace-hero {
        background: linear-gradient(135deg, #111a30 0%, #1a2744 35%, #1B4332 70%, #0d9488 100%);
        position: relative;
        overflow: hidden;
        padding: 3rem 0 5.5rem;
    }
    .marketplace-hero::before {
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

    .marketplace-content {
        margin-top: -3.5rem;
        position: relative;
        z-index: 10;
    }

    .article-card {
        background: #ffffff;
        border-radius: 1.5rem;
        border: 1px solid #e2e8f0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .article-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 32px -8px rgba(13,148,136,0.15), 0 4px 12px rgba(15,23,42,0.06);
        border-color: #99f6e4;
    }

    .card-banner {
        height: 180px;
        background: linear-gradient(135deg, #0f172a, #1e293b 40%, #0d9488);
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .card-banner img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }
    .article-card:hover .card-banner img {
        transform: scale(1.06);
    }
    .card-banner-pattern {
        position: absolute;
        inset: 0;
        opacity: 0.15;
        background-image: radial-gradient(#fff 1.5px, transparent 1.5px);
        background-size: 16px 16px;
    }

    .badge-statut-disponible { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .badge-statut-reserve    { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .badge-statut-vendu      { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .badge-statut-archive    { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    .tag-filter-btn {
        padding: 0.45rem 1rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        transition: all 0.15s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
    }
    .tag-filter-btn:hover {
        background: #f0fdf9;
        border-color: #0d9488;
        color: #0d9488;
    }
    .tag-filter-btn.active {
        background: #0d9488;
        border-color: #0d9488;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(13,148,136,0.25);
    }
</style>
@endsection

@section('content')
<div class="marketplace-page">

    {{-- ===== HERO ===== --}}
    <div class="marketplace-hero">
        <div class="hero-grid"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">

            {{-- Breadcrumb --}}
            <div class="flex items-center gap-2 mb-3">
                <a href="{{ route('home') }}" class="text-white/50 hover:text-white/80 text-sm transition-colors" style="text-decoration:none">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <span class="text-white/80 text-sm font-medium">Marketplace Textile</span>
            </div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-500/20 border border-teal-400/30 text-teal-300 text-xs font-semibold mb-3">
                        <i class="fas fa-recycle text-[10px]"></i> Économie Circulaire & Seconde Main
                    </div>
                    <h1 class="font-display text-3xl sm:text-4xl font-extrabold text-white mb-2 tracking-tight">
                        Marketplace Textile
                    </h1>
                    <p class="text-white/70 text-sm max-w-xl">
                        Découvrez, achetez et publiez des articles textiles upcyclés, réemployés ou revalorisés. Donnez une seconde vie à vos vêtements.
                    </p>
                </div>

                {{-- Action button + Counters --}}
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <a href="{{ route('articles.create') }}"
                       class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl font-bold text-sm text-slate-900 bg-emerald-400 hover:bg-emerald-300 shadow-lg shadow-emerald-500/20 transition-all duration-200 transform hover:-translate-y-0.5"
                       style="text-decoration:none">
                        <i class="fas fa-plus-circle text-base"></i>
                        <span>Publier un article</span>
                    </a>
                </div>
            </div>

            {{-- Stats Pills --}}
            <div class="mt-6 flex flex-wrap items-center gap-3 pt-4 border-t border-white/10">
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-3 py-1.5 rounded-xl text-xs text-white">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span class="font-bold">{{ $stats['total'] }}</span> article(s) au total
                </div>
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-3 py-1.5 rounded-xl text-xs text-white">
                    <span class="w-2 h-2 rounded-full bg-teal-300"></span>
                    <span class="font-bold">{{ $stats['disponibles'] }}</span> disponible(s)
                </div>
                @auth
                <a href="{{ route('articles.index', ['filter' => 'mine']) }}"
                   class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 backdrop-blur-md px-3 py-1.5 rounded-xl text-xs text-teal-200 transition-colors"
                   style="text-decoration:none">
                    <i class="fas fa-user-tag text-xs"></i>
                    <span class="font-bold">{{ $stats['mesArticles'] }}</span> publié(s) par vous
                </a>
                @endauth
            </div>

        </div>
    </div>

    {{-- ===== MAIN CONTENT ===== --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 marketplace-content pb-16">

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

        {{-- ===== SEARCH & FILTER BAR ===== --}}
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-4 sm:p-5 mb-8">
            <form method="GET" action="{{ route('articles.index') }}" class="space-y-4">

                <div class="flex flex-col md:flex-row gap-3">
                    {{-- Search Input --}}
                    <div class="relative flex-1">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text"
                               name="q"
                               value="{{ $currentQ }}"
                               placeholder="Rechercher par titre, matière, mot-clé…"
                               class="w-full pl-11 pr-4 py-3 rounded-2xl border border-slate-200 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition-all">
                        @if($currentQ)
                            <a href="{{ route('articles.index', array_filter(['categorie' => $currentCat, 'statut' => $currentStat, 'filter' => $filterMine ? 'mine' : null])) }}"
                               class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <i class="fas fa-times-circle"></i>
                            </a>
                        @endif
                    </div>

                    {{-- Category Select --}}
                    <div class="w-full md:w-56">
                        <select name="categorie"
                                class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm text-slate-700 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none bg-white">
                            <option value="">Toutes les catégories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ $currentCat === $cat ? 'selected' : '' }}>
                                    {{ $cat }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Statut Select --}}
                    <div class="w-full md:w-44">
                        <select name="statut"
                                class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm text-slate-700 focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none bg-white">
                            <option value="">Tous les statuts</option>
                            @foreach($statuts as $st)
                                <option value="{{ $st }}" {{ $currentStat === $st ? 'selected' : '' }}>
                                    {{ $st }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter Buttons --}}
                    <div class="flex items-center gap-2">
                        <button type="submit"
                                class="px-5 py-3 rounded-2xl bg-teal-600 hover:bg-teal-700 text-white font-semibold text-sm transition-colors flex items-center justify-center gap-2">
                            <i class="fas fa-filter"></i>
                            <span>Filtrer</span>
                        </button>
                        @if($currentQ || $currentCat || $currentStat || $filterMine)
                            <a href="{{ route('articles.index') }}"
                               class="px-4 py-3 rounded-2xl border border-slate-200 hover:bg-slate-50 text-slate-600 text-sm font-semibold transition-colors"
                               title="Réinitialiser les filtres"
                               style="text-decoration:none">
                                <i class="fas fa-undo-alt"></i>
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Quick Tab: Tous vs Mes articles --}}
                <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider mr-1">Affichage :</span>
                        <a href="{{ route('articles.index', array_filter(['q' => $currentQ, 'categorie' => $currentCat, 'statut' => $currentStat])) }}"
                           class="tag-filter-btn {{ !$filterMine ? 'active' : '' }}">
                            <i class="fas fa-globe-americas"></i>
                            <span>Tous les articles</span>
                        </a>
                        <a href="{{ route('articles.index', array_filter(['filter' => 'mine', 'q' => $currentQ, 'categorie' => $currentCat, 'statut' => $currentStat])) }}"
                           class="tag-filter-btn {{ $filterMine ? 'active' : '' }}">
                            <i class="fas fa-user"></i>
                            <span>Mes articles uniquement</span>
                        </a>
                    </div>

                    <div class="text-xs text-slate-500">
                        {{ $articles->total() }} résultat(s) trouvé(s)
                    </div>
                </div>

            </form>
        </div>

        {{-- ===== ARTICLES GRID ===== --}}
        @if($articles->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($articles as $article)
                    @php
                        $isOwner = Auth::id() === $article->user_id;
                        $isAdmin = Auth::user()->isAdmin();
                        $statutClass = match($article->statut) {
                            'DISPONIBLE' => 'badge-statut-disponible',
                            'RESERVE'    => 'badge-statut-reserve',
                            'VENDU'      => 'badge-statut-vendu',
                            default      => 'badge-statut-archive',
                        };
                        $coverImg = $article->getFirstImageUrl();
                    @endphp

                    <div class="article-card group">

                        {{-- Card Header / Banner (Photo or Pattern) --}}
                        <div class="card-banner">
                            @if($coverImg)
                                <img src="{{ $coverImg }}" alt="{{ $article->titre }}">
                            @else
                                <div class="card-banner-pattern"></div>
                                <div class="text-white/30 group-hover:scale-110 transition-transform duration-300">
                                    @if(str_contains(strtolower($article->categorie), 'homme'))
                                        <i class="fas fa-tshirt text-5xl"></i>
                                    @elseif(str_contains(strtolower($article->categorie), 'femme'))
                                        <i class="fas fa-vest text-5xl"></i>
                                    @elseif(str_contains(strtolower($article->categorie), 'enfant'))
                                        <i class="fas fa-baby text-5xl"></i>
                                    @elseif(str_contains(strtolower($article->categorie), 'sac') || str_contains(strtolower($article->categorie), 'accessoire'))
                                        <i class="fas fa-shopping-bag text-5xl"></i>
                                    @elseif(str_contains(strtolower($article->categorie), 'upcycl'))
                                        <i class="fas fa-recycle text-5xl text-emerald-400/40"></i>
                                    @else
                                        <i class="fas fa-cut text-5xl"></i>
                                    @endif
                                </div>
                            @endif

                            {{-- Category Badge --}}
                            <div class="absolute top-3 left-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-white/90 backdrop-blur-sm text-slate-800 shadow-sm">
                                    {{ $article->categorie }}
                                </span>
                            </div>

                            {{-- Status Badge --}}
                            <div class="absolute top-3 right-3">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $statutClass }} shadow-sm">
                                    {{ $article->statut }}
                                </span>
                            </div>

                            {{-- Photo count badge if multiple --}}
                            @if($article->images && count($article->images) > 1)
                                <div class="absolute bottom-2 right-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-black/60 text-white backdrop-blur-sm">
                                        <i class="fas fa-camera"></i> {{ count($article->images) }}
                                    </span>
                                </div>
                            @endif

                            {{-- Donation badge if linked --}}
                            @if($article->don_vetement_id)
                                <div class="absolute bottom-2 left-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-500/80 text-white backdrop-blur-sm" title="Issu d'un don textile">
                                        <i class="fas fa-hand-holding-heart"></i> Don
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Card Body --}}
                        <div class="p-5 flex-1 flex flex-col justify-between">
                            <div>
                                <h3 class="font-display font-bold text-slate-900 text-base mb-1.5 line-clamp-1 group-hover:text-teal-600 transition-colors">
                                    <a href="{{ route('articles.show', $article) }}" style="text-decoration:none; color:inherit;">
                                        {{ $article->titre }}
                                    </a>
                                </h3>

                                <p class="text-xs text-slate-500 line-clamp-2 mb-4 leading-relaxed">
                                    {{ $article->description ?: 'Aucune description fournie.' }}
                                </p>
                            </div>

                            <div>
                                {{-- Price & Stock --}}
                                <div class="flex items-baseline justify-between pt-3 border-t border-slate-100 mb-3">
                                    <div>
                                        <span class="font-display font-extrabold text-xl text-teal-700">
                                            {{ number_format($article->prix, 2) }} DT
                                        </span>
                                    </div>

                                    {{-- Stock --}}
                                    <div class="text-right">
                                        @if($article->stock > 0)
                                            <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">
                                                {{ $article->stock }} en stock
                                            </span>
                                        @else
                                            <span class="text-[11px] font-semibold text-red-600 bg-red-50 px-2 py-0.5 rounded-md">
                                                Rupture
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Seller info --}}
                                <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs text-slate-500">
                                    <div class="flex items-center gap-2 truncate">
                                        <div class="w-6 h-6 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-[10px] flex-shrink-0">
                                            {{ $article->user->initials }}
                                        </div>
                                        <span class="truncate font-medium text-slate-700">
                                            {{ $article->user->full_name ?: $article->user->name }}
                                        </span>
                                    </div>

                                    @if($isOwner)
                                        <span class="text-[10px] font-bold text-teal-600 bg-teal-50 px-1.5 py-0.5 rounded">Vous</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Card Actions Footer --}}
                        <div class="p-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-2">
                            <a href="{{ route('articles.show', $article) }}"
                               class="flex-1 text-center py-2 px-3 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-700 transition-colors"
                               style="text-decoration:none">
                                <i class="fas fa-eye mr-1"></i> Voir détails
                            </a>

                            {{-- Edit & Delete buttons: ONLY for owner or admin --}}
                            @can('update', $article)
                                <a href="{{ route('articles.edit', $article) }}"
                                   class="p-2 rounded-xl bg-white hover:bg-teal-50 border border-slate-200 text-teal-600 text-xs font-semibold transition-colors"
                                   title="Modifier cet article"
                                   style="text-decoration:none">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <form method="POST"
                                      action="{{ route('articles.destroy', $article) }}"
                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet article ? Cette action est irréversible.');"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 rounded-xl bg-white hover:bg-red-50 border border-slate-200 text-red-500 text-xs font-semibold transition-colors"
                                            title="Supprimer cet article">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            @endcan
                        </div>

                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-8">
                {{ $articles->links() }}
            </div>

        @else
            {{-- Empty State --}}
            <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center max-w-lg mx-auto shadow-sm">
                <div class="w-16 h-16 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center mx-auto mb-4 text-2xl">
                    <i class="fas fa-box-open"></i>
                </div>
                <h3 class="font-display font-bold text-slate-800 text-lg mb-1">Aucun article trouvé</h3>
                <p class="text-xs text-slate-500 mb-6 leading-relaxed">
                    @if($currentQ || $currentCat || $currentStat || $filterMine)
                        Aucun article ne correspond à vos filtres de recherche. Essayez de réinitialiser vos critères.
                    @else
                        La marketplace est encore vide. Soyez le premier à publier un vêtement ou un article textile !
                    @endif
                </p>
                <div class="flex items-center justify-center gap-3">
                    @if($currentQ || $currentCat || $currentStat || $filterMine)
                        <a href="{{ route('articles.index') }}"
                           class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                           style="text-decoration:none">
                            Réinitialiser les filtres
                        </a>
                    @endif
                    <a href="{{ route('articles.create') }}"
                       class="px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition-colors"
                       style="text-decoration:none">
                        <i class="fas fa-plus mr-1"></i> Publier un article
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
