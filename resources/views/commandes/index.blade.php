@extends('layouts.app')

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

        {{-- FLASH --}}
        @if(session('remboursement'))
            <div class="mb-5 bg-gradient-to-r from-purple-50 via-emerald-50 to-teal-50 border-2 border-purple-300 rounded-3xl p-5 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-purple-600 text-white flex items-center justify-center text-xl flex-shrink-0 shadow-md">
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-extrabold text-purple-900 flex items-center gap-2">
                            <span>L'argent a été remboursé avec succès !</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold">
                                {{ is_array(session('remboursement')) ? session('remboursement')['montant'] : '' }} DT remboursés
                            </span>
                        </h3>
                        <p class="text-sm text-slate-700 mt-1 leading-relaxed">
                            {{ is_array(session('remboursement')) ? session('remboursement')['message'] : session('remboursement') }}
                        </p>
                        <div class="mt-2 text-xs font-semibold text-purple-800 flex items-center gap-1.5">
                            <i class="fas fa-check-circle text-emerald-600"></i>
                            <span>Remboursement Stripe confirmé · Le stock a été augmenté car le produit est rendu</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
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

        {{-- INTERACTIVE STATUS TABS --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-4 scrollbar-none">
            <a href="{{ route('commandes.index', request()->except(['statut', 'page'])) }}"
               class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 {{ empty($currentStatut) ? 'bg-slate-900 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}"
               style="text-decoration:none">
                <i class="fas fa-list-check"></i>
                <span>Toutes</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ empty($currentStatut) ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $totalOrdersCount }}</span>
            </a>
            @foreach($statuts as $s)
                @php
                    $isActive = $currentStatut === $s;
                    $count = $countsByStatut[$s] ?? 0;
                @endphp
                <a href="{{ route('commandes.index', array_merge(request()->except(['statut', 'page']), ['statut' => $s])) }}"
                   class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center gap-2 {{ $isActive ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}"
                   style="text-decoration:none">
                    <span>{{ str_replace('_', ' ', $s) }}</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $count }}</span>
                </a>
            @endforeach
        </div>

        {{-- FILTERS CARD --}}
        <form method="GET" action="{{ route('commandes.index') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-5">
            @if(!empty($currentStatut))
                <input type="hidden" name="statut" value="{{ $currentStatut }}">
            @endif
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                {{-- Search keyword --}}
                <div class="sm:col-span-5">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Recherche</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" name="q" value="{{ $currentQ }}"
                               placeholder="N° commande (TC-...), article..."
                               class="w-full pl-9 pr-3 py-2 text-sm border border-slate-200 rounded-xl outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/10 bg-slate-50 focus:bg-white transition-all font-medium">
                    </div>
                </div>

                {{-- Date Du --}}
                <div class="sm:col-span-2">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Du</label>
                    <input type="date" name="from" value="{{ $currentFrom }}"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl outline-none focus:border-teal-500 bg-slate-50 focus:bg-white transition-all font-medium">
                </div>

                {{-- Date Au --}}
                <div class="sm:col-span-2">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Au</label>
                    <input type="date" name="to" value="{{ $currentTo }}"
                           class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl outline-none focus:border-teal-500 bg-slate-50 focus:bg-white transition-all font-medium">
                </div>

                {{-- Actions --}}
                <div class="sm:col-span-3 flex items-center gap-2">
                    <button type="submit" class="flex-1 py-2 px-4 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-sm font-bold transition-all shadow-sm flex items-center justify-center gap-1.5">
                        <i class="fas fa-filter text-xs"></i>
                        <span>Filtrer</span>
                    </button>
                    @if($currentStatut || $currentQ || $currentFrom || $currentTo)
                        <a href="{{ route('commandes.index') }}"
                           class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-semibold transition-colors flex items-center gap-1"
                           title="Réinitialiser tous les filtres"
                           style="text-decoration:none">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>

        {{-- ACTIVE FILTER CHIPS --}}
        @php
            $hasActiveFilters = $currentStatut || $currentQ || $currentFrom || $currentTo;
        @endphp
        @if($hasActiveFilters)
            <div class="flex flex-wrap items-center gap-2 mb-5 p-2.5 bg-white/80 rounded-xl border border-slate-200 text-xs">
                <span class="font-bold text-slate-400 uppercase tracking-wider text-[10px] mr-1">Filtres actifs :</span>
                @if($currentStatut)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-teal-50 border border-teal-200 text-teal-700 font-medium">
                        Statut : <strong>{{ str_replace('_', ' ', $currentStatut) }}</strong>
                        <a href="{{ route('commandes.index', request()->except(['statut', 'page'])) }}" class="text-teal-500 hover:text-red-500"><i class="fas fa-times-circle"></i></a>
                    </span>
                @endif
                @if($currentQ)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-700 font-medium">
                        Recherche : <strong>{{ $currentQ }}</strong>
                        <a href="{{ route('commandes.index', request()->except(['q', 'page'])) }}" class="text-blue-500 hover:text-red-500"><i class="fas fa-times-circle"></i></a>
                    </span>
                @endif
                @if($currentFrom)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-medium">
                        Depuis : <strong>{{ $currentFrom }}</strong>
                        <a href="{{ route('commandes.index', request()->except(['from', 'page'])) }}" class="text-slate-400 hover:text-red-500"><i class="fas fa-times-circle"></i></a>
                    </span>
                @endif
                @if($currentTo)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-medium">
                        Jusqu'au : <strong>{{ $currentTo }}</strong>
                        <a href="{{ route('commandes.index', request()->except(['to', 'page'])) }}" class="text-slate-400 hover:text-red-500"><i class="fas fa-times-circle"></i></a>
                    </span>
                @endif
                <a href="{{ route('commandes.index') }}" class="ml-auto text-red-600 hover:text-red-700 font-bold text-[11px] inline-flex items-center gap-1">
                    <i class="fas fa-trash-alt text-[10px]"></i> Tout effacer
                </a>
            </div>
        @endif

        {{-- ORDERS LIST --}}
        @if($commandes->isEmpty())
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-12 text-center">
                <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-400 text-3xl">
                    <i class="fas fa-box-open"></i>
                </div>
                <p class="text-slate-700 font-semibold text-lg mb-1">
                    @if($hasActiveFilters)
                        Aucune commande ne correspond à vos filtres
                    @else
                        Aucune commande trouvée
                    @endif
                </p>
                <p class="text-slate-400 text-sm mb-5">
                    @if($hasActiveFilters)
                        Essayez d'élargir votre recherche ou de modifier les filtres appliqués.
                    @else
                        Vous n'avez pas encore passé de commande sur la marketplace.
                    @endif
                </p>
                <div class="flex items-center justify-center gap-3">
                    @if($hasActiveFilters)
                        <a href="{{ route('commandes.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-2xl font-semibold text-sm transition-colors" style="text-decoration:none">
                            <i class="fas fa-rotate-left mr-1"></i> Réinitialiser les filtres
                        </a>
                    @endif
                    <a href="{{ route('articles.index') }}" class="inline-flex items-center gap-2 px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-2xl font-semibold text-sm transition-colors" style="text-decoration:none">
                        <i class="fas fa-store"></i> Découvrir la marketplace
                    </a>
                </div>
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
                                <div class="flex items-center flex-wrap gap-2 mb-1">
                                    <span class="font-bold text-slate-900 font-mono text-sm">{{ $commande->numero }}</span>
                                    <span class="statut-badge {{ $badgeClass }}">
                                        {{ $commande->statutLabel() }}
                                    </span>
                                    @if($commande->statut_paiement)
                                        @php
                                            $payBadgeMap = [
                                                'PAYE'       => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                                'EN_ATTENTE' => 'bg-amber-100 text-amber-800 border-amber-200',
                                                'ECHOUE'     => 'bg-rose-100 text-rose-800 border-rose-200',
                                                'REMBOURSE'  => 'bg-purple-100 text-purple-800 border-purple-200',
                                            ];
                                            $payBadgeClass = $payBadgeMap[$commande->statut_paiement] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                        @endphp
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $payBadgeClass }}">
                                            <i class="fas {{ $commande->statut_paiement === 'PAYE' ? 'fa-check' : ($commande->statut_paiement === 'REMBOURSE' ? 'fa-rotate-left' : ($commande->statut_paiement === 'ECHOUE' ? 'fa-circle-xmark' : 'fa-clock')) }}"></i>
                                            <span>{{ $commande->statutPaiementLabel() }}</span>
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500">
                                    <i class="fas fa-calendar-day mr-1"></i>
                                    {{ $commande->date_commande->format('d/m/Y à H:i') }}
                                    @if(auth()->user()->isAdmin())
                                        · <i class="fas fa-user mr-1"></i> {{ $commande->user->full_name ?? $commande->user->name }}
                                    @endif
                                </p>
                                <p class="text-xs text-slate-400 mt-1">
                                    {{ $commande->lignes->count() }} article(s) · {{ \App\Models\Commande::MODES_PAIEMENT[$commande->mode_paiement] ?? $commande->mode_paiement }}
                                </p>
                            </div>
                            {{-- Right --}}
                            <div class="flex flex-col items-end gap-2">
                                <div class="text-lg font-extrabold text-teal-700">{{ number_format($commande->montant_total, 2) }} DT</div>
                                <div class="flex items-center flex-wrap justify-end gap-2">
                                    {{-- Stripe Pay button if pending payment --}}
                                    @if($commande->enAttenteDeStripe() && $commande->user_id === auth()->id() && ! $commande->estPaye())
                                        <form method="POST" action="{{ route('paiement.checkout') }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="commande_id" value="{{ $commande->id }}">
                                            <button type="submit"
                                                    class="text-xs px-3 py-1.5 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white rounded-lg font-bold transition-all shadow-sm flex items-center gap-1.5">
                                                <i class="fab fa-stripe text-sm"></i>
                                                <span>Payer</span>
                                            </button>
                                        </form>
                                    @endif

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

                                    {{-- Delete / Cancel button --}}
                                    @can('delete', $commande)
                                        @if($commande->estAnnulable())
                                            <form action="{{ route('commandes.destroy', $commande) }}" method="POST" class="inline"
                                                  data-confirm="Êtes-vous sûr de vouloir annuler la commande {{ $commande->numero }} ? {{ $commande->estPaye() ? 'Le montant de ' . number_format($commande->montant_total, 2) . ' DT sera remboursé via Stripe et les articles remis en stock.' : 'La commande sera annulée et les articles remis en stock.' }}"
                                                  data-confirm-title="Annuler la commande"
                                                  data-confirm-button="Oui, annuler">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="text-xs px-3 py-1.5 border border-red-200 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg font-semibold transition-colors flex items-center gap-1"
                                                        title="Annuler cette commande">
                                                    <i class="fas fa-ban"></i>
                                                    <span>Annuler</span>
                                                </button>
                                            </form>
                                        @elseif($commande->statut === \App\Models\Commande::STATUT_ANNULEE || auth()->user()->isAdmin())
                                            <form action="{{ route('commandes.destroy', $commande) }}" method="POST" class="inline"
                                                  data-confirm="Êtes-vous sûr de vouloir supprimer définitivement la commande {{ $commande->numero }} de votre historique ?"
                                                  data-confirm-title="Supprimer la commande"
                                                  data-confirm-button="Oui, supprimer">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="force" value="1">
                                                <button type="submit"
                                                        class="text-xs px-3 py-1.5 border border-red-200 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg font-semibold transition-colors flex items-center gap-1"
                                                        title="Supprimer la commande">
                                                    <i class="fas fa-trash-alt"></i>
                                                    <span>Supprimer</span>
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
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

@section('scripts')
@if(session('remboursement'))
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: "L'argent est remboursé !",
            html: `<div class="text-left text-slate-700 text-sm space-y-2.5 mt-2">
                     <p>Le montant de <strong class="text-emerald-600 text-base">{{ is_array(session('remboursement')) ? session('remboursement')['montant'] : '' }} DT</strong> a été remboursé avec succès via Stripe sur votre compte bancaire.</p>
                     <div class="p-3 bg-purple-50 rounded-2xl border border-purple-200 text-xs text-purple-900">
                       <i class="fas fa-box text-purple-600 mr-1"></i>
                       <strong>Information Stock :</strong> Le stock du produit a été augmenté car le produit est rendu.
                     </div>
                   </div>`,
            confirmButtonText: 'D\'accord, merci !',
            confirmButtonColor: '#0d9488',
            customClass: {
                popup: 'rounded-3xl p-6 shadow-2xl',
                confirmButton: 'px-6 py-2.5 rounded-xl font-bold text-white'
            }
        });
    }
});
</script>
@endif
@endsection
