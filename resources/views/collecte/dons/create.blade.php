@extends('layouts.app-auth')

@section('title', 'Déclarer un don')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-10">
    <div class="mb-6">

        <h1 class="mt-2 text-3xl font-bold text-slate-900">Déclarer un don de vêtement</h1>
    </div>

        <form id="don-form" action="{{ route('collecte.dons.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <input type="hidden" name="duplicate_check_token" id="duplicate-check-token">
            <input type="hidden" name="duplicate_confirmed" id="duplicate-confirmed" value="0">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="type" class="mb-2 block text-sm font-medium text-slate-700">Type de vêtement</label>
                    <input id="type" name="type" value="{{ old('type') }}" placeholder="Ex. Chemise" required maxlength="100" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="matiere" class="mb-2 block text-sm font-medium text-slate-700">Matière</label>
                    <input id="matiere" name="matiere" value="{{ old('matiere') }}" placeholder="Ex. Coton" required maxlength="100" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @error('matiere') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="couleur" class="mb-2 block text-sm font-medium text-slate-700">Couleur</label>
                    <input id="couleur" name="couleur" value="{{ old('couleur') }}" placeholder="Ex. Bleu marine" required maxlength="100" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @error('couleur') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="taille" class="mb-2 block text-sm font-medium text-slate-700">Taille</label>
                    <input id="taille" name="taille" value="{{ old('taille') }}" placeholder="Ex. M, 40, unique" required maxlength="50" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                    @error('taille') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-slate-500">La taille proposée par l’IA est une estimation visuelle. Vérifiez-la ou corrigez-la avant d’enregistrer.</p>
                </div>
                <div>
                    <label for="etat" class="mb-2 block text-sm font-medium text-slate-700">État</label>
                    <select id="etat" name="etat" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                        <option value="">Choisir l’état</option>
                        @foreach(['Neuf', 'Très bon', 'Bon', 'À réparer'] as $etat)
                            <option value="{{ $etat }}" @selected(old('etat') === $etat)>{{ $etat }}</option>
                        @endforeach
                    </select>
                    @error('etat') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="photo" class="mb-2 block text-sm font-medium text-slate-700">Photo du vêtement</label>
                    <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required class="peer sr-only">
                    <label for="photo" class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-dark hover:shadow-md peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary">
                        <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
                        <span>Choisir une photo</span>
                    </label>
                    <span id="photo-selection-status" class="ml-3 text-sm text-slate-500" aria-live="polite">Aucune photo choisie</span>
                    <p class="mt-1 text-xs text-slate-500">Formats acceptés : JPEG, PNG ou WebP. Taille maximale : 5 Mo.</p>
                    @error('photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <div id="photo-preview-container" class="mt-4 hidden">
                        <img id="photo-preview" alt="Aperçu de la photo sélectionnée" class="max-h-80 w-full rounded-2xl border border-slate-200 bg-slate-50 object-contain p-2">
                    </div>
                    <button id="analyser-photo" type="button" disabled class="mt-3 rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-semibold text-violet-700 hover:bg-violet-100 disabled:cursor-not-allowed disabled:opacity-50">
                        <span data-label>Suggérer les informations avec l’IA</span>
                    </button>
                    <p class="mt-1 text-xs text-slate-500">La photo peut être envoyée à Gemini pour analyse et comparaison avec vos propres anciens dons. Aucune photo d’un autre donateur n’est utilisée. Vérifiez les suggestions avant de valider.</p>
                    <p id="resultat-analyse" role="status" class="mt-2 hidden text-sm text-emerald-700"></p>
                    <p id="erreur-analyse" role="alert" class="mt-2 hidden text-sm text-red-600"></p>
                    <fieldset class="mt-4 rounded-xl border border-slate-200 p-4">
                        <legend class="px-2 text-sm font-semibold text-slate-700">Mode de traitement de la photo</legend>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="cursor-pointer rounded-xl border border-slate-200 p-3 has-[:checked]:border-primary has-[:checked]:ring-2 has-[:checked]:ring-primary/20">
                                <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                    <input type="radio" name="image_mode" value="faithful" checked>
                                    Détourage fidèle
                                </span>
                                <span class="mt-2 block text-xs text-slate-600">Conserve les pixels visibles du vêtement et remplace le fond par un fond neutre. Pour un vêtement porté, les parties cachées ne peuvent pas être reconstituées.</span>
                            </label>
                            <label class="cursor-pointer rounded-xl border border-slate-200 p-3 has-[:checked]:border-primary has-[:checked]:ring-2 has-[:checked]:ring-primary/20">
                                <span class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                                    <input type="radio" name="image_mode" value="generative">
                                    Retrait IA expérimental
                                </span>
                                <span class="mt-2 block text-xs text-slate-600">Tente de retirer la personne et le fond par génération. Le motif, le logo ou d’autres détails peuvent être modifiés ou inventés.</span>
                            </label>
                        </div>
                    </fieldset>
                    <button id="ameliorer-photo" type="button" disabled class="mt-4 rounded-xl border border-primary/30 bg-primary/5 px-4 py-2.5 text-sm font-semibold text-primary hover:bg-primary/10 disabled:cursor-not-allowed disabled:opacity-50">
                        <span data-label>Détourer le vêtement sans le recréer</span>
                    </button>
                    <p class="mt-1 text-xs text-slate-500">
                        @if(config('services.donation_images.provider') === 'comfyui')
                            La photo est traitée par votre serveur ComfyUI local. Le détourage fidèle nécessite le node pack <code>storyicon/comfyui_segment_anything</code> et les modèles SAM/GroundingDINO.
                        @else
                            Le mode génératif utilise Gemini. Le détourage fidèle est disponible uniquement avec ComfyUI local.
                        @endif
                        Comparez les deux photos avant de choisir celle qui représentera le don. Le détourage conserve uniquement les zones visibles : il ne peut pas retrouver les parties cachées par une personne.
                    </p>
                    <p id="erreur-amelioration" role="alert" class="mt-2 hidden text-sm text-red-600"></p>
                    <input type="hidden" name="enhanced_token" id="enhanced-token">
                    <fieldset id="comparaison-photos" class="mt-4 hidden rounded-xl border border-slate-200 p-4">
                        <legend class="px-2 text-sm font-semibold text-slate-700">Choisissez la photo utilisée pour ce don</legend>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="cursor-pointer rounded-xl border border-slate-200 p-3 has-[:checked]:border-primary has-[:checked]:ring-2 has-[:checked]:ring-primary/20">
                                <span class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700">
                                    <input type="radio" name="photo_choice" value="original" checked>
                                    Photo originale
                                </span>
                                <img id="photo-originale-apercu" alt="Photo originale du vêtement" class="max-h-64 w-full rounded-lg object-contain">
                            </label>
                            <label class="cursor-pointer rounded-xl border border-slate-200 p-3 has-[:checked]:border-primary has-[:checked]:ring-2 has-[:checked]:ring-primary/20">
                                <span class="mb-2 flex items-center gap-2 text-sm font-medium text-slate-700">
                                    <input type="radio" name="photo_choice" value="enhanced">
                                    Photo améliorée par l’IA
                                </span>
                                <img id="photo-amelioree-apercu" alt="Photo améliorée du vêtement" class="max-h-64 w-full rounded-lg object-contain">
                            </label>
                        </div>
                        @error('enhanced_token') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </fieldset>
                    <p id="duplicate-check-status" role="status" class="mt-4 hidden text-sm text-slate-600"></p>
                    <section id="duplicate-warning" aria-live="polite" class="mt-4 hidden rounded-xl border border-amber-300 bg-amber-50 p-4">
                        <h2 class="font-semibold text-amber-900">Un don similaire a été trouvé</h2>
                        <p class="mt-1 text-sm text-amber-800">Vérifiez qu’il ne s’agit pas du même vêtement. Vous pouvez annuler ou confirmer la publication.</p>
                        <p id="duplicate-ai-status" class="mt-2 hidden text-sm text-amber-800"></p>
                        <ul id="duplicate-matches" class="mt-3 list-inside list-disc space-y-1 text-sm text-amber-900"></ul>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <button id="confirm-duplicate" type="button" class="rounded-xl bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">Continuer malgré tout</button>
                            <button id="cancel-duplicate" type="button" class="rounded-xl border border-amber-400 px-4 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100">Vérifier mon vêtement</button>
                        </div>
                    </section>
                    @error('duplicate_confirmation') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-3">
                <a href="{{ route('collecte.dons.index') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Annuler</a>
                <button id="submit-don" type="submit" class="rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark">Enregistrer le don</button>
            </div>
        </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const photo = document.getElementById('photo');
    const previewContainer = document.getElementById('photo-preview-container');
    const previewImage = document.getElementById('photo-preview');
    const selectionStatus = document.getElementById('photo-selection-status');
    const bouton = document.getElementById('analyser-photo');
    const boutonAmeliorer = document.getElementById('ameliorer-photo');
    const resultat = document.getElementById('resultat-analyse');
    const erreur = document.getElementById('erreur-analyse');
    const erreurAmelioration = document.getElementById('erreur-amelioration');
    const comparaison = document.getElementById('comparaison-photos');
    const form = document.getElementById('don-form');
    const submitDon = document.getElementById('submit-don');
    const duplicateCheckToken = document.getElementById('duplicate-check-token');
    const duplicateConfirmed = document.getElementById('duplicate-confirmed');
    const duplicateStatus = document.getElementById('duplicate-check-status');
    const duplicateWarning = document.getElementById('duplicate-warning');
    const duplicateMatches = document.getElementById('duplicate-matches');
    const duplicateAiStatus = document.getElementById('duplicate-ai-status');
    const confirmDuplicate = document.getElementById('confirm-duplicate');
    const cancelDuplicate = document.getElementById('cancel-duplicate');
    const routeAnalyse = @json(route('collecte.dons.analyse-photo'));
    const routeAmelioration = @json(route('collecte.dons.ameliorer-photo'));
    const routeDetectionDoublon = @json(route('collecte.dons.detecter-doublon', [], false));
    const modesImage = document.querySelectorAll('input[name="image_mode"]');
    let urlPhotoOriginale = null;
    let resultatsDoublons = [];
    let ignoreProchaineVerification = false;
    let verificationEnCours = false;

    const champsComparaison = ['type', 'matiere', 'couleur', 'taille'];
    const invaliderVerificationDoublon = () => {
        duplicateCheckToken.value = '';
        duplicateConfirmed.value = '0';
        resultatsDoublons = [];
        duplicateWarning.classList.add('hidden');
        duplicateStatus.classList.add('hidden');
        duplicateStatus.classList.remove('text-red-600');
        duplicateStatus.classList.add('text-slate-600');
        duplicateAiStatus.classList.add('hidden');
    };
    const libelleAmelioration = () => document.querySelector('input[name="image_mode"]:checked').value === 'faithful'
        ? 'Détourer le vêtement sans le recréer'
        : 'Retrait IA expérimental';

    modesImage.forEach((mode) => mode.addEventListener('change', () => {
        boutonAmeliorer.querySelector('[data-label]').textContent = libelleAmelioration();
        erreurAmelioration.classList.add('hidden');
        comparaison.classList.add('hidden');
        document.getElementById('enhanced-token').value = '';
        document.querySelector('input[name="photo_choice"][value="original"]').checked = true;
    }));

    photo.addEventListener('change', () => {
        invaliderVerificationDoublon();
        bouton.disabled = photo.files.length === 0;
        boutonAmeliorer.disabled = photo.files.length === 0;
        resultat.classList.add('hidden');
        erreur.classList.add('hidden');
        erreurAmelioration.classList.add('hidden');
        comparaison.classList.add('hidden');
        document.getElementById('enhanced-token').value = '';
        document.querySelector('input[name="photo_choice"][value="original"]').checked = true;
        if (urlPhotoOriginale) URL.revokeObjectURL(urlPhotoOriginale);
        urlPhotoOriginale = photo.files.length ? URL.createObjectURL(photo.files[0]) : null;
        if (urlPhotoOriginale) {
            previewImage.src = urlPhotoOriginale;
            previewContainer.classList.remove('hidden');
            selectionStatus.textContent = 'Photo sélectionnée';
        } else {
            previewImage.removeAttribute('src');
            previewContainer.classList.add('hidden');
            selectionStatus.textContent = 'Aucune photo choisie';
        }
    });

    champsComparaison.forEach((champ) => {
        document.getElementById(champ).addEventListener('input', invaliderVerificationDoublon);
        document.getElementById(champ).addEventListener('change', invaliderVerificationDoublon);
    });

    form.addEventListener('submit', async (event) => {
        if (ignoreProchaineVerification) {
            ignoreProchaineVerification = false;
            return;
        }
        event.preventDefault();
        if (verificationEnCours) return;

        if (duplicateCheckToken.value) {
            if (resultatsDoublons.length) {
                duplicateWarning.classList.remove('hidden');
                return;
            }
            ignoreProchaineVerification = true;
            form.requestSubmit(submitDon);
            return;
        }

        verificationEnCours = true;
        submitDon.disabled = true;
        duplicateStatus.textContent = 'Vérification des photos et de vos dons précédents…';
        duplicateStatus.classList.remove('hidden');
        duplicateWarning.classList.add('hidden');

        const valeurs = Object.fromEntries(champsComparaison.map((champ) => [
            champ,
            document.getElementById(champ).value,
        ]));
        const donnees = new FormData();
        donnees.append('photo', photo.files[0]);
        Object.entries(valeurs).forEach(([champ, valeur]) => donnees.append(champ, valeur));
        donnees.append('_token', @json(csrf_token()));

        try {
            const response = await fetch(routeDetectionDoublon, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: donnees,
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'La vérification du doublon a échoué.');
            }

            if (champsComparaison.some((champ) => document.getElementById(champ).value !== valeurs[champ])) {
                throw new Error('Les informations du vêtement ont changé pendant la vérification. Cliquez de nouveau sur « Enregistrer le don ».');
            }

            duplicateCheckToken.value = data.check_token;
            resultatsDoublons = data.matches || [];
            duplicateConfirmed.value = '0';

            if (resultatsDoublons.length) {
                duplicateMatches.replaceChildren();
                resultatsDoublons.forEach((match) => {
                    const item = document.createElement('li');
                    const details = [match.type, match.couleur, match.taille ? `taille ${match.taille}` : '']
                        .filter(Boolean)
                        .join(' · ');
                    const confidence = Math.round((match.confidence || 0) * 100);
                    item.textContent = `Don #${match.donation_id} — ${details} (correspondance estimée : ${confidence} %)`;
                    duplicateMatches.appendChild(item);
                });
                if (['unavailable', 'error'].includes(data.ai_status)) {
                    duplicateAiStatus.textContent = 'La comparaison des vêtements vus sous un autre angle est indisponible ; cette alerte repose sur la ressemblance des images.';
                    duplicateAiStatus.classList.remove('hidden');
                }
                duplicateWarning.classList.remove('hidden');
                duplicateStatus.classList.add('hidden');
                return;
            }

            if (['unavailable', 'error'].includes(data.ai_status)) {
                duplicateStatus.textContent = 'La comparaison IA des angles différents est indisponible ; seules les ressemblances d’image ont pu être vérifiées.';
            } else {
                duplicateStatus.textContent = 'Aucun doublon probable trouvé. Enregistrement du don…';
            }
            ignoreProchaineVerification = true;
            form.requestSubmit(submitDon);
        } catch (e) {
            duplicateStatus.textContent = e.message;
            duplicateStatus.classList.remove('text-slate-600');
            duplicateStatus.classList.add('text-red-600');
        } finally {
            verificationEnCours = false;
            submitDon.disabled = false;
        }
    });

    confirmDuplicate.addEventListener('click', () => {
        duplicateConfirmed.value = '1';
        ignoreProchaineVerification = true;
        form.requestSubmit(submitDon);
    });

    cancelDuplicate.addEventListener('click', () => {
        duplicateWarning.classList.add('hidden');
        duplicateConfirmed.value = '0';
    });

    bouton.addEventListener('click', async () => {
        if (!photo.files.length) return;

        const label = bouton.querySelector('[data-label]');
        bouton.disabled = true;
        label.textContent = 'Analyse de la photo…';
        resultat.classList.add('hidden');
        erreur.classList.add('hidden');

        const donnees = new FormData();
        donnees.append('photo', photo.files[0]);
        donnees.append('_token', @json(csrf_token()));

        try {
            const response = await fetch(routeAnalyse, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: donnees,
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Analyse impossible. Réessayez.');
            }

            const suggestion = data.suggestion;
            ['type', 'matiere', 'couleur', 'taille', 'etat'].forEach((champ) => {
                document.getElementById(champ).value = suggestion[champ] || '';
            });
            resultat.textContent = 'Suggestions ajoutées au formulaire. Vérifiez et corrigez chaque champ avant d’enregistrer.';
            resultat.classList.remove('hidden');
        } catch (e) {
            erreur.textContent = e.message;
            erreur.classList.remove('hidden');
        } finally {
            bouton.disabled = photo.files.length === 0;
            label.textContent = 'Suggérer les informations avec l’IA';
        }
    });

    boutonAmeliorer.addEventListener('click', async () => {
        if (!photo.files.length) return;

        const label = boutonAmeliorer.querySelector('[data-label]');
        boutonAmeliorer.disabled = true;
        label.textContent = 'Création de la photo catalogue…';
        erreurAmelioration.classList.add('hidden');
        comparaison.classList.add('hidden');
        document.getElementById('enhanced-token').value = '';
        document.querySelector('input[name="photo_choice"][value="original"]').checked = true;

        const donnees = new FormData();
        donnees.append('photo', photo.files[0]);
        donnees.append('mode', document.querySelector('input[name="image_mode"]:checked').value);
        donnees.append('type', document.getElementById('type').value);
        donnees.append('_token', @json(csrf_token()));

        try {
            const response = await fetch(routeAmelioration, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: donnees,
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Amélioration impossible. Réessayez.');
            }

            document.getElementById('photo-originale-apercu').src = urlPhotoOriginale;
            document.getElementById('photo-amelioree-apercu').src = data.preview;
            document.getElementById('enhanced-token').value = data.token;
            document.querySelector('input[name="photo_choice"][value="original"]').checked = true;
            comparaison.classList.remove('hidden');
        } catch (e) {
            erreurAmelioration.textContent = e.message;
            erreurAmelioration.classList.remove('hidden');
        } finally {
            boutonAmeliorer.disabled = photo.files.length === 0;
            label.textContent = libelleAmelioration();
        }
    });
});
</script>
@endsection