{{-- Champs communs à la création et à la modification d'une demande --}}
@php
    $champ = fn ($nom) => 'w-full pl-11 pr-4 py-3 rounded-xl border '
        . ($errors->has($nom) ? 'border-red-400 bg-red-50' : 'border-gray-200')
        . ' focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200 text-gray-700';
@endphp

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">

    <h2 class="font-semibold text-gray-900 flex items-center gap-2">
        <i class="fas fa-tshirt text-primary"></i>
        Le vêtement à transformer
    </h2>

    <div class="grid sm:grid-cols-2 gap-5">
        {{-- Type --}}
        <div>
            <label for="type_vetement" class="block text-sm font-semibold text-gray-700 mb-2">Type de vêtement <span class="text-red-500">*</span></label>
            <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-tag text-sm"></i></span>
                <input type="text" id="type_vetement" name="type_vetement" list="types-vetements"
                       value="{{ old('type_vetement', $projet->type_vetement) }}" placeholder="ex: jean, chemise, pull"
                       class="{{ $champ('type_vetement') }}">
                <datalist id="types-vetements">
                    @foreach(['Jean', 'Chemise', 'T-shirt', 'Pull', 'Robe', 'Veste', 'Jupe', 'Pantalon'] as $t)
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
                       value="{{ old('matiere', $projet->matiere) }}" placeholder="ex: denim, coton, laine"
                       class="{{ $champ('matiere') }}">
                <datalist id="matieres">
                    @foreach(['Denim', 'Coton', 'Laine', 'Lin', 'Polyester', 'Soie', 'Cuir'] as $m)
                        <option value="{{ $m }}">
                    @endforeach
                </datalist>
            </div>
            @error('matiere')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
        </div>
    </div>

    {{-- État --}}
    <div>
        <p class="block text-sm font-semibold text-gray-700 mb-2">État <span class="text-red-500">*</span></p>
        <div class="grid grid-cols-3 gap-3">
            @foreach(\App\Models\ProjetUpcycling::ETATS as $code => $label)
                <label class="cursor-pointer">
                    <input type="radio" name="etat" value="{{ $code }}" class="peer sr-only" @checked(old('etat', $projet->etat) === $code)>
                    <span class="block text-center text-sm font-medium px-3 py-2.5 rounded-xl border-2 border-gray-200 text-gray-600
                                 peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:text-primary transition-all">
                        {{ $label }}
                    </span>
                </label>
            @endforeach
        </div>
        @error('etat')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
    </div>

    {{-- Description --}}
    <div>
        <label for="description" class="block text-sm font-semibold text-gray-700 mb-2">Description et envies <span class="text-red-500">*</span></label>
        <textarea id="description" name="description" rows="4"
                  placeholder="ex: Jean taille 38 un peu délavé, trou au genou. J'aimerais un accessoire que je peux utiliser tous les jours."
                  class="w-full px-4 py-3 rounded-xl border {{ $errors->has('description') ? 'border-red-400 bg-red-50' : 'border-gray-200' }}
                         focus:outline-none focus:ring-2 focus:ring-primary text-gray-700 text-sm resize-none">{{ old('description', $projet->description) }}</textarea>
        <p class="mt-1.5 text-xs text-gray-400"><i class="fas fa-robot mr-1 text-blue-400"></i> L'IA s'appuie sur cette description pour personnaliser ses idées</p>
        @error('description')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
    </div>

    {{-- Budget --}}
    <div class="sm:w-1/2">
        <label for="budget_max" class="block text-sm font-semibold text-gray-700 mb-2">Budget maximum <span class="text-gray-400 font-normal">(optionnel)</span></label>
        <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-wallet text-sm"></i></span>
            <input type="number" id="budget_max" name="budget_max" step="1" min="1"
                   value="{{ old('budget_max', $projet->budget_max) }}" placeholder="ex: 80"
                   class="{{ str_replace('pr-4', 'pr-14', $champ('budget_max')) }}">
            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">DT</span>
        </div>
        @error('budget_max')<p class="mt-1.5 text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>@enderror
    </div>
</div>
