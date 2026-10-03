@extends('layouts.app')

@section('title', 'Nouveau mot de passe')

@section('content')
<div class="min-h-screen flex">

    {{-- ---- Panneau gauche : branding ---- --}}
    <div class="hidden lg:flex lg:w-1/2 auth-bg relative overflow-hidden flex-col justify-between p-12">

        <div class="absolute top-[-60px] right-[-60px] w-80 h-80 bg-white/5 rounded-full blur-3xl"></div>
        <div class="absolute bottom-[-40px] left-[-40px] w-64 h-64 bg-secondary-DEFAULT/10 rounded-full blur-3xl"></div>

        {{-- Logo --}}
        <div class="relative flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                <span class="text-white font-black text-base">R</span>
            </div>
            <span class="font-display font-bold text-2xl text-white tracking-tight">RETISS</span>
        </div>

        {{-- Contenu central --}}
        <div class="relative flex-1 flex flex-col justify-center py-12">
            <div class="w-20 h-20 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center mb-8">
                <i class="fas fa-unlock-alt text-white text-3xl"></i>
            </div>
            <h2 class="font-display text-4xl font-bold text-white leading-tight mb-4">
                Créez votre<br>
                <span class="text-secondary-DEFAULT">nouveau</span><br>
                mot de passe
            </h2>
            <p class="text-white/70 text-lg leading-relaxed max-w-md mb-8">
                Choisissez un mot de passe fort et unique pour sécuriser votre compte RETISS.
            </p>

            {{-- Conseils sécurité --}}
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 border border-white/15 max-w-sm">
                <p class="text-white font-semibold text-sm mb-3">
                    <i class="fas fa-shield-alt text-secondary-DEFAULT mr-2"></i>
                    Conseils de sécurité
                </p>
                <ul class="space-y-2">
                    <li class="flex items-center gap-2 text-white/70 text-xs">
                        <i class="fas fa-check text-secondary-DEFAULT text-xs w-3"></i>
                        Minimum 8 caractères
                    </li>
                    <li class="flex items-center gap-2 text-white/70 text-xs">
                        <i class="fas fa-check text-secondary-DEFAULT text-xs w-3"></i>
                        Mélangez lettres, chiffres et symboles
                    </li>
                    <li class="flex items-center gap-2 text-white/70 text-xs">
                        <i class="fas fa-check text-secondary-DEFAULT text-xs w-3"></i>
                        N'utilisez pas un mot de passe déjà utilisé
                    </li>
                    <li class="flex items-center gap-2 text-white/70 text-xs">
                        <i class="fas fa-check text-secondary-DEFAULT text-xs w-3"></i>
                        Évitez les informations personnelles
                    </li>
                </ul>
            </div>
        </div>

        <div class="relative flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/15">
            <i class="fas fa-lock text-secondary-DEFAULT text-xl flex-shrink-0"></i>
            <p class="text-white/70 text-sm">
                Votre nouveau mot de passe sera <strong class="text-white">chiffré</strong> et stocké de façon sécurisée.
            </p>
        </div>
    </div>

    {{-- ---- Panneau droit : formulaire ---- --}}
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-10 bg-[#FAFAF8]">
        <div class="w-full max-w-md fade-in">

            {{-- Header mobile --}}
            <div class="flex items-center gap-2 mb-8 lg:hidden">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-primary-dark to-primary-light flex items-center justify-center">
                    <span class="text-white font-black text-xs">R</span>
                </div>
                <span class="font-display font-bold text-xl text-primary-dark">RETISS</span>
            </div>

            {{-- Icône --}}
            <div class="w-16 h-16 rounded-2xl bg-primary-DEFAULT/10 border border-primary-DEFAULT/20 flex items-center justify-center mb-6">
                <i class="fas fa-unlock-alt text-primary-DEFAULT text-2xl"></i>
            </div>

            {{-- Titre --}}
            <div class="mb-8">
                <h1 class="font-display text-3xl font-bold text-gray-900 mb-2">
                    Nouveau mot de passe
                </h1>
                <p class="text-gray-500 text-sm">
                    Choisissez un mot de passe sécurisé pour votre compte.
                </p>
            </div>

            {{-- Erreurs --}}
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
            <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
                @csrf

                {{-- Token caché --}}
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                        Adresse e-mail
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-envelope text-sm"></i>
                        </span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email', $request->email) }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full pl-11 pr-4 py-3 rounded-xl border
                                   {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50' }}
                                   focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                   transition-all duration-200 text-gray-700"
                        >
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Nouveau mot de passe --}}
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                        Nouveau mot de passe
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="8 caractères minimum"
                            required
                            autocomplete="new-password"
                            class="w-full pl-11 pr-12 py-3 rounded-xl border
                                   {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white' }}
                                   focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                   transition-all duration-200 text-gray-700 placeholder-gray-400"
                        >
                        <button type="button"
                                onclick="togglePassword('password', 'eye1')"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                            <i id="eye1" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>

                    {{-- Indicateur de force --}}
                    <div class="mt-2 flex gap-1" id="strength-bars">
                        <div class="h-1 flex-1 rounded-full bg-gray-200" id="bar1"></div>
                        <div class="h-1 flex-1 rounded-full bg-gray-200" id="bar2"></div>
                        <div class="h-1 flex-1 rounded-full bg-gray-200" id="bar3"></div>
                        <div class="h-1 flex-1 rounded-full bg-gray-200" id="bar4"></div>
                    </div>
                    <p id="strength-label" class="text-xs text-gray-400 mt-1"></p>

                    @error('password')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Confirmation --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">
                        Confirmer le mot de passe
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder="Répétez le mot de passe"
                            required
                            autocomplete="new-password"
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
                    {{-- Match indicator --}}
                    <p id="match-label" class="text-xs mt-1 hidden"></p>
                </div>

                {{-- Bouton --}}
                <button type="submit"
                        class="w-full bg-gradient-to-r from-primary-dark to-primary-DEFAULT text-white
                               font-bold py-3.5 px-6 rounded-xl hover:shadow-lg hover:scale-[1.01]
                               active:scale-[0.99] transition-all duration-200
                               flex items-center justify-center gap-2 mt-2">
                    <i class="fas fa-check-circle"></i>
                    Réinitialiser le mot de passe
                </button>
            </form>

            <div class="mt-8 text-center">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-primary-DEFAULT transition-colors font-medium">
                    <i class="fas fa-arrow-left text-xs"></i>
                    Retour à la connexion
                </a>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        input.type  = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'password' ? 'fas fa-eye text-sm' : 'fas fa-eye-slash text-sm';
    }

    // Indicateur de force du mot de passe
    document.getElementById('password').addEventListener('input', function () {
        const val    = this.value;
        const bars   = [document.getElementById('bar1'), document.getElementById('bar2'), document.getElementById('bar3'), document.getElementById('bar4')];
        const label  = document.getElementById('strength-label');
        let score    = 0;

        if (val.length >= 8)               score++;
        if (/[A-Z]/.test(val))             score++;
        if (/[0-9]/.test(val))             score++;
        if (/[^A-Za-z0-9]/.test(val))      score++;

        const colors = ['bg-red-400', 'bg-orange-400', 'bg-yellow-400', 'bg-green-500'];
        const labels = ['Très faible', 'Faible', 'Moyen', 'Fort'];

        bars.forEach((bar, i) => {
            bar.className = 'h-1 flex-1 rounded-full ' + (i < score ? colors[score - 1] : 'bg-gray-200');
        });

        label.textContent = val.length > 0 ? labels[score - 1] ?? '' : '';
        label.className   = 'text-xs mt-1 ' + (score >= 3 ? 'text-green-600' : 'text-orange-500');
    });

    // Vérification correspondance mots de passe
    document.getElementById('password_confirmation').addEventListener('input', function () {
        const pwd   = document.getElementById('password').value;
        const label = document.getElementById('match-label');
        label.classList.remove('hidden');
        if (this.value === pwd) {
            label.textContent = '✓ Les mots de passe correspondent';
            label.className   = 'text-xs mt-1 text-green-600';
        } else {
            label.textContent = '✗ Les mots de passe ne correspondent pas';
            label.className   = 'text-xs mt-1 text-red-500';
        }
    });
</script>
@endsection
