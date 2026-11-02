@extends('admin.layouts.admin')

@section('title', 'Prévisions des dons')

@section('breadcrumb')
    <span class="hidden sm:inline text-slate-300">/</span>
    <a href="{{ route('admin.logistique.index') }}" class="hover:text-slate-900">Logistique</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-900 font-medium">Prévisions des dons</span>
@endsection

@section('admin-content')

@php
    $p = $prevision;
    $t = $p['total'];
    $prochaine = $t['prevision'][0] ?? null;
    $sur4 = collect($t['prevision'])->take(4)->sum('valeur');
    $graphique = [
        'historique' => array_slice($t['historique'], -26),
        'prevision'  => $t['prevision'],
    ];
@endphp

<div class="mb-4">
    <h1 class="text-xl font-semibold text-slate-900 tracking-tight">Prévisions des dons</h1>
    <p class="text-slate-500 mt-0.5 num">
        Dons attendus pour les {{ $p['horizon'] }} prochaines semaines, d'après {{ $p['semaines_historique'] }} semaines d'historique.
    </p>
</div>

@if(!$prochaine)
    <div class="a-card text-center py-14">
        <i class="fas fa-chart-line text-slate-300 text-2xl"></i>
        <h3 class="font-medium text-slate-900 mt-3">Pas encore assez d'historique</h3>
        <p class="text-slate-500 mt-1">Il faut au moins quatre semaines de dons pour établir une prévision.</p>
    </div>
@else

{{-- Chiffres clés --}}
<dl class="a-card grid grid-cols-2 lg:grid-cols-4 mb-4">
    <div class="p-tile">
        <dt>Cette semaine</dt>
        <dd class="num">{{ round($prochaine['valeur']) }} dons</dd>
        <p class="num">entre {{ round($prochaine['bas']) }} et {{ round($prochaine['haut']) }}</p>
    </div>
    <div class="p-tile">
        <dt>Évolution</dt>
        <dd class="num">{{ $t['tendance_pct'] > 0 ? '+' : '' }}{{ $t['tendance_pct'] }} %</dd>
        <p class="num">par rapport à la moyenne des 4 dernières semaines ({{ $t['moyenne_4'] }})</p>
    </div>
    <div class="p-tile">
        <dt>Collecte à prévoir</dt>
        <dd class="num">{{ $capacite['journees'] }} journée{{ $capacite['journees'] > 1 ? 's' : '' }}</dd>
        <p class="num">
            {{ $capacite['journees_haut'] > $capacite['journees'] ? 'jusqu’à ' . $capacite['journees_haut'] . ' au pire, ' : '' }}sur {{ $capacite['journees_dispo'] }} disponibles
        </p>
    </div>
    <div class="p-tile">
        <dt>Fiabilité du modèle</dt>
        <dd class="num">{{ $t['erreur_pct'] !== null ? '± ' . $t['erreur_pct'] . ' %' : '—' }}</dd>
        <p>{{ $t['erreur_pct'] !== null ? 'erreur mesurée sur les 8 dernières semaines' : 'historique trop court pour la mesurer' }}</p>
    </div>
</dl>

{{-- Briefing --}}
@if($briefing)
    <div class="a-card px-4 py-3 mb-4 flex items-start gap-3">
        <span class="p-ia"><i class="fas fa-wand-magic-sparkles"></i></span>
        <div>
            <p class="text-xs font-medium text-slate-500">Analyse</p>
            <p class="text-slate-800 mt-0.5 leading-relaxed">{{ $briefing }}</p>
        </div>
    </div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-5 gap-4">

    {{-- Graphique --}}
    <figure class="a-card xl:col-span-3 p-5 self-start">
        <figcaption class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
            <span>
                <span class="block font-semibold text-slate-900">Dons par semaine</span>
                <span class="block text-slate-500 mt-0.5 num">26 dernières semaines, puis prévision sur {{ $p['horizon'] }} semaines ({{ round($sur4) }} dons sur le prochain mois)</span>
            </span>
            <span class="flex items-center gap-4 text-xs text-slate-600">
                <span class="flex items-center gap-1.5"><span class="p-cle" style="border-top: 2px solid #0f766e"></span> Réel</span>
                <span class="flex items-center gap-1.5"><span class="p-cle" style="border-top: 2px dashed #0d9488"></span> Prévu</span>
                <span class="flex items-center gap-1.5"><span class="p-cle-bande"></span> Intervalle à 80 %</span>
            </span>
        </figcaption>
        <div id="graphique-prevision" class="relative" role="img"
             aria-label="Dons par semaine : historique puis prévision. Cette semaine {{ round($prochaine['valeur']) }} dons attendus, entre {{ round($prochaine['bas']) }} et {{ round($prochaine['haut']) }}.">
            <svg id="svg-prevision" width="100%" height="280"></svg>
            <div id="bulle-prevision" class="p-bulle" hidden></div>
        </div>
        @unless($p['saisonnalite'])
            <p class="text-xs text-slate-500 mt-2">Moins d'un an d'historique : la saisonnalité n'est pas encore prise en compte.</p>
        @endunless
    </figure>

    {{-- Par gouvernorat --}}
    <div class="a-card xl:col-span-2 self-start">
        <h2 class="font-semibold text-slate-900 px-4 py-3 border-b border-slate-200">Par gouvernorat</h2>
        <div class="overflow-x-auto rounded-b-lg">
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Zone</th>
                        <th class="text-right">Cette semaine</th>
                        <th class="text-right">Évolution</th>
                        <th class="text-right" title="Erreur mesurée sur les 8 dernières semaines">Fiabilité</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($p['zones'] as $zone)
                        @php $fiable = $zone['erreur_pct'] === null ? null : $zone['erreur_pct'] <= 30; @endphp
                        <tr>
                            <td class="font-medium text-slate-900">{{ $zone['zone'] }}</td>
                            <td class="num text-right">{{ round($zone['prevision'][0]['valeur'] ?? 0) }}</td>
                            <td class="num text-right {{ $zone['tendance_pct'] >= 10 ? 'text-emerald-700' : ($zone['tendance_pct'] <= -10 ? 'text-red-700' : 'text-slate-600') }}">
                                @if(abs($zone['tendance_pct']) >= 10)
                                    <i class="fas {{ $zone['tendance_pct'] > 0 ? 'fa-arrow-up' : 'fa-arrow-down' }} text-[10px]"></i>
                                @endif
                                {{ $zone['tendance_pct'] > 0 ? '+' : '' }}{{ $zone['tendance_pct'] }} %
                            </td>
                            <td class="num text-right text-xs">
                                @if($fiable === null)
                                    <span class="text-slate-400">—</span>
                                @else
                                    <span class="{{ $fiable ? 'text-slate-600' : 'text-amber-700' }}">± {{ $zone['erreur_pct'] }} %</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="px-4 py-2.5 border-t border-slate-200 text-xs text-slate-500">
            Les petites zones reçoivent peu de dons par semaine : leur prévision est naturellement moins précise.
        </p>
    </div>
</div>

<details class="a-card mt-4">
    <summary class="px-4 py-3 cursor-pointer font-medium text-slate-900">Comment la prévision est calculée</summary>
    <div class="px-4 pb-4 text-slate-600 leading-relaxed space-y-2">
        <p>Les dons sont comptés par semaine et par gouvernorat (adresse du donateur). La croissance de la plateforme est d'abord retirée,
           puis chaque semaine de l'année reçoit un coefficient saisonnier appris sur l'année écoulée.</p>
        <p>Le niveau et la tendance sont suivis par lissage exponentiel (méthode de Holt), qui donne plus de poids aux dernières semaines.
           L'intervalle à 80 % s'élargit avec l'horizon, d'après les erreurs passées du modèle.</p>
        <p>Pour mesurer la fiabilité, le modèle est rejoué en cachant les 8 dernières semaines, puis comparé aux dons réellement reçus.</p>
    </div>
</details>
@endif

@endsection

@section('styles')
<style>
    .p-tile { padding: 14px 16px; border-color: var(--line); }
    .p-tile + .p-tile { border-left-width: 1px; }
    .p-tile dt { color: var(--muted); }
    .p-tile dd { font-size: 22px; line-height: 1.25; font-weight: 600; color: var(--ink); margin-top: 2px; }
    .p-tile p { color: var(--muted); font-size: 12px; margin-top: 2px; }
    @media (max-width: 1023px) { .p-tile:nth-child(odd) { border-left-width: 0; } .p-tile:nth-child(n+3) { border-top-width: 1px; } }
    .p-ia { flex-shrink: 0; width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
            background: #e6f4f2; color: var(--teal-dark); font-size: 12px; }
    .p-cle { display: inline-block; width: 18px; }
    .p-cle-bande { display: inline-block; width: 14px; height: 10px; background: rgba(13,148,136,.16); border-radius: 2px; }
    .p-bulle { position: absolute; pointer-events: none; padding: 6px 10px; border-radius: 6px; background: var(--navy);
               color: #fff; font-size: 12px; white-space: nowrap; transform: translate(-50%, -100%); margin-top: -10px; }
    #svg-prevision text { font: 11px 'IBM Plex Sans', sans-serif; fill: #64748b; font-variant-numeric: tabular-nums; }
</style>
@endsection

@section('scripts')
<script>
    // ---- Graphique : historique (trait plein), prévision (pointillés) et intervalle à 80 % ----
    (function () {
        const donnees = @json($graphique ?? null);
        const svg = document.getElementById('svg-prevision');
        if (!donnees || !svg) return;

        const NS = 'http://www.w3.org/2000/svg';
        const bulle = document.getElementById('bulle-prevision');
        const points = [
            ...donnees.historique.map(p => ({ semaine: p.semaine, reel: p.valeur })),
            ...donnees.prevision.map(p => ({ semaine: p.semaine, prevu: p.valeur, bas: p.bas, haut: p.haut })),
        ];
        const nbHist = donnees.historique.length;
        const libelle = s => new Date(s + 'T00:00:00').toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
        const el = (nom, attributs, parent) => { const e = document.createElementNS(NS, nom); Object.entries(attributs).forEach(([k, v]) => e.setAttribute(k, v)); (parent || svg).appendChild(e); return e; };

        function dessiner() {
            svg.innerHTML = '';
            const largeur = svg.clientWidth, hauteur = 280, m = { g: 34, d: 8, h: 12, b: 26 };
            const max = Math.max(...points.map(p => Math.max(p.reel ?? 0, p.haut ?? 0, p.prevu ?? 0)));
            const pas = max > 40 ? 10 : 5, plafond = Math.ceil(max / pas) * pas || pas;
            const x = i => m.g + i * (largeur - m.g - m.d) / (points.length - 1);
            const y = v => m.h + (1 - v / plafond) * (hauteur - m.h - m.b);

            // Grille et axe des valeurs (discrets)
            for (let v = 0; v <= plafond; v += pas) {
                el('line', { x1: m.g, x2: largeur - m.d, y1: y(v), y2: y(v), stroke: v === 0 ? '#cfd5df' : '#eceff4' });
                el('text', { x: m.g - 6, y: y(v) + 4, 'text-anchor': 'end' }).textContent = v;
            }
            // Dates : une toutes les quatre semaines
            points.forEach((p, i) => { if (i % 4 === 0) el('text', { x: x(i), y: hauteur - 6, 'text-anchor': 'middle' }).textContent = libelle(p.semaine); });

            // Séparation réel / prévu
            const xSep = (x(nbHist - 1) + x(nbHist)) / 2;
            el('line', { x1: xSep, x2: xSep, y1: m.h, y2: hauteur - m.b, stroke: '#cbd5e1', 'stroke-dasharray': '3 3' });
            el('text', { x: xSep + 6, y: m.h + 10 }).textContent = 'Prévision';

            // Intervalle à 80 %
            const prev = points.slice(nbHist);
            const bande = prev.map((p, k) => x(nbHist + k) + ',' + y(p.haut)).concat(prev.slice().reverse().map((p, k) => x(points.length - 1 - k) + ',' + y(p.bas)));
            el('polygon', { points: bande.join(' '), fill: 'rgba(13,148,136,.16)' });

            // Courbes : le prévu repart du dernier point réel
            const ligne = (pts, attributs) => el('polyline', Object.assign({ points: pts.join(' '), fill: 'none', 'stroke-width': 2, 'stroke-linejoin': 'round', 'stroke-linecap': 'round' }, attributs));
            ligne(points.slice(0, nbHist).map((p, i) => x(i) + ',' + y(p.reel)), { stroke: '#0f766e' });
            ligne([x(nbHist - 1) + ',' + y(points[nbHist - 1].reel), ...prev.map((p, k) => x(nbHist + k) + ',' + y(p.prevu))], { stroke: '#0d9488', 'stroke-dasharray': '5 4' });

            // Survol : réticule + point + infobulle
            const reticule = el('line', { y1: m.h, y2: hauteur - m.b, stroke: '#94a3b8', visibility: 'hidden' });
            const point = el('circle', { r: 4, fill: '#0d9488', stroke: '#fff', 'stroke-width': 2, visibility: 'hidden' });
            const zone = el('rect', { x: m.g, y: 0, width: largeur - m.g - m.d, height: hauteur - m.b, fill: 'transparent' });

            zone.addEventListener('mousemove', function (event) {
                const rect = svg.getBoundingClientRect();
                const i = Math.max(0, Math.min(points.length - 1, Math.round((event.clientX - rect.left - m.g) / ((largeur - m.g - m.d) / (points.length - 1)))));
                const p = points[i], v = p.reel ?? p.prevu;
                reticule.setAttribute('x1', x(i)); reticule.setAttribute('x2', x(i)); reticule.setAttribute('visibility', 'visible');
                point.setAttribute('cx', x(i)); point.setAttribute('cy', y(v)); point.setAttribute('visibility', 'visible');
                bulle.innerHTML = '<span style="opacity:.65">Semaine du ' + libelle(p.semaine) + '</span><br><strong>'
                    + (p.reel !== undefined ? p.reel + ' dons reçus' : Math.round(p.prevu) + ' dons prévus (' + Math.round(p.bas) + '–' + Math.round(p.haut) + ')') + '</strong>';
                bulle.style.left = x(i) + 'px'; bulle.style.top = y(v) + 'px'; bulle.hidden = false;
            });
            zone.addEventListener('mouseleave', function () {
                reticule.setAttribute('visibility', 'hidden'); point.setAttribute('visibility', 'hidden'); bulle.hidden = true;
            });
        }

        dessiner();
        window.addEventListener('resize', dessiner);
    })();
</script>
@endsection
