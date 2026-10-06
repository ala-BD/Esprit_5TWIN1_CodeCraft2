{{--
    Zone d'upload d'une photo avec aperçu (glisser-déposer ou clic).
    Paramètres : $nom (nom du champ), $actuelle (?string URL de la photo existante),
                 $titre, $aide, $hauteur (classe Tailwind, optionnel)
--}}
@php $id = 'zone-' . $nom; @endphp

<div>
    <label for="{{ $id }}-input"
           id="{{ $id }}"
           data-zone-photo
           class="relative flex flex-col items-center justify-center {{ $hauteur ?? 'h-72' }} rounded-3xl border-2 border-dashed cursor-pointer overflow-hidden transition-all
                  {{ $errors->has($nom) ? 'border-red-300 bg-red-50' : 'border-gray-200 bg-gradient-to-br from-slate-50 to-white hover:border-primary hover:bg-primary/5' }}">

        <img data-apercu src="{{ $actuelle ?? '' }}" alt="Aperçu"
             class="absolute inset-0 w-full h-full object-contain p-4 bg-white {{ $actuelle ? '' : 'hidden' }}">

        <div data-invite class="text-center px-6 {{ $actuelle ? 'hidden' : '' }}">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-white shadow-sm border border-gray-100 flex items-center justify-center mb-4">
                <i class="fas fa-camera text-2xl text-primary"></i>
            </div>
            <p class="font-semibold text-gray-800">{{ $titre }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $aide }}</p>
            <p class="text-[11px] text-gray-400 mt-3">JPG, PNG ou WEBP · 5 Mo max</p>
        </div>

        <span data-changer class="absolute bottom-3 right-3 text-xs font-semibold bg-white/90 backdrop-blur px-3 py-1.5 rounded-lg shadow-sm text-gray-700 {{ $actuelle ? '' : 'hidden' }}">
            <i class="fas fa-sync-alt mr-1"></i> Changer
        </span>

        <input type="file" id="{{ $id }}-input" name="{{ $nom }}" accept="image/jpeg,image/png,image/webp" class="sr-only">
    </label>
    @error($nom)<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
</div>

@once
<script>
    // Aperçu + glisser-déposer pour toutes les zones photo de la page
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-zone-photo]').forEach((zone) => {
            const input = zone.querySelector('input[type=file]');
            const apercu = zone.querySelector('[data-apercu]');

            const afficher = (fichier) => {
                if (!fichier || !fichier.type.startsWith('image/')) return;
                apercu.src = URL.createObjectURL(fichier);
                apercu.classList.remove('hidden');
                zone.querySelector('[data-invite]').classList.add('hidden');
                zone.querySelector('[data-changer]').classList.remove('hidden');
                zone.dispatchEvent(new CustomEvent('photo-choisie', { detail: fichier, bubbles: true }));
            };

            input.addEventListener('change', () => afficher(input.files[0]));
            ['dragenter', 'dragover'].forEach((e) => zone.addEventListener(e, (ev) => { ev.preventDefault(); zone.classList.add('border-primary', 'bg-primary/5'); }));
            ['dragleave', 'drop'].forEach((e) => zone.addEventListener(e, (ev) => { ev.preventDefault(); zone.classList.remove('border-primary', 'bg-primary/5'); }));
            zone.addEventListener('drop', (ev) => {
                if (!ev.dataTransfer.files.length) return;
                input.files = ev.dataTransfer.files;
                afficher(input.files[0]);
            });
        });
    });
</script>
@endonce
