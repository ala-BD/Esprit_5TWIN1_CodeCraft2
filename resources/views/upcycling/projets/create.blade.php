@extends('upcycling.layouts.upcycling')

@section('title', 'Demander un upcycling')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('upcycling.projets.index') }}" class="hover:text-primary transition-colors">Projets</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Nouvelle demande</span>
@endsection

@section('upcycling-content')

<div class="mb-8">
    <h1 class="font-display text-3xl font-extrabold text-gray-900">Donnez une seconde vie à un vêtement</h1>
    <p class="text-gray-500 mt-1">Photographiez-le, l'IA le reconnaît et imagine 3 transformations sur mesure.</p>
</div>

{{-- Étapes --}}
<ol class="flex flex-wrap items-center gap-2 sm:gap-4 text-xs font-semibold mb-8">
    @foreach(['Photo', 'Analyse IA', 'Détails', '3 idées'] as $i => $etape)
        <li class="flex items-center gap-2 {{ $i === 0 ? 'text-primary' : 'text-gray-400' }}">
            <span class="w-6 h-6 rounded-full flex items-center justify-center {{ $i === 0 ? 'bg-primary text-white' : 'bg-gray-100' }}">{{ $i + 1 }}</span>
            {{ $etape }}
            @unless($loop->last)<i class="fas fa-chevron-right text-gray-300 text-[10px] ml-1 sm:ml-2"></i>@endunless
        </li>
    @endforeach
</ol>

<form method="POST" action="{{ route('upcycling.projets.store') }}" enctype="multipart/form-data" id="form-projet" novalidate>
    @csrf

    <div class="grid lg:grid-cols-5 gap-6">

        {{-- ===== Colonne photo + IA ===== --}}
        <div class="lg:col-span-2 space-y-4">
            @include('upcycling.partials.photo-input', [
                'nom'     => 'photo',
                'actuelle'=> null,
                'titre'   => 'Ajoutez une photo du vêtement',
                'aide'    => 'Glissez-déposez ou cliquez pour choisir',
                'hauteur' => 'h-80',
            ])

            @if($iaActivee)
                <button type="button" id="btn-analyser" disabled
                        class="w-full flex items-center justify-center gap-2 py-3.5 rounded-2xl font-bold text-white
                               bg-gradient-to-r from-violet-600 via-fuchsia-500 to-pink-500 shadow-lg shadow-violet-200
                               hover:shadow-xl hover:scale-[1.01] transition-all disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:scale-100">
                    <i class="fas fa-wand-magic-sparkles"></i>
                    <span data-label>Analyser la photo avec l'IA</span>
                </button>

                {{-- Résultat de l'analyse --}}
                <div id="resultat-analyse" class="hidden bg-white rounded-3xl border border-violet-100 shadow-sm p-5">
                    <p class="text-xs font-bold uppercase tracking-wider text-violet-600 mb-3"><i class="fas fa-eye mr-1"></i> Ce que voit l'IA</p>
                    <p data-description class="text-sm text-gray-700 leading-relaxed"></p>
                    <div data-defauts class="flex flex-wrap gap-1.5 mt-3"></div>
                </div>
                <p id="erreur-analyse" class="hidden text-sm text-red-600 bg-red-50 border border-red-100 rounded-2xl p-3"></p>
            @else
                <div class="bg-amber-50 border border-amber-100 rounded-2xl p-4 text-xs text-amber-700">
                    <i class="fas fa-info-circle mr-1"></i>
                    Analyse IA indisponible (clé <code>GEMINI_API_KEY</code> absente) : les idées viendront du générateur local.
                </div>
            @endif
        </div>

        {{-- ===== Colonne formulaire ===== --}}
        <div class="lg:col-span-3 space-y-5">
            @include('upcycling.projets._form', ['projet' => new \App\Models\ProjetUpcycling()])

            @if($dons->isNotEmpty())
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6">
                <h2 class="font-display font-bold text-gray-900 flex items-center gap-2 mb-1">
                    <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center"><i class="fas fa-hand-holding-heart text-primary text-sm"></i></span>
                    Partir d'un don <span class="text-gray-400 font-normal text-sm">(optionnel)</span>
                </h2>
                <p class="text-gray-500 text-xs mb-4 ml-10">Transformez un vêtement donné sur la plateforme et en attente de tri.</p>
                <select name="don_vetement_id"
                        class="w-full px-4 py-3 rounded-xl border {{ $errors->has('don_vetement_id') ? 'border-red-400 bg-red-50' : 'border-gray-200' }} focus:outline-none focus:ring-2 focus:ring-primary text-sm text-gray-700">
                    <option value="">— Mon propre vêtement —</option>
                    @foreach($dons as $don)
                        <option value="{{ $don->id }}" @selected(old('don_vetement_id') == $don->id)>
                            Don #{{ $don->id }} — {{ $don->type }} en {{ $don->matiere }}, taille {{ $don->taille }} ({{ $don->etat }})
                        </option>
                    @endforeach
                </select>
                @error('don_vetement_id')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
            </div>
            @endif

            <div class="flex gap-3">
                <button type="submit" class="btn-primary flex-1 !py-4 !rounded-2xl text-base">
                    <i class="fas fa-lightbulb"></i> Générer mes 3 idées
                </button>
                <a href="{{ route('upcycling.projets.index') }}"
                   class="px-6 py-4 rounded-2xl border-2 border-gray-200 text-gray-600 font-semibold hover:bg-gray-50 transition-all flex items-center gap-2">
                    Annuler
                </a>
            </div>
        </div>
    </div>
</form>

{{-- Écran d'attente pendant la génération --}}
<div id="attente-ia" class="hidden fixed inset-0 z-[60] bg-navy-dark/80 backdrop-blur-sm flex items-center justify-center p-6">
    <div class="bg-white rounded-3xl shadow-2xl p-10 max-w-sm w-full text-center">
        <div class="relative w-20 h-20 mx-auto mb-6">
            <div class="absolute inset-0 rounded-full bg-gradient-to-r from-violet-500 to-pink-500 animate-ping opacity-30"></div>
            <div class="relative w-20 h-20 rounded-full bg-gradient-to-r from-violet-600 to-pink-500 flex items-center justify-center">
                <i class="fas fa-wand-magic-sparkles text-white text-2xl animate-pulse"></i>
            </div>
        </div>
        <p class="font-display font-bold text-xl text-gray-900">L'IA imagine vos transformations…</p>
        <p class="text-sm text-gray-500 mt-2">Analyse du vêtement, recherche d'idées, estimation des prix. Quelques secondes.</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('form-projet');
    const bouton = document.getElementById('btn-analyser');
    const zone = document.querySelector('[data-zone-photo]');

    form.addEventListener('submit', () => document.getElementById('attente-ia').classList.remove('hidden'));

    if (!bouton) return;

    zone.addEventListener('photo-choisie', () => { bouton.disabled = false; });

    const remplir = (id, valeur) => {
        const champ = document.getElementById(id);
        if (!champ || !valeur) return;
        champ.value = valeur;
        champ.classList.add('ring-2', 'ring-violet-400', 'bg-violet-50');
        setTimeout(() => champ.classList.remove('ring-2', 'ring-violet-400', 'bg-violet-50'), 2000);
    };

    bouton.addEventListener('click', async () => {
        const fichier = zone.querySelector('input[type=file]').files[0];
        if (!fichier) return;

        const label = bouton.querySelector('[data-label]');
        const erreur = document.getElementById('erreur-analyse');
        bouton.disabled = true;
        label.textContent = "Analyse en cours…";
        bouton.querySelector('i').className = 'fas fa-circle-notch fa-spin';
        erreur.classList.add('hidden');

        const donnees = new FormData();
        donnees.append('photo', fichier);

        try {
            const reponse = await fetch(@json(route('upcycling.analyse-photo')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                body: donnees,
            });
            const json = await reponse.json();
            if (!reponse.ok) throw new Error(json.message || (json.errors && Object.values(json.errors)[0][0]) || 'Analyse impossible.');

            const a = json.analyse;
            remplir('type_vetement', a.type_vetement);
            remplir('matiere', a.matiere);
            remplir('couleur', a.couleur);
            const description = document.getElementById('description');
            if (!description.value.trim()) remplir('description', a.description);
            const etat = form.querySelector(`input[name=etat][value="${a.etat}"]`);
            if (etat) etat.checked = true;

            const panneau = document.getElementById('resultat-analyse');
            panneau.querySelector('[data-description]').textContent = a.description;
            const defauts = panneau.querySelector('[data-defauts]');
            defauts.innerHTML = '';
            const pastille = (texte, classe) => {
                const span = document.createElement('span');
                span.className = 'text-[11px] font-semibold px-2.5 py-1 rounded-full ' + classe;
                span.textContent = texte;
                defauts.appendChild(span);
            };
            [a.type_vetement, a.matiere, a.couleur].filter(Boolean).forEach((t) => pastille(t, 'bg-violet-50 text-violet-700'));
            (a.defauts.length ? a.defauts : ['Aucun défaut visible']).forEach((d) => pastille(d, a.defauts.length ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'));
            panneau.classList.remove('hidden');
            document.getElementById('badge-prerempli').classList.remove('hidden');
            label.textContent = 'Analyser à nouveau';
        } catch (e) {
            erreur.textContent = e.message;
            erreur.classList.remove('hidden');
            label.textContent = "Analyser la photo avec l'IA";
        } finally {
            bouton.disabled = false;
            bouton.querySelector('i').className = 'fas fa-wand-magic-sparkles';
        }
    });
});
</script>

@endsection
