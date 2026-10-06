{{-- Champs partagés création / édition — attend $tournee --}}
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

    {{-- ===== Planification ===== --}}
    <section class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-8 gap-y-4 p-5">
        <div>
            <h2 class="font-semibold text-slate-900">Planification</h2>
            <p class="text-slate-500 mt-1">Jour et secteur couverts par la tournée.</p>
        </div>

        <div class="max-w-3xl grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Date --}}
            <div>
                <label for="date" class="a-label">Date</label>
                <input type="date" id="date" name="date"
                       value="{{ old('date', $tournee->date?->format('Y-m-d')) }}"
                       class="{{ $inputClass('date') }} num">
                @error('date') <p class="a-error">{{ $message }}</p> @enderror
            </div>

            {{-- Statut --}}
            <div>
                <label for="statut" class="a-label">Statut</label>
                @include('admin.partials.select', [
                    'name'     => 'statut',
                    'invalid'  => $errors->has('statut'),
                    'selected' => old('statut', $tournee->statut),
                    'options'  => \App\Models\Tournee::statutOptions(),
                ])
                @error('statut') <p class="a-error">{{ $message }}</p> @enderror
            </div>

            {{-- Zone --}}
            <div class="sm:col-span-2">
                <label for="zone" class="a-label">Zone</label>
                <input type="text" id="zone" name="zone" value="{{ old('zone', $tournee->zone) }}"
                       placeholder="Tunis Centre" autocomplete="off" class="{{ $inputClass('zone') }}">
                @error('zone') <p class="a-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- ===== Transport ===== --}}
    <section class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-8 gap-y-4 p-5">
        <div>
            <h2 class="font-semibold text-slate-900">Transport</h2>
            <p class="text-slate-500 mt-1">Véhicule utilisé et distance prévue.</p>
        </div>

        <div class="max-w-3xl grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Véhicule --}}
            <div>
                <label for="vehicule" class="a-label">Véhicule</label>
                <input type="text" id="vehicule" name="vehicule" value="{{ old('vehicule', $tournee->vehicule) }}"
                       placeholder="Camionnette 123 TU 4567" autocomplete="off" class="{{ $inputClass('vehicule') }}">
                @error('vehicule') <p class="a-error">{{ $message }}</p> @enderror
            </div>

            {{-- Distance --}}
            <div>
                <label for="distance_km" class="a-label">Distance en km <span class="text-slate-400 font-normal">(facultatif)</span></label>
                <input type="number" id="distance_km" name="distance_km" step="0.1" min="0"
                       value="{{ old('distance_km', $tournee->distance_km) }}"
                       placeholder="24.5" class="{{ $inputClass('distance_km') }} num">
                @error('distance_km') <p class="a-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>
</div>
