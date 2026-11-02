@extends('layouts.app')

@section('title', 'Ma Liste de Souhaits — RETISS')

@section('content')
<div class="py-8 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Top Navigation Breadcrumb --}}
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-6">
            <a href="{{ route('articles.index') }}" class="hover:text-teal-600 transition-colors flex items-center gap-1">
                <i class="fas fa-store"></i>
                <span>Marketplace</span>
            </a>
            <i class="fas fa-chevron-right text-[10px] text-slate-400"></i>
            <span class="font-semibold text-slate-800">Liste de souhaits</span>
        </div>

        {{-- Page Header --}}
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-rose-500 to-pink-600 text-white flex items-center justify-center text-2xl shadow-lg shadow-rose-500/20">
                    <i class="fas fa-heart"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                        <span>Ma Liste de Souhaits</span>
                        <span id="favoris-badge-count" class="px-3 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-600 border border-rose-200">
                            {{ $favoris->count() }} article{{ $favoris->count() > 1 ? 's' : '' }}
                        </span>
                    </h1>
                    <p class="text-slate-500 text-sm mt-1">
                        Retrouvez vos articles favoris et soyez alerté(e) instantanément en cas de baisse de prix ou de retour en stock.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('articles.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition-all shadow-sm">
                    <i class="fas fa-arrow-left"></i>
                    <span>Retourner aux articles</span>
                </a>
            </div>
        </div>

        {{-- Grid of Wishlist Items --}}
        @if($favoris->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="favoris-grid">
                @foreach($favoris as $favori)
                    @php
                        $article = $favori->article;
                        $coverImg = $article->getFirstImageUrl();
                        $hasPriceDrop = $favori->prix_au_moment_ajout && $article->prix < $favori->prix_au_moment_ajout;
                        $priceDifference = $hasPriceDrop ? round($favori->prix_au_moment_ajout - $article->prix, 2) : 0;
                    @endphp

                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col justify-between group hover:shadow-md transition-all duration-300 relative favori-card"
                         id="favori-card-{{ $article->id }}">

                        {{-- Card Header & Photo --}}
                        <div class="relative aspect-[4/3] bg-slate-100 overflow-hidden">
                            @if($coverImg)
                                <img src="{{ $coverImg }}" alt="{{ $article->titre }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-300">
                                    <i class="fas fa-tshirt text-5xl"></i>
                                </div>
                            @endif

                            {{-- Category Badge --}}
                            <div class="absolute top-3 left-3">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-white/90 backdrop-blur-sm text-slate-800 shadow-sm">
                                    {{ $article->categorie }}
                                </span>
                            </div>

                            {{-- Heart / Remove button --}}
                            <button type="button"
                                    class="absolute top-3 right-3 w-9 h-9 rounded-full bg-white/95 text-rose-500 hover:text-rose-600 hover:bg-rose-50 shadow-md flex items-center justify-center transition-all duration-200 btn-remove-favori"
                                    data-article-id="{{ $article->id }}"
                                    data-article-titre="{{ $article->titre }}"
                                    title="Retirer des favoris">
                                <i class="fas fa-heart text-base"></i>
                            </button>

                            {{-- Price Drop Badge if lowered --}}
                            @if($hasPriceDrop)
                                <div class="absolute bottom-3 left-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-600 text-white shadow-md animate-pulse">
                                        <i class="fas fa-arrow-down"></i> -{{ $priceDifference }} DT
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Card Body --}}
                        <div class="p-4 flex-1 flex flex-col justify-between">
                            <div>
                                <h3 class="font-bold text-slate-900 text-sm mb-1 line-clamp-1 group-hover:text-teal-600 transition-colors">
                                    <a href="{{ route('articles.show', $article) }}">
                                        {{ $article->titre }}
                                    </a>
                                </h3>

                                <div class="flex items-center gap-2 mb-2">
                                    @if($article->taille)
                                        <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-bold">T: {{ $article->taille }}</span>
                                    @endif
                                    @if($article->etat)
                                        <span class="px-2 py-0.5 rounded bg-teal-50 text-teal-700 text-[10px] font-bold">{{ $article->etat }}</span>
                                    @endif
                                </div>

                                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed mb-3">
                                    {{ $article->description ?: 'Aucune description fournie.' }}
                                </p>
                            </div>

                            <div>
                                {{-- Price & Stock --}}
                                <div class="flex items-baseline justify-between pt-2 border-t border-slate-100 mb-3">
                                    <div>
                                        <span class="text-base font-extrabold text-teal-700">{{ number_format($article->prix, 2) }} DT</span>
                                        @if($hasPriceDrop)
                                            <span class="text-xs text-slate-400 line-through ml-1.5">{{ number_format($favori->prix_au_moment_ajout, 2) }} DT</span>
                                        @endif
                                    </div>

                                    @if($article->stock > 2)
                                        <span class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">En stock ({{ $article->stock }})</span>
                                    @elseif($article->stock > 0)
                                        <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded">Plus que {{ $article->stock }} !</span>
                                    @else
                                        <span class="text-[10px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded">Rupture</span>
                                    @endif
                                </div>

                                {{-- Action buttons --}}
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('articles.show', $article) }}"
                                       class="flex-1 text-center py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition-colors">
                                        Voir détails
                                    </a>

                                    @if($article->isDisponible() && Auth::id() !== $article->user_id)
                                        <form method="POST" action="{{ route('panier.ajouter', $article) }}" class="inline add-to-cart-form">
                                            @csrf
                                            <input type="hidden" name="quantite" value="1">
                                            <button type="submit"
                                                    class="py-2 px-3 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 cart-btn"
                                                    title="Ajouter au panier">
                                                <i class="fas fa-cart-plus"></i>
                                                <span>Panier</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

            {{-- Empty State (Hidden initially, shown when all items removed) --}}
            <div id="favoris-empty-state" class="hidden bg-white rounded-3xl border border-slate-200 p-12 text-center max-w-md mx-auto shadow-sm">
                <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4 text-3xl">
                    <i class="far fa-heart"></i>
                </div>
                <h3 class="font-bold text-slate-800 text-lg mb-2">Votre liste de souhaits est vide</h3>
                <p class="text-xs text-slate-500 mb-6">
                    Vous n'avez pas encore d'article en favoris. Explorez la marketplace pour enregistrer les articles qui vous plaisent !
                </p>
                <a href="{{ route('articles.index') }}"
                   class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition-all shadow-sm">
                    <i class="fas fa-search"></i>
                    <span>Découvrir la marketplace</span>
                </a>
            </div>

        @else
            {{-- Empty State --}}
            <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center max-w-md mx-auto shadow-sm">
                <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4 text-3xl">
                    <i class="far fa-heart"></i>
                </div>
                <h3 class="font-bold text-slate-800 text-lg mb-2">Votre liste de souhaits est vide</h3>
                <p class="text-xs text-slate-500 mb-6 leading-relaxed">
                    Ajoutez vos articles préférés en cliquant sur l'icône cœur <i class="fas fa-heart text-rose-500 mx-1"></i>. Nous vous préviendrons dès qu'un prix baisse ou qu'un article revient en stock !
                </p>
                <a href="{{ route('articles.index') }}"
                   class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition-all shadow-sm">
                    <i class="fas fa-search"></i>
                    <span>Découvrir la marketplace</span>
                </a>
            </div>
        @endif

    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    // Toggle / Remove favori
    document.querySelectorAll('.btn-remove-favori').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const articleId = this.dataset.articleId;
            const articleTitre = this.dataset.articleTitre;
            const card = document.getElementById(`favori-card-${articleId}`);

            try {
                const response = await fetch(`/favoris/toggle/${articleId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();

                if (response.ok) {
                    if (card) {
                        card.style.transition = 'all 0.3s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            card.remove();
                            // Update badge count
                            const badge = document.getElementById('favoris-badge-count');
                            if (badge) {
                                badge.textContent = `${data.favori_count} article${data.favori_count > 1 ? 's' : ''}`;
                            }
                            // Update navbar count
                            document.querySelectorAll('.favoris-badge-count').forEach(el => {
                                el.textContent = data.favori_count;
                                el.style.display = data.favori_count > 0 ? 'flex' : 'none';
                            });
                            // If empty, show empty state
                            const grid = document.getElementById('favoris-grid');
                            if (grid && grid.children.length === 0) {
                                grid.classList.add('hidden');
                                const emptyState = document.getElementById('favoris-empty-state');
                                if (emptyState) emptyState.classList.remove('hidden');
                            }
                        }, 300);
                    }

                    if (typeof retissToast === 'function') {
                        retissToast({ title: 'Retiré', text: `« ${articleTitre} » a été retiré de vos favoris.`, icon: 'info' });
                    }
                }
            } catch (err) {
                console.error(err);
            }
        });
    });

    // Add to cart AJAX
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
                    if (data.resume?.nb_articles !== undefined) {
                        document.querySelectorAll('.cart-badge-count').forEach(b => {
                            b.textContent = data.resume.nb_articles > 99 ? '99+' : data.resume.nb_articles;
                            b.style.display = data.resume.nb_articles > 0 ? 'flex' : 'none';
                        });
                    }
                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.classList.replace('bg-emerald-600','bg-teal-600');
                        btn.disabled = false;
                    }, 1800);
                } else {
                    alert(data.message || "Erreur lors de l'ajout au panier.");
                    btn.innerHTML = originalHtml; btn.disabled = false;
                }
            } catch (err) {
                btn.innerHTML = originalHtml; btn.disabled = false;
            }
        });
    });
});
</script>
@endsection
