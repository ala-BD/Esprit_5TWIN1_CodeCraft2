{{-- Champs communs à la création et à la modification d'une demande --}}
@php
    $champ = fn ($nom, $padding = 'pl-11 pr-4') => "w-full {$padding} py-3 rounded-xl border "
        . ($errors->has($nom) ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white')
        . ' focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-300 text-gray-700';
@endphp

<div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 space-y-5">

    <div class="flex items-center justify-between">
        <h2 class="font-display font-bold text-gray-900 flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center"><i class="fas fa-tshirt text-primary text-sm"></i></span>
            Le vêtement
        </h2>
        <span id="badge-prerempli" class="hidden text-[11px] font-semibold px-2.5 py-1 rounded-full bg-violet-100 text-violet-700">
            <i class="fas fa-wand-magic-sparkles"></i> Pré-rempli par l'IA
        </span>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        {{-- Type --}}
        <div>
            <label for="type_vetement" class="block text-sm font-semibold text-gray-700 mb-2">Type <span class="text-red-500">*</span></label>
            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-tag text-sm"></i></span>
                <input type="text" id="type_vetement" name="type_vetement" list="types-vetements"
                       value="{{ old('type_vetement', $projet->type_vetement) }}" placeholder="Jean, chemise…"
                       class="{{ $champ('type_vetement') }}">
                <datalist id="types-vetements">
                    @foreach(['Jean', 'Chemise', 'T-shirt', 'Pull', 'Robe', 'Veste', 'Blazer', 'Jupe', 'Pantalon', 'Short'] as $t)
                        <option value="{{ $t }}">
                    @endforeach
                </datalist>
            </div>
            @error('type_vetement')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
        </div>

        {{-- Matière --}}
        <div>
            <label for="matiere" class="block text-sm font-semibold text-gray-700 mb-2">Matière <span class="text-red-500">*</span></label>
            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-layer-group text-sm"></i></span>
                <input type="text" id="matiere" name="matiere" list="matieres"
                       value="{{ old('matiere', $projet->matiere) }}" placeholder="Denim, coton…"
                       class="{{ $champ('matiere') }}">
                <datalist id="matieres">
                    @foreach(['Denim', 'Coton', 'Laine', 'Lin', 'Polyester', 'Soie', 'Cuir', 'Viscose'] as $m)
                        <option value="{{ $m }}">
                    @endforeach
                </datalist>
            </div>
            @error('matiere')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
        </div>

        {{-- Couleur --}}
        <div>
            <label for="couleur" class="block text-sm font-semibold text-gray-700 mb-2">Couleur</label>
            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-palette text-sm"></i></span>
                <input type="text" id="couleur" name="couleur" value="{{ old('couleur', $projet->couleur) }}" placeholder="Bleu, noir…"
                       class="{{ $champ('couleur') }}">
            </div>
            @error('couleur')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
        </div>
    </div>

    {{-- État --}}
    <div>
        <p class="block text-sm font-semibold text-gray-700 mb-2">État <span class="text-red-500">*</span></p>
        <div class="grid grid-cols-3 gap-3">
            @foreach([
                'BON'   => ['Bon état', 'fa-face-smile', 'Comme neuf'],
                'USE'   => ['Usé', 'fa-face-meh', 'Usure visible'],
                'ABIME' => ['Abîmé', 'fa-face-frown', 'Trou, tache'],
            ] as $code => [$label, $icone, $aide])
                <label class="cursor-pointer">
                    <input type="radio" name="etat" value="{{ $code }}" class="peer sr-only" @checked(old('etat', $projet->etat) === $code)>
                    <span class="flex flex-col items-center gap-1 text-center px-3 py-3 rounded-2xl border-2 border-gray-200 text-gray-500
                                 peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary hover:border-gray-300 transition-all">
                        <i class="fas {{ $icone }} text-lg"></i>
                        <span class="text-sm font-semibold">{{ $label }}</span>
                        <span class="text-[10px] text-gray-400">{{ $aide }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('etat')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
    </div>

    {{-- Description --}}
    <div>
        <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Description et envies <span class="text-red-500">*</span></label>
        <textarea id="description" name="description" rows="3"
                  placeholder="Ex : jean un peu délavé, trou au genou. J'aimerais un accessoire que je peux utiliser tous les jours."
                  class="{{ $champ('description', 'px-4') }} text-sm resize-none">{{ old('description', $projet->description) }}</textarea>
        <p class="mt-1.5 text-xs text-gray-400"><i class="fas fa-robot mr-1 text-violet-400"></i> L'IA s'appuie sur votre description pour personnaliser ses idées</p>
        @error('description')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
    </div>

    {{-- Budget --}}
    <div class="sm:w-1/2">
        <label for="budget_max" class="block text-sm font-semibold text-gray-700 mb-2">Budget maximum <span class="text-gray-400 font-normal">(optionnel)</span></label>
        <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-wallet text-sm"></i></span>
            <input type="number" id="budget_max" name="budget_max" step="1" min="1"
                   value="{{ old('budget_max', $projet->budget_max) }}" placeholder="80"
                   class="{{ $champ('budget_max', 'pl-11 pr-14') }}">
            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">DT</span>
        </div>
        @error('budget_max')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
    </div>
</div>
