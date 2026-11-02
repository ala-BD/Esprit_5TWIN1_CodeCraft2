{{--
    Bibliothèque de carte partagée (Leaflet + OpenStreetMap) : à inclure une fois par page,
    dans les sections « styles » et « scripts » via @include('logistique.partials.carte', ['partie' => 'styles'|'scripts']).
    Expose window.CarteRetiss : créer(), arret(), depot(), collecteur(), trace().
--}}
@if(($partie ?? 'scripts') === 'styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<style>
    .cr-carte { background: #eef1f5; }
    .cr-carte .leaflet-tile-pane { filter: saturate(.2) brightness(1.06) contrast(.9); }
    .cr-marqueur { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
                   font: 600 12px/1 'IBM Plex Sans', sans-serif; color: #fff; border: 2px solid #fff;
                   box-shadow: 0 1px 4px rgba(26,32,48,.35); }
    .cr-marqueur.actif { outline: 3px solid rgba(13,148,136,.35); }
    .cr-depot { background: #1a2030; }
    .cr-collecteur { width: 18px; height: 18px; border-radius: 50%; background: #2563eb; border: 3px solid #fff;
                     box-shadow: 0 0 0 0 rgba(37,99,235,.5), 0 1px 4px rgba(26,32,48,.4); }
    .cr-collecteur.en-direct { animation: cr-pouls 2s infinite; }
    .cr-collecteur.ancienne { background: #94a3b8; }
    @keyframes cr-pouls { 0% { box-shadow: 0 0 0 0 rgba(37,99,235,.5), 0 1px 4px rgba(26,32,48,.4); }
                          70% { box-shadow: 0 0 0 14px rgba(37,99,235,0), 0 1px 4px rgba(26,32,48,.4); }
                          100% { box-shadow: 0 0 0 0 rgba(37,99,235,0), 0 1px 4px rgba(26,32,48,.4); } }
    @media (prefers-reduced-motion: reduce) { .cr-collecteur.en-direct { animation: none; } }
</style>
@else
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    window.CarteRetiss = (function () {
        if (!window.L) return null;   // hors ligne : les pages restent utilisables sans carte

        // Une couleur par statut d'arrêt (mêmes teintes que les badges)
        const COULEURS = { A_FAIRE: '#1d4fa3', EN_COURS: '#b07800', TERMINEE: '#17803d', ECHOUEE: '#c62828' };
        const echapper = t => String(t ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const icone = (html, taille) => L.divIcon({ className: '', html: html, iconSize: [taille, taille], iconAnchor: [taille / 2, taille / 2] });

        return {
            COULEURS: COULEURS,
            echapper: echapper,

            creer(id, options) {
                const carte = L.map(id, Object.assign({ scrollWheelZoom: false }, options || {}));
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                }).addTo(carte);
                return carte;
            },

            /** Arrêt numéroté, coloré selon son statut */
            arret(carte, a, actif) {
                const html = '<div class="cr-marqueur' + (actif ? ' actif' : '') + '" style="background:' + (COULEURS[a.statut] || '#475569') + '">' + a.ordre + '</div>';
                return L.marker([a.lat, a.lng], { icon: icone(html, 26), zIndexOffset: actif ? 500 : 0 })
                    .bindTooltip('<strong>' + a.ordre + '. ' + echapper(a.heure) + '</strong> — ' + echapper(a.adresse))
                    .addTo(carte);
            },

            depot(carte, d) {
                return L.marker([d.lat, d.lng], { icon: icone('<div class="cr-marqueur cr-depot"><i class="fas fa-warehouse" style="font-size:10px"></i></div>', 26) })
                    .bindTooltip(echapper(d.nom)).addTo(carte);
            },

            /** Position du collecteur : bleue et animée si récente, grise sinon */
            collecteur(carte, p, libelle) {
                const html = '<div class="cr-collecteur ' + (p.en_direct ? 'en-direct' : 'ancienne') + '"></div>';
                return L.marker([p.lat, p.lng], { icon: icone(html, 18), zIndexOffset: 1000 })
                    .bindTooltip(echapper(libelle)).addTo(carte);
            },

            /** Trajet reliant les points dans l'ordre */
            trace(carte, points, style) {
                return L.polyline(points.map(p => [p.lat, p.lng]), Object.assign({ color: '#0d9488', weight: 3, opacity: .75 }, style || {})).addTo(carte);
            },
        };
    })();
</script>
@endif
