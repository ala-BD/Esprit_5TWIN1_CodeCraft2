@extends('upcycling.layouts.upcycling')

@section('title', 'Tableau de production')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('upcycling.projets.index') }}" class="hover:text-primary transition-colors">Projets</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Tableau de production</span>
@endsection

@section('upcycling-content')

@php $enCours = $colonnes->only(\App\Models\ProjetUpcycling::ETAPES_ATELIER)->sum(fn ($c) => $c['projets']->count()); @endphp

<div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="font-display text-3xl font-extrabold text-gray-900">Tableau de production</h1>
        <p class="text-gray-500 mt-1">
            <i class="fas fa-hand-pointer text-primary"></i>
            Glissez une carte vers la colonne suivante pour faire avancer le projet : le client est notifié aussitôt.
        </p>
    </div>
    <div class="flex gap-3">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-2.5 text-center">
            <p class="text-lg font-extrabold text-gray-900" id="stat-en-cours">{{ $enCours }}</p>
            <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">En production</p>
        </div>
        <div class="bg-white rounded-2xl border {{ $enRetard ? 'border-red-200' : 'border-gray-100' }} shadow-sm px-4 py-2.5 text-center">
            <p class="text-lg font-extrabold {{ $enRetard ? 'text-red-500' : 'text-gray-900' }}">{{ $enRetard }}</p>
            <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">En retard</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-4 py-2.5 text-center">
            <p class="text-lg font-extrabold text-emerald-600">{{ number_format($chiffreAffaires, 0, ',', ' ') }} DT</p>
            <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">Devis acceptés</p>
        </div>
    </div>
</div>

<div class="flex gap-4 overflow-x-auto pb-4 -mx-1 px-1 snap-x" id="kanban">
    @foreach($colonnes as $statut => $colonne)
        @php $fixe = $statut === 'ATELIER_CHOISI' || $statut === 'TERMINE'; @endphp
        <section class="kanban-colonne snap-start flex-shrink-0 w-72 rounded-3xl bg-slate-100/80 border border-slate-200/70 p-3 flex flex-col transition-all"
                 aria-label="{{ $colonne['label'] }}">
            <header class="flex items-center justify-between px-1 mb-3">
                <h2 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg flex items-center justify-center text-xs {{ $statut === 'TERMINE' ? 'bg-emerald-500 text-white' : ($fixe ? 'bg-amber-100 text-amber-700' : 'bg-primary/10 text-primary') }}">
                        <i class="fas {{ $colonne['icon'] }}"></i>
                    </span>
                    {{ $colonne['label'] }}
                </h2>
                <span class="text-xs font-bold text-gray-500 bg-white rounded-full px-2 py-0.5" data-compteur="{{ $statut }}">{{ $colonne['projets']->count() }}</span>
            </header>

            <div class="relative flex-1">
                <div class="space-y-3 min-h-[8rem] h-full" data-colonne data-statut="{{ $statut }}">
                    @foreach($colonne['projets'] as $projet)
                        @include('upcycling.projets.partials.kanban-carte', ['projet' => $projet])
                    @endforeach
                </div>
                <p data-vide="{{ $statut }}" class="absolute inset-x-0 top-0 h-32 flex items-center justify-center text-center text-xs text-gray-400 border-2 border-dashed border-slate-200 rounded-2xl pointer-events-none {{ $colonne['projets']->isEmpty() ? '' : 'hidden' }}">
                    {{ $statut === 'ATELIER_CHOISI' ? 'Aucune demande à chiffrer' : 'Déposez une carte ici' }}
                </p>
            </div>
        </section>
    @endforeach
</div>

{{-- Fin des travaux : photo du produit fini (optionnelle) pour le contrôle qualité IA --}}
<div id="modale-terminer" class="hidden fixed inset-0 z-[90] items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm">
    <div class="modale-boite bg-white rounded-3xl shadow-2xl w-full max-w-md p-6" role="dialog" aria-modal="true" aria-labelledby="titre-terminer">
        <h3 id="titre-terminer" class="font-display font-bold text-gray-900 text-lg">Terminer « <span data-titre></span> »</h3>
        <p class="text-sm text-gray-500 mt-1 mb-4">
            Ajoutez une photo du produit fini : le <span class="font-semibold text-violet-600">contrôle qualité IA</span>
            la compare au vêtement d'origine et elle rejoint votre portfolio.
        </p>
        @include('upcycling.partials.photo-input', [
            'nom'     => 'photo_resultat',
            'actuelle' => null,
            'titre'   => 'Photo du produit fini',
            'aide'    => 'Glissez-la ici ou cliquez',
            'hauteur' => 'h-52',
        ])
        <div class="flex justify-end gap-2 mt-5">
            <button type="button" data-annuler class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200">Annuler</button>
            <button type="button" data-valider class="btn-primary !py-2.5 !px-5 !rounded-xl"><i class="fas fa-check-double"></i> Terminer le projet</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tableau = document.getElementById('kanban');
    const listes = [...tableau.querySelectorAll('[data-colonne]')];
    const liste = (statut) => listes.find((l) => l.dataset.statut === statut);

    const majCompteurs = () => {
        listes.forEach((l) => {
            const nombre = l.querySelectorAll('.kanban-carte').length;
            tableau.querySelector(`[data-compteur="${l.dataset.statut}"]`).textContent = nombre;
            tableau.querySelector(`[data-vide="${l.dataset.statut}"]`).classList.toggle('hidden', nombre > 0);
        });
        document.getElementById('stat-en-cours').textContent =
            ['DEVIS_ACCEPTE', 'CONCEPTION', 'CONFECTION', 'FINITION'].reduce((n, s) => n + liste(s).querySelectorAll('.kanban-carte').length, 0);
    };

    /* Modale de fin des travaux → File (avec photo), null (sans), false (annulé) */
    const modale = document.getElementById('modale-terminer');
    const demanderPhoto = (titre) => new Promise((resoudre) => {
        const input = modale.querySelector('input[type=file]');
        input.value = '';
        modale.querySelector('[data-apercu]').classList.add('hidden');
        modale.querySelector('[data-changer]').classList.add('hidden');
        modale.querySelector('[data-taille]').classList.add('hidden');
        modale.querySelector('[data-invite]').classList.remove('hidden');
        modale.querySelector('[data-titre]').textContent = titre;
        modale.classList.replace('hidden', 'flex');

        const fermer = (valeur) => {
            modale.classList.replace('flex', 'hidden');
            modale.querySelector('[data-valider]').onclick = modale.querySelector('[data-annuler]').onclick = null;
            resoudre(valeur);
        };
        modale.querySelector('[data-valider]').onclick = () => fermer(input.files[0] ?? null);
        modale.querySelector('[data-annuler]').onclick = () => fermer(false);
    });

    /* Envoi au serveur ; en cas de refus, la carte revient à sa place */
    const avancer = async (carte, origine, indexOrigine) => {
        const revenir = () => { origine.insertBefore(carte, origine.children[indexOrigine] ?? null); majCompteurs(); };
        const donnees = new FormData();
        donnees.append('_method', 'PATCH');

        if (carte.dataset.suivante === 'TERMINE') {
            const photo = await demanderPhoto(carte.dataset.titre);
            if (photo === false) return revenir();
            if (photo) donnees.append('photo_resultat', photo);
        }

        carte.classList.add('chargement-live');
        try {
            const reponse = await Upcycling.requete(carte.dataset.urlAvancer, { method: 'POST', body: donnees });
            const modele = document.createElement('template');
            modele.innerHTML = reponse.carte.trim();
            const nouvelle = modele.content.firstElementChild;
            nouvelle.classList.add('message-nouveau');
            carte.replaceWith(nouvelle);
            Upcycling.toast(reponse.message, reponse.controle_qualite_ia ? 'ia' : 'success');
        } catch (erreur) {
            carte.classList.remove('chargement-live');
            revenir();
            Upcycling.toast(Upcycling.premiereErreur(erreur, 'photo_resultat'), 'error', 6000);
        }
        majCompteurs();
    };

    /* Glisser-déposer : seule la colonne de l'étape suivante accepte la carte */
    let enGlisse = false;
    // Lâcher une carte sur son lien ne doit pas ouvrir le projet
    tableau.addEventListener('click', (e) => { if (enGlisse && e.target.closest('a')) e.preventDefault(); }, true);

    listes.forEach((l) => Sortable.create(l, {
        group: { name: 'kanban', pull: true, put: (vers, depuis, carte) => carte.dataset.suivante === vers.el.dataset.statut },
        draggable: '.carte-deplacable',
        filter: 'button',
        preventOnFilter: false,
        sort: false,
        forceFallback: true,          // même comportement sur tous les navigateurs (souris et tactile)
        fallbackOnBody: true,         // la carte glissée n'est pas coupée par le défilement horizontal
        animation: 180,
        ghostClass: 'carte-fantome',
        chosenClass: 'carte-choisie',
        onStart: (evt) => {
            enGlisse = true;
            listes.forEach((c) => {
                if (c === evt.from) return;
                c.closest('.kanban-colonne').classList.add(c.dataset.statut === evt.item.dataset.suivante ? 'cible-autorisee' : 'cible-interdite');
            });
        },
        onEnd: (evt) => {
            setTimeout(() => { enGlisse = false; }, 50);
            listes.forEach((c) => c.closest('.kanban-colonne').classList.remove('cible-autorisee', 'cible-interdite'));
            if (evt.to === evt.from) return;

            // Sortable range la carte dans la dernière colonne autorisée survolée :
            // on n'avance que si elle est réellement lâchée sur cette colonne.
            const point = evt.originalEvent?.changedTouches?.[0] ?? evt.originalEvent;
            const sous = point && document.elementFromPoint(point.clientX, point.clientY)?.closest('.kanban-colonne');
            if (point && sous !== evt.to.closest('.kanban-colonne')) {
                evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex] ?? null);
                majCompteurs();
                return;
            }
            majCompteurs();
            avancer(evt.item, evt.from, evt.oldIndex);
        },
    }));

    /* Alternative au glisser-déposer (clavier, mobile) */
    tableau.addEventListener('click', (e) => {
        const bouton = e.target.closest('[data-avancer]');
        if (!bouton) return;
        const carte = bouton.closest('.kanban-carte');
        const origine = carte.parentElement;
        const index = [...origine.children].indexOf(carte);
        liste(carte.dataset.suivante).prepend(carte);
        majCompteurs();
        avancer(carte, origine, index);
    });
});
</script>
@endpush
