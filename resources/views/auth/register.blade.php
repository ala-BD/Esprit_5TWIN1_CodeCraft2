@extends('layouts.app')

@section('title', 'Inscription')

@section('content')
<div class="min-h-screen flex">

    {{-- ---- Panneau gauche : branding ---- --}}
    <div class="hidden lg:flex lg:w-1/2 auth-bg relative overflow-hidden flex-col justify-between p-12">

        <div class="absolute top-[-60px] right-[-60px] w-80 h-80 bg-white/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-[-40px] left-[-40px] w-64 h-64 bg-secondary-DEFAULT/10 rounded-full blur-3xl"></div>

        {{-- Logo --}}
        <div class="relative flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center shadow-md">
                <span class="text-white font-black text-base">R</span>
            </div>
            <span class="font-display font-bold text-2xl text-white tracking-tight">RETISS</span>
        </div>

        {{-- Contenu central --}}
        <div class="relative flex-1 flex flex-col justify-center py-12">
            <h2 class="font-display text-4xl xl:text-5xl font-bold text-white leading-tight mb-6">
                Rejoignez le<br>
                <span class="text-secondary-DEFAULT">mouvement</span><br>
                textile circulaire
            </h2>
            <p class="text-white/70 text-lg leading-relaxed max-w-md mb-10">
                Créez votre compte et choisissez votre rôle dans la chaîne de valeur du textile durable.
            </p>

            {{-- Rôles disponibles --}}
            <div class="space-y-3 max-w-sm">
                @php
                    $roles_preview = [
                        ['icon' => 'fa-hand-holding-heart', 'label' => 'Donateur — Déposez vos vêtements'],
                        ['icon' => 'fa-shopping-bag',       'label' => 'Client — Achetez & commandez'],
                        ['icon' => 'fa-cut',                'label' => 'Atelier — Créez & transformez'],
                        ['icon' => 'fa-recycle',            'label' => 'Recycleur — Traitez les lots'],
                    ];
                @endphp
                @foreach($roles_preview as $r)
                <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/15">
                    <div class="w-8 h-8 rounded-lg bg-secondary-DEFAULT/20 flex items-center justify-center flex-shrink-0">
                        <i class="fas {{ $r['icon'] }} text-secondary-DEFAULT text-sm"></i>
                    </div>
                    <span class="text-white/80 text-sm">{{ $r['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Bas --}}
        <div class="relative flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/15">
            <div class="w-10 h-10 rounded-full bg-secondary-DEFAULT/30 flex items-center justify-center flex-shrink-0">
                <i class="fas fa-leaf text-secondary-DEFAULT"></i>
            </div>
            <p class="text-white/70 text-sm">
                Inscription <strong class="text-white">100% gratuite</strong> — Aucune carte bancaire requise
            </p>
        </div>
    </div>

    {{-- ---- Panneau droit : formulaire ---- --}}
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-10 bg-[#FAFAF8] overflow-y-auto">
        <div class="w-full max-w-md fade-in py-6">

            {{-- Header mobile --}}
            <div class="flex items-center gap-2 mb-8 lg:hidden">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary-dark to-primary-light flex items-center justify-center">
                    <span class="text-white font-black text-xs">R</span>
                </div>
                <span class="font-display font-bold text-xl text-primary-dark">RETISS</span>
            </div>

            {{-- Titre --}}
            <div class="mb-8">
                <h1 class="font-display text-3xl font-bold text-gray-900 mb-2">Créer un compte</h1>
                <p class="text-gray-500">Déjà inscrit ?
                    <a href="{{ route('login') }}" class="text-primary-DEFAULT font-semibold hover:text-primary-dark transition-colors">
                        Se connecter
                    </a>
                </p>
            </div>

            {{-- Erreurs globales --}}
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex items-start gap-3">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5 flex-shrink-0"></i>
                    <div>
                        @foreach ($errors->all() as $error)
                            <p class="text-red-600 text-sm">{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Formulaire --}}
            <form method="POST" action="{{ route('register') }}" class="space-y-5" novalidate>
                @csrf

                {{-- Nom & Prénom --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="prenom" class="block text-sm font-semibold text-gray-700 mb-2">Prénom</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="fas fa-user text-sm"></i>
                            </span>
                            <input
                                type="text"
                                id="prenom"
                                name="prenom"
                                value="{{ old('prenom') }}"
                                placeholder="Ali"
                                class="w-full pl-10 pr-3 py-3 rounded-xl border {{ $errors->has('prenom') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white' }}
                                       focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                       transition-all duration-200 text-gray-700 placeholder-gray-400 text-sm"
                            >
                        </div>
                        @error('prenom')
                            <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Nom</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="fas fa-user text-sm"></i>
                            </span>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="Ben Ali"
                                class="w-full pl-10 pr-3 py-3 rounded-xl border {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white' }}
                                       focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                       transition-all duration-200 text-gray-700 placeholder-gray-400 text-sm"
                            >
                        </div>
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Adresse e-mail</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-envelope text-sm"></i>
                        </span>
                        <input
                            type="text"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="vous@exemple.com"
                            class="w-full pl-11 pr-4 py-3 rounded-xl border {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white' }}
                                   focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                   transition-all duration-200 text-gray-700 placeholder-gray-400"
                        >
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Téléphone --}}
                <div>
                    <label for="telephone" class="block text-sm font-semibold text-gray-700 mb-2">Téléphone <span class="text-gray-400 font-normal">(optionnel)</span></label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-phone text-sm"></i>
                        </span>
                        <input
                            type="text"
                            id="telephone"
                            name="telephone"
                            value="{{ old('telephone') }}"
                            placeholder="+216 XX XXX XXX"
                            class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-200 bg-white
                                   focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                   transition-all duration-200 text-gray-700 placeholder-gray-400"
                        >
                    </div>
                </div>

                {{-- Rôle --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Je m'inscris en tant que</label>
                    <div class="grid grid-cols-2 gap-3">
                        @php
                            $roles = [
                                ['value' => 'DONATEUR',   'icon' => 'fa-hand-holding-heart', 'label' => 'Donateur'],
                                ['value' => 'CLIENT',     'icon' => 'fa-shopping-bag',       'label' => 'Client'],
                                ['value' => 'COLLECTEUR', 'icon' => 'fa-truck',              'label' => 'Collecteur'],
                                ['value' => 'ATELIER',    'icon' => 'fa-cut',                'label' => 'Atelier'],
                                ['value' => 'RECYCLEUR',  'icon' => 'fa-recycle',            'label' => 'Recycleur'],
                            ];
                        @endphp

                        @foreach($roles as $role)
                        <label class="role-card cursor-pointer">
                            <input type="radio" name="role" value="{{ $role['value'] }}"
                                   {{ old('role') === $role['value'] ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="flex items-center gap-2 p-3 rounded-xl border-2
                                        {{ $errors->has('role') ? 'border-red-200' : 'border-gray-200' }} bg-white
                                        peer-checked:border-primary-DEFAULT peer-checked:bg-primary-DEFAULT/5
                                        hover:border-gray-300 transition-all duration-200">
                                <div class="w-7 h-7 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fas {{ $role['icon'] }} text-gray-500 text-xs"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700">{{ $role['label'] }}</span>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('role')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Mot de passe --}}
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Mot de passe</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="8 caractères minimum"
                            class="w-full pl-11 pr-12 py-3 rounded-xl border {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white' }}
                                   focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                   transition-all duration-200 text-gray-700 placeholder-gray-400"
                        >
                        <button type="button"
                                onclick="togglePassword('password', 'eye1')"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                            <i id="eye1" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Confirmer mot de passe --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">Confirmer le mot de passe</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder="Répétez le mot de passe"
                            class="w-full pl-11 pr-12 py-3 rounded-xl border border-gray-200 bg-white
                                   focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                   transition-all duration-200 text-gray-700 placeholder-gray-400"
                        >
                        <button type="button"
                                onclick="togglePassword('password_confirmation', 'eye2')"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                            <i id="eye2" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                </div>

                {{-- CGU --}}
                <div class="flex items-start gap-3">
                    <input
                        type="checkbox"
                        id="cgu"
                        name="cgu"
                        required
                        class="w-4 h-4 mt-0.5 rounded border-gray-300 text-primary-DEFAULT focus:ring-primary-DEFAULT cursor-pointer flex-shrink-0"
                    >
                    <label for="cgu" class="text-sm text-gray-600 cursor-pointer leading-relaxed">
                        J'accepte les
                        <a href="#" class="text-primary-DEFAULT font-semibold hover:underline">conditions générales d'utilisation</a>
                        et la
                        <a href="#" class="text-primary-DEFAULT font-semibold hover:underline">politique de confidentialité</a>
                        de RETISS.
                    </label>
                </div>

                {{-- Bouton --}}
                <button type="submit"
                        class="w-full bg-gradient-to-r from-primary-dark to-primary-DEFAULT text-white font-bold py-3.5 px-6 rounded-xl
                               hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all duration-200
                               flex items-center justify-center gap-2 mt-2">
                    <i class="fas fa-user-plus"></i>
                    Créer mon compte
                </button>
            </form>

            <p class="text-center text-sm text-gray-500 mt-6">
                Déjà un compte ?
                <a href="{{ route('login') }}" class="text-primary-DEFAULT font-semibold hover:text-primary-dark transition-colors">
                    Se connecter
                </a>
            </p>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fas fa-eye-slash text-sm';
        } else {
            input.type = 'password';
            icon.className = 'fas fa-eye text-sm';
        }
    }

    // Highlight rôle sélectionné
    document.querySelectorAll('.role-card input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', () => {
            document.querySelectorAll('.role-card div').forEach(div => {
                div.querySelector('i').classList.remove('text-primary-DEFAULT');
                div.querySelector('i').classList.add('text-gray-500');
            });
            if (radio.checked) {
                const icon = radio.nextElementSibling.querySelector('i');
                icon.classList.remove('text-gray-500');
                icon.classList.add('text-primary-DEFAULT');
            }
        });
    });
</script>
@endsection
