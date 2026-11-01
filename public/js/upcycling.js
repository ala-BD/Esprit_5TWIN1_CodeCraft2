/**
 * Kit front du module Upcycling (M3) — JavaScript natif, sans build.
 *
 *  - Upcycling.requete()          : fetch JSON avec jeton CSRF et gestion des erreurs Laravel (422, 429...)
 *  - Upcycling.toast()            : notifications éphémères
 *  - Upcycling.confirmer()        : modale de confirmation (remplace window.confirm)
 *  - <form data-confirm="...">    : confirmation automatique avant l'envoi d'un formulaire
 *  - Upcycling.compresserImage()  : redimensionne une photo dans le navigateur avant l'envoi
 *  - <form data-recherche-live>   : recherche et filtres sans rechargement de la page
 */
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    /* ------------------------------------------------------------------
     | Requêtes AJAX
     * ------------------------------------------------------------------ */
    async function requete(url, { method = 'GET', body = null, accept = 'application/json' } = {}) {
        let reponse;
        try {
            reponse = await fetch(url, {
                method,
                body,
                credentials: 'same-origin',
                headers: { Accept: accept, 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
            });
        } catch {
            throw Object.assign(new Error('Connexion impossible. Vérifiez votre réseau.'), { status: 0, erreurs: {} });
        }

        if (accept !== 'application/json') {
            if (!reponse.ok) throw Object.assign(new Error('Chargement impossible.'), { status: reponse.status, erreurs: {} });
            return reponse.text();
        }

        const donnees = await reponse.json().catch(() => ({}));
        if (!reponse.ok) {
            const messages = {
                419: 'Votre session a expiré : rechargez la page.',
                429: 'Trop de demandes : patientez quelques secondes.',
                500: 'Erreur du serveur. Réessayez plus tard.',
            };
            const erreur = new Error(donnees.message || messages[reponse.status] || 'Une erreur est survenue.');
            erreur.status = reponse.status;
            erreur.erreurs = donnees.errors || {};
            throw erreur;
        }
        return donnees;
    }

    /** Première erreur de validation d'un champ (ou message global) */
    const premiereErreur = (erreur, champ) => erreur.erreurs?.[champ]?.[0] ?? erreur.message;

    /* ------------------------------------------------------------------
     | Toasts
     * ------------------------------------------------------------------ */
    const STYLES_TOAST = {
        success: ['fa-circle-check', 'text-emerald-500'],
        error:   ['fa-circle-exclamation', 'text-red-500'],
        info:    ['fa-bell', 'text-teal-600'],
        ia:      ['fa-wand-magic-sparkles', 'text-violet-500'],
    };

    function conteneurToasts() {
        let conteneur = document.getElementById('upcycling-toasts');
        if (!conteneur) {
            conteneur = document.createElement('div');
            conteneur.id = 'upcycling-toasts';
            conteneur.setAttribute('aria-live', 'polite');
            conteneur.className = 'fixed bottom-5 right-5 z-[100] flex flex-col gap-2 w-[min(22rem,calc(100vw-2.5rem))]';
            document.body.appendChild(conteneur);
        }
        return conteneur;
    }

    function toast(message, type = 'success', duree = 4500) {
        const [icone, couleur] = STYLES_TOAST[type] ?? STYLES_TOAST.info;
        const element = document.createElement('div');
        element.className = 'toast-upcycling flex items-start gap-3 bg-white border border-gray-100 shadow-xl rounded-2xl px-4 py-3';
        element.innerHTML = `<i class="fas ${icone} ${couleur} mt-0.5"></i>
            <p class="text-sm text-gray-700 leading-snug flex-1"></p>
            <button type="button" class="text-gray-300 hover:text-gray-500" aria-label="Fermer"><i class="fas fa-times text-xs"></i></button>`;
        element.querySelector('p').textContent = message;

        const fermer = () => {
            element.classList.add('toast-sortie');
            setTimeout(() => element.remove(), 250);
        };
        element.querySelector('button').addEventListener('click', fermer);
        conteneurToasts().appendChild(element);
        setTimeout(fermer, duree);
    }

    /* ------------------------------------------------------------------
     | Modale de confirmation
     * ------------------------------------------------------------------ */
    function confirmer(message, { titre = 'Confirmer', bouton = 'Confirmer', danger = true } = {}) {
        return new Promise((resoudre) => {
            const fond = document.createElement('div');
            fond.className = 'modale-upcycling fixed inset-0 z-[90] flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm';
            fond.innerHTML = `
                <div role="dialog" aria-modal="true" class="modale-boite bg-white rounded-3xl shadow-2xl w-full max-w-md p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 ${danger ? 'bg-red-50 text-red-500' : 'bg-teal-50 text-teal-600'}">
                            <i class="fas ${danger ? 'fa-triangle-exclamation' : 'fa-circle-question'} text-lg"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-display font-bold text-gray-900 text-lg" data-titre></h3>
                            <p class="text-sm text-gray-500 mt-1 leading-relaxed" data-message></p>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" data-annuler class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">Annuler</button>
                        <button type="button" data-valider class="px-4 py-2.5 rounded-xl text-sm font-bold text-white transition-colors ${danger ? 'bg-red-500 hover:bg-red-600' : 'bg-teal-600 hover:bg-teal-700'}"></button>
                    </div>
                </div>`;
            fond.querySelector('[data-titre]').textContent = titre;
            fond.querySelector('[data-message]').textContent = message;
            fond.querySelector('[data-valider]').textContent = bouton;

            const precedent = document.activeElement;
            const terminer = (valeur) => {
                document.removeEventListener('keydown', clavier);
                fond.remove();
                precedent?.focus?.();
                resoudre(valeur);
            };
            const clavier = (e) => { if (e.key === 'Escape') terminer(false); };

            fond.addEventListener('click', (e) => { if (e.target === fond) terminer(false); });
            fond.querySelector('[data-annuler]').addEventListener('click', () => terminer(false));
            fond.querySelector('[data-valider]').addEventListener('click', () => terminer(true));
            document.addEventListener('keydown', clavier);

            document.body.appendChild(fond);
            fond.querySelector('[data-valider]').focus();
        });
    }

    // <form data-confirm="Message" data-confirm-titre="..." data-confirm-bouton="...">
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!form.matches?.('form[data-confirm]') || form.dataset.confirme === '1') return;

        e.preventDefault();
        const ok = await confirmer(form.dataset.confirm, {
            titre:  form.dataset.confirmTitre || 'Confirmer',
            bouton: form.dataset.confirmBouton || 'Confirmer',
            danger: form.dataset.confirmDanger !== '0',
        });
        if (!ok) return;

        form.dataset.confirme = '1';
        form.requestSubmit(e.submitter ?? undefined);
    });

    /* ------------------------------------------------------------------
     | Compression d'image côté navigateur
     | (photos de smartphone de 4 à 8 Mo → ~300 Ko : envoi et analyse IA plus rapides)
     * ------------------------------------------------------------------ */
    async function compresserImage(fichier, { max = 1600, qualite = 0.85 } = {}) {
        if (!fichier?.type?.match(/^image\/(jpeg|png|webp)$/)) return fichier;

        let image;
        try {
            image = await createImageBitmap(fichier);
        } catch {
            return fichier;
        }

        const echelle = Math.min(1, max / Math.max(image.width, image.height));
        if (echelle === 1 && fichier.size < 700 * 1024) return fichier;

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(image.width * echelle);
        canvas.height = Math.round(image.height * echelle);
        const contexte = canvas.getContext('2d');
        contexte.fillStyle = '#ffffff';                       // fond blanc pour les PNG transparents
        contexte.fillRect(0, 0, canvas.width, canvas.height);
        contexte.drawImage(image, 0, 0, canvas.width, canvas.height);

        const blob = await new Promise((r) => canvas.toBlob(r, 'image/jpeg', qualite));
        if (!blob || blob.size >= fichier.size) return fichier;

        return new File([blob], fichier.name.replace(/\.\w+$/, '') + '.jpg', { type: 'image/jpeg', lastModified: Date.now() });
    }

    /** Remplace le fichier d'un <input type=file> */
    function remplacerFichier(input, fichier) {
        const transfert = new DataTransfer();
        transfert.items.add(fichier);
        input.files = transfert.files;
    }

    const formaterTaille = (octets) => octets >= 1024 * 1024
        ? (octets / 1024 / 1024).toFixed(1).replace('.', ',') + ' Mo'
        : Math.max(1, Math.round(octets / 1024)) + ' Ko';

    /* ------------------------------------------------------------------
     | Recherche live
     | <form data-recherche-live> + zones [data-live-region id="..."] remplacées
     | sans recharger la page ; les liens des zones [data-live-liens] (filtres,
     | pagination) sont aussi chargés en AJAX. L'URL reste partageable.
     * ------------------------------------------------------------------ */
    function initialiserRechercheLive(form) {
        let minuteur = null;
        let controleur = null;

        const charger = async (url, { historique = 'push' } = {}) => {
            controleur?.abort();
            controleur = new AbortController();
            const regions = document.querySelectorAll('[data-live-region]');
            regions.forEach((r) => r.classList.add('chargement-live'));

            try {
                const reponse = await fetch(url, {
                    signal: controleur.signal,
                    credentials: 'same-origin',
                    headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!reponse.ok) throw new Error();
                const page = new DOMParser().parseFromString(await reponse.text(), 'text/html');

                regions.forEach((region) => {
                    const nouvelle = page.getElementById(region.id);
                    if (nouvelle) region.innerHTML = nouvelle.innerHTML;
                });
                if (historique === 'push') history.pushState({ live: true }, '', url);
                if (historique === 'replace') history.replaceState({ live: true }, '', url);
                synchroniserChamps(new URL(url, location.href));
            } catch (erreur) {
                if (erreur.name !== 'AbortError') location.href = url;   // repli : navigation classique
            } finally {
                regions.forEach((r) => r.classList.remove('chargement-live'));
            }
        };

        const urlDuFormulaire = () => {
            const url = new URL(form.action || location.href, location.href);
            url.search = '';
            new FormData(form).forEach((valeur, cle) => { if (String(valeur).trim() !== '') url.searchParams.set(cle, valeur); });
            return url.toString();
        };

        // Les champs cachés (filtre actif) suivent l'URL chargée
        const synchroniserChamps = (url) => {
            form.querySelectorAll('input[type=hidden][name]').forEach((champ) => {
                champ.value = url.searchParams.get(champ.name) ?? '';
            });
            const recherche = form.querySelector('input[type=text][name], input[type=search][name]');
            if (recherche && document.activeElement !== recherche) recherche.value = url.searchParams.get(recherche.name) ?? '';
        };

        form.addEventListener('input', (e) => {
            if (!e.target.matches('input[type=text], input[type=search]')) return;
            clearTimeout(minuteur);
            minuteur = setTimeout(() => charger(urlDuFormulaire(), { historique: 'replace' }), 350);
        });
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            clearTimeout(minuteur);
            charger(urlDuFormulaire());
        });
        document.addEventListener('click', (e) => {
            const lien = e.target.closest('[data-live-liens] a[href]');
            if (!lien || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
            e.preventDefault();
            charger(lien.href);
        });
        window.addEventListener('popstate', () => charger(location.href, { historique: 'none' }));
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('form[data-recherche-live]').forEach(initialiserRechercheLive);
    });

    window.Upcycling = { requete, premiereErreur, toast, confirmer, compresserImage, remplacerFichier, formaterTaille };
})();
