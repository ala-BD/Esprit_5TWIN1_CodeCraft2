@extends('layouts.app')

@section('body-class', 'no-hero')

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
                <i class="fas fa-lock text-white text-3xl"></i>
            </div>
            <h2 class="font-display text-4xl font-bold text-white leading-tight mb-4">
                Mot de passe<br>
                <span class="text-secondary-DEFAULT">oublié ?</span>
            </h2>
            <p class="text-white/70 text-lg leading-relaxed max-w-md mb-8">
                Pas de panique. Entrez votre adresse e-mail et nous vous enverrons un lien pour réinitialiser votre mot de passe.
            </p>

            {{-- Étapes --}}
            <div class="space-y-4 max-w-sm">
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 rounded-full bg-secondary-DEFAULT/20 border border-secondary-DEFAULT/30 flex items-center justify-center flex-shrink-0">
                        <span class="text-secondary-DEFAULT font-bold text-sm">1</span>
                    </div>
                    <p class="text-white/70 text-sm">Entrez votre adresse e-mail</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 rounded-full bg-secondary-DEFAULT/20 border border-secondary-DEFAULT/30 flex items-center justify-center flex-shrink-0">
                        <span class="text-secondary-DEFAULT font-bold text-sm">2</span>
                    </div>
                    <p class="text-white/70 text-sm">Recevez un lien sécurisé par email</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-9 h-9 rounded-full bg-secondary-DEFAULT/20 border border-secondary-DEFAULT/30 flex items-center justify-center flex-shrink-0">
                        <span class="text-secondary-DEFAULT font-bold text-sm">3</span>
                    </div>
                    <p class="text-white/70 text-sm">Créez un nouveau mot de passe</p>
                </div>
            </div>
        </div>

        {{-- Bas --}}
        <div class="relative flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/15">
            <i class="fas fa-shield-alt text-secondary-DEFAULT text-xl flex-shrink-0"></i>
            <p class="text-white/70 text-sm">
                Le lien est valable <strong class="text-white">60 minutes</strong> pour des raisons de sécurité.
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
                <i class="fas fa-key text-primary-DEFAULT text-2xl"></i>
            </div>

            {{-- Titre --}}
            <div class="mb-8">
                <h1 class="font-display text-3xl font-bold text-gray-900 mb-2">
                    Mot de passe oublié
                </h1>
                <p class="text-gray-500 text-sm leading-relaxed">
                    Entrez l'adresse e-mail associée à votre compte RETISS. Nous vous enverrons un lien de réinitialisation.
                </p>
            </div>

            {{-- Message succès --}}
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fas fa-check text-green-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-green-800 font-semibold text-sm">Email envoyé !</p>
                        <p class="text-green-600 text-sm mt-0.5">{{ session('status') }}</p>
                    </div>
                </div>
            @endif

            {{-- Formulaire --}}
            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
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
                            required
                            autofocus
                            class="w-full pl-11 pr-4 py-3 rounded-xl border
                                   {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white' }}
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

                {{-- Bouton --}}
                <button type="submit"
                        class="w-full bg-gradient-to-r from-primary-dark to-primary-DEFAULT text-white
                               font-bold py-3.5 px-6 rounded-xl hover:shadow-lg hover:scale-[1.01]
                               active:scale-[0.99] transition-all duration-200
                               flex items-center justify-center gap-2">
                    <i class="fas fa-paper-plane"></i>
                    Envoyer le lien de réinitialisation
                </button>
            </form>

            {{-- Retour login --}}
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
