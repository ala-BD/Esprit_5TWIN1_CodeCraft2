@extends('layouts.app-auth')

@section('title', 'Mes Commandes — RETISS')

@section('styles')
<style>
.commandes-page {
    min-height: 100vh;
    background: linear-gradient(160deg, #f0fdf9 0%, #f8fafc 50%, #f0f4ff 100%);
    padding-top: 72px;
}
.hero-commandes {
    background: linear-gradient(135deg, #111a30 0%, #1a2744 35%, #1B4332 70%, #0d9488 100%);
    position: relative; overflow: hidden;
    padding: 2.5rem 0 5rem;
}
.hero-commandes::before {
    content: ''; position: absolute; inset: 0;
    background-image:
        radial-gradient(ellipse at 75% 50%, rgba(13,148,136,0.25) 0%, transparent 60%),
        radial-gradient(ellipse at 25% 80%, rgba(74,222,128,0.1) 0%, transparent 50%);
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
.content-wrap { margin-top: -3.5rem; position: relative; z-index: 10; }

.statut-badge {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.25rem 0.85rem; border-radius: 9999px;
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.03em;
}
</style>
@endsection

@section('content')
<div class="commandes-page">

    {{-- HERO --}}
    <div class="hero-commandes">
        <div class="hero-grid"></div>
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex items-center gap-2 mb-3">
                <a href="{{ route('home') }}" class="text-white/50 hover:text-white/80 text-sm" style="text-decoration:none"><i class="fas fa-home"></i></a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <span class="text-white/80 text-sm font-medium">
                    {{ auth()->user()->isAdmin() ? 'Tableau de bord — Commandes' : 'Mes commandes' }}
                </span>
            </div>
            <h1 class="font-display text-3xl font-extrabold text-white mb-1">
                {{ auth()->user()->isAdmin() ? 'Gestion des Commandes' : 'Mes Commandes' }}
            </h1>
            <p class="text-white/60 text-sm">
                Suivez vos achats, téléchargez vos factures et gérez vos livraisons.
            </p>
        </div>
    </div>

    {{-- MAIN --}}
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 content-wrap pb-16">

        {{-- SUCCESS / ERROR FLASH --}}
        @if(session('success'))
            <div class="mb-5 flex items-start gap-3 bg-teal-50 border border-teal-200 rounded-2xl p-4 text-teal-800">
                <i class="fas fa-circle-check mt-0.5 text-teal-600"></i>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 rounded-2xl p-4 text-red-700 text-sm">
                @foreach($errors->all() as $error) <div><i class="fas fa-circle-exclamation mr-1"></i>{{ $error }}</div> @endforeach
            </div>
        @endif

        {{-- ADMIN STATS --}}
        @if($stats)
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm text-center">
                    <div class="text-2xl font-extrabold text-teal-600">{{ number_format($stats['total_revenue'], 2) }} DT</div>
                    <div class="text-xs text-slate-500 mt-1 font-medium">Revenu total</div>
                </div>
                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm text-center">
                    <div class="text-2xl font-extrabold text-navy-700">{{ $stats['total_orders'] }}</div>
                    <div class="text-xs text-slate-500 mt-1 font-medium">Commandes totales</div>
                </div>
                @foreach(['EN_ATTENTE' => 'amber', 'LIVREE' => 'green', 'ANNULEE' => 'red'] as $s => $c)
                    @if(isset($stats['by_statut'][$s]))
                        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm text-center">
                            <div class="text-2xl font-extrabold text-{{ $c }}-600">{{ $stats['by_statut'][$s] }}</div>
                            <div class="text-xs text-slate-500 mt-1 font-medium">{{ str_replace('_', ' ', $s) }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif

        {{-- FILTERS --}}
        <form method="GET" action="{{ route('commandes.index') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
            <div>
                <label class="text-xs font-semibold text-slate-600 block mb-1">Statut</label>
                <select name="statut" class="text-sm border border-slate-200 rounded-xl px-3 py-2 bg-white outline-none focus:border-teal-500">
                    <option value="">Tous</option>
                    @foreach($statuts as $s)
                        <option value="{{ $s }}" {{ $currentStatut === $s ? 'selected' : '' }}>{{ str_replace('_', ' ', $s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 block mb-1">Du</label>
                <input type="date" name="from" value="{{ request('from') }}" class="text-sm border border-slate-200 rounded-xl px-3 py-2 outline-none focus:border-teal-500">
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-600 block mb-1">Au</label>
                <input type="date" name="to" value="{{ request('to') }}" class="text-sm border border-slate-200 rounded-xl px-3 py-2 outline-none focus:border-teal-500">
            </div>
            <button type="submit" class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-sm font-semibold transition-colors">
                <i class="fas fa-filter mr-1"></i> Filtrer
            </button>
            <a href="{{ route('commandes.index') }}" class="text-xs text-slate-500 hover:text-slate-700 font-medium" style="text-decoration:none">Réinitialiser</a>
        </form>

        {{-- ORDERS LIST --}}
        @if($commandes->isEmpty())
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-12 text-center">
                <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-400 text-3xl">
                    <i class="fas fa-box-open"></i>
                </div>
                <p class="text-slate-700 font-semibold text-lg mb-1">Aucune commande trouvée</p>
                <p class="text-slate-400 text-sm mb-5">Vous n'avez pas encore passé de commande sur la marketplace.</p>
                <a href="{{ route('articles.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl font-semibold text-sm transition-colors" style="text-decoration:none">
                    <i class="fas fa-store"></i> Découvrir la marketplace
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($commandes as $commande)
                    @php
                        $color = $commande->statutColor();
                        $colorMap = [
                            'amber' => 'bg-amber-100 text-amber-700',
                            'blue'  => 'bg-blue-100 text-blue-700',
                            'indigo'=> 'bg-indigo-100 text-indigo-700',
                            'teal'  => 'bg-teal-100 text-teal-700',
                            'green' => 'bg-green-100 text-green-700',
                            'red'   => 'bg-red-100 text-red-700',
                            'slate' => 'bg-slate-100 text-slate-700',
                        ];
                        $badgeClass = $colorMap[$color] ?? 'bg-slate-100 text-slate-700';
                    @endphp
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-all p-5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            {{-- Left --}}
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <span class="font-bold text-slate-900 font-mono text-sm">{{ $commande->numero }}</span>
                                    <span class="statut-badge {{ $badgeClass }}">
                                        {{ $commande->statutLabel() }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500">
                                    <i class="fas fa-calendar-day mr-1"></i>
                                    {{ $commande->date_commande->format('d/m/Y à H:i') }}
                                    @if(auth()->user()->isAdmin())
                                        · <i class="fas fa-user mr-1"></i> {{ $commande->user->full_name ?? $commande->user->name }}
                                    @endif
                                </p>
                                <p class="text-xs text-slate-400 mt-1">
                                    {{ $commande->lignes->count() }} article(s) · {{ $commande->mode_paiement }}
                                </p>
                            </div>
                            {{-- Right --}}
                            <div class="flex flex-col items-end gap-2">
                                <div class="text-lg font-extrabold text-teal-700">{{ number_format($commande->montant_total, 2) }} DT</div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('commandes.show', $commande) }}"
                                       class="text-xs px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-semibold transition-colors"
                                       style="text-decoration:none">
                                        <i class="fas fa-eye mr-1"></i> Détails
                                    </a>
                                    @if($commande->estModifiable() && ($commande->user_id === auth()->id() || auth()->user()->isAdmin()))
                                        <a href="{{ route('commandes.edit', $commande) }}"
                                           class="text-xs px-3 py-1.5 border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-lg font-semibold transition-colors"
                                           style="text-decoration:none">
                                            <i class="fas fa-pen mr-1"></i> Modifier
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $commandes->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
