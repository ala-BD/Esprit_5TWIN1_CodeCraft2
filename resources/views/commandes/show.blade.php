@extends('layouts.app-auth')

@section('title', 'Commande ' . $commande->numero . ' — RETISS')

@section('styles')
<style>
.tracking-page {
    min-height: 100vh;
    background: linear-gradient(160deg, #f0fdf9 0%, #f8fafc 50%, #f0f4ff 100%);
    padding-top: 72px;
}
.hero-tracking {
    background: linear-gradient(135deg, #111a30 0%, #1a2744 35%, #1B4332 70%, #0d9488 100%);
    position: relative; overflow: hidden; padding: 2.5rem 0 5.5rem;
}
.hero-tracking::before {
    content: ''; position: absolute; inset: 0;
    background-image: radial-gradient(ellipse at 75% 50%, rgba(13,148,136,0.25) 0%, transparent 60%);
    pointer-events: none;
}
.hero-grid {
    position: absolute; inset: 0;
    background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
    background-size: 40px 40px; pointer-events: none;
}
.content-wrap { margin-top: -4rem; position: relative; z-index: 10; }

/* Status timeline */
.timeline { position: relative; }
.timeline::before {
    content: ''; position: absolute; left: 16px; top: 0; bottom: 0;
    width: 2px; background: #e2e8f0;
}
.timeline-item { position: relative; padding-left: 44px; margin-bottom: 1.5rem; }
.timeline-dot {
    position: absolute; left: 8px; top: 3px;
    width: 18px; height: 18px;
    border-radius: 9999px;
    display: flex; align-items: center; justify-content: center;
    font-size: 9px; z-index: 1;
}
.timeline-dot.done { background: #0d9488; color: white; }
.timeline-dot.current { background: #1a2744; color: white; box-shadow: 0 0 0 4px rgba(26,39,68,0.15); }
.timeline-dot.pending { background: #e2e8f0; color: #94a3b8; }
</style>
@endsection

@section('content')
<div class="tracking-page">

    {{-- HERO --}}
    <div class="hero-tracking">
        <div class="hero-grid"></div>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex items-center gap-2 mb-3">
                <a href="{{ route('commandes.index') }}" class="text-white/50 hover:text-white/80 text-sm" style="text-decoration:none">
                    <i class="fas fa-box"></i> Mes commandes
                </a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <span class="text-white/80 text-sm font-mono font-bold">{{ $commande->numero }}</span>
            </div>

            <div class="flex items-start justify-between">
                <div>
                    <h1 class="font-display text-3xl font-extrabold text-white mb-1">
                        Suivi de commande
                    </h1>
                    <p class="text-white/60 text-sm">Commandé le {{ $commande->date_commande->format('d/m/Y à H:i') }}</p>
                </div>
                {{-- Status badge --}}
                @php
                    $color = $commande->statutColor();
                    $colorMap = [
                        'amber' => 'bg-amber-400/20 text-amber-200 border-amber-400/30',
                        'blue'  => 'bg-blue-400/20 text-blue-200 border-blue-400/30',
                        'indigo'=> 'bg-indigo-400/20 text-indigo-200 border-indigo-400/30',
                        'teal'  => 'bg-teal-400/20 text-teal-200 border-teal-400/30',
                        'green' => 'bg-green-400/20 text-green-200 border-green-400/30',
                        'red'   => 'bg-red-400/20 text-red-200 border-red-400/30',
                    ];
                    $badgeClass = $colorMap[$color] ?? 'bg-slate-400/20 text-slate-200 border-slate-400/30';
                @endphp
                <div class="hidden sm:flex items-center px-4 py-2 rounded-full border {{ $badgeClass }} text-sm font-bold">
                    {{ $commande->statutLabel() }}
                </div>
            </div>
        </div>
    </div>

    {{-- MAIN CONTENT --}}
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 content-wrap pb-16">

        {{-- FLASH --}}
        @if(session('success'))
            <div class="mb-5 flex items-start gap-3 bg-teal-50 border border-teal-200 rounded-2xl p-4 text-teal-800">
                <i class="fas fa-circle-check mt-0.5 text-teal-600"></i>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- LEFT COLUMN --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- STATUS TIMELINE --}}
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                    <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-5 flex items-center gap-2">
                        <i class="fas fa-route text-teal-600"></i> Suivi de livraison
                    </h2>

                    @php
                        $steps = [
                            ['key' => 'EN_ATTENTE',     'label' => 'En attente',     'icon' => 'fa-clock'],
                            ['key' => 'CONFIRMEE',      'label' => 'Confirmée',      'icon' => 'fa-circle-check'],
                            ['key' => 'EN_PREPARATION', 'label' => 'En préparation', 'icon' => 'fa-box'],
                            ['key' => 'EXPEDIEE',       'label' => 'Expédiée',       'icon' => 'fa-truck'],
                            ['key' => 'LIVREE',         'label' => 'Livrée',         'icon' => 'fa-house-chimney-window'],
                        ];
                        $statutOrder  = ['EN_ATTENTE' => 0, 'CONFIRMEE' => 1, 'EN_PREPARATION' => 2, 'EXPEDIEE' => 3, 'LIVREE' => 4, 'ANNULEE' => 99];
                        $currentOrder = $statutOrder[$commande->statut] ?? 0;

                        // build map of historique by statut
                        $histMap = [];
                        foreach ($commande->historique_statuts ?? [] as $h) {
                            $histMap[$h['statut']] = $h;
                        }
                    @endphp

                    @if($commande->statut === 'ANNULEE')
                        <div class="flex items-center gap-3 bg-red-50 border border-red-200 rounded-2xl p-4 text-red-700">
                            <i class="fas fa-ban text-xl"></i>
                            <div>
                                <div class="font-bold">Commande annulée</div>
                                <div class="text-xs text-red-500">Le stock des articles a été restauré automatiquement.</div>
                            </div>
                        </div>
                    @else
                        <div class="timeline">
                            @foreach($steps as $step)
                                @php
                                    $stepOrder = $statutOrder[$step['key']] ?? 0;
                                    $isDone    = $stepOrder < $currentOrder;
                                    $isCurrent = $stepOrder === $currentOrder;
                                    $dotClass  = $isDone ? 'done' : ($isCurrent ? 'current' : 'pending');
                                    $hist      = $histMap[$step['key']] ?? null;
                                @endphp
                                <div class="timeline-item">
                                    <div class="timeline-dot {{ $dotClass }}">
                                        <i class="fas {{ $step['icon'] }}"></i>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-bold {{ $isDone || $isCurrent ? 'text-slate-800' : 'text-slate-400' }}">
                                                {{ $step['label'] }}
                                            </p>
                                            @if($hist)
                                                <p class="text-xs text-slate-500 mt-0.5">
                                                    {{ \Carbon\Carbon::parse($hist['date'])->format('d/m/Y à H:i') }}
                                                    @if($hist['commentaire'])
                                                        · {{ $hist['commentaire'] }}
                                                    @endif
                                                </p>
                                            @elseif($isCurrent)
                                                <p class="text-xs text-teal-600 font-medium mt-0.5">Étape actuelle</p>
                                            @endif
                                        </div>
                                        @if($isDone)
                                            <i class="fas fa-check text-teal-500 text-xs"></i>
                                        @elseif($isCurrent)
                                            <span class="w-2 h-2 rounded-full bg-navy-700 animate-pulse"></span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Estimated delivery --}}
                    @if($commande->date_livraison_estimee && !in_array($commande->statut, ['LIVREE', 'ANNULEE']))
                        <div class="mt-5 pt-4 border-t border-slate-100 flex items-center gap-3 text-sm text-slate-700">
                            <i class="fas fa-calendar-check text-teal-600"></i>
                            <span>Livraison estimée le <strong>{{ $commande->date_livraison_estimee->format('d/m/Y') }}</strong></span>
                        </div>
                    @endif
                </div>

                {{-- ARTICLES IN ORDER --}}
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
                    <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-5 flex items-center gap-2">
                        <i class="fas fa-shopping-bag text-teal-600"></i>
                        Articles commandés ({{ $commande->lignes->sum('quantite') }})
                    </h2>
                    <div class="space-y-4">
                        @foreach($commande->lignes as $ligne)
                            @php $article = $ligne->article; @endphp
                            <div class="flex items-center gap-4 p-3 rounded-2xl bg-slate-50 border border-slate-100">
                                {{-- Image --}}
                                @if($article && $article->getFirstImageUrl())
                                    <img src="{{ $article->getFirstImageUrl() }}"
                                         alt="{{ $article->titre }}"
                                         class="w-16 h-16 object-cover rounded-xl flex-shrink-0 shadow-sm">
                                @else
                                    <div class="w-16 h-16 bg-gradient-to-br from-teal-100 to-teal-200 rounded-xl flex items-center justify-center text-teal-500 text-xl flex-shrink-0">
                                        <i class="fas fa-tshirt"></i>
                                    </div>
                                @endif
                                {{-- Info --}}
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-slate-800 text-sm truncate">{{ $article->titre ?? 'Article supprimé' }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        Vendu par {{ $article->user->full_name ?? 'N/A' }}
                                        @if($article->categorie) · {{ $article->categorie }} @endif
                                    </p>
                                    @if($ligne->remise > 0)
                                        <p class="text-xs text-teal-600 font-semibold mt-0.5">
                                            <i class="fas fa-tag"></i> Remise 10% appliquée
                                        </p>
                                    @endif
                                </div>
                                {{-- Price --}}
                                <div class="text-right flex-shrink-0">
                                    <p class="text-sm font-bold text-slate-800">{{ number_format($ligne->total_ligne, 2) }} DT</p>
                                    <p class="text-xs text-slate-400">
                                        {{ $ligne->quantite }} × {{ number_format($ligne->prix_unitaire - $ligne->remise, 2) }} DT
                                    </p>
                                    @if($ligne->remise > 0)
                                        <p class="text-xs text-slate-400 line-through">{{ number_format($ligne->prix_unitaire, 2) }} DT</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- ECOLOGICAL IMPACT --}}
                @php $impact = $commande->impactEcologique(); @endphp
                <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-3xl border border-emerald-200 p-6">
                    <h2 class="text-sm font-bold text-emerald-800 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <i class="fas fa-leaf text-emerald-600"></i> Impact écologique positif
                    </h2>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-extrabold text-emerald-700">{{ $impact['co2_kg'] }} kg</div>
                            <div class="text-xs text-emerald-600 mt-1">CO₂ évité</div>
                        </div>
                        <div>
                            <div class="text-2xl font-extrabold text-emerald-700">{{ number_format($impact['eau_litres']) }} L</div>
                            <div class="text-xs text-emerald-600 mt-1">Eau économisée</div>
                        </div>
                        <div>
                            <div class="text-2xl font-extrabold text-emerald-700">{{ $impact['nb_articles'] }}</div>
                            <div class="text-xs text-emerald-600 mt-1">Pièce(s) sauvées</div>
                        </div>
                    </div>
                    <p class="text-xs text-emerald-600/80 mt-3 text-center">
        En achetant d'occasion, vous avez contribué à l'économie circulaire textile. Merci !
                    </p>
                </div>

            </div>

            {{-- RIGHT COLUMN --}}
            <div class="space-y-5">

                {{-- COST BREAKDOWN --}}
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4">Récapitulatif</h3>
                    <div class="space-y-2.5 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Sous-total</span>
                            <span>{{ number_format($commande->montant_sous_total, 2) }} DT</span>
                        </div>
                        @if($commande->remise > 0)
                            <div class="flex justify-between text-teal-600 font-semibold">
                                <span><i class="fas fa-tag mr-1 text-xs"></i> Remise (10%)</span>
                                <span>− {{ number_format($commande->remise, 2) }} DT</span>
                            </div>
                        @endif
                        <div class="flex justify-between text-slate-600">
                            <span>Frais de livraison</span>
                            <span>{{ number_format($commande->frais_livraison, 2) }} DT</span>
                        </div>
                        <div class="border-t border-slate-100 pt-2.5 flex justify-between font-extrabold text-slate-900 text-base">
                            <span>Total</span>
                            <span>{{ number_format($commande->montant_total, 2) }} DT</span>
                        </div>
                    </div>
                </div>

                {{-- DELIVERY ADDRESS --}}
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i class="fas fa-location-dot text-teal-600"></i> Adresse de livraison
                    </h3>
                    <p class="text-sm text-slate-700">
                        {{ $commande->adresse_snapshot ?? ($commande->adresse?->formatted_address ?? 'Adresse non renseignée') }}
                    </p>
                </div>

                {{-- PAYMENT --}}
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i class="fas fa-credit-card text-teal-600"></i> Mode de paiement
                    </h3>
                    <p class="text-sm text-slate-700">
                        {{ \App\Models\Commande::MODES_PAIEMENT[$commande->mode_paiement] ?? $commande->mode_paiement }}
                    </p>
                </div>

                {{-- ACTIONS --}}
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 space-y-3">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Actions</h3>

                    {{-- PDF Invoice --}}
                    <a href="{{ route('commandes.invoice', $commande) }}"
                       class="flex items-center gap-3 w-full px-4 py-3 bg-slate-800 hover:bg-slate-900 text-white rounded-2xl text-sm font-semibold transition-colors"
                       style="text-decoration:none">
                        <i class="fas fa-file-pdf"></i>
                        <span>Télécharger la facture PDF</span>
                    </a>

                    {{-- QR Code --}}
                    <div class="text-center pt-2">
                        <p class="text-xs text-slate-500 mb-2">QR Code de suivi</p>
                        <img src="{{ route('commandes.qrcode', $commande) }}" alt="QR Code" class="w-24 h-24 mx-auto rounded-xl border border-slate-200">
                    </div>

                    {{-- Edit (if modifiable) --}}
                    @can('update', $commande)
                        @if($commande->estModifiable())
                            <a href="{{ route('commandes.edit', $commande) }}"
                               class="flex items-center gap-3 w-full px-4 py-3 border border-teal-300 hover:bg-teal-50 text-teal-700 rounded-2xl text-sm font-semibold transition-colors"
                               style="text-decoration:none">
                                <i class="fas fa-pen"></i>
                                <span>Modifier la commande</span>
                            </a>
                        @endif
                    @endcan

                    {{-- Cancel --}}
                    @can('delete', $commande)
                        @if($commande->estAnnulable())
                            <form action="{{ route('commandes.destroy', $commande) }}" method="POST"
                                  onsubmit="return confirm('Confirmer l\'annulation de la commande {{ $commande->numero }} ?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="flex items-center gap-3 w-full px-4 py-3 border border-red-200 hover:bg-red-50 text-red-600 rounded-2xl text-sm font-semibold transition-colors">
                                    <i class="fas fa-ban"></i>
                                    <span>Annuler la commande</span>
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>

                {{-- ADMIN: Status update --}}
                @can('updateStatut', $commande)
                    <div class="bg-gradient-to-br from-navy-50 to-slate-50 rounded-3xl border border-slate-300 shadow-sm p-5">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <i class="fas fa-shield-halved text-navy-700"></i> Admin — Avancer le statut
                        </h3>
                        <form action="{{ route('commandes.statut', $commande) }}" method="POST" class="space-y-3">
                            @csrf @method('PATCH')
                            <select name="statut" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white outline-none focus:border-teal-500">
                                @foreach(\App\Models\Commande::STATUTS as $s)
                                    <option value="{{ $s }}" {{ $commande->statut === $s ? 'selected' : '' }}>{{ str_replace('_', ' ', $s) }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="commentaire" placeholder="Commentaire (optionnel)"
                                   class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-teal-500">
                            <button type="submit" class="w-full px-4 py-2.5 bg-navy-800 hover:bg-navy-900 text-white rounded-xl text-sm font-bold transition-colors"
                                    style="background:#1a2744">
                                Mettre à jour le statut
                            </button>
                        </form>
                    </div>
                @endcan

            </div>
        </div>
    </div>
</div>
@endsection
