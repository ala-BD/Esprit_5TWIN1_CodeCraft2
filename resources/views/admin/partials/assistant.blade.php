{{--
    Assistant vocal : bouton micro flottant + panneau de conversation.
    La voix est enregistrée puis transcrite côté serveur (/assistant/transcrire), et la commande
    est comprise par un modèle de langage (/assistant/interpreter).
    Toute création, modification ou suppression passe par une confirmation avant /assistant/executer.
--}}
@php
    $exemples = Auth::user()->isAdmin()
        ? [
            'fr' => ['Ajoute un utilisateur Karim Mansouri, e-mail karim arobase exemple point tn, rôle collecteur', 'Désactive le compte de Karim Mansouri', 'Montre-moi les recycleurs', 'Ouvre les statistiques'],
            'en' => ['Add a user named Karim Mansouri, email karim at exemple dot tn, role collector', 'Deactivate Karim Mansouri\'s account', 'Show me the recyclers', 'Open the statistics'],
        ]
        : [
            'fr' => ['Planifie une tournée demain à La Marsa avec le fourgon 87 TU 2210', 'Ajoute une collecte à 9h30 au 12 rue de Marseille dans la tournée d\'aujourd\'hui', 'Marque la mission 2 comme terminée', 'Montre mes tournées en cours'],
            'en' => ['Plan a round tomorrow in La Marsa with the van 87 TU 2210', 'Add a pickup at 9:30 at 12 rue de Marseille to today\'s round', 'Mark mission 2 as done', 'Show my rounds in progress'],
        ];
@endphp

<button type="button" id="va-fab" class="va-fab" aria-haspopup="dialog" aria-controls="va-panel" aria-expanded="false"
        aria-label="Assistant vocal" title="Assistant vocal (Alt+V)">
    <i class="fas fa-microphone"></i>
</button>

<section id="va-panel" class="va-panel" role="dialog" aria-label="Assistant vocal" aria-hidden="true"
         data-transcrire="{{ route('assistant.transcrire') }}"
         @if(app(\App\Services\Assistant\Synthetiseur::class)->estConfigure()) data-parler="{{ route('assistant.parler') }}" data-parler-max="{{ \App\Services\Assistant\Synthetiseur::LONGUEUR_MAX }}" @endif
         data-interpreter="{{ route('assistant.interpreter') }}"
         data-preparer="{{ route('assistant.preparer') }}"
         data-executer="{{ route('assistant.executer') }}">

    <header class="va-header">
        <span class="font-semibold text-slate-900">Assistant vocal</span>

        {{-- Langue dictée --}}
        <div class="va-lang" role="group" aria-label="Langue dictée">
            <button type="button" data-lang="fr" aria-pressed="true">FR</button>
            <button type="button" data-lang="en" aria-pressed="false">EN</button>
        </div>

        <button type="button" class="a-icon-btn ml-auto" id="va-reset" title="Nouvelle conversation" aria-label="Nouvelle conversation">
            <i class="fas fa-rotate-left"></i>
        </button>
        <button type="button" class="a-icon-btn" id="va-voice" aria-pressed="true" title="Lire les réponses à voix haute" aria-label="Lire les réponses à voix haute">
            <i class="fas fa-volume-high"></i>
        </button>
        <button type="button" class="a-icon-btn" id="va-close" aria-label="Fermer" title="Fermer (Échap)">
            <i class="fas fa-xmark"></i>
        </button>
    </header>

    {{-- Conversation --}}
    <div id="va-log" class="va-log" aria-live="polite">
        <div class="va-intro" id="va-intro">
            <p class="text-slate-600" id="va-intro-text">Appuyez sur le micro et dites ce que vous voulez faire. Par exemple :</p>
            @foreach($exemples as $langue => $phrases)
                <div class="va-examples" data-examples="{{ $langue }}" @if($langue !== 'fr') hidden @endif>
                    @foreach($phrases as $phrase)
                        <button type="button" class="va-example">{{ $phrase }}</button>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    {{-- Saisie : micro ou clavier --}}
    <footer class="va-footer">
        <p id="va-status" class="va-status">Prêt</p>
        <form id="va-form" class="va-form" autocomplete="off">
            <button type="button" id="va-mic" class="va-mic" aria-label="Dicter une commande" aria-pressed="false">
                <span class="va-mic-ring"></span>
                <i class="fas fa-microphone"></i>
            </button>
            <input type="text" id="va-input" class="a-input" maxlength="500" placeholder="Ou tapez votre commande" aria-label="Commande">
            <button type="submit" class="a-btn" aria-label="Envoyer"><i class="fas fa-arrow-up"></i></button>
        </form>
    </footer>
</section>

<style>
    /* ---- Bouton flottant ---- */
    .va-fab {
        position: fixed; z-index: 45; right: 20px; bottom: 20px;
        width: 46px; height: 46px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: var(--teal); color: #fff; font-size: 16px;
        box-shadow: 0 8px 20px -4px rgba(13,148,136,.5), 0 2px 4px rgba(26,32,48,.2);
        transition: transform .18s cubic-bezier(.16,1,.3,1), background .14s, opacity .18s;
    }
    .va-fab:hover { background: var(--teal-dark); transform: translateY(-2px); }
    .va-fab:focus-visible { outline: 2px solid var(--teal); outline-offset: 3px; }
    .va-fab[aria-expanded="true"] { opacity: 0; pointer-events: none; transform: scale(.8); }

    /* ---- Panneau ---- */
    .va-panel {
        position: fixed; z-index: 46; right: 20px; bottom: 20px;
        width: min(400px, calc(100vw - 24px)); max-height: min(600px, calc(100vh - 40px));
        display: flex; flex-direction: column;
        background: #fff; border: 1px solid var(--line); border-radius: 14px;
        box-shadow: 0 24px 56px -12px rgba(26,32,48,.30), 0 2px 6px rgba(26,32,48,.10);
        opacity: 0; visibility: hidden; transform: translateY(16px) scale(.97); transform-origin: bottom right;
        transition: opacity .16s ease, transform .2s cubic-bezier(.4,0,1,1), visibility 0s .2s;
    }
    .va-panel.open {
        opacity: 1; visibility: visible; transform: none;
        transition: opacity .2s ease, transform .34s cubic-bezier(.16,1,.3,1), visibility 0s;
    }

    .va-header { display: flex; align-items: center; gap: 10px; padding: 10px 10px 10px 14px; border-bottom: 1px solid var(--line); }
    .va-lang { display: flex; padding: 2px; border-radius: 7px; background: #eef0f4; }
    .va-lang button { height: 22px; padding: 0 8px; border-radius: 5px; font-size: 11.5px; font-weight: 600; color: var(--muted); transition: background .12s, color .12s; }
    .va-lang button[aria-pressed="true"] { background: #fff; color: var(--ink); box-shadow: 0 1px 2px rgba(26,32,48,.14); }
    #va-voice[aria-pressed="false"] { color: #b3bac6; }

    /* ---- Conversation ---- */
    .va-log { flex: 1; min-height: 180px; overflow-y: auto; padding: 14px; display: flex; flex-direction: column; gap: 10px; }
    .va-log > * { flex-shrink: 0; }   /* une longue conversation défile, elle n'écrase pas les cartes */
    .va-examples { display: flex; flex-direction: column; gap: 6px; margin-top: 10px; }
    .va-examples[hidden] { display: none; }
    .va-example {
        text-align: left; padding: 7px 10px; border-radius: 8px; border: 1px solid var(--line);
        color: #3a4558; transition: background .12s, border-color .12s;
    }
    .va-example:hover { background: #f6f7f9; border-color: #cbd5e1; }

    .va-msg { max-width: 88%; padding: 8px 11px; border-radius: 12px; animation: va-in .24s cubic-bezier(.16,1,.3,1); overflow-wrap: anywhere; }
    .va-msg.user { align-self: flex-end; background: var(--navy); color: #fff; border-bottom-right-radius: 4px; }
    .va-msg.bot  { align-self: flex-start; background: #f1f3f6; color: var(--ink); border-bottom-left-radius: 4px; }
    .va-msg.bot.ok { background: #e1f3e6; color: #17663a; }
    @keyframes va-in { from { opacity: 0; transform: translateY(6px); } }

    .va-dots { display: inline-flex; gap: 4px; padding: 4px 0; }
    .va-dots span { width: 5px; height: 5px; border-radius: 50%; background: #8590a3; animation: va-dot 1s infinite ease-in-out; }
    .va-dots span:nth-child(2) { animation-delay: .15s; }
    .va-dots span:nth-child(3) { animation-delay: .3s; }
    @keyframes va-dot { 0%, 60%, 100% { opacity: .3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-3px); } }

    /* Action à confirmer */
    .va-card { align-self: stretch; border: 1px solid var(--line); border-radius: 12px; overflow: hidden; animation: va-in .24s cubic-bezier(.16,1,.3,1); }
    .va-card.danger { border-color: #f1c3be; }
    .va-card-title { padding: 10px 12px; font-weight: 600; color: var(--ink); }
    .va-card dl { padding: 0 12px 10px; display: grid; grid-template-columns: auto 1fr; gap: 4px 12px; }
    .va-card dt { color: var(--muted); }
    .va-card dd { color: var(--ink); font-weight: 500; overflow-wrap: anywhere; }
    .va-card-actions { display: flex; gap: 8px; justify-content: flex-end; padding: 10px 12px; background: #fafbfc; border-top: 1px solid var(--line); }
    .va-card-hint { margin-right: auto; align-self: center; color: var(--muted); font-size: 12px; }
    .va-card.done .va-card-actions { display: none; }
    .va-card.done { opacity: .6; }

    .va-secret { display: flex; align-items: center; gap: 8px; margin-top: 6px; }
    .va-secret code { padding: 3px 8px; border-radius: 6px; background: #fff; border: 1px solid #b9dcc3; font: 600 13px/1.4 ui-monospace, Menlo, Consolas, monospace; letter-spacing: .04em; }
    .va-secret a { text-decoration: underline; }

    /* ---- Pied : micro + saisie ---- */
    .va-footer { padding: 10px 12px 12px; border-top: 1px solid var(--line); }
    .va-status { min-height: 18px; margin-bottom: 8px; color: var(--muted); font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .va-status.live { color: var(--ink); }
    .va-form { display: flex; align-items: center; gap: 8px; }
    .va-mic {
        position: relative; flex-shrink: 0; width: 38px; height: 38px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: var(--teal); color: #fff; font-size: 14px; transition: background .14s, transform .12s;
    }
    .va-mic:hover:not(:disabled) { background: var(--teal-dark); }
    .va-mic:active:not(:disabled) { transform: scale(.94); }
    .va-mic:focus-visible { outline: 2px solid var(--teal); outline-offset: 3px; }
    .va-mic:disabled { background: #cfd5df; cursor: not-allowed; }
    .va-mic[aria-pressed="true"] { background: #c62828; }
    .va-mic-ring { position: absolute; inset: 0; border-radius: 50%; border: 2px solid #c62828; opacity: 0; pointer-events: none; }
    /* Pendant l'écoute, le halo grossit avec le volume de la voix (--niveau, de 0 à 1) */
    .va-mic[aria-pressed="true"] .va-mic-ring {
        opacity: .45; background: rgba(198,40,40,.18);
        transform: scale(calc(1.15 + var(--niveau, 0) * .9)); transition: transform .08s linear;
    }

    /* Restauration après un changement de page : pas d'animation rejouée */
    .va-restoring, .va-restoring * { transition: none !important; animation: none !important; }

    @media (prefers-reduced-motion: reduce) {
        .va-panel, .va-panel.open, .va-fab { transition: opacity .15s ease, visibility 0s; transform: none; }
        .va-msg, .va-card, .va-dots span { animation: none; }
        .va-mic[aria-pressed="true"] .va-mic-ring { transform: scale(1.3); transition: none; }
    }
</style>

<script>
    // ---- Assistant vocal ----
    (function () {
        const fab    = document.getElementById('va-fab');
        const panel  = document.getElementById('va-panel');
        const log    = document.getElementById('va-log');
        const status = document.getElementById('va-status');
        const mic    = document.getElementById('va-mic');
        const input  = document.getElementById('va-input');
        const form   = document.getElementById('va-form');
        const voice  = document.getElementById('va-voice');
        const token  = document.querySelector('meta[name="csrf-token"]').content;

        const peutEnregistrer = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);
        const storage = { get: k => { try { return localStorage.getItem(k); } catch (e) { return null; } }, set: (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} } };

        let langue = storage.get('va-langue') === 'en' ? 'en' : 'fr';
        let parler = storage.get('va-voix') !== '0';
        let historique = [];      // échanges envoyés au modèle pour les questions de suivi
        let journal = [];         // contenu affiché, conservé d'une page à l'autre (sessionStorage)
        let enAttente = null;     // action en attente de confirmation : { action, arguments, card, entree }
        let file = [];            // actions suivantes d'une commande multiple, préparées une à une
        let encore = false;       // le modèle a signalé une suite qui dépend de l'action en cours
        let destination = null;   // page à ouvrir une fois la commande entièrement traitée
        let vocal = false;        // la dernière commande a été dictée : on réécoute après une question
        let occupe = false, restauration = false;
        let enregistrement = null;   // { recorder, stream, contexte, arreter() }

        const TEXTES = {
            fr: { pret: 'Prêt', ecoute: 'Je vous écoute…', transcription: 'Transcription…', analyse: 'Analyse…',
                  indisponible: 'Micro indisponible dans ce navigateur : tapez votre commande.',
                  micro: "Le micro est bloqué. Autorisez-le dans le navigateur, ou tapez votre commande.", rien: "Je n'ai rien entendu.", annule: 'Action annulée.',
                  reseau: 'La requête a échoué. Vérifiez votre connexion et réessayez.', trop: 'Trop de commandes à la suite. Patientez une minute.',
                  patience: n => 'Quota gratuit atteint, nouvelle tentative dans ' + n + ' s…',
                  dites: 'Dites « oui » ou « non »', intro: 'Appuyez sur le micro et dites ce que vous voulez faire. Par exemple :',
                  saisie: 'Ou tapez votre commande', confirmer: 'Confirmer', annuler: 'Annuler', ouvrir: 'Ouvrir la fiche' },
            en: { pret: 'Ready', ecoute: 'Listening…', transcription: 'Transcribing…', analyse: 'Thinking…',
                  indisponible: 'Microphone unavailable in this browser: type your command.',
                  micro: 'The microphone is blocked. Allow it in the browser, or type your command.', rien: "I didn't hear anything.", annule: 'Action cancelled.',
                  reseau: 'The request failed. Check your connection and try again.', trop: 'Too many commands in a row. Wait a minute.',
                  patience: n => 'Free quota reached, retrying in ' + n + ' s…',
                  dites: 'Say "yes" or "no"', intro: 'Press the microphone and say what you want to do. For example:',
                  saisie: 'Or type your command', confirmer: 'Confirm', annuler: 'Cancel', ouvrir: 'Open profile' },
        };
        const t = cle => TEXTES[langue][cle];
        const repos = () => peutEnregistrer ? t('pret') : t('indisponible');

        /* ---------- Affichage ---------- */

        function setStatus(texte, live) {
            status.textContent = texte;
            status.classList.toggle('live', !!live);
        }

        function ajouter(element) {
            document.getElementById('va-intro').hidden = true;
            log.appendChild(element);
            log.scrollTop = log.scrollHeight;
            return element;
        }

        function bulle(texte, qui, classe) {
            const el = document.createElement('div');
            el.className = 'va-msg ' + qui + (classe ? ' ' + classe : '');
            el.textContent = texte;
            noter({ t: 'msg', texte: texte, qui: qui, classe: classe || '' });
            return ajouter(el);
        }

        function memoriser(role, content) {
            historique.push({ role: role, content: String(content).slice(0, 600) });
            historique = historique.slice(-12);
            sauver();
        }

        /* ---------- Synthèse vocale ---------- */

        // Choisit explicitement une voix de la bonne langue : laisser le navigateur décider
        // donne parfois une voix française qui lit de l'anglais, ou l'inverse.
        function voixPour(code) {
            const voix = window.speechSynthesis ? speechSynthesis.getVoices() : [];
            const candidates = voix.filter(v => v.lang.replace('_', '-').toLowerCase().startsWith(code));
            const preferee = code === 'en' ? 'en-us' : 'fr-fr';
            const note = v => (/natural|neural|online|google/i.test(v.name) ? 4 : 0)
                + (v.lang.replace('_', '-').toLowerCase() === preferee ? 2 : 0)
                + (v.localService ? 1 : 0);
            return candidates.sort((a, b) => note(b) - note(a))[0] || null;
        }

        // Réécrit ce que les voix lisent mal : le texte affiché, lui, ne change pas.
        function pourLaVoix(texte, code) {
            const en = code === 'en';
            return texte
                .replace(/[   ]/g, ' ')                                   // espaces insécables et fines
                .replace(/(\d)\s*%/g, '$1 ' + (en ? 'percent' : 'pour cent'))            // « 17 % » se lisait de travers
                .replace(/%/g, en ? ' percent' : ' pour cent')
                .replace(/\b(\d{4})-(\d{2})-(\d{2})\b/g, function (tout, a, m, j) {       // 2026-10-03 → 3 octobre 2026
                    const date = new Date(Number(a), Number(m) - 1, Number(j));
                    return isNaN(date) ? tout : date.toLocaleDateString(en ? 'en-US' : 'fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
                })
                .replace(/\s*→\s*/g, en ? ' to ' : ' vers ')
                .replace(/\s*&\s*/g, en ? ' and ' : ' et ')
                .replace(/(\d)\s*km\b/g, '$1 ' + (en ? 'kilometers' : 'kilomètres'))
                .replace(/[#*_`]/g, '')
                .replace(/\s{2,}/g, ' ')
                .trim();
        }

        function dire(texte, code, ensuite) {
            const apres = () => { if (ensuite) ensuite(); };
            taire();
            if (!parler) return apres();
            texte = pourLaVoix(texte, code || langue);

            // Anglais : voix naturelle du serveur ; sinon (ou en secours) voix du navigateur
            if ((code || langue) === 'en' && voixNaturelleDisponible()) return direNaturel(texte, apres);
            direNavigateur(texte, code, apres);
        }

        function direNavigateur(texte, code, apres) {
            if (!window.speechSynthesis) return apres();

            const choisie = voixPour(code || langue);
            if (!choisie) return apres();   // aucune voix dans cette langue : mieux vaut se taire que mal prononcer

            const enonce = new SpeechSynthesisUtterance(texte);
            enonce.voice = choisie;
            enonce.lang = choisie.lang;
            enonce.rate = 1.05;
            enonce.onend = apres;
            enonce.onerror = apres;
            speechSynthesis.speak(enonce);
        }

        /* ---------- Voix naturelle (anglais) ---------- */

        let lecture = 0;        // numéro de la lecture en cours : en changer interrompt la précédente
        let lecteur = null;     // élément <audio> en cours

        // Après un échec (conditions du modèle non acceptées, quota…), on n'insiste pas pendant 5 minutes
        function voixNaturelleDisponible() {
            if (!panel.dataset.parler) return false;
            try { return Date.now() > Number(sessionStorage.getItem('va-voix-ko') || 0); } catch (e) { return true; }
        }

        // Coupe toute parole en cours, quelle que soit la voix
        function taire() {
            lecture++;
            if (lecteur) { lecteur.pause(); lecteur = null; }
            if (window.speechSynthesis) speechSynthesis.cancel();
        }

        // Le service lit de courts textes : on découpe la réponse en phrases, regroupées jusqu'à la limite
        function decouper(texte, limite) {
            const phrases = texte.match(/[^.!?…]+[.!?…]*\s*/g) || [texte];
            const blocs = [];
            let bloc = '';
            phrases.forEach(function (phrase) {
                while (phrase.length > limite) {                       // phrase trop longue : coupe sur un espace
                    let coupe = phrase.lastIndexOf(' ', limite);
                    if (coupe < limite / 2) coupe = limite;
                    if (bloc.trim()) { blocs.push(bloc.trim()); bloc = ''; }
                    blocs.push(phrase.slice(0, coupe).trim());
                    phrase = phrase.slice(coupe);
                }
                if ((bloc + phrase).length > limite) { blocs.push(bloc.trim()); bloc = ''; }
                bloc += phrase;
            });
            if (bloc.trim()) blocs.push(bloc.trim());
            return blocs.filter(Boolean);
        }

        async function sonPour(texte) {
            const reponse = await fetch(panel.dataset.parler, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'audio/*, application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ texte: texte }),
            });
            if (!reponse.ok) throw new Error('voix ' + reponse.status);
            return URL.createObjectURL(await reponse.blob());
        }

        async function direNaturel(texte, apres) {
            const numero = lecture;
            const blocs = decouper(texte, Number(panel.dataset.parlerMax) || 200);
            let suivant = sonPour(blocs[0]);

            for (let i = 0; i < blocs.length; i++) {
                let url;
                try {
                    url = await suivant;
                } catch (e) {
                    // Service indisponible : on termine avec la voix du navigateur, et on n'y revient pas tout de suite
                    try { sessionStorage.setItem('va-voix-ko', String(Date.now() + 5 * 60 * 1000)); } catch (e2) {}
                    if (numero === lecture) direNavigateur(blocs.slice(i).join(' '), 'en', apres);
                    return;
                }
                if (numero !== lecture) return URL.revokeObjectURL(url);   // interrompu entre-temps

                // Le bloc suivant se prépare pendant que celui-ci est lu : pas de blanc entre les phrases
                if (i + 1 < blocs.length) { suivant = sonPour(blocs[i + 1]); suivant.catch(() => {}); }

                await new Promise(function (fini) {
                    lecteur = new Audio(url);
                    lecteur.onended = lecteur.onerror = lecteur.onpause = fini;
                    lecteur.play().catch(fini);
                });
                URL.revokeObjectURL(url);
                if (numero !== lecture) return;
            }
            apres();
        }

        // Après une question posée à quelqu'un qui dicte, on rouvre le micro pour sa réponse
        function reecouter() {
            if (vocal && panel.classList.contains('open') && !occupe) demarrerEcoute();
        }

        /* ---------- Conservation entre les pages ---------- */

        function noter(entree) {
            if (restauration) return entree;
            journal.push(entree);
            journal = journal.slice(-40);
            sauver();
            return entree;
        }

        function sauver() {
            try {
                sessionStorage.setItem('va-etat', JSON.stringify({
                    ouvert: panel.classList.contains('open'), journal: journal, historique: historique,
                    file: file, encore: encore, destination: destination,
                }));
            } catch (e) {}
        }

        function restaurer() {
            let etat = null;
            try { etat = JSON.parse(sessionStorage.getItem('va-etat')); } catch (e) {}
            if (!etat) return;

            restauration = true;
            journal = Array.isArray(etat.journal) ? etat.journal : [];
            historique = Array.isArray(etat.historique) ? etat.historique : [];
            file = Array.isArray(etat.file) ? etat.file : [];
            encore = !!etat.encore;
            destination = etat.destination || null;

            journal.forEach(function (entree, index) {
                if (entree.t === 'msg') bulle(entree.texte, entree.qui, entree.classe);
                else if (entree.t === 'secret') afficherSecret(entree);
                // Seule la dernière proposition peut encore attendre une réponse
                else if (entree.t === 'carte') afficherCarte(entree, index === journal.length - 1);
            });
            restauration = false;

            if (etat.ouvert) {
                panel.classList.add('va-restoring');
                fab.classList.add('va-restoring');
                ouvrir(true);
                requestAnimationFrame(() => requestAnimationFrame(function () {
                    panel.classList.remove('va-restoring');
                    fab.classList.remove('va-restoring');
                }));
            }
            sauver();
        }

        function reinitialiser() {
            arreterEcoute(true);
            taire();
            enAttente = null; journal = []; historique = []; file = []; encore = false; destination = null;
            log.querySelectorAll('.va-msg, .va-card').forEach(el => el.remove());
            document.getElementById('va-intro').hidden = false;
            sauver();
            input.focus();
        }

        /* ---------- Panneau ---------- */

        function ouvrir(sansFocus) {
            panel.classList.add('open');
            panel.setAttribute('aria-hidden', 'false');
            fab.setAttribute('aria-expanded', 'true');
            log.scrollTop = log.scrollHeight;
            if (sansFocus !== true) input.focus();
            sauver();
        }

        function fermer() {
            arreterEcoute(true);
            taire();
            panel.classList.remove('open');
            panel.setAttribute('aria-hidden', 'true');
            fab.setAttribute('aria-expanded', 'false');
            fab.focus();
            sauver();
        }

        function appliquerLangue() {
            panel.querySelectorAll('[data-lang]').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.lang === langue)));
            panel.querySelectorAll('[data-examples]').forEach(e => e.hidden = e.dataset.examples !== langue);
            document.getElementById('va-intro-text').textContent = t('intro');
            input.placeholder = t('saisie');
            if (!enregistrement && !occupe) setStatus(repos());
        }

        /* ---------- Échanges avec le serveur ---------- */

        // Langue, date locale (le serveur peut être sur un autre fuseau) et page affichée
        function contexte() {
            const d = new Date();
            const jour = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            return { langue: langue, aujourdhui: jour, page: window.location.pathname };
        }

        async function poster(url, corps) {
            const estFormulaire = corps instanceof FormData;
            const reponse = await fetch(url, {
                method: 'POST',
                headers: Object.assign({ 'Accept': 'application/json', 'X-CSRF-TOKEN': token }, estFormulaire ? {} : { 'Content-Type': 'application/json' }),
                body: estFormulaire ? corps : JSON.stringify(corps),
            });
            if (reponse.status === 429) return { type: 'message', message: t('trop'), voix: langue };
            if (!reponse.ok) throw new Error('HTTP ' + reponse.status);
            return reponse.json();
        }

        const pause = secondes => new Promise(resoudre => setTimeout(resoudre, secondes * 1000));

        // Interroge le modèle ; si le quota gratuit par minute est atteint, patiente et réessaie seul
        async function interroger(texte) {
            for (let essai = 0; essai < 3; essai++) {
                const r = await poster(panel.dataset.interpreter, Object.assign({ texte: texte, historique: historique }, contexte()));
                if (!r.reessayer || essai === 2) return r;
                for (let reste = r.reessayer; reste > 0; reste--) { setStatus(t('patience')(reste)); await pause(1); }
                setStatus(t('analyse'));
            }
        }

        const normaliser = texte => texte.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9' ]+/g, ' ').trim();
        const OUI = /^(oui|ouais|ok|okay|d'? ?accord|confirme\w*|valide\w*|vas y|c'? ?est bon|parfait|yes|yeah|yep|sure|confirm\w*|do it|go ahead|correct|exactly)\b/;
        const NON = /^(non|annule\w*|stop|laisse tomber|no|nope|cancel\w*|never ?mind)\b/;

        async function envoyer(texte, dicte) {
            texte = texte.trim();
            if (!texte || occupe) return;
            vocal = !!dicte;

            // Une action attend une réponse : un « oui » ou un « non » bref se traite ici, sans passer par l'IA.
            // Une phrase plus longue (« non, plutôt à 10 heures ») est une correction : elle part au modèle.
            if (enAttente) {
                const bref = normaliser(texte);
                if (bref.split(' ').length <= 4) {
                    if (OUI.test(bref)) { bulle(texte, 'user'); return confirmer(); }
                    if (NON.test(bref)) { bulle(texte, 'user'); return annuler(); }
                }
                abandonner();
            }

            bulle(texte, 'user');
            await demander(texte);
        }

        // Envoie une commande au modèle (ou « [suite] » pour poursuivre une demande en plusieurs étapes)
        async function demander(texte) {
            const attente = ajouter(Object.assign(document.createElement('div'), { className: 'va-msg bot', innerHTML: '<span class="va-dots"><span></span><span></span><span></span></span>' }));
            occupe = true;
            setStatus(t('analyse'));

            try {
                const resultat = await interroger(texte);
                attente.remove();
                if (texte !== '[suite]') memoriser('user', texte);
                occupe = false;
                traiter(resultat);
            } catch (e) {
                attente.remove();
                occupe = false;
                bulle(t('reseau'), 'bot');
            } finally {
                if (!enregistrement && !occupe) setStatus(repos());
            }
        }

        function traiter(r) {
            if (r.type === 'navigation') {
                bulle(r.message, 'bot');
                memoriser('assistant', r.message);
                dire(r.message, r.voix);
                return allerVers(r.url);
            }

            if (r.type === 'confirmation') {
                // Commande multiple : « suite » liste les actions suivantes, « encore » annonce une étape dépendante
                if (Array.isArray(r.suite) && r.suite.length) file = r.suite;
                if (r.encore) encore = true;
                memoriser('assistant', 'PROPOSED (waiting for yes/no): ' + r.resume + ' [' + r.details.map(d => d.label + ': ' + d.valeur).join(', ') + ']');
                proposer(r);
                return dire(r.resume, r.voix, reecouter);
            }

            if (r.type === 'succes') return reussir(r);

            // Rien à ajouter : fin d'une demande en plusieurs étapes
            if (r.type === 'rien') return terminer();

            // Question ou explication : la suite prévue n'a plus lieu d'être
            file = []; encore = false;
            memoriser('assistant', r.message);
            bulle(r.message, 'bot');
            dire(r.message, r.voix, /\?\s*$/.test(r.message) ? reecouter : null);
            if (destination) terminer();
        }

        /* ---------- Confirmation ---------- */

        function proposer(r) {
            const entree = noter({ t: 'carte', r: r, fait: false });
            afficherCarte(entree, true);
        }

        function afficherCarte(entree, peutAttendre) {
            const r = entree.r;
            const card = document.createElement('div');
            card.className = 'va-card' + (r.danger ? ' danger' : '');

            const titre = Object.assign(document.createElement('p'), { className: 'va-card-title', textContent: r.resume });
            const liste = document.createElement('dl');
            r.details.forEach(function (detail) {
                liste.appendChild(Object.assign(document.createElement('dt'), { textContent: detail.label }));
                liste.appendChild(Object.assign(document.createElement('dd'), { textContent: detail.valeur }));
            });

            const actions = Object.assign(document.createElement('div'), { className: 'va-card-actions' });
            const indice  = Object.assign(document.createElement('span'), { className: 'va-card-hint', textContent: peutEnregistrer ? t('dites') : '' });
            const non = Object.assign(document.createElement('button'), { type: 'button', className: 'a-btn', textContent: t('annuler') });
            const oui = Object.assign(document.createElement('button'), { type: 'button', className: 'a-btn ' + (r.danger ? 'a-btn-danger-solid' : 'a-btn-primary'), textContent: t('confirmer') });
            non.addEventListener('click', () => annuler());
            oui.addEventListener('click', confirmer);
            actions.append(indice, non, oui);

            card.append(titre, liste, actions);
            ajouter(card);

            if (entree.fait || !peutAttendre) {
                entree.fait = true;
                card.classList.add('done');
                return;
            }

            enAttente = { action: r.action, arguments: r.arguments, card: card, entree: entree };
            if (!restauration) oui.focus();
        }

        // La proposition a reçu sa réponse : elle ne doit plus être proposée après un changement de page
        function clore(attente) {
            attente.card.classList.add('done');
            attente.entree.fait = true;
            sauver();
        }

        // Dans l'historique du modèle, la dernière proposition change d'état (faite, annulée…)
        function marquer(etat) {
            for (let i = historique.length - 1; i >= 0; i--) {
                if (historique[i].role === 'assistant' && historique[i].content.startsWith('PROPOSED')) {
                    historique[i].content = historique[i].content.replace(/^PROPOSED \(waiting for yes\/no\)/, etat);
                    break;
                }
            }
            sauver();
        }

        async function confirmer() {
            if (!enAttente || occupe) return;
            arreterEcoute(true);
            const { action, arguments: args } = enAttente;
            clore(enAttente);
            enAttente = null;
            occupe = true;
            setStatus(t('analyse'));

            try {
                const r = await poster(panel.dataset.executer, Object.assign({ action: action, arguments: args }, contexte()));
                occupe = false;
                if (r.type === 'succes') marquer('DONE');
                else { marquer('FAILED'); file = []; encore = false; }
                traiter(r);
            } catch (e) {
                occupe = false;
                bulle(t('reseau'), 'bot');
            } finally {
                if (!enregistrement && !occupe) setStatus(repos());
            }
        }

        function annuler() {
            if (!enAttente) return;
            arreterEcoute(true);
            clore(enAttente);
            enAttente = null;
            file = []; encore = false;
            marquer('CANCELLED BY THE USER');
            bulle(t('annule'), 'bot');
            dire(t('annule'), langue);
            if (destination) terminer();
        }

        // Une nouvelle commande arrive alors qu'une proposition attend : elle reste « PROPOSED »
        // dans l'historique, pour que le modèle puisse la corriger ou la compléter.
        function abandonner() {
            clore(enAttente);
            enAttente = null;
            file = []; encore = false;
        }

        /* ---------- Réussite et enchaînement ---------- */

        async function reussir(r) {
            if (r.secret) {
                // Un mot de passe vient d'être généré : il faut pouvoir le lire, donc pas de redirection
                afficherSecret(noter({ t: 'secret', message: r.message, secret: r.secret, url: r.url }));
                destination = 'rester';
            } else {
                bulle(r.message, 'bot', 'ok');
                if (destination !== 'rester') destination = r.url;
            }
            sauver();

            // Action suivante d'une commande multiple
            if (file.length) {
                const prochaine = file.shift();
                occupe = true;
                setStatus(t('analyse'));
                try {
                    const suivante = await poster(panel.dataset.preparer, Object.assign({ action: prochaine.action, arguments: prochaine.arguments }, contexte()));
                    occupe = false;
                    return traiter(suivante);
                } catch (e) {
                    occupe = false;
                    file = [];
                    bulle(t('reseau'), 'bot');
                } finally {
                    if (!enregistrement && !occupe) setStatus(repos());
                }
            }

            // Étape qui dépendait de celle-ci (« crée une tournée puis ajoute-lui une collecte »)
            if (encore) {
                encore = false;
                return demander('[suite]');
            }

            dire(r.message, r.voix);
            terminer();
        }

        function afficherSecret(entree) {
            const el = Object.assign(document.createElement('div'), { className: 'va-msg bot ok', textContent: entree.message });
            const ligne = Object.assign(document.createElement('div'), { className: 'va-secret' });
            ligne.append(
                Object.assign(document.createElement('span'), { textContent: entree.secret.label + ' :' }),
                Object.assign(document.createElement('code'), { textContent: entree.secret.valeur }),
                Object.assign(document.createElement('a'), { href: entree.url, textContent: t('ouvrir') }),
            );
            el.appendChild(ligne);
            ajouter(el);
        }

        // La commande est entièrement traitée : on affiche la page concernée
        function terminer() {
            const url = destination;
            destination = null;
            sauver();
            if (url && url !== 'rester') allerVers(url);
        }

        // Le panneau et son contenu sont restaurés sur la page d'arrivée
        function allerVers(url) {
            destination = null;
            sauver();
            setTimeout(() => window.location.href = url, 900);
        }

        /* ---------- Dictée : enregistrement + détection de silence + transcription ---------- */

        async function demarrerEcoute() {
            if (!peutEnregistrer || enregistrement || occupe) return;
            taire();

            let stream;
            try {
                stream = await navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true } });
            } catch (e) {
                vocal = false;
                return bulle(t('micro'), 'bot');
            }

            const type = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg'].find(m => MediaRecorder.isTypeSupported(m));
            const recorder = new MediaRecorder(stream, type ? { mimeType: type } : {});
            const morceaux = [];
            const audio = new (window.AudioContext || window.webkitAudioContext)();
            const analyseur = audio.createAnalyser();
            analyseur.fftSize = 1024;
            audio.createMediaStreamSource(stream).connect(analyseur);
            const echantillons = new Float32Array(analyseur.fftSize);

            const debut = performance.now();
            let aParle = false, derniereVoix = 0, bruit = 0.01, annule = false, boucle;

            function arreter(sansEnvoyer) {
                if (sansEnvoyer) annule = true;
                if (recorder.state !== 'inactive') recorder.stop();
            }

            // Mesure le volume : on s'arrête tout seul après un silence, sans couper une simple hésitation
            function mesurer() {
                analyseur.getFloatTimeDomainData(echantillons);
                let somme = 0;
                for (let i = 0; i < echantillons.length; i++) somme += echantillons[i] * echantillons[i];
                const volume = Math.sqrt(somme / echantillons.length);
                const maintenant = performance.now();
                const ecoule = maintenant - debut;

                if (ecoule < 300) bruit = Math.max(bruit, volume);          // bruit de fond de la pièce
                const seuil = Math.max(0.018, bruit * 2.2);
                if (ecoule >= 300 && volume > seuil) { aParle = true; derniereVoix = maintenant; }

                mic.style.setProperty('--niveau', Math.min(1, volume * 9).toFixed(2));

                if (aParle && maintenant - derniereVoix > 1600) return arreter();   // fin de phrase
                if (!aParle && ecoule > 7000) return arreter();                     // personne ne parle
                if (ecoule > 30000) return arreter();
                boucle = requestAnimationFrame(mesurer);
            }

            recorder.ondataavailable = event => { if (event.data.size) morceaux.push(event.data); };
            recorder.onstop = async function () {
                cancelAnimationFrame(boucle);
                stream.getTracks().forEach(piste => piste.stop());
                audio.close();
                enregistrement = null;
                mic.setAttribute('aria-pressed', 'false');
                mic.style.removeProperty('--niveau');

                if (annule) return setStatus(repos());
                if (!aParle) { vocal = false; return setStatus(t('rien')); }

                // Transcription côté serveur (Whisper) : plus fiable que la dictée du navigateur
                occupe = true;
                setStatus(t('transcription'));
                try {
                    const corps = new FormData();
                    corps.append('audio', new Blob(morceaux, { type: recorder.mimeType || 'audio/webm' }), 'commande.webm');
                    corps.append('langue', langue);
                    const r = await poster(panel.dataset.transcrire, corps);
                    occupe = false;
                    if (r.type === 'texte' && r.texte) return envoyer(r.texte, true);
                    if (r.type === 'texte') { vocal = false; return setStatus(t('rien')); }
                    bulle(r.message, 'bot');
                } catch (e) {
                    occupe = false;
                    bulle(t('reseau'), 'bot');
                } finally {
                    if (!enregistrement && !occupe) setStatus(repos());
                }
            };

            enregistrement = { arreter: arreter };
            mic.setAttribute('aria-pressed', 'true');
            setStatus(t('ecoute'), true);
            recorder.start();
            mesurer();
        }

        function arreterEcoute(sansEnvoyer) {
            if (enregistrement) enregistrement.arreter(sansEnvoyer);
        }

        /* ---------- Événements ---------- */

        fab.addEventListener('click', () => ouvrir());
        document.getElementById('va-close').addEventListener('click', fermer);
        document.getElementById('va-reset').addEventListener('click', reinitialiser);
        mic.addEventListener('click', () => enregistrement ? arreterEcoute() : demarrerEcoute());

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            const texte = input.value;
            input.value = '';
            arreterEcoute(true);
            envoyer(texte, false);
        });

        panel.querySelectorAll('[data-lang]').forEach(b => b.addEventListener('click', function () {
            langue = b.dataset.lang;
            storage.set('va-langue', langue);
            arreterEcoute(true);
            taire();
            appliquerLangue();
        }));

        voice.addEventListener('click', function () {
            parler = !parler;
            storage.set('va-voix', parler ? '1' : '0');
            voice.setAttribute('aria-pressed', String(parler));
            voice.querySelector('i').className = 'fas ' + (parler ? 'fa-volume-high' : 'fa-volume-xmark');
            if (!parler) taire();
        });

        // Les exemples remplissent le champ : on peut les envoyer tels quels ou les adapter
        panel.querySelectorAll('.va-example').forEach(b => b.addEventListener('click', function () {
            input.value = b.textContent;
            input.focus();
        }));

        document.addEventListener('keydown', function (event) {
            if (event.altKey && event.key.toLowerCase() === 'v') { event.preventDefault(); panel.classList.contains('open') ? fermer() : ouvrir(); }
        });
        panel.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') { event.stopPropagation(); fermer(); }
        });

        /* ---------- Initialisation ---------- */

        if (!peutEnregistrer) mic.disabled = true;
        if (window.speechSynthesis) speechSynthesis.getVoices();   // déclenche le chargement des voix, qui est asynchrone
        voice.setAttribute('aria-pressed', String(parler));
        voice.querySelector('i').className = 'fas ' + (parler ? 'fa-volume-high' : 'fa-volume-xmark');
        appliquerLangue();
        restaurer();
    })();
</script>
