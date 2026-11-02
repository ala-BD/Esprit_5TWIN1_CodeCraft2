@extends('logistique.layouts.logistique')

@section('title', 'Mode tournée')

@section('breadcrumb')
    <span class="hidden sm:inline text-slate-300">/</span>
    <a href="{{ route('logistique.tournees.show', $tournee) }}" class="hover:text-slate-900 truncate">{{ $tournee->zone }}</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-900 font-medium">Mode tournée</span>
@endsection

@section('logistique-content')

@php
    use App\Models\Mission;

    $missions = $tournee->missions;
    $faites   = $missions->whereIn('statut', [Mission::STATUT_TERMINEE, Mission::STATUT_ECHOUEE])->count();
    $total    = $missions->count();
    $restants = $missions->whereIn('statut', [Mission::STATUT_A_FAIRE, Mission::STATUT_EN_COURS])->where('id', '!=', $prochain?->id);
    $contact  = $prochain?->contact();
    $aujourdhui = $tournee->date->isToday();

    $carte = [
        'arrets' => $missions->filter->estLocalisee()->map(fn ($m) => [
            'lat' => $m->latitude, 'lng' => $m->longitude, 'ordre' => $m->ordre, 'statut' => $m->statut,
            'adresse' => $m->adresse, 'heure' => $m->heure, 'id' => $m->id,
        ])->values(),
        'prochain' => $prochain?->id,
        'depot' => ['lat' => config('logistique.depot.latitude'), 'lng' => config('logistique.depot.longitude'), 'nom' => config('logistique.depot.nom')],
    ];
@endphp

<div class="max-w-2xl mx-auto">

    {{-- Avancement --}}
    <div class="flex items-center justify-between gap-3 mb-2">
        <div class="min-w-0">
            <h1 class="text-lg font-semibold text-slate-900 tracking-tight truncate">{{ $tournee->zone }}</h1>
            <p class="text-slate-500 num">{{ $tournee->date->locale('fr')->isoFormat('dddd D MMMM') }} · {{ $tournee->vehicule }}</p>
        </div>
        <span id="gps-etat" class="c-gps" data-etat="{{ $aujourdhui ? 'attente' : 'inactif' }}">
            <span class="c-gps-point"></span>
            <span data-texte>{{ $aujourdhui ? 'Localisation…' : 'Partage de position le jour de la tournée' }}</span>
        </span>
    </div>
    <div class="flex items-center gap-3 mb-4">
        <span class="block flex-1 h-2 rounded-full bg-slate-200 overflow-hidden">
            <span class="block h-full bg-primary transition-all" style="width: {{ $total ? $faites / $total * 100 : 0 }}%"></span>
        </span>
        <span class="num text-slate-600 text-sm">{{ $faites }}/{{ $total }} arrêts</span>
    </div>

    {{-- Carte --}}
    <div class="a-card overflow-hidden mb-4">
        <div id="carte-conduite" class="cr-carte" style="height: 34vh; min-height: 220px;"></div>
    </div>

    @if($prochain)
        {{-- ===== Prochain arrêt ===== --}}
        <section class="a-card c-prochain mb-4" aria-labelledby="titre-prochain">
            <div class="flex items-start gap-3 p-4">
                <span class="c-num">{{ $prochain->ordre }}</span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium uppercase tracking-wide {{ $prochain->statut === Mission::STATUT_EN_COURS ? 'text-amber-700' : 'text-slate-500' }}">
                        {{ $prochain->statut === Mission::STATUT_EN_COURS ? 'En cours' : 'Prochain arrêt' }} · {{ $prochain->type_label }} · prévu à {{ $prochain->heure }}
                    </p>
                    <h2 id="titre-prochain" class="text-lg font-semibold text-slate-900 leading-snug mt-1">{{ $prochain->adresse }}</h2>

                    @if($prochain->commande)
                        <p class="text-slate-600 mt-1">Commande {{ $prochain->commande->numero }} · {{ $prochain->commande->lignes()->count() }} article(s)</p>
                    @elseif($prochain->donVetement)
                        <p class="text-slate-600 mt-1">Don n° {{ $prochain->donVetement->id }} · {{ $prochain->donVetement->type }}, {{ $prochain->donVetement->matiere }}</p>
                    @endif

                    @if($contact)
                        <p class="text-slate-600 mt-1">
                            <i class="fas fa-user text-slate-400 text-xs"></i> {{ $contact->full_name }}
                            @if($contact->telephone)
                                · <a href="tel:{{ $contact->telephone }}" class="text-primary-dark font-medium num">{{ $contact->telephone }}</a>
                            @endif
                        </p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 px-4 pb-4">
                @if($prochain->estLocalisee())
                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ $prochain->latitude }},{{ $prochain->longitude }}"
                       target="_blank" rel="noopener" class="c-btn">
                        <i class="fas fa-diamond-turn-right"></i> Itinéraire
                    </a>
                @else
                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($prochain->adresse) }}"
                       target="_blank" rel="noopener" class="c-btn">
                        <i class="fas fa-magnifying-glass-location"></i> Chercher l'adresse
                    </a>
                @endif

                @if($prochain->statut === Mission::STATUT_A_FAIRE)
                    <button type="button" class="c-btn c-btn-primaire" data-action="demarrer">
                        <i class="fas fa-play"></i> Démarrer
                    </button>
                @else
                    <button type="button" class="c-btn c-btn-primaire" data-ouvrir="fenetre-terminer">
                        <i class="fas fa-check"></i> Terminé
                    </button>
                    <button type="button" class="c-btn c-btn-echec col-span-2" data-ouvrir="fenetre-echec">
                        <i class="fas fa-xmark"></i> Impossible de faire cet arrêt
                    </button>
                @endif
            </div>
        </section>

        {{-- Terminer : preuve facultative --}}
        <dialog id="fenetre-terminer" class="c-fenetre">
            <form method="dialog" class="p-5" data-envoi="terminer">
                <h2 class="text-base font-semibold text-slate-900">Arrêt {{ $prochain->ordre }} terminé</h2>
                <p class="text-slate-500 mt-1">Ajoutez une preuve si vous le souhaitez.</p>
                <label class="a-label mt-4" for="preuve-terminer">Note</label>
                <input id="preuve-terminer" name="preuve" class="a-input" maxlength="255"
                       placeholder="{{ $prochain->type === Mission::TYPE_COLLECTE ? '3 sacs collectés' : 'Remis à M. Ben Ali' }}">
                <label class="a-label mt-3" for="photo-terminer">Photo</label>
                <input id="photo-terminer" name="photo" type="file" accept="image/*" capture="environment" class="block w-full text-sm text-slate-600">
                <p class="a-error" data-erreur hidden></p>
                <div class="flex justify-end gap-2 mt-5">
                    <button type="button" class="a-btn" data-fermer>Annuler</button>
                    <button type="submit" class="a-btn a-btn-primary">Confirmer</button>
                </div>
            </form>
        </dialog>

        {{-- Échec : raison obligatoire --}}
        <dialog id="fenetre-echec" class="c-fenetre">
            <form method="dialog" class="p-5" data-envoi="echouer">
                <h2 class="text-base font-semibold text-slate-900">Pourquoi l'arrêt {{ $prochain->ordre }} n'a pas pu être fait ?</h2>
                <div class="flex flex-wrap gap-2 mt-4">
                    @foreach(['Client absent', 'Adresse introuvable', 'Refusé par le client', 'Accès impossible'] as $raison)
                        <button type="button" class="c-raison" data-raison="{{ $raison }}">{{ $raison }}</button>
                    @endforeach
                </div>
                <label class="a-label mt-4" for="preuve-echec">Raison</label>
                <input id="preuve-echec" name="preuve" class="a-input" maxlength="255" required placeholder="Précisez la raison">
                <p class="a-error" data-erreur hidden></p>
                <div class="flex justify-end gap-2 mt-5">
                    <button type="button" class="a-btn" data-fermer>Annuler</button>
                    <button type="submit" class="a-btn a-btn-danger-solid">Signaler l'échec</button>
                </div>
            </form>
        </dialog>
    @else
        <section class="a-card p-6 text-center mb-4">
            <span class="inline-flex w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 items-center justify-center text-xl"><i class="fas fa-flag-checkered"></i></span>
            <h2 class="text-lg font-semibold text-slate-900 mt-3">Tournée terminée</h2>
            <p class="text-slate-500 mt-1 num">{{ $missions->where('statut', Mission::STATUT_TERMINEE)->count() }} arrêt(s) réussi(s), {{ $missions->where('statut', Mission::STATUT_ECHOUEE)->count() }} échec(s).</p>
            <a href="{{ route('logistique.tournees.show', $tournee) }}" class="a-btn mt-4">Voir le détail</a>
        </section>
    @endif

    {{-- Arrêts suivants --}}
    @if($restants->isNotEmpty())
        <div class="a-card">
            <h2 class="font-semibold text-slate-900 px-4 py-3 border-b border-slate-200">Ensuite</h2>
            <ol class="divide-y divide-slate-100">
                @foreach($restants as $mission)
                    <li class="flex items-center gap-3 px-4 py-2.5">
                        <span class="c-num c-num-petit">{{ $mission->ordre }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-slate-900 truncate">{{ $mission->adresse }}</span>
                            <span class="block text-xs text-slate-500">{{ $mission->type_label }}</span>
                        </span>
                        <span class="num text-slate-600">{{ $mission->heure }}</span>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif
</div>

@endsection

@section('styles')
@include('logistique.partials.carte', ['partie' => 'styles'])
<style>
    .c-num { flex-shrink: 0; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
             background: var(--teal); color: #fff; font-weight: 600; font-size: 15px; }
    .c-num-petit { width: 24px; height: 24px; font-size: 11px; background: #e4e7ee; color: #3a4558; }
    .c-btn { display: flex; align-items: center; justify-content: center; gap: 8px; height: 48px; border-radius: 10px;
             border: 1px solid var(--line); background: #fff; color: #334155; font-weight: 600; font-size: 15px; }
    .c-btn:active { transform: scale(.98); }
    .c-btn-primaire { background: var(--teal); border-color: var(--teal); color: #fff; }
    .c-btn-echec { height: 42px; font-size: 14px; color: #b91c1c; border-color: #f1c3be; }
    .c-btn:disabled { opacity: .6; }
    .c-raison { padding: 6px 12px; border-radius: 999px; border: 1px solid var(--line); color: #334155; }
    .c-raison[aria-pressed="true"] { background: #fef2f2; border-color: #f1c3be; color: #b91c1c; }
    .c-fenetre { width: min(440px, calc(100vw - 24px)); border-radius: 12px; padding: 0; border: 0;
                 box-shadow: 0 24px 48px rgba(26,32,48,.25); }
    .c-fenetre::backdrop { background: rgba(26,32,48,.45); }
    .c-gps { display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0; padding: 4px 10px; border-radius: 999px;
             font-size: 12px; background: #eef0f4; color: #475569; }
    .c-gps-point { width: 8px; height: 8px; border-radius: 50%; background: #94a3b8; }
    .c-gps[data-etat="actif"] { background: #e6f4f2; color: var(--teal-dark); }
    .c-gps[data-etat="actif"] .c-gps-point { background: var(--teal); }
    .c-gps[data-etat="refuse"] { background: #fef2f2; color: #b91c1c; }
    .c-gps[data-etat="refuse"] .c-gps-point { background: #c62828; }
</style>
@endsection

@section('scripts')
@include('logistique.partials.carte', ['partie' => 'scripts'])
<script>
    (function () {
        const donnees  = @json($carte);
        const jeton    = document.querySelector('meta[name="csrf-token"]').content;
        const avancer  = @json($prochain ? route('logistique.missions.avancer', $prochain) : null);
        const urlPosition = @json(route('logistique.position'));
        const tourneeId = {{ $tournee->id }};
        const aujourdhui = @json($aujourdhui);

        /* ---------- Carte ---------- */
        let carte = null, moi = null;
        if (window.CarteRetiss) {
            carte = CarteRetiss.creer('carte-conduite');
            CarteRetiss.depot(carte, donnees.depot);
            const restants = donnees.arrets.filter(a => a.statut === 'A_FAIRE' || a.statut === 'EN_COURS');
            if (restants.length) CarteRetiss.trace(carte, restants, { dashArray: '6 6', weight: 3 });
            donnees.arrets.forEach(a => CarteRetiss.arret(carte, a, a.id === donnees.prochain));

            const prochain = donnees.arrets.find(a => a.id === donnees.prochain);
            if (prochain) carte.setView([prochain.lat, prochain.lng], 14);
            else if (donnees.arrets.length) carte.fitBounds(donnees.arrets.map(a => [a.lat, a.lng]), { padding: [30, 30] });
            else carte.setView([donnees.depot.lat, donnees.depot.lng], 12);
        }

        /* ---------- Position partagée (le jour de la tournée seulement) ---------- */
        const etat = document.getElementById('gps-etat');
        const afficherEtat = (code, texte) => { etat.dataset.etat = code; etat.querySelector('[data-texte]').textContent = texte; };
        let dernierEnvoi = 0;

        if (aujourdhui && navigator.geolocation) {
            navigator.geolocation.watchPosition(function (pos) {
                const p = { lat: pos.coords.latitude, lng: pos.coords.longitude, en_direct: true };
                afficherEtat('actif', 'Position partagée');
                if (carte) { if (moi) moi.setLatLng([p.lat, p.lng]); else moi = CarteRetiss.collecteur(carte, p, 'Vous êtes ici'); }

                if (Date.now() - dernierEnvoi < 20000) return;
                dernierEnvoi = Date.now();
                fetch(urlPosition, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': jeton },
                    body: JSON.stringify({ latitude: p.lat, longitude: p.lng, precision: pos.coords.accuracy, tournee_id: tourneeId }),
                }).catch(() => {});
            }, function (erreur) {
                afficherEtat('refuse', erreur.code === 1 ? 'Localisation refusée' : 'Position indisponible');
            }, { enableHighAccuracy: true, maximumAge: 10000, timeout: 20000 });
        } else if (aujourdhui) {
            afficherEtat('refuse', 'Localisation indisponible');
        }

        /* ---------- Actions sur l'arrêt ---------- */
        async function envoyer(action, corps, bouton, zoneErreur) {
            corps = corps || new FormData();
            corps.append('action', action);
            if (bouton) bouton.disabled = true;
            try {
                const reponse = await fetch(avancer, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': jeton }, body: corps });
                if (reponse.ok) return window.location.reload();
                const r = await reponse.json().catch(() => ({}));
                const message = r.errors ? Object.values(r.errors)[0][0] : "L'action n'a pas pu être enregistrée.";
                if (zoneErreur) { zoneErreur.textContent = message; zoneErreur.hidden = false; } else alert(message);
            } catch (e) {
                const message = 'Pas de connexion : réessayez dans un instant.';
                if (zoneErreur) { zoneErreur.textContent = message; zoneErreur.hidden = false; } else alert(message);
            }
            if (bouton) bouton.disabled = false;
        }

        document.querySelectorAll('[data-action]').forEach(b => b.addEventListener('click', () => envoyer(b.dataset.action, null, b)));
        document.querySelectorAll('[data-ouvrir]').forEach(b => b.addEventListener('click', () => document.getElementById(b.dataset.ouvrir).showModal()));
        document.querySelectorAll('dialog [data-fermer]').forEach(b => b.addEventListener('click', () => b.closest('dialog').close()));

        document.querySelectorAll('[data-raison]').forEach(b => b.addEventListener('click', function () {
            document.querySelectorAll('[data-raison]').forEach(x => x.setAttribute('aria-pressed', String(x === b)));
            document.getElementById('preuve-echec').value = b.dataset.raison;
        }));

        document.querySelectorAll('form[data-envoi]').forEach(f => f.addEventListener('submit', function (event) {
            event.preventDefault();
            envoyer(f.dataset.envoi, new FormData(f), f.querySelector('[type="submit"]'), f.querySelector('[data-erreur]'));
        }));
    })();
</script>
@endsection
