{{-- Champs partagés création / édition — attend $mission, $dons --}}
@php
    $inputClass = fn (string $champ) => 'a-input' . ($errors->has($champ) ? ' invalid' : '');
@endphp

{{-- Erreurs globales --}}
@if($errors->any())
    <div class="flex items-start gap-2.5 mb-4 px-3 py-2.5 rounded-md border border-red-200 bg-red-50 text-red-800">
        <i class="fas fa-circle-exclamation text-red-600 mt-0.5"></i>
        <p>Le formulaire contient {{ $errors->count() }} erreur(s). Corrigez les champs signalés.</p>
    </div>
@endif

<div class="a-card divide-y divide-slate-200">

    {{-- ===== Mission ===== --}}
    <section class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-8 gap-y-4 p-5">
        <div>
            <h2 class="font-semibold text-slate-900">Mission</h2>
            <p class="text-slate-500 mt-1">Ce qu'il faut faire et où se rendre.</p>
        </div>

        <div class="max-w-3xl grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Type --}}
            <div>
                <label for="type" class="a-label">Type</label>
                @include('admin.partials.select', [
                    'name'     => 'type',
                    'invalid'  => $errors->has('type'),
                    'selected' => old('type', $mission->type),
                    'options'  => \App\Models\Mission::TYPES,
                ])
                @error('type') <p class="a-error">{{ $message }}</p> @enderror
            </div>

            {{-- Don lié --}}
            <div>
                <label for="don_vetement_id" class="a-label">Don à collecter <span class="text-slate-400 font-normal">(facultatif)</span></label>
                @include('admin.partials.select', [
                    'name'     => 'don_vetement_id',
                    'invalid'  => $errors->has('don_vetement_id'),
                    'selected' => old('don_vetement_id', $mission->don_vetement_id),
                    'options'  => $dons,
                ])
                @error('don_vetement_id') <p class="a-error">{{ $message }}</p> @enderror
            </div>

            {{-- Adresse --}}
            <div class="sm:col-span-2">
                <label for="adresse" class="a-label">Adresse</label>
                <input type="text" id="adresse" name="adresse" value="{{ old('adresse', $mission->adresse) }}"
                       placeholder="12 rue de Marseille, Tunis" autocomplete="off" class="{{ $inputClass('adresse') }}">
                @error('adresse') <p class="a-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- ===== Passage ===== --}}
    <section class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-8 gap-y-4 p-5">
        <div>
            <h2 class="font-semibold text-slate-900">Passage</h2>
            <p class="text-slate-500 mt-1">Position dans la tournée et heure d'arrivée prévue.</p>
        </div>

        <div class="max-w-3xl grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Ordre --}}
            <div>
                <label for="ordre" class="a-label">Ordre de passage</label>
                <input type="number" id="ordre" name="ordre" min="1" step="1"
                       value="{{ old('ordre', $mission->ordre) }}" class="{{ $inputClass('ordre') }} num">
                @error('ordre') <p class="a-error">{{ $message }}</p> @enderror
            </div>

            {{-- Heure prévue --}}
            <div>
                <label for="heure_prevue" class="a-label">Heure prévue</label>
                <input type="time" id="heure_prevue" name="heure_prevue"
                       value="{{ old('heure_prevue', $mission->heure) }}" class="{{ $inputClass('heure_prevue') }} num">
                @error('heure_prevue') <p class="a-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- ===== Suivi ===== --}}
    <section class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-8 gap-y-4 p-5">
        <div>
            <h2 class="font-semibold text-slate-900">Suivi</h2>
            <p class="text-slate-500 mt-1">État de la mission et preuve de passage.</p>
        </div>

        <div class="max-w-3xl grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Statut --}}
            <div>
                <label for="statut" class="a-label">Statut</label>
                @include('admin.partials.select', [
                    'name'     => 'statut',
                    'invalid'  => $errors->has('statut'),
                    'selected' => old('statut', $mission->statut),
                    'options'  => \App\Models\Mission::statutOptions(),
                ])
                @error('statut') <p class="a-error">{{ $message }}</p> @enderror
            </div>

            {{-- Preuve de livraison --}}
            <div>
                <label for="preuve_livraison" class="a-label">Preuve de livraison <span class="text-slate-400 font-normal">(facultatif)</span></label>
                <input type="text" id="preuve_livraison" name="preuve_livraison"
                       value="{{ old('preuve_livraison', $mission->preuve_livraison) }}"
                       placeholder="Signé par M. Ben Ali" autocomplete="off" class="{{ $inputClass('preuve_livraison') }}">
                @error('preuve_livraison') <p class="a-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>
</div>
