@extends('admin.layouts.admin')

@section('title', 'Carte en direct')

@section('breadcrumb')
    <span class="hidden sm:inline text-slate-300">/</span>
    <a href="{{ route('admin.logistique.index') }}" class="hover:text-slate-900">Logistique</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-900 font-medium">Carte en direct</span>
@endsection

@section('admin-content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-xl font-semibold text-slate-900 tracking-tight">Carte en direct</h1>
        <p class="text-slate-500 mt-0.5">
            <span id="resume-carte" class="num"></span>
            <span class="text-slate-400 num" id="maj-carte"></span>
        </p>
    </div>
    <form method="GET" class="flex items-center gap-2">
        <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" aria-label="Date" class="a-input num w-40" style="height: 32px;" onchange="this.form.submit()">
        @unless($date->isToday())
            <a href="{{ route('admin.logistique.carte') }}" class="a-btn">Aujourd'hui</a>
        @endunless
    </form>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_340px] gap-4">
    <div class="a-card overflow-hidden">
        <div id="carte-direct" class="cr-carte" style="height: calc(100vh - 190px); min-height: 420px;"></div>
    </div>

    <div class="a-card self-start">
        <h2 class="font-semibold text-slate-900 px-4 py-3 border-b border-slate-200">Collecteurs</h2>
        <ul id="liste-tournees" class="divide-y divide-slate-100" aria-live="polite"></ul>
        <p id="aucune-tournee" class="text-slate-500 text-center py-10" hidden>Aucune tournée ce jour-là.</p>
    </div>
</div>

@endsection

@section('styles')
@include('logistique.partials.carte', ['partie' => 'styles'])
<style>
    .d-tournee { display: block; width: 100%; text-align: left; padding: 10px 16px; transition: background .12s; }
    .d-tournee:hover, .d-tournee[aria-pressed="true"] { background: #f6f7f9; }
    .d-pastille { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .d-barre { display: block; height: 4px; border-radius: 4px; background: #e4e7ee; overflow: hidden; }
    .d-barre > span { display: block; height: 100%; }
</style>
@endsection

@section('scripts')
@include('logistique.partials.carte', ['partie' => 'scripts'])
<script>
    (function () {
        let donnees = @json($donnees);
        const url = @json(route('admin.logistique.carte.donnees', ['date' => $date->format('Y-m-d')]));
        const estAujourdhui = @json($date->isToday());
        // Une couleur de trajet par tournée, dans un ordre fixe ; le nom du collecteur l'accompagne toujours
        const PALETTE = ['#0d9488', '#7c3aed', '#ea580c', '#2563eb', '#db2777', '#65a30d', '#0891b2', '#a16207'];
        const couleur = i => PALETTE[i % PALETTE.length];
        const duree = m => m < 60 ? m + ' min' : Math.floor(m / 60) + ' h ' + String(m % 60).padStart(2, '0');
        const echapper = t => String(t ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

        const liste = document.getElementById('liste-tournees');
        let carte = null, calques = {}, selection = null, premierAffichage = true;

        if (window.CarteRetiss) {
            carte = CarteRetiss.creer('carte-direct', { scrollWheelZoom: true });
            CarteRetiss.depot(carte, donnees.depot);
        }

        function dessiner() {
            const ts = donnees.tournees;
            const enCours = ts.filter(t => t.faites < t.total).length;
            const enRetard = ts.filter(t => t.retard >= 15).length;
            document.getElementById('resume-carte').textContent =
                ts.length + ' tournée' + (ts.length > 1 ? 's' : '') + ', ' + enCours + ' en cours'
                + (enRetard ? ', ' + enRetard + ' en retard' : '') + '.';
            document.getElementById('maj-carte').textContent = estAujourdhui ? ' Mis à jour à ' + donnees.mis_a_jour + '.' : '';
            document.getElementById('aucune-tournee').hidden = ts.length > 0;

            // Liste
            liste.innerHTML = '';
            ts.forEach(function (t, i) {
                const part = t.total ? Math.round(t.faites / t.total * 100) : 0;
                let suivi = t.faites === t.total ? 'Tournée terminée'
                    : t.prochain ? 'Arrêt ' + t.prochain.ordre + ' prévu à ' + t.prochain.heure + (t.prochain.statut === 'EN_COURS' ? ' (en cours)' : '') : '';
                let position = !t.position ? 'Aucune position reçue'
                    : t.position.en_direct ? 'Position en direct' : 'Position il y a ' + duree(t.position.il_y_a);

                const li = document.createElement('li');
                li.innerHTML =
                    '<button type="button" class="d-tournee" aria-pressed="' + (selection === t.id) + '">'
                    + '<span class="flex items-center gap-2"><span class="d-pastille" style="background:' + couleur(i) + '"></span>'
                    + '<span class="font-medium text-slate-900 truncate">' + echapper(t.collecteur.nom) + '</span>'
                    + (t.retard >= 15 ? '<span class="ml-auto text-xs font-medium text-red-700 num" title="Retard sur l’heure prévue du prochain arrêt">+' + duree(t.retard) + '</span>' : '')
                    + '</span>'
                    + '<span class="block text-xs text-slate-500 mt-0.5">' + echapper(t.zone) + '</span>'
                    + '<span class="flex items-center gap-2 mt-2"><span class="d-barre flex-1"><span style="width:' + part + '%;background:' + couleur(i) + '"></span></span>'
                    + '<span class="text-xs text-slate-600 num">' + t.faites + '/' + t.total + '</span></span>'
                    + '<span class="block text-xs text-slate-600 mt-1.5">' + echapper(suivi) + '</span>'
                    + '<span class="block text-xs ' + (t.position && t.position.en_direct ? 'text-blue-700' : 'text-slate-400') + ' mt-0.5">' + position + '</span>'
                    + '</button>';
                li.querySelector('button').addEventListener('click', () => focaliser(t.id));
                liste.appendChild(li);
            });

            if (!carte) return;

            // Carte : un calque par tournée, redessiné à chaque rafraîchissement
            Object.values(calques).forEach(c => c.remove());
            calques = {};
            const limites = [[donnees.depot.lat, donnees.depot.lng]];

            ts.forEach(function (t, i) {
                const calque = L.layerGroup().addTo(carte);
                const restants = t.arrets.filter(a => a.statut === 'A_FAIRE' || a.statut === 'EN_COURS');
                const depart = t.position ? [t.position] : [donnees.depot];
                if (restants.length) CarteRetiss.trace(calque, [...depart, ...restants], { color: couleur(i), weight: 3, opacity: .8 });
                t.arrets.forEach(a => CarteRetiss.arret(calque, a));
                if (t.position) CarteRetiss.collecteur(calque, t.position, t.collecteur.nom + (t.position.en_direct ? ' — en direct' : ' — il y a ' + t.position.il_y_a + ' min'));
                t.arrets.forEach(a => limites.push([a.lat, a.lng]));
                if (t.position) limites.push([t.position.lat, t.position.lng]);
                calques[t.id] = calque;
            });

            if (premierAffichage && limites.length > 1) carte.fitBounds(limites, { padding: [40, 40] });
            premierAffichage = false;
        }

        function focaliser(id) {
            selection = selection === id ? null : id;
            const t = donnees.tournees.find(x => x.id === id);
            if (carte && t && selection) {
                const points = [...t.arrets.map(a => [a.lat, a.lng]), ...(t.position ? [[t.position.lat, t.position.lng]] : [])];
                if (points.length) carte.fitBounds(points, { padding: [50, 50], maxZoom: 15 });
            }
            dessiner();
        }

        dessiner();

        // Rafraîchissement toutes les 10 secondes, seulement pour aujourd'hui et quand l'onglet est visible
        if (estAujourdhui) {
            setInterval(async function () {
                if (document.hidden) return;
                try {
                    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (r.ok) { donnees = await r.json(); dessiner(); }
                } catch (e) {}
            }, 10000);
        }
    })();
</script>
@endsection
