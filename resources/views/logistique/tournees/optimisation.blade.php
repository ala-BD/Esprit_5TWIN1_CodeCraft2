@extends('logistique.layouts.logistique')

@section('title', 'Itinéraire optimisé')

@section('breadcrumb')
    <span class="hidden sm:inline text-slate-300">/</span>
    <a href="{{ route('logistique.tournees.index') }}" class="hover:text-slate-900">Tournées</a>
    <span class="text-slate-300">/</span>
    <a href="{{ route('logistique.tournees.show', $tournee) }}" class="hover:text-slate-900 truncate num">{{ $tournee->date->format('d/m/Y') }}</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-900 font-medium">Itinéraire</span>
@endsection

@section('logistique-content')

@php
    $p = $proposition;
    $finTournee = $p['etapes']->isNotEmpty() ? $p['etapes']->last()['fin'] : $p['heure_depart'];
    $gainNotable = $p['gain_km'] > 0.05;

    // Données de la carte
    $point = fn ($m) => ['lat' => $m->latitude, 'lng' => $m->longitude];
    $carte = [
        'depart'   => ['lat' => $p['depart']['latitude'], 'lng' => $p['depart']['longitude'], 'nom' => $p['depart']['nom']],
        'retour'   => $p['retour'] ? ['lat' => $p['retour']['latitude'], 'lng' => $p['retour']['longitude'], 'nom' => $p['retour']['nom']] : null,
        'faits'    => $p['figees']->filter->estLocalisee()->map(fn ($m) => $point($m) + ['adresse' => $m->adresse])->values(),
        'avant'    => $p['etapes']->pluck('mission')->sortBy('ordre')->map($point)->values(),
        'apres'    => $p['etapes']->map(fn ($e, $i) => $point($e['mission']) + [
            'n' => $p['figees']->count() + $i + 1,
            'adresse' => $e['mission']->adresse,
            'heure' => $e['arrivee']->format('H:i'),
            'type' => $e['mission']->type,
        ])->values(),
    ];
@endphp

{{-- Header --}}
<div class="flex flex-wrap items-center justify-between gap-4 mb-4">
    <div>
        <h1 class="text-xl font-semibold text-slate-900 tracking-tight">Itinéraire optimisé</h1>
        <p class="text-slate-500 mt-0.5 num">{{ $tournee->zone }} · {{ $tournee->date->format('d/m/Y') }} · {{ $p['etapes']->count() }} arrêt{{ $p['etapes']->count() > 1 ? 's' : '' }} à réordonner</p>
    </div>
    <form method="POST" action="{{ route('logistique.tournees.optimisation.appliquer', $tournee) }}" class="flex items-center gap-2">
        @csrf
        <input type="hidden" name="empreinte" value="{{ $p['empreinte'] }}">
        <a href="{{ route('logistique.tournees.show', $tournee) }}" class="a-btn">Annuler</a>
        <button type="submit" class="a-btn a-btn-primary">
            <i class="fas fa-check"></i> Appliquer cet itinéraire
        </button>
    </form>
</div>

{{-- Chiffres --}}
<dl class="a-card grid grid-cols-2 lg:grid-cols-4 mb-4">
    <div class="o-tile">
        <dt>Ordre actuel</dt>
        <dd class="num text-slate-500">{{ number_format($p['distance_avant'], 1, ',', ' ') }} km</dd>
    </div>
    <div class="o-tile">
        <dt>Ordre optimisé</dt>
        <dd class="num">{{ number_format($p['distance_apres'], 1, ',', ' ') }} km</dd>
        <p class="num {{ $gainNotable ? 'text-emerald-700' : '' }}">
            {{ $gainNotable ? '−' . number_format($p['gain_km'], 1, ',', ' ') . ' km (' . $p['gain_pct'] . ' %)' : "déjà l'ordre le plus court" }}
        </p>
    </div>
    <div class="o-tile">
        <dt>Départ</dt>
        <dd class="num">{{ $p['heure_depart']->format('H:i') }}</dd>
        <p class="truncate" title="{{ $p['depart']['nom'] }}">{{ $p['depart']['nom'] }}</p>
    </div>
    <div class="o-tile">
        <dt>Dernier arrêt terminé vers</dt>
        <dd class="num">{{ $finTournee->format('H:i') }}</dd>
        <p class="num">{{ intdiv($p['duree_minutes'], 60) }} h {{ str_pad($p['duree_minutes'] % 60, 2, '0', STR_PAD_LEFT) }} sur la route et aux arrêts</p>
    </div>
</dl>

{{-- Explication --}}
<div class="a-card px-4 py-3 mb-4 flex items-start gap-3">
    <span class="o-ia"><i class="fas fa-wand-magic-sparkles"></i></span>
    <div>
        <p class="text-xs font-medium text-slate-500">Analyse de l'itinéraire</p>
        <p class="text-slate-800 mt-0.5 leading-relaxed">{{ $explication }}</p>
    </div>
</div>

@if($p['non_localisees']->isNotEmpty())
    <div class="flex items-start gap-2.5 mb-4 px-3 py-2.5 rounded-md border border-amber-200 bg-amber-50 text-amber-900">
        <i class="fas fa-triangle-exclamation text-amber-600 mt-0.5"></i>
        <p>
            {{ $p['non_localisees']->count() }} arrêt{{ $p['non_localisees']->count() > 1 ? 's' : '' }} sans position sur la carte
            ({{ $p['non_localisees']->pluck('adresse')->join(' ; ') }}) : placé{{ $p['non_localisees']->count() > 1 ? 's' : '' }} en fin de tournée, heure inchangée.
        </p>
    </div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-5 gap-4">

    {{-- Carte --}}
    <div class="a-card xl:col-span-3 overflow-hidden">
        <div id="carte-itineraire" class="o-carte" role="img"
             aria-label="Carte de la tournée : ordre actuel en pointillés gris, ordre optimisé en trait plein"></div>
        <div class="flex flex-wrap items-center gap-x-5 gap-y-1 px-4 py-2.5 border-t border-slate-200 text-xs text-slate-600">
            <span class="flex items-center gap-2"><span class="o-legende" style="border-top: 3px solid var(--teal)"></span> Ordre optimisé</span>
            <span class="flex items-center gap-2"><span class="o-legende" style="border-top: 2px dashed #475569"></span> Ordre actuel</span>
            <span class="flex items-center gap-2"><span class="o-pastille" style="background: #1a2030"></span> Dépôt</span>
            <span class="flex items-center gap-2"><span class="o-pastille" style="background: #cbd5e1"></span> Arrêt déjà fait</span>
        </div>
    </div>

    {{-- Nouvel ordre --}}
    <div class="a-card xl:col-span-2 self-start">
        <h2 class="font-semibold text-slate-900 px-4 py-3 border-b border-slate-200">Nouvel ordre de passage</h2>
        <ol class="divide-y divide-slate-100">
            <li class="o-etape text-slate-500">
                <span class="o-num o-num-depot"><i class="fas fa-warehouse text-[9px]"></i></span>
                <span class="min-w-0 flex-1 truncate">{{ $p['depart']['nom'] }}</span>
                <span class="num">{{ $p['heure_depart']->format('H:i') }}</span>
            </li>
            @foreach($p['etapes'] as $i => $etape)
                @php $mission = $etape['mission']; $avant = $mission->ordre; $apres = $p['figees']->count() + $i + 1; @endphp
                <li class="o-etape">
                    <span class="o-num">{{ $apres }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-slate-900 font-medium truncate">{{ $mission->adresse }}</span>
                        <span class="block text-xs text-slate-500 mt-0.5 num">
                            {{ $mission->type_label }} · {{ number_format($etape['km'], 1, ',', ' ') }} km
                            @if($avant !== $apres)
                                · <span class="text-slate-400">était n° {{ $avant }}</span>
                            @endif
                            @if($mission->statut === \App\Models\Mission::STATUT_EN_COURS)
                                · <span class="text-amber-700">en cours</span>
                            @endif
                        </span>
                    </span>
                    <span class="num text-right">
                        <span class="block font-medium text-slate-900">{{ $etape['arrivee']->format('H:i') }}</span>
                        @if($mission->heure !== $etape['arrivee']->format('H:i'))
                            <span class="block text-xs text-slate-400 line-through">{{ $mission->heure }}</span>
                        @endif
                    </span>
                </li>
            @endforeach
            @if($p['retour'])
                <li class="o-etape text-slate-500">
                    <span class="o-num o-num-depot"><i class="fas fa-flag-checkered text-[9px]"></i></span>
                    <span class="min-w-0 flex-1 truncate">Retour : {{ $p['retour']['nom'] }}</span>
                </li>
            @endif
        </ol>
    </div>
</div>

@endsection

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<style>
    .o-tile { padding: 14px 16px; border-color: var(--line); }
    .o-tile + .o-tile { border-left-width: 1px; }
    .o-tile dt { color: var(--muted); }
    .o-tile dd { font-size: 22px; line-height: 1.25; font-weight: 600; color: var(--ink); margin-top: 2px; }
    .o-tile p { color: var(--muted); font-size: 12px; margin-top: 2px; }
    @media (max-width: 1023px) {
        .o-tile:nth-child(odd) { border-left-width: 0; }
        .o-tile:nth-child(n+3) { border-top-width: 1px; }
    }

    .o-ia { flex-shrink: 0; width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
            background: #e6f4f2; color: var(--teal-dark); font-size: 12px; }

    .o-carte { height: 460px; background: #eef1f5; }
    /* Fond de carte adouci : les tracés et les numéros ressortent mieux */
    .o-carte .leaflet-tile-pane { filter: saturate(.2) brightness(1.06) contrast(.9); }
    .o-legende { display: inline-block; width: 22px; }
    .o-pastille { display: inline-block; width: 10px; height: 10px; border-radius: 50%; }

    .o-etape { display: flex; align-items: center; gap: 10px; padding: 9px 16px; }
    .o-num { flex-shrink: 0; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
             background: var(--teal); color: #fff; font-size: 11px; font-weight: 600; }
    .o-num-depot { background: #1a2030; }

    /* Marqueurs de la carte */
    .o-marqueur { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
                  font: 600 12px/1 'IBM Plex Sans', sans-serif; color: #fff; background: var(--teal);
                  border: 2px solid #fff; box-shadow: 0 1px 4px rgba(26,32,48,.35); }
    .o-marqueur.depot { background: #1a2030; }
    .o-marqueur.fait  { background: #cbd5e1; color: #475569; width: 18px; height: 18px; font-size: 0; }
</style>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    // ---- Carte de l'itinéraire ----
    (function () {
        const donnees = @json($carte);
        if (!window.L) return;   // bibliothèque indisponible (hors ligne) : la liste suffit

        const carte = L.map('carte-itineraire', { scrollWheelZoom: false });
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(carte);

        const icone = (classe, texte) => L.divIcon({ className: '', html: '<div class="o-marqueur ' + classe + '">' + (texte ?? '') + '</div>', iconSize: [26, 26], iconAnchor: [13, 13] });
        const pt = p => [p.lat, p.lng];
        const echapper = t => String(t).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

        // Ordre actuel (pointillés) puis ordre optimisé (trait plein), depuis le départ jusqu'au retour
        const trace = arrets => [pt(donnees.depart), ...arrets.map(pt), ...(donnees.retour ? [pt(donnees.retour)] : [])];
        L.polyline(trace(donnees.avant), { color: '#475569', weight: 2.5, opacity: .75, dashArray: '7 7' }).addTo(carte);
        const optimise = L.polyline(trace(donnees.apres), { color: '#0d9488', weight: 4, opacity: .9 }).addTo(carte);

        donnees.faits.forEach(p => L.marker(pt(p), { icon: icone('fait') }).bindTooltip('Fait : ' + echapper(p.adresse)).addTo(carte));
        donnees.apres.forEach(p => L.marker(pt(p), { icon: icone('', p.n) })
            .bindTooltip('<strong>' + p.n + '. ' + p.heure + '</strong> — ' + echapper(p.adresse)).addTo(carte));
        L.marker(pt(donnees.depart), { icon: icone('depot', '<i class="fas fa-warehouse" style="font-size:10px"></i>'), zIndexOffset: 1000 })
            .bindTooltip(echapper(donnees.depart.nom)).addTo(carte);

        carte.fitBounds(optimise.getBounds(), { padding: [30, 30] });
    })();
</script>
@endsection
