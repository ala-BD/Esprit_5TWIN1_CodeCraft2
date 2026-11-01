{{-- Discussion du projet : client, atelier et styliste IA (temps réel en AJAX, repli formulaire classique) --}}
@php
    $monRole      = $estProprietaire ? 'CLIENT' : 'ATELIER';
    $autrePartie  = $estProprietaire ? $projet->atelier?->nom : $projet->client->full_name;
    $peutEcrire   = $projet->statut !== 'ANNULE';
    $suggestions  = $projet->produit_final
        ? ['Quelle doublure ou finition choisir ?', 'Comment entretenir le produit fini ?', 'Comment réduire le coût ?']
        : ['Quelle idée convient le mieux à mon usage ?', 'Mon vêtement est-il assez solide ?', 'Quelle idée est la plus écologique ?'];
@endphp

<section id="discussion" class="bg-white rounded-3xl shadow-sm border border-gray-100 mt-6 overflow-hidden"
         data-url-messages="{{ route('upcycling.messages.index', $projet) }}"
         data-dernier-id="{{ $projet->messages->max('id') ?? 0 }}">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <h2 class="font-display font-bold text-gray-900 flex items-center gap-2">
                <i class="fas fa-comments text-primary"></i> Discussion du projet
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">
                {{ $autrePartie ? "Échangez avec {$autrePartie}, ou" : 'En attendant de choisir un atelier,' }}
                posez vos questions au <span class="font-semibold text-violet-600">styliste IA</span>, qui connaît tout votre projet.
            </p>
        </div>
        <span class="text-xs text-gray-400 flex items-center gap-2">
            <span class="inline-flex items-center gap-1 text-emerald-600 font-semibold" title="Les nouveaux messages s'affichent automatiquement">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> En direct
            </span>
            · <span id="compteur-messages">{{ $projet->messages->count() }}</span> message(s)
        </span>
    </div>

    {{-- Messages --}}
    <div id="fil-messages" class="px-6 py-5 space-y-4 max-h-[28rem] overflow-y-auto bg-slate-50/60" aria-live="polite">
        @foreach($projet->messages as $message)
            @include('upcycling.projets.partials.message', ['message' => $message, 'monRole' => $monRole])
        @endforeach

        <div id="discussion-vide" class="text-center py-8 {{ $projet->messages->isEmpty() ? '' : 'hidden' }}">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-violet-100 to-pink-100 flex items-center justify-center mx-auto mb-3">
                <i class="fas fa-wand-magic-sparkles text-violet-500 text-xl"></i>
            </div>
            <p class="text-sm font-semibold text-gray-700">Aucun message pour l'instant</p>
            <p class="text-xs text-gray-500 mt-1">Une question sur votre projet ? Le styliste IA vous répond tout de suite.</p>
        </div>

        <div id="ia-redige" class="hidden flex gap-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-600 to-pink-500 text-white flex items-center justify-center text-xs"><i class="fas fa-wand-magic-sparkles"></i></div>
            <div class="bulle-ia bg-violet-50 border border-violet-100 rounded-2xl px-4 py-3 text-violet-500 text-sm flex items-center gap-2">
                <span class="points-ia text-lg leading-none"><span>●</span><span>●</span><span>●</span></span>
                <span class="text-xs font-semibold">Le styliste IA rédige sa réponse…</span>
            </div>
        </div>
    </div>

    {{-- Saisie --}}
    @if($peutEcrire)
    <form method="POST" action="{{ route('upcycling.messages.store', $projet) }}" id="form-message" class="px-6 py-4 border-t border-gray-100" novalidate>
        @csrf
        <div class="flex flex-wrap gap-2 mb-3">
            @foreach($suggestions as $suggestion)
                <button type="button" data-suggestion="{{ $suggestion }}"
                        class="text-[11px] font-semibold px-3 py-1.5 rounded-full bg-violet-50 text-violet-700 hover:bg-violet-100 transition-colors">
                    <i class="fas fa-lightbulb mr-1"></i>{{ $suggestion }}
                </button>
            @endforeach
        </div>

        <textarea name="contenu" id="contenu-message" rows="2" maxlength="1000"
                  placeholder="{{ $autrePartie ? "Votre message à {$autrePartie} ou votre question au styliste IA…" : 'Votre question au styliste IA…' }}"
                  class="w-full px-4 py-3 rounded-2xl border {{ $errors->has('contenu') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} focus:outline-none focus:ring-2 focus:ring-primary text-sm resize-none">{{ old('contenu') }}</textarea>
        <p id="erreur-message" class="mt-1 text-xs text-red-500 {{ $errors->has('contenu') ? '' : 'hidden' }}">@error('contenu'){{ $message }}@enderror</p>

        <div class="flex flex-wrap items-center justify-between gap-2 mt-3">
            <p class="text-[11px] text-gray-400">
                <kbd class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-200 font-sans">Ctrl</kbd> +
                <kbd class="px-1.5 py-0.5 rounded bg-gray-100 border border-gray-200 font-sans">Entrée</kbd> pour demander au styliste
                · <span id="compteur-caracteres">0</span>/1000
            </p>
            <div class="flex flex-wrap justify-end gap-2">
                <button type="submit" name="ia" value="1" data-bouton-ia
                        class="inline-flex items-center gap-2 text-sm font-bold text-white px-5 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-pink-500 hover:shadow-lg transition-all disabled:opacity-60 disabled:cursor-wait">
                    <i class="fas fa-wand-magic-sparkles"></i> Demander au styliste IA
                </button>
                @if($autrePartie)
                    <button type="submit" name="ia" value="0" class="btn-primary !py-2.5 !px-5 !rounded-xl">
                        <i class="fas fa-paper-plane"></i> Envoyer à {{ \Illuminate\Support\Str::limit($autrePartie, 22) }}
                    </button>
                @endif
            </div>
        </div>
    </form>
    @endif
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const section = document.getElementById('discussion');
    const fil = document.getElementById('fil-messages');
    const redige = document.getElementById('ia-redige');
    const vide = document.getElementById('discussion-vide');
    const compteur = document.getElementById('compteur-messages');
    let dernierId = Number(section.dataset.dernierId);
    let envoiEnCours = false;

    fil.scrollTop = fil.scrollHeight;

    const presDuBas = () => fil.scrollHeight - fil.scrollTop - fil.clientHeight < 120;

    /** Ajoute les bulles reçues du serveur (sans doublon) */
    const ajouter = (donnees, { defiler = true } = {}) => {
        if (!donnees.html) return;
        const modele = document.createElement('template');
        modele.innerHTML = donnees.html;
        modele.content.querySelectorAll('[data-message-id]').forEach((bulle) => {
            if (!fil.querySelector(`[data-message-id="${bulle.dataset.messageId}"]`)) fil.insertBefore(bulle, redige);
        });
        dernierId = Math.max(dernierId, donnees.dernier_id ?? 0);
        compteur.textContent = donnees.total;
        vide.classList.add('hidden');
        if (defiler) fil.scrollTo({ top: fil.scrollHeight, behavior: 'smooth' });
    };

    /* Mise à jour automatique (toutes les 6 s, seulement si l'onglet est visible) */
    const actualiser = async () => {
        if (envoiEnCours || document.hidden || !window.Upcycling) return;
        try {
            const url = new URL(section.dataset.urlMessages);
            url.searchParams.set('apres', dernierId);
            const donnees = await Upcycling.requete(url);
            if (donnees.ids.length) {
                const defiler = presDuBas();
                ajouter(donnees, { defiler });
                if (!defiler) Upcycling.toast('Nouveau message dans la discussion.', 'info');
            }
        } catch { /* réseau indisponible : on réessaiera au prochain tour */ }
    };
    setInterval(actualiser, 6000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) actualiser(); });

    /* Envoi */
    const form = document.getElementById('form-message');
    if (!form || !window.Upcycling) return;
    const champ = document.getElementById('contenu-message');
    const erreur = document.getElementById('erreur-message');
    const caracteres = document.getElementById('compteur-caracteres');
    const boutons = form.querySelectorAll('button[type=submit]');

    const majCompteur = () => { caracteres.textContent = champ.value.length; };
    champ.addEventListener('input', () => { majCompteur(); erreur.classList.add('hidden'); champ.classList.remove('border-red-400', 'bg-red-50'); });
    majCompteur();

    form.querySelectorAll('[data-suggestion]').forEach((bouton) => {
        bouton.addEventListener('click', () => { champ.value = bouton.dataset.suggestion; majCompteur(); champ.focus(); });
    });

    champ.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            form.requestSubmit(form.querySelector('[data-bouton-ia]'));
        }
    });

    const afficherErreur = (message) => {
        erreur.textContent = message;
        erreur.classList.remove('hidden');
        champ.classList.add('border-red-400', 'bg-red-50');
    };

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (envoiEnCours) return;

        const contenu = champ.value.trim();
        if (contenu.length < 2) return afficherErreur(contenu ? 'Le message est trop court.' : 'Écrivez votre message.');

        const demanderIa = e.submitter?.value !== '0';
        const donnees = new FormData();
        donnees.append('contenu', contenu);
        donnees.append('ia', demanderIa ? '1' : '0');

        envoiEnCours = true;
        boutons.forEach((b) => { b.disabled = true; });
        champ.value = '';
        majCompteur();
        if (demanderIa) {
            redige.classList.remove('hidden');
            vide.classList.add('hidden');
            fil.scrollTo({ top: fil.scrollHeight, behavior: 'smooth' });
        }

        try {
            const reponse = await Upcycling.requete(form.action, { method: 'POST', body: donnees });
            ajouter(reponse);
            if (!demanderIa) Upcycling.toast('Message envoyé.', 'success', 2500);
        } catch (err) {
            champ.value = contenu;                 // on rend le texte à l'utilisateur
            majCompteur();
            afficherErreur(Upcycling.premiereErreur(err, 'contenu'));
        } finally {
            envoiEnCours = false;
            redige.classList.add('hidden');
            boutons.forEach((b) => { b.disabled = false; });
            champ.focus();
        }
    });
});
</script>
