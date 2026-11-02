@extends('admin.layouts.admin')

@section('title', 'Répartition automatique')

@section('breadcrumb')
    <span class="hidden sm:inline text-slate-300">/</span>
    <a href="{{ route('admin.logistique.index') }}" class="hover:text-slate-900">Logistique</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-900 font-medium">Répartition automatique</span>
@endsection

@section('admin-content')

@php
    $p = $proposition;
    $palette = ['#0d9488', '#7c3aed', '#ea580c', '#2563eb', '#db2777', '#65a30d', '#0891b2', '#a16207'];
    $carte = [
        'depot' => ['lat' => config('logistique.depot.latitude'), 'lng' => config('logistique.depot.longitude'), 'nom' => config('logistique.depot.nom')],
        'plans' => $p['plans']->map(fn ($plan, $i) => [
            'couleur'    => $palette[$i % count($palette)],
            'collecteur' => $plan['collecteur']->full_name,
            'existants'  => $plan['tournee']
                ? $plan['tournee']->missions()->whereIn('statut', ['A_FAIRE', 'EN_COURS'])->get()->filter->estLocalisee()
                    ->map(fn ($m) => ['lat' => $m->latitude, 'lng' => $m->longitude, 'adresse' => $m->adresse])->values()
                : [],
            'ajouts'     => $plan['ajouts']->map(fn ($a) => [
                'lat' => $a['point']['latitude'], 'lng' => $a['point']['longitude'],
                'libelle' => $a['libelle'], 'adresse' => $a['adresse'], 'type' => $a['type'],
            ])->values(),
        ])->values(),
    ];
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-xl font-semibold text-slate-900 tracking-tight">Répartition automatique</h1>
        <p class="text-slate-500 mt-0.5 num">
            @if($p['total'] > 0)
                {{ $p['total'] }} travau{{ $p['total'] > 1 ? 'x' : '' }} réparti{{ $p['total'] > 1 ? 's' : '' }} entre {{ $p['plans']->count() }} collecteur{{ $p['plans']->count() > 1 ? 's' : '' }}
                pour le {{ $p['date']->locale('fr')->isoFormat('dddd D MMMM') }}, soit {{ number_format($p['km_ajoutes'], 1, ',', ' ') }} km de trajet en plus.
            @else
                Rien à répartir pour le {{ $p['date']->locale('fr')->isoFormat('dddd D MMMM') }}.
            @endif
        </p>
    </div>
    <div class="flex items-center gap-2">
        <form method="GET">
            <input type="date" name="date" value="{{ $p['date']->format('Y-m-d') }}" min="{{ today()->format('Y-m-d') }}" aria-label="Date"
                   class="a-input num w-40" style="height: 32px;" onchange="this.form.submit()">
        </form>
        @if($p['total'] > 0)
            <form method="POST" action="{{ route('admin.logistique.affectation.appliquer') }}">
                @csrf
                <input type="hidden" name="date" value="{{ $p['date']->format('Y-m-d') }}">
                <input type="hidden" name="empreinte" value="{{ $p['empreinte'] }}">
                <button type="submit" class="a-btn a-btn-primary"><i class="fas fa-check"></i> Appliquer la répartition</button>
            </form>
        @endif
    </div>
</div>

<div class="a-card px-4 py-3 mb-4 flex items-start gap-3">
    <span class="f-ia"><i class="fas fa-wand-magic-sparkles"></i></span>
    <p class="text-slate-700 leading-relaxed">
        Chaque travail est confié au collecteur dont le trajet s'allonge le moins en l'ajoutant, en tenant compte
        de sa charge (au plus {{ config('logistique.affectation.arrets_max') }} arrêts par tournée) et en préférant
        compléter une tournée existante plutôt qu'en ouvrir une nouvelle. Après application, l'ordre de passage de chaque
        tournée est optimisé et les collecteurs sont prévenus.
    </p>
</div>

@if($p['non_affectes']->isNotEmpty())
    <div class="flex items-start gap-2.5 mb-4 px-3 py-2.5 rounded-md border border-amber-200 bg-amber-50 text-amber-900">
        <i class="fas fa-triangle-exclamation text-amber-600 mt-0.5"></i>
        <div>
            <p class="font-medium">{{ $p['non_affectes']->count() }} travail{{ $p['non_affectes']->count() > 1 ? 'x' : '' }} non réparti{{ $p['non_affectes']->count() > 1 ? 's' : '' }}</p>
            <ul class="mt-1 space-y-0.5">
                @foreach($p['non_affectes'] as $n)
                    <li>{{ $n['libelle'] }} — {{ $n['personne'] }} : {{ $n['raison'] }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if($p['total'] > 0)
<div class="grid grid-cols-1 xl:grid-cols-5 gap-4">
    <div class="a-card xl:col-span-3 overflow-hidden self-start">
        <div id="carte-affectation" class="cr-carte" style="height: 520px;"></div>
        <p class="px-4 py-2 border-t border-slate-200 text-xs text-slate-600">
            Grands points : travaux ajoutés, dans la couleur du collecteur. Petits points gris : arrêts déjà prévus dans sa tournée.
        </p>
    </div>

    <div class="xl:col-span-2 space-y-4">
        @foreach($p['plans'] as $i => $plan)
            <div class="a-card">
                <div class="flex items-start gap-3 px-4 py-3 border-b border-slate-200">
                    <span class="f-pastille" style="background: {{ $palette[$i % count($palette)] }}"></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-slate-900">{{ $plan['collecteur']->full_name }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ $plan['nouvelle'] ? 'Nouvelle tournée' : 'Complète la tournée' }} « {{ $plan['zone'] }} »
                            · {{ $plan['arrets'] }} arrêt{{ $plan['arrets'] > 1 ? 's' : '' }} au total
                        </p>
                    </div>
                    <span class="num text-sm text-slate-700 whitespace-nowrap">+{{ number_format($plan['km_ajoutes'], 1, ',', ' ') }} km</span>
                </div>
                <ul class="divide-y divide-slate-100">
                    @foreach($plan['ajouts'] as $ajout)
                        <li class="flex items-start gap-3 px-4 py-2.5">
                            <i class="fas {{ $ajout['type'] === 'COLLECTE' ? 'fa-box-open' : 'fa-truck' }} text-slate-400 text-xs mt-1.5"></i>
                            <span class="min-w-0 flex-1">
                                <span class="block text-slate-900">{{ $ajout['libelle'] }}</span>
                                <span class="block text-xs text-slate-500 truncate">{{ $ajout['personne'] }} — {{ $ajout['adresse'] }}</span>
                            </span>
                            <span class="num text-xs text-slate-500 whitespace-nowrap mt-0.5">+{{ number_format($ajout['surcout'], 1, ',', ' ') }} km</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</div>
@endif

@endsection

@section('styles')
@include('logistique.partials.carte', ['partie' => 'styles'])
<style>
    .f-ia { flex-shrink: 0; width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
            background: #e6f4f2; color: var(--teal-dark); font-size: 12px; }
    .f-pastille { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; margin-top: 4px; }
</style>
@endsection

@section('scripts')
@include('logistique.partials.carte', ['partie' => 'scripts'])
<script>
    (function () {
        const donnees = @json($carte);
        const element = document.getElementById('carte-affectation');
        if (!window.CarteRetiss || !element) return;

        const carte = CarteRetiss.creer('carte-affectation');
        CarteRetiss.depot(carte, donnees.depot);
        const limites = [[donnees.depot.lat, donnees.depot.lng]];
        const echapper = CarteRetiss.echapper;

        donnees.plans.forEach(function (plan) {
            plan.existants.forEach(function (a) {
                L.circleMarker([a.lat, a.lng], { radius: 5, color: '#fff', weight: 1.5, fillColor: '#94a3b8', fillOpacity: 1 })
                    .bindTooltip(echapper(plan.collecteur) + ' — déjà prévu : ' + echapper(a.adresse)).addTo(carte);
                limites.push([a.lat, a.lng]);
            });
            plan.ajouts.forEach(function (a) {
                const icone = a.type === 'COLLECTE' ? 'fa-box-open' : 'fa-truck';
                L.marker([a.lat, a.lng], { icon: L.divIcon({ className: '', iconSize: [26, 26], iconAnchor: [13, 13],
                    html: '<div class="cr-marqueur" style="background:' + plan.couleur + '"><i class="fas ' + icone + '" style="font-size:10px"></i></div>' }) })
                    .bindTooltip('<strong>' + echapper(plan.collecteur) + '</strong> — ' + echapper(a.libelle) + '<br>' + echapper(a.adresse)).addTo(carte);
                limites.push([a.lat, a.lng]);
            });
        });

        carte.fitBounds(limites, { padding: [40, 40] });
    })();
</script>
@endsection
