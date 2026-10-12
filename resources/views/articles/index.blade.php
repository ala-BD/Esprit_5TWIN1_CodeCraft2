@extends('layouts.app')

@section('title', 'Marketplace Textile — RETISS')

@section('styles')
<style>
    /* ===== Sidebar Filters ===== */
    .filter-sidebar {
        background: #fff; border-radius: 1.25rem; border: 1px solid #e2e8f0;
        box-shadow: 0 4px 24px rgba(15,23,42,0.06); position: sticky; top: 88px;
    }
    .filter-section { border-bottom: 1px solid #f1f5f9; padding: 0.85rem 1.1rem; }
    .filter-section:last-child { border-bottom: none; }
    .filter-section-title {
        font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em;
        text-transform: uppercase; color: #94a3b8; margin-bottom: 0.6rem;
    }
    .filter-chip {
        display: inline-flex; align-items: center; padding: 0.28rem 0.7rem;
        border-radius: 9999px; font-size: 0.7rem; font-weight: 600;
        border: 1.5px solid #e2e8f0; background: #f8fafc; color: #475569;
        cursor: pointer; transition: all 0.15s; margin: 0.18rem 0.12rem 0.18rem 0;
        text-decoration: none; white-space: nowrap;
    }
    .filter-chip:hover  { border-color: #0d9488; background: #f0fdf9; color: #0d9488; }
    .filter-chip.active { background: #0d9488; border-color: #0d9488; color: #fff; box-shadow: 0 2px 8px rgba(13,148,136,0.3); }
    /* Sort bar */
    .sort-bar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; background: #fff; border-radius: 1rem; border: 1px solid #e2e8f0; padding: 0.55rem 1rem; margin-bottom: 1.1rem; }
    .sort-btn { padding: 0.3rem 0.8rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 600; border: 1.5px solid #e2e8f0; background: transparent; color: #64748b; cursor: pointer; transition: all 0.15s; text-decoration: none; }
    .sort-btn.active { background: #0f172a; border-color: #0f172a; color: #fff; }
    .sort-btn:hover:not(.active) { background: #f1f5f9; color: #0f172a; }
    /* Active chips */
    .active-chip { display: inline-flex; align-items: center; gap: 0.3rem; background: #e0f2fe; border: 1.5px solid #bae6fd; color: #0369a1; border-radius: 9999px; padding: 0.22rem 0.6rem; font-size: 0.68rem; font-weight: 700; }
    .active-chip a { text-decoration: none; color: inherit; }
    /* Price inputs */
    .price-range-inputs { display: flex; gap: 0.4rem; align-items: center; }
    .price-input { flex: 1; padding: 0.4rem 0.55rem; border: 1.5px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.75rem; outline: none; transition: border-color 0.15s; }
    .price-input:focus { border-color: #0d9488; }
    /* Autocomplete */
    #suggest-list { position: absolute; top: 100%; left: 0; right: 0; z-index: 100; background: #fff; border: 1px solid #e2e8f0; border-radius: 0.875rem; box-shadow: 0 8px 24px rgba(15,23,42,0.12); overflow: hidden; margin-top: 4px; }
    .suggest-item { padding: 0.55rem 1rem; font-size: 0.8rem; color: #334155; cursor: pointer; transition: background 0.1s; }
    .suggest-item:hover { background: #f0fdf9; color: #0d9488; }
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
                    <a href="{{ route('commandes.index') }}"
                       class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-2xl font-bold text-sm text-white bg-white/10 hover:bg-white/20 border border-white/20 backdrop-blur-md transition-all duration-200"
                       style="text-decoration:none">
                        <i class="fas fa-shopping-bag text-teal-300"></i>
                        <span>Mes Commandes</span>
                    </a>

                    @can('create', \App\Models\Article::class)
                        <a href="{{ route('articles.create') }}"
                           class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl font-bold text-sm text-slate-900 bg-emerald-400 hover:bg-emerald-300 shadow-lg shadow-emerald-500/20 transition-all duration-200 transform hover:-translate-y-0.5"
                           style="text-decoration:none">
                            <i class="fas fa-plus-circle text-base"></i>
                            <span>Publier un article</span>
                        </a>
                    @endcan
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

        <div class="flex gap-7 items-start">

        {{-- ===== SIDEBAR FILTERS ===== --}}
        <aside class="hidden lg:block w-60 flex-shrink-0">
            <form method="GET" action="{{ route('articles.index') }}" id="filter-form" class="filter-sidebar">

                {{-- Header --}}
                <div class="filter-section flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-xs"><i class="fas fa-sliders-h"></i></div>
                        <span class="font-bold text-sm text-slate-800">Filtres</span>
                        @if($activeFilters > 0)
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-teal-600 text-white text-[10px] font-bold">{{ $activeFilters }}</span>
                        @endif
                    </div>
                    @if($activeFilters > 0)
                        <a href="{{ route('articles.index') }}" class="text-xs text-red-500 hover:text-red-700 font-semibold" style="text-decoration:none">
                            <i class="fas fa-times mr-1"></i>Effacer
                        </a>
                    @endif
                </div>

                {{-- Mes articles / En stock / Gratuits --}}
                <div class="filter-section">
                    <div class="filter-section-title">Affichage</div>
                    <label class="flex items-center gap-2 cursor-pointer mb-1.5">
                        <input type="checkbox" name="mine" value="1" {{ request()->boolean('mine') || request()->get('filter') === 'mine' ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-teal-600 border-slate-300" onchange="this.form.submit()">
                        <span class="text-xs text-slate-700 font-medium">Mes articles uniquement</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer mb-1.5">
                        <input type="checkbox" name="en_stock" value="1" {{ request()->boolean('en_stock') ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-teal-600 border-slate-300" onchange="this.form.submit()">
                        <span class="text-xs text-slate-700 font-medium">En stock seulement</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="gratuits" value="1" {{ request()->boolean('gratuits') ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-teal-600 border-slate-300" onchange="this.form.submit()">
                        <span class="text-xs text-slate-700 font-medium">Dons gratuits (0 DT)</span>
                    </label>
                </div>

                {{-- Prix --}}
                <div class="filter-section">
                    <div class="filter-section-title">Prix (DT)</div>
                    <div class="price-range-inputs">
                        <input type="number" name="prix_min" value="{{ request('prix_min') }}" placeholder="Min" min="0" class="price-input">
                        <span class="text-slate-400 text-xs font-bold">—</span>
                        <input type="number" name="prix_max" value="{{ request('prix_max') }}" placeholder="Max" min="0" class="price-input">
                    </div>
                    <button type="submit" class="mt-2 w-full py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">Appliquer</button>
                </div>

                {{-- Catégorie --}}
                <div class="filter-section">
                    <div class="filter-section-title">Catégorie</div>
                    <div class="flex flex-wrap">
                        @foreach($categories as $cat)
                            <a href="{{ route('articles.index', array_merge(request()->except(['categorie','page']), ['categorie' => request('categorie') === $cat ? '' : $cat])) }}"
                               class="filter-chip {{ request('categorie') === $cat ? 'active' : '' }}">{{ $cat }}</a>
                        @endforeach
                    </div>
                </div>

                {{-- Taille --}}
                <div class="filter-section">
                    <div class="filter-section-title">Taille</div>
                    <div class="flex flex-wrap">
                        @foreach($tailles as $t)
                            <a href="{{ route('articles.index', array_merge(request()->except(['taille','page']), ['taille' => request('taille') === $t ? '' : $t])) }}"
                               class="filter-chip {{ request('taille') === $t ? 'active' : '' }}">{{ $t }}</a>
                        @endforeach
                    </div>
                </div>

                {{-- Matière --}}
                <div class="filter-section">
                    <div class="filter-section-title">Matière</div>
                    <div class="flex flex-wrap">
                        @foreach($matieres as $m)
                            <a href="{{ route('articles.index', array_merge(request()->except(['matiere','page']), ['matiere' => request('matiere') === $m ? '' : $m])) }}"
                               class="filter-chip {{ request('matiere') === $m ? 'active' : '' }}">{{ $m }}</a>
                        @endforeach
                    </div>
                </div>

                {{-- État --}}
                <div class="filter-section">
                    <div class="filter-section-title">État</div>
                    <div class="flex flex-wrap">
                        @foreach($etats as $e)
                            <a href="{{ route('articles.index', array_merge(request()->except(['etat','page']), ['etat' => request('etat') === $e ? '' : $e])) }}"
                               class="filter-chip {{ request('etat') === $e ? 'active' : '' }}">{{ $e }}</a>
                        @endforeach
                    </div>
                </div>

                {{-- Couleur --}}
                <div class="filter-section">
                    <div class="filter-section-title">Couleur</div>
                    <div class="flex flex-wrap">
                        @foreach($couleurs as $c)
                            <a href="{{ route('articles.index', array_merge(request()->except(['couleur','page']), ['couleur' => request('couleur') === $c ? '' : $c])) }}"
                               class="filter-chip {{ request('couleur') === $c ? 'active' : '' }}">{{ $c }}</a>
                        @endforeach
                    </div>
                </div>

                {{-- Statut --}}
                <div class="filter-section">
                    <div class="filter-section-title">Statut</div>
                    <div class="flex flex-wrap">
                        @foreach($statuts as $st)
                            <a href="{{ route('articles.index', array_merge(request()->except(['statut','page']), ['statut' => request('statut') === $st ? '' : $st])) }}"
                               class="filter-chip {{ request('statut') === $st ? 'active' : '' }}">{{ $st }}</a>
                        @endforeach
                    </div>
                </div>

                {{-- Vendeur --}}
                <div class="filter-section">
                    <div class="filter-section-title">Type de vendeur</div>
                    <div class="flex flex-wrap">
                        @foreach(['Atelier','Donateur','Recycleur','Collecteur'] as $role)
                            <a href="{{ route('articles.index', array_merge(request()->except(['vendeur_role','page']), ['vendeur_role' => request('vendeur_role') === $role ? '' : $role])) }}"
                               class="filter-chip {{ request('vendeur_role') === $role ? 'active' : '' }}">{{ $role }}</a>
                        @endforeach
                    </div>
                </div>

            </form>
        </aside>

        {{-- ===== RIGHT COLUMN ===== --}}
        <div class="flex-1 min-w-0">

            {{-- Search bar --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-4">
                <form method="GET" action="{{ route('articles.index') }}" class="flex gap-3 items-center">
                    @foreach(request()->except(['q','page']) as $k => $v)
                        @if(!empty($v))
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach
                    <div class="relative flex-1" id="search-wrapper">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text" id="search-input" name="q" value="{{ $currentQ }}"
                               placeholder="Rechercher par titre, mot-clé, matière…" autocomplete="off"
                               class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-200 text-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none transition-all">
                        <div id="suggest-list" class="hidden"></div>
                    </div>
                    <button type="submit" class="px-5 py-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-semibold text-sm transition-colors flex items-center gap-2">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                </form>
            </div>

            {{-- Sort bar --}}
            <div class="sort-bar">
                <div class="flex items-center flex-wrap gap-2">
                    <span class="text-xs font-semibold text-slate-500">Tri :</span>
                    @foreach(['recent' => 'Plus récents', 'prix_asc' => 'Prix ↑', 'prix_desc' => 'Prix ↓'] as $key => $label)
                        <a href="{{ route('articles.index', array_merge(request()->except(['tri','page']), ['tri' => $key])) }}"
                           class="sort-btn {{ $currentTri === $key ? 'active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>
                <div class="text-xs text-slate-500 flex-shrink-0">
                    <span class="font-bold text-slate-800">{{ $articles->total() }}</span> résultat(s)
                </div>
            </div>

            {{-- Active filter chips --}}
            @if($activeFilters > 0)
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach(['q' => 'Recherche', 'categorie' => 'Catégorie', 'taille' => 'Taille', 'matiere' => 'Matière', 'etat' => 'État', 'couleur' => 'Couleur', 'statut' => 'Statut', 'prix_min' => 'Prix min', 'prix_max' => 'Prix max', 'vendeur_role' => 'Vendeur'] as $param => $label)
                        @if(request()->filled($param))
                            <span class="active-chip">
                                {{ $label }}: <strong>{{ request($param) }}</strong>
                                <a href="{{ route('articles.index', array_merge(request()->except([$param,'page']))) }}"><i class="fas fa-times-circle"></i></a>
                            </span>
                        @endif
                    @endforeach
                    @if(request()->boolean('en_stock'))
                        <span class="active-chip">En stock <a href="{{ route('articles.index', array_merge(request()->except(['en_stock','page']))) }}"><i class="fas fa-times-circle"></i></a></span>
                    @endif
                    @if(request()->boolean('gratuits'))
                        <span class="active-chip">Gratuits <a href="{{ route('articles.index', array_merge(request()->except(['gratuits','page']))) }}"><i class="fas fa-times-circle"></i></a></span>
                    @endif
                </div>
            @endif

        {{-- ===== ARTICLES GRID ===== --}}
        @if($articles->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
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
                        <div class="p-4 flex-1 flex flex-col justify-between">
                            <div>
                                <h3 class="font-display font-bold text-slate-900 text-sm mb-1 line-clamp-1 group-hover:text-teal-600 transition-colors">
                                    <a href="{{ route('articles.show', $article) }}" style="text-decoration:none; color:inherit;">
                                        {{ $article->titre }}
                                    </a>
                                </h3>
                                {{-- Meta badges --}}
                                <div class="flex flex-wrap gap-1 mb-1.5">
                                    @if($article->taille)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-semibold"><i class="fas fa-ruler text-[8px]"></i>{{ $article->taille }}</span>
                                    @endif
                                    @if($article->etat)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 text-[10px] font-semibold"><i class="fas fa-star text-[8px]"></i>{{ $article->etat }}</span>
                                    @endif
                                    @if($article->couleur)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 text-[10px] font-semibold"><i class="fas fa-palette text-[8px]"></i>{{ $article->couleur }}</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 line-clamp-2 mb-2 leading-relaxed">
                                    {{ $article->description ?: 'Aucune description fournie.' }}
                                </p>
                            </div>

                            <div>
                                {{-- Price & Stock --}}
                                <div class="flex items-baseline justify-between pt-2 border-t border-slate-100 mb-2">
                                    <span class="font-display font-extrabold text-lg text-teal-700">{{ number_format($article->prix, 2) }} DT</span>
                                    @if($article->stock > 0)
                                        <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">{{ $article->stock }} en stock</span>
                                    @else
                                        <span class="text-[11px] font-semibold text-red-600 bg-red-50 px-2 py-0.5 rounded-md">Rupture</span>
                                    @endif
                                </div>
                                @if($article->co2_economise_kg)
                                    <div class="flex items-center gap-1 mb-1.5 text-[10px] font-semibold text-emerald-700 bg-emerald-50 rounded-lg px-2 py-1">
                                        <i class="fas fa-leaf"></i>{{ number_format($article->co2_economise_kg, 2) }} kg CO₂ économisé
                                    </div>
                                @endif

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
                                <i class="fas fa-eye mr-1"></i> Détails
                            </a>

                            @if($article->isDisponible() && !$isOwner)
                                <form method="POST" action="{{ route('panier.ajouter', $article) }}" class="inline add-to-cart-form">
                                    @csrf
                                    <input type="hidden" name="quantite" value="1">
                                    <button type="submit"
                                            class="py-2 px-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 cart-btn"
                                            title="Ajouter au panier"
                                            data-article-id="{{ $article->id }}"
                                            data-article-titre="{{ $article->titre }}">
                                        <i class="fas fa-cart-plus"></i>
                                        <span>Panier</span>
                                    </button>
                                </form>
                            @endif

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
                                      data-confirm="Êtes-vous sûr de vouloir supprimer l'article « {{ addslashes($article->titre) }} » ? Cette action est irréversible."
                                      data-confirm-title="Supprimer cet article"
                                      data-confirm-button="Oui, supprimer"
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
            <div class="mt-8">{{ $articles->links() }}</div>

        @else
            {{-- Empty State --}}
            <div class="bg-white rounded-3xl border border-slate-200 p-10 text-center max-w-md mx-auto shadow-sm">
                <div class="w-14 h-14 rounded-2xl bg-teal-50 text-teal-600 flex items-center justify-center mx-auto mb-4 text-2xl"><i class="fas fa-box-open"></i></div>
                <h3 class="font-display font-bold text-slate-800 text-lg mb-1">Aucun article trouvé</h3>
                <p class="text-xs text-slate-500 mb-5">
                    @if($activeFilters > 0) Aucun article ne correspond à vos filtres. @else La marketplace est encore vide. @endif
                </p>
                <div class="flex items-center justify-center gap-3">
                    @if($activeFilters > 0)
                        <a href="{{ route('articles.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors" style="text-decoration:none">Réinitialiser</a>
                    @endif
                    @can('create', \App\Models\Article::class)
                        <a href="{{ route('articles.create') }}" class="px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition-colors" style="text-decoration:none"><i class="fas fa-plus mr-1"></i> Publier</a>
                    @endcan
                </div>
            </div>
        @endif

        </div>{{-- end right column --}}
        </div>{{-- end flex row --}}

    </div>{{-- end max-w-7xl --}}
</div>{{-- end marketplace-page --}}
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // ===== Autocomplete =====
    const searchInput = document.getElementById('search-input');
    const suggestList = document.getElementById('suggest-list');
    let debounceTimer;
    if (searchInput && suggestList) {
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const q = this.value.trim();
            if (q.length < 2) { suggestList.classList.add('hidden'); return; }
            debounceTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`/articles/suggest?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } });
                    const items = await res.json();
                    if (!items.length) { suggestList.classList.add('hidden'); return; }
                    suggestList.innerHTML = items.map(t => `<div class="suggest-item" data-value="${t}">${t}</div>`).join('');
                    suggestList.classList.remove('hidden');
                } catch(e) { suggestList.classList.add('hidden'); }
            }, 250);
        });
        suggestList.addEventListener('click', function(e) {
            const item = e.target.closest('.suggest-item');
            if (item) { searchInput.value = item.dataset.value; suggestList.classList.add('hidden'); searchInput.closest('form').submit(); }
        });
        document.addEventListener('click', e => { if (!e.target.closest('#search-wrapper')) suggestList.classList.add('hidden'); });
    }

    // ===== Add to cart =====
    document.querySelectorAll('.add-to-cart-form').forEach(function(form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = form.querySelector('.cart-btn');
            if (!btn || btn.disabled) return;
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ quantite: 1 }),
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    btn.innerHTML = '<i class="fas fa-check"></i><span>Ajouté !</span>';
                    btn.classList.replace('bg-teal-600','bg-emerald-600');
                    btn.classList.replace('hover:bg-teal-700','hover:bg-emerald-700');
                    if (data.resume?.nb_articles !== undefined) {
                        document.querySelectorAll('.cart-badge-count').forEach(b => {
                            b.textContent = data.resume.nb_articles > 99 ? '99+' : data.resume.nb_articles;
                            b.style.display = data.resume.nb_articles > 0 ? 'flex' : 'none';
                        });
                    }
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.classList.replace('bg-emerald-600','bg-teal-600');
                        btn.classList.replace('hover:bg-emerald-700','hover:bg-teal-700');
                        btn.disabled = false;
                    }, 1800);
                } else {
                    retissAlert({ title: 'Attention', text: data.message || data.error || "Erreur lors de l'ajout au panier.", icon: 'warning' });
                    btn.innerHTML = originalHtml; btn.disabled = false;
                }
            } catch (err) {
                retissAlert({ title: 'Erreur', text: "Erreur réseau lors de l'ajout au panier.", icon: 'error' });
                btn.innerHTML = originalHtml; btn.disabled = false;
            }
        });
    });
});
</script>
@endsection
