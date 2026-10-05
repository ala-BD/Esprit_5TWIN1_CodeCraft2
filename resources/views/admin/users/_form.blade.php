{{-- Champs partagés création / édition — attend $user --}}
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

    {{-- ===== Identité ===== --}}
    <section class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-8 gap-y-4 p-5">
        <div>
            <h2 class="font-semibold text-slate-900">Identité</h2>
            <p class="text-slate-500 mt-1">Nom affiché et coordonnées de contact.</p>
        </div>

        <div class="max-w-3xl grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Prénom --}}
            <div>
                <label for="prenom" class="a-label">Prénom</label>
                <input type="text" id="prenom" name="prenom" value="{{ old('prenom', $user->prenom) }}"
                       autocomplete="off" class="{{ $inputClass('prenom') }}">
                @error('prenom') <p class="a-error">{{ $message }}</p> @enderror
            </div>

            {{-- Nom --}}
            <div>
                <label for="name" class="a-label">Nom</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                       autocomplete="off" class="{{ $inputClass('name') }}">
                @error('name') <p class="a-error">{{ $message }}</p> @enderror
            </div>

            {{-- E-mail --}}
            <div>
                <label for="email" class="a-label">Adresse e-mail</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                       placeholder="nom@exemple.tn" autocomplete="off" class="{{ $inputClass('email') }}">
                @error('email') <p class="a-error">{{ $message }}</p> @enderror
            </div>

            {{-- Téléphone --}}
            <div>
                <label for="telephone" class="a-label">Téléphone <span class="text-slate-400 font-normal">(facultatif)</span></label>
                <input type="text" id="telephone" name="telephone" value="{{ old('telephone', $user->telephone) }}"
                       placeholder="21655000001" autocomplete="off" class="{{ $inputClass('telephone') }} num">
                @error('telephone') <p class="a-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    {{-- ===== Rôle et accès ===== --}}
    <section class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-8 gap-y-4 p-5">
        <div>
            <h2 class="font-semibold text-slate-900">Rôle et accès</h2>
            <p class="text-slate-500 mt-1">Le rôle détermine l'espace auquel l'utilisateur accède.</p>
        </div>

        <div class="max-w-3xl grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Rôle --}}
            <div>
                <label for="role" class="a-label">Rôle</label>
                @include('admin.partials.select', [
                    'name'     => 'role',
                    'invalid'  => $errors->has('role'),
                    'selected' => old('role', $user->role),
                    'options'  => collect(\App\Models\User::ROLES)->mapWithKeys(fn ($role) => [$role => ucfirst(strtolower($role))])->all(),
                ])
                @error('role') <p class="a-error">{{ $message }}</p> @enderror
            </div>

            {{-- Actif --}}
            <div class="sm:col-span-2">
                <label for="actif" class="flex items-start gap-2.5 cursor-pointer">
                    <input type="hidden" name="actif" value="0">
                    <input type="checkbox" id="actif" name="actif" value="1"
                           @checked(old('actif', $user->actif))
                           class="mt-0.5 w-4 h-4 rounded border-slate-300 accent-teal-600">
                    <span>
                        <span class="block font-medium text-slate-700">Compte actif</span>
                        <span class="block text-slate-500 text-xs mt-0.5">Décochez pour désactiver le compte sans le supprimer.</span>
                    </span>
                </label>
            </div>
        </div>
    </section>

    {{-- ===== Mot de passe ===== --}}
    <section class="grid grid-cols-1 md:grid-cols-[260px_1fr] gap-x-8 gap-y-4 p-5">
        <div>
            <h2 class="font-semibold text-slate-900">Mot de passe</h2>
            <p class="text-slate-500 mt-1">
                @if($user->exists)
                    Laissez vide pour conserver le mot de passe actuel.
                @else
                    8 caractères minimum. À communiquer à l'utilisateur.
                @endif
            </p>
        </div>

        <div class="max-w-3xl grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Mot de passe --}}
            <div>
                <label for="password" class="a-label">{{ $user->exists ? 'Nouveau mot de passe' : 'Mot de passe' }}</label>
                <input type="password" id="password" name="password" autocomplete="new-password"
                       class="{{ $inputClass('password') }}">
                @error('password') <p class="a-error">{{ $message }}</p> @enderror
            </div>

            {{-- Confirmation --}}
            <div>
                <label for="password_confirmation" class="a-label">Confirmation</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                       class="a-input">
            </div>
        </div>
    </section>
</div>
