@extends('layouts.app')

@section('title', 'Gisement de Dons Textiles — RETISS')
@section('body-class', 'no-hero')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-emerald-50/20 py-10">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- ── HEADER ────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    <i class="fas fa-hand-holding-heart text-emerald-600"></i> Matière Première Gratuite
                </span>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                    <i class="fas fa-wand-magic-sparkles"></i> IA d'Aide à la Décision
                </span>
            </div>
            <h1 class="font-display font-black text-2xl sm:text-3xl text-slate-900 tracking-tight">
                Gisement de Dons Textiles Disponibles
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
                Consultez les dons publiés par la communauté et simulez leur rentabilité par pièce ou par lot avec l'IA.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard.atelier') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-xs transition">
                <i class="fas fa-arrow-left"></i> Tableau de bord
            </a>
            <a href="{{ route('atelier.estimations.historique') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-sm transition">
                <i class="fas fa-chart-line text-indigo-400"></i> Historique IA
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-boxes-stacked"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Lots de dons disponibles</span>
                <span class="text-2xl font-black text-slate-900">{{ $statsDons['total_lots'] }}</span>
                <span class="text-xs text-emerald-600 font-semibold block mt-0.5">Prêts pour l'upcycling</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-shirt"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total pièces valorisables</span>
                <span class="text-2xl font-black text-slate-900">{{ $statsDons['total_pieces'] }}</span>
                <span class="text-xs text-teal-600 font-semibold block mt-0.5">Vêtements & coupons</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shrink-0">
                <i class="fas fa-weight-scale"></i>
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Masse textile disponible</span>
                <span class="text-2xl font-black text-slate-900">{{ number_format($statsDons['total_poids_kg'], 1) }} <span class="text-sm font-bold text-slate-500">kg</span></span>
                <span class="text-xs text-indigo-600 font-semibold block mt-0.5">Gisement local gratuit</span>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
        <form method="GET" action="{{ route('atelier.dons') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[200px] relative">
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Rechercher un don (ex: chemises, laine, lin...)"
                       class="w-full text-xs rounded-xl border-slate-200 pl-9 pr-3 py-2.5 focus:border-emerald-500 focus:ring-emerald-500">
                <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-xs"></i>
            </div>

            <div class="w-40">
                <select name="matiere" onchange="this.form.submit()"
                        class="w-full text-xs rounded-xl border-slate-200 py-2.5 focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Toutes les matières</option>
                    @foreach(\App\Models\Article::MATIERES as $m)
                        <option value="{{ $m }}" {{ request('matiere') === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-40">
                <select name="etat" onchange="this.form.submit()"
                        class="w-full text-xs rounded-xl border-slate-200 py-2.5 focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="">Tous les états</option>
                    @foreach(\App\Models\Article::ETATS as $e)
                        <option value="{{ $e }}" {{ request('etat') === $e ? 'selected' : '' }}>{{ $e }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs">
                Filtrer
            </button>

            @if(request()->hasAny(['q', 'matiere', 'etat']))
                <a href="{{ route('atelier.dons') }}"
                   class="text-xs text-slate-500 hover:text-slate-700 font-semibold px-2 py-2.5">
                    <i class="fas fa-times-circle"></i> Réinitialiser
                </a>
            @endif
        </form>
    </div>

    {{-- Dons Grid --}}
    @if($dons->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($dons as $don)
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between hover:shadow-md transition-all group">
                    <div>
                        {{-- Photo & Badges --}}
                        <div class="relative h-48 bg-slate-100 overflow-hidden">
                            @if($don->getFirstImageUrl())
                                <img src="{{ $don->getFirstImageUrl() }}"
                                     alt="{{ $don->titre }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-300 text-4xl">
                                    <i class="fas fa-shirt"></i>
                                </div>
                            @endif

                            {{-- Top badges --}}
                            <div class="absolute top-3 left-3 flex items-center gap-1.5">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-600 text-white shadow-sm">
                                    <i class="fas fa-gift"></i> Don Gratuit (0 DT)
                                </span>
                            </div>

                            <div class="absolute top-3 right-3">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-900/80 text-white backdrop-blur-sm shadow-sm">
                                    <i class="fas fa-boxes-stacked"></i> {{ $don->stock }} {{ $don->stock > 1 ? 'pièces' : 'pièce' }}
                                </span>
                            </div>

                            @if($don->matiere)
                                <div class="absolute bottom-3 left-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[11px] font-bold bg-white/95 text-slate-700 shadow-sm">
                                        <i class="fas fa-scroll text-teal-600"></i> {{ $don->matiere }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="p-5">
                            <h3 class="font-display font-bold text-slate-900 text-base mb-1.5 line-clamp-1 group-hover:text-emerald-700 transition">
                                {{ $don->titre }}
                            </h3>

                            <div class="flex flex-wrap items-center gap-2 mb-3 text-xs text-slate-500">
                                @if($don->etat)
                                    <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 font-semibold">{{ $don->etat }}</span>
                                @endif
                                @if($don->taille)
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold">Taille {{ $don->taille }}</span>
                                @endif
                                @if($don->poids_kg)
                                    <span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 font-semibold">{{ $don->poids_kg }} kg/pc</span>
                                @endif
                            </div>

                            <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed mb-4">
                                {{ $don->description }}
                            </p>

                            {{-- AI Potential Highlight --}}
                            <div class="p-3 bg-gradient-to-r from-emerald-50/70 to-teal-50/70 rounded-2xl border border-emerald-100 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs">
                                        <i class="fas fa-wand-magic-sparkles"></i>
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-800 block text-[11px]">Potentiel d'Upcycling</span>
                                        <span class="text-[10px] text-emerald-700 font-semibold">Coût d'acquisition : 0 DT</span>
                                    </div>
                                </div>
                                <span class="px-2 py-1 rounded-md text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    🟢 Gagnant
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Footer CTA --}}
                    <div class="p-5 pt-0">
                        <a href="{{ route('articles.show', $don) }}"
                           class="w-full py-3 px-4 rounded-xl bg-slate-900 hover:bg-emerald-700 text-white text-xs font-bold flex items-center justify-center gap-2 transition-all shadow-sm group-hover:shadow-md"
                           style="text-decoration:none">
                            <i class="fas fa-calculator text-emerald-400"></i>
                            <span>Estimer la rentabilité (Pièce & Quantité)</span>
                            <i class="fas fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $dons->links() }}
        </div>
    @else
        <div class="bg-white rounded-3xl border border-slate-200 p-12 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fas fa-gift"></i>
            </div>
            <h3 class="font-display font-bold text-slate-800 text-base mb-1">Aucun don textile trouvé</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto">
                Aucun don ne correspond à vos filtres de recherche. Réessayez avec d'autres critères ou revenez bientôt.
            </p>
        </div>
    @endif

</div>
</div>
@endsection
