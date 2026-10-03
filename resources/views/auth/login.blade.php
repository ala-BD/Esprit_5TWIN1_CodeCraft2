@extends('layouts.app')

@section('title', 'Connexion')

@section('styles')
<style>
    .auth-bg {
        background: linear-gradient(135deg, #1B4332 0%, #2D6A4F 50%, #52B788 100%);
    }
</style>
@endsection

@section('content')
<div class="min-h-screen flex">

    {{-- ---- Panneau gauche : illustration / branding ---- --}}
    <div class="hidden lg:flex lg:w-1/2 auth-bg relative overflow-hidden flex-col justify-between p-12">

        {{-- Cercles décoratifs --}}
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
                Bienvenue sur<br>
                <span class="text-secondary-DEFAULT">la plateforme</span><br>
                du textile circulaire
            </h2>
            <p class="text-white/70 text-lg leading-relaxed max-w-md mb-10">
                Connectez-vous pour accéder à votre espace et continuer à faire la différence.
            </p>

            {{-- Cards stats --}}
            <div class="grid grid-cols-2 gap-4 max-w-sm">
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 border border-white/15">
                    <p class="text-2xl font-bold text-white">12k+</p>
                    <p class="text-white/60 text-xs mt-1">Vêtements sauvés</p>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 border border-white/15">
                    <p class="text-2xl font-bold text-white">3.2t</p>
                    <p class="text-white/60 text-xs mt-1">CO₂ économisé</p>
                </div>
            </div>
        </div>

        {{-- Citation bas --}}
        <div class="relative bg-white/10 backdrop-blur-sm rounded-2xl p-5 border border-white/15">
            <i class="fas fa-quote-left text-secondary-DEFAULT text-xl mb-3 block"></i>
            <p class="text-white/80 text-sm leading-relaxed italic">
                "Chaque vêtement donné évite en moyenne 2.4 kg de CO₂ et préserve 3 000 litres d'eau."
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

            {{-- Titre --}}
            <div class="mb-8">
                <h1 class="font-display text-3xl font-bold text-gray-900 mb-2">Connexion</h1>
                <p class="text-gray-500">Pas encore de compte ?
                    <a href="{{ route('register') }}" class="text-primary-DEFAULT font-semibold hover:text-primary-dark transition-colors">
                        S'inscrire gratuitement
                    </a>
                </p>
            </div>

            {{-- Message d'erreur global --}}
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

            {{-- Message session --}}
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-green-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-green-800 font-semibold text-sm">Inscription réussie !</p>
                        <p class="text-green-600 text-sm mt-0.5">{{ session('status') }}</p>
                    </div>
                </div>
            @endif

            {{-- Formulaire --}}
            <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
                @csrf

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
                            value="{{ old('email') }}"
                            placeholder="vous@exemple.com"
                            autofocus
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

                {{-- Mot de passe --}}
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label for="password" class="block text-sm font-semibold text-gray-700">
                            Mot de passe
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                               class="text-xs text-primary-DEFAULT hover:text-primary-dark font-medium transition-colors">
                                Mot de passe oublié ?
                            </a>
                        @endif
                    </div>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="••••••••"
                            class="w-full pl-11 pr-12 py-3 rounded-xl border {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white' }}
                                   focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent
                                   transition-all duration-200 text-gray-700 placeholder-gray-400"
                        >
                        <button type="button"
                                onclick="togglePassword('password', 'eye-icon')"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                            <i id="eye-icon" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Se souvenir --}}
                <div class="flex items-center gap-3">
                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                        class="w-4 h-4 rounded border-gray-300 text-primary-DEFAULT focus:ring-primary-DEFAULT cursor-pointer"
                    >
                    <label for="remember" class="text-sm text-gray-600 cursor-pointer select-none">
                        Se souvenir de moi
                    </label>
                </div>

                {{-- Bouton submit --}}
                <button type="submit"
                        class="w-full bg-gradient-to-r from-primary-dark to-primary-DEFAULT text-white font-bold py-3.5 px-6 rounded-xl
                               hover:shadow-lg hover:scale-[1.01] active:scale-[0.99] transition-all duration-200
                               flex items-center justify-center gap-2 mt-2">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </button>
            </form>

            {{-- Séparateur --}}
            <div class="flex items-center gap-4 my-6">
                <div class="flex-1 h-px bg-gray-200"></div>
                <span class="text-xs text-gray-400 font-medium">ou</span>
                <div class="flex-1 h-px bg-gray-200"></div>
            </div>

            {{-- Lien vers register --}}
            <p class="text-center text-sm text-gray-500">
                Vous n'avez pas de compte ?
                <a href="{{ route('register') }}" class="text-primary-DEFAULT font-semibold hover:text-primary-dark transition-colors">
                    Créer un compte
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
</script>
@endsection
