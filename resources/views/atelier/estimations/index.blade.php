@extends('layouts.app')

@section('title', 'Historique des Estimations & Rentabilité — RETISS')
@section('body-class', 'no-hero')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-purple-50/20 py-10">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- ── HEADER ────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('dashboard.atelier') }}" class="text-xs font-semibold text-purple-600 hover:underline">
                    ← Retour au tableau de bord
                </a>
            </div>
            <h1 class="font-display text-3xl font-bold text-gray-900 flex items-center gap-3">
                <span class="w-10 h-10 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl">
                    <i class="fas fa-sparkles"></i>
                </span>
                Historique des estimations IA & Rentabilité
            </h1>
            <p class="text-gray-500 mt-1 text-sm">
                Consultez toutes les simulations économiques, barèmes appliqués et alertes de rentabilité de votre atelier.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('articles.create') }}"
               class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold px-5 py-3 rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all duration-200 text-sm">
                <i class="fas fa-plus"></i> Nouvelle estimation (Article)
            </a>
        </div>
    </div>

    {{-- ── KPIS STATISTIQUES ──────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-500 font-medium">Total simulations</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $stats['total'] }}</p>
            <p class="text-[11px] text-gray-400 mt-0.5">Historique complet</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-emerald-100 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs text-emerald-700 font-semibold">Articles gagnants</p>
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            </div>
            <p class="text-2xl font-extrabold text-emerald-700 mt-1">{{ $stats['gagnants'] }}</p>
            <p class="text-[11px] text-emerald-600/80 mt-0.5">Marge &ge; 20%</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-amber-100 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs text-amber-700 font-semibold">À surveiller</p>
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
            </div>
            <p class="text-2xl font-extrabold text-amber-700 mt-1">{{ $stats['a_surveiller'] }}</p>
            <p class="text-[11px] text-amber-600/80 mt-0.5">Marge 0% à 20%</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-rose-100 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs text-rose-700 font-semibold">Articles perdants</p>
                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
            </div>
            <p class="text-2xl font-extrabold text-rose-700 mt-1">{{ $stats['perdants'] }}</p>
            <p class="text-[11px] text-rose-600/80 mt-0.5">Vente à perte (&le; 0%)</p>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-purple-100 shadow-sm col-span-2 lg:col-span-1">
            <p class="text-xs text-purple-700 font-semibold">Marge moyenne</p>
            <p class="text-2xl font-extrabold text-purple-700 mt-1">
                {{ $stats['marge_moyenne'] > 0 ? '+' : '' }}{{ $stats['marge_moyenne'] }}%
            </p>
            <p class="text-[11px] text-purple-600/80 mt-0.5">Sur l'ensemble estimé</p>
        </div>
    </div>

    {{-- ── FILTRES ────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-sm mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider mr-2">Filtrer par alerte :</span>
            <a href="{{ route('atelier.estimations.historique', ['source' => $currentSource]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ empty($currentNiveau) ? 'bg-purple-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Toutes
            </a>
            <a href="{{ route('atelier.estimations.historique', ['niveau' => 'vert', 'source' => $currentSource]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $currentNiveau === 'vert' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                🟢 Gagnants ({{ $stats['gagnants'] }})
            </a>
            <a href="{{ route('atelier.estimations.historique', ['niveau' => 'orange', 'source' => $currentSource]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $currentNiveau === 'orange' ? 'bg-amber-500 text-white shadow-sm' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                🟠 À surveiller ({{ $stats['a_surveiller'] }})
            </a>
            <a href="{{ route('atelier.estimations.historique', ['niveau' => 'rouge', 'source' => $currentSource]) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $currentNiveau === 'rouge' ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                🔴 Perdants ({{ $stats['perdants'] }})
            </a>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider mr-1">Source :</span>
            <a href="{{ route('atelier.estimations.historique', ['niveau' => $currentNiveau, 'source' => 'ia']) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $currentSource === 'ia' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-700 hover:bg-purple-100' }}">
                <i class="fas fa-sparkles mr-1"></i> IA
            </a>
            <a href="{{ route('atelier.estimations.historique', ['niveau' => $currentNiveau, 'source' => 'regles']) }}"
               class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $currentSource === 'regles' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                <i class="fas fa-calculator mr-1"></i> Règles
            </a>
        </div>
    </div>

    {{-- ── LISTE DES ESTIMATIONS ──────────────────────────────── --}}
    @if($estimations->isEmpty())
        <div class="bg-white rounded-3xl border border-gray-100 p-12 text-center shadow-sm">
            <div class="w-16 h-16 rounded-2xl bg-purple-50 text-purple-500 flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fas fa-calculator"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900">Aucune estimation enregistrée</h3>
            <p class="text-gray-500 text-xs mt-1 max-w-md mx-auto">
                Lorsque vous utilisez l'assistant d'estimation sur un article, l'analyse des charges, la fourchette de prix et la rentabilité sont conservées ici pour votre suivi.
            </p>
            <a href="{{ route('articles.create') }}"
               class="inline-flex items-center gap-2 mt-5 px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-semibold text-xs shadow-md transition-all">
                <i class="fas fa-plus"></i> Créer et estimer un article
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($estimations as $est)
            @php
                $badge = $est->alerte_badge;
                $sourceBadge = $est->source_badge;
            @endphp
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all p-5 sm:p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                    {{-- Info Article & Alerte --}}
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl flex-shrink-0 {{ $badge['bg'] }} {{ $badge['text'] }} border {{ $badge['border'] }}">
                            <i class="fas {{ $badge['icon'] }}"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <h3 class="font-bold text-gray-900 text-base">
                                    {{ $est->article?->titre ?? 'Estimation d\'article' }}
                                </h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $badge['bg'] }} {{ $badge['text'] }} {{ $badge['border'] }}">
                                    {{ $badge['label'] }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $sourceBadge['class'] }}">
                                    <i class="fas {{ $sourceBadge['icon'] }} mr-1"></i> {{ $sourceBadge['label'] }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-400">
                                Réalisée le {{ $est->created_at->format('d/m/Y à H:i') }}
                                @if($est->user)
                                    · Par <strong>{{ $est->user->full_name }}</strong>
                                @endif
                                · Indice de confiance : <strong class="capitalize text-gray-600">{{ $est->confiance }}</strong>
                            </p>
                        </div>
                    </div>

                    {{-- Chiffres clés --}}
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 bg-gray-50/80 rounded-xl p-3 text-center text-xs">
                        <div>
                            <span class="text-gray-400 block text-[10px]">Coût Upcycling</span>
                            <strong class="text-gray-800 font-bold">{{ number_format($est->cout_upcycling, 2) }} DT</strong>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px]">Coût Recyclage</span>
                            <strong class="text-blue-700 font-bold">{{ number_format($est->cout_recyclage, 2) }} DT</strong>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-[10px]">Prix Conseillé</span>
                            <strong class="text-purple-700 font-bold">{{ number_format($est->prix_conseille, 2) }} DT</strong>
                        </div>
                        <div class="col-span-3 sm:col-span-1">
                            <span class="text-gray-400 block text-[10px]">Marge Nette</span>
                            <strong class="font-extrabold {{ $est->marge_nette > 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $est->marge_nette > 0 ? '+' : '' }}{{ number_format($est->marge_nette, 2) }} DT
                                ({{ number_format($est->marge_pourcent, 1) }}%)
                            </strong>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 self-end lg:self-center">
                        @if($est->article)
                            <a href="{{ route('articles.edit', $est->article) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-orange-50 hover:bg-orange-100 text-orange-700 font-semibold text-xs transition-colors">
                                <i class="fas fa-edit"></i>
                                <span>Ajuster l'article</span>
                            </a>
                        @endif

                        <form method="POST" action="{{ route('atelier.estimations.destroy', $est) }}"
                              onsubmit="return confirm('Supprimer cette estimation de votre historique ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-9 h-9 rounded-xl border border-gray-200 text-gray-400 hover:text-red-500 hover:border-red-200 hover:bg-red-50 flex items-center justify-center transition-colors"
                                    title="Supprimer">
                                <i class="fas fa-trash-can text-xs"></i>
                            </button>
                        </form>
                    </div>

                </div>

                {{-- Explication détaillée --}}
                @if($est->explication)
                    <div class="mt-4 pt-3 border-t border-gray-100 text-xs text-gray-600 flex items-start gap-2 bg-purple-50/30 p-3 rounded-xl">
                        <i class="fas fa-comment-dots text-purple-500 mt-0.5 flex-shrink-0"></i>
                        <span class="italic">« {{ $est->explication }} »</span>
                    </div>
                @endif
            </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $estimations->links() }}
        </div>
    @endif

</div>
</div>
@endsection

