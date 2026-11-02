@extends('layouts.app')

@section('title', 'Laisser un avis — ' . $article->titre . ' — RETISS')

@section('styles')
<style>
.star-rating {
    display: inline-flex;
    flex-direction: row-reverse;
    gap: 4px;
}
.star-rating input {
    display: none;
}
.star-rating label {
    font-size: 24px;
    color: #cbd5e1;
    cursor: pointer;
    transition: color 0.15s, transform 0.15s;
}
.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: #f59e0b;
}
.star-rating label:hover {
    transform: scale(1.15);
}
</style>
@endsection

@section('content')
<div class="py-10 bg-slate-50 min-h-screen">
    <div class="max-w-2xl mx-auto px-4 sm:px-6">

        {{-- Breadcrumb --}}
        <div class="mb-6 flex items-center gap-2 text-xs text-slate-500">
            <a href="{{ route('commandes.index') }}" class="hover:text-teal-600">Mes commandes</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <a href="{{ route('commandes.show', $commande) }}" class="hover:text-teal-600">Commande {{ $commande->numero }}</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-slate-800 font-semibold">Évaluer l'article</span>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8">

            {{-- Header --}}
            <div class="flex items-center gap-3 pb-6 border-b border-slate-100">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center text-xl flex-shrink-0">
                    <i class="fas fa-star"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900">Donnez votre avis sur cet article</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Votre retour aide la communauté et récompense le travail de nos ateliers.</p>
                </div>
            </div>

            {{-- Article Recap Card --}}
            <div class="my-6 p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-center gap-4">
                @if($article->getFirstImageUrl())
                    <img src="{{ $article->getFirstImageUrl() }}" alt="{{ $article->titre }}"
                         class="w-16 h-16 object-cover rounded-xl shadow-sm flex-shrink-0">
                @else
                    <div class="w-16 h-16 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center text-xl flex-shrink-0">
                        <i class="fas fa-tshirt"></i>
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <h2 class="font-bold text-slate-900 text-sm truncate">{{ $article->titre }}</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Vendu par <span class="font-semibold text-slate-700">{{ $article->user->full_name ?: $article->user->name }}</span>
                        @if($article->categorie) · {{ $article->categorie }} @endif
                    </p>
                    <p class="text-xs text-teal-600 font-bold mt-1">{{ number_format($article->prix, 2) }} DT</p>
                </div>
            </div>

            {{-- Error alerts --}}
            @if($errors->any())
                <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs">
                    <p class="font-bold mb-1">Veuillez corriger les erreurs suivantes :</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Form --}}
            <form action="{{ route('avis.store', ['commande' => $commande, 'article' => $article]) }}" method="POST" id="form-avis" class="space-y-6">
                @csrf

                {{-- Criteria 1 : Livraison --}}
                <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-truck-fast text-teal-600 text-sm"></i>
                            <span class="font-bold text-slate-800 text-sm">Livraison</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">Rapidité d'expédition, soin de l'emballage</p>
                    </div>
                    <div class="star-rating">
                        @for($i = 5; $i >= 1; $i--)
                            <input type="radio" id="livraison-{{ $i }}" name="note_livraison" value="{{ $i }}" {{ old('note_livraison') == $i ? 'checked' : '' }} required onchange="recalculerMoyenne()">
                            <label for="livraison-{{ $i }}" title="{{ $i }} étoile(s)">★</label>
                        @endfor
                    </div>
                </div>

                {{-- Criteria 2 : Article acheté --}}
                <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-shirt text-teal-600 text-sm"></i>
                            <span class="font-bold text-slate-800 text-sm">Article acheté</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">Qualité du textile, état général, finitions</p>
                    </div>
                    <div class="star-rating">
                        @for($i = 5; $i >= 1; $i--)
                            <input type="radio" id="article-{{ $i }}" name="note_article" value="{{ $i }}" {{ old('note_article') == $i ? 'checked' : '' }} required onchange="recalculerMoyenne()">
                            <label for="article-{{ $i }}" title="{{ $i }} étoile(s)">★</label>
                        @endfor
                    </div>
                </div>

                {{-- Criteria 3 : Clarté de la description --}}
                <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-file-lines text-teal-600 text-sm"></i>
                            <span class="font-bold text-slate-800 text-sm">Clarté de la description</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">Conformité par rapport aux photos et dimensions</p>
                    </div>
                    <div class="star-rating">
                        @for($i = 5; $i >= 1; $i--)
                            <input type="radio" id="clarte-{{ $i }}" name="note_clarte" value="{{ $i }}" {{ old('note_clarte') == $i ? 'checked' : '' }} required onchange="recalculerMoyenne()">
                            <label for="clarte-{{ $i }}" title="{{ $i }} étoile(s)">★</label>
                        @endfor
                    </div>
                </div>

                {{-- Criteria 4 : Service client --}}
                <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <i class="fas fa-comments text-teal-600 text-sm"></i>
                            <span class="font-bold text-slate-800 text-sm">Service client</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">Réactivité du vendeur, professionnalisme</p>
                    </div>
                    <div class="star-rating">
                        @for($i = 5; $i >= 1; $i--)
                            <input type="radio" id="service-{{ $i }}" name="note_service" value="{{ $i }}" {{ old('note_service') == $i ? 'checked' : '' }} required onchange="recalculerMoyenne()">
                            <label for="service-{{ $i }}" title="{{ $i }} étoile(s)">★</label>
                        @endfor
                    </div>
                </div>

                {{-- Live Overall Rating Banner --}}
                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200/80 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-award text-amber-500 text-lg"></i>
                        <span class="text-xs font-bold text-amber-900">Note globale calculée par le serveur :</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-black text-amber-600" id="preview-note">-- / 5</span>
                        <div class="flex text-amber-400 text-sm" id="preview-stars">
                            <span>★</span><span>★</span><span>★</span><span>★</span><span>★</span>
                        </div>
                    </div>
                </div>

                {{-- Comment textarea --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="commentaire" class="text-xs font-bold text-slate-700">Votre commentaire (optionnel)</label>
                        <span class="text-[11px] text-slate-400" id="char-counter">0 / 500 caractères</span>
                    </div>
                    <textarea name="commentaire" id="commentaire" rows="4" maxlength="500"
                              placeholder="Partagez votre expérience avec cet article et cet atelier (qualité du tissu, tenue, livraison...)"
                              class="w-full px-4 py-3 rounded-2xl border border-slate-200 text-sm text-slate-800 outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100 transition-all resize-none">{{ old('commentaire') }}</textarea>
                </div>

                {{-- Action buttons --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('commandes.show', $commande) }}" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-semibold transition-colors">
                        Annuler
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold shadow-md transition-all flex items-center gap-2">
                        <i class="fas fa-paper-plane text-xs"></i>
                        <span>Publier mon avis</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function recalculerMoyenne() {
    const l = document.querySelector('input[name="note_livraison"]:checked');
    const a = document.querySelector('input[name="note_article"]:checked');
    const c = document.querySelector('input[name="note_clarte"]:checked');
    const s = document.querySelector('input[name="note_service"]:checked');

    if (l && a && c && s) {
        const valL = parseInt(l.value, 10);
        const valA = parseInt(a.value, 10);
        const valC = parseInt(c.value, 10);
        const valS = parseInt(s.value, 10);
        const avg = ((valL + valA + valC + valS) / 4).toFixed(1);
        document.getElementById('preview-note').textContent = avg + ' / 5';
    } else {
        document.getElementById('preview-note').textContent = '-- / 5';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const textarea = document.getElementById('commentaire');
    const counter  = document.getElementById('char-counter');
    if (textarea && counter) {
        textarea.addEventListener('input', function () {
            counter.textContent = this.value.length + ' / 500 caractères';
        });
        counter.textContent = textarea.value.length + ' / 500 caractères';
    }
    recalculerMoyenne();
});
</script>
@endsection
