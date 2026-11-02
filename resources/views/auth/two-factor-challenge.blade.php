@extends('layouts.app')

@section('title', 'Code de vérification')

@section('content')
@php $modeRecuperation = $errors->has('recovery_code') || old('recovery_code'); @endphp
<div class="min-h-screen flex">

    {{-- Panneau gauche --}}
    <div class="hidden lg:flex lg:w-1/2 auth-bg relative overflow-hidden flex-col justify-between p-12">
        <div class="absolute top-[-60px] right-[-60px] w-80 h-80 bg-white/5 rounded-full blur-3xl"></div>
        <div class="relative flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                <span class="text-white font-black text-base">R</span>
            </div>
            <span class="font-display font-bold text-2xl text-white tracking-tight">RETISS</span>
        </div>
        <div class="relative flex-1 flex flex-col justify-center py-12">
            <div class="w-20 h-20 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center mb-8">
                <i class="fas fa-mobile-alt text-white text-3xl"></i>
            </div>
            <h2 class="font-display text-4xl font-bold text-white leading-tight mb-4">
                Double <span class="text-secondary-DEFAULT">vérification</span>
            </h2>
            <p class="text-white/70 text-lg leading-relaxed max-w-md">
                Votre compte est protégé par l'authentification à deux facteurs.
            </p>
        </div>
        <div class="relative flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-2xl p-4 border border-white/15">
            <i class="fas fa-lock text-secondary-DEFAULT text-xl flex-shrink-0"></i>
            <p class="text-white/70 text-sm">Le code change <strong class="text-white">toutes les 30 secondes</strong>.</p>
        </div>
    </div>

    {{-- Panneau droit --}}
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-10 bg-[#FAFAF8]">
        <div class="w-full max-w-md fade-in">

            <div class="w-16 h-16 rounded-2xl bg-primary-DEFAULT/10 border border-primary-DEFAULT/20 flex items-center justify-center mb-6">
                <i class="fas fa-shield-alt text-primary-DEFAULT text-2xl"></i>
            </div>

            <div class="mb-8">
                <h1 class="font-display text-3xl font-bold text-gray-900 mb-2">Code de vérification</h1>
                <p id="aide-code" class="text-gray-500 text-sm leading-relaxed {{ $modeRecuperation ? 'hidden' : '' }}">
                    Ouvrez votre application d'authentification (Google Authenticator, Microsoft Authenticator…)
                    et saisissez le code à 6 chiffres affiché pour RETISS.
                </p>
                <p id="aide-recuperation" class="text-gray-500 text-sm leading-relaxed {{ $modeRecuperation ? '' : 'hidden' }}">
                    Saisissez l'un de vos codes de récupération. Chaque code ne fonctionne qu'une seule fois.
                </p>
            </div>

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex items-start gap-3">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5 flex-shrink-0"></i>
                    <div>@foreach($errors->all() as $error)<p class="text-red-600 text-sm">{{ $error }}</p>@endforeach</div>
                </div>
            @endif

            <form method="POST" action="{{ route('deux-facteurs.verifier') }}" novalidate>
                @csrf

                <div id="bloc-code" class="mb-5 {{ $modeRecuperation ? 'hidden' : '' }}">
                    <label for="code" class="block text-sm font-semibold text-gray-700 mb-2">Code à 6 chiffres</label>
                    <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
                           maxlength="6" placeholder="123456" {{ $modeRecuperation ? '' : 'autofocus' }}
                           class="w-full px-4 py-3 rounded-xl border tracking-[0.5em] text-center text-lg font-semibold
                                  {{ $errors->has('code') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white' }}
                                  focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent text-gray-700">
                </div>

                <div id="bloc-recuperation" class="mb-5 {{ $modeRecuperation ? '' : 'hidden' }}">
                    <label for="recovery_code" class="block text-sm font-semibold text-gray-700 mb-2">Code de récupération</label>
                    <input type="text" id="recovery_code" name="recovery_code" autocomplete="off"
                           placeholder="abcde12345-fghij67890" value="{{ old('recovery_code') }}"
                           class="w-full px-4 py-3 rounded-xl border font-mono
                                  {{ $errors->has('recovery_code') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-white' }}
                                  focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT focus:border-transparent text-gray-700">
                </div>

                <button type="submit"
                        class="w-full bg-gradient-to-r from-primary-dark to-primary-DEFAULT text-white
                               font-bold py-3.5 px-6 rounded-xl hover:shadow-lg hover:scale-[1.01]
                               transition-all duration-200 flex items-center justify-center gap-2">
                    <i class="fas fa-sign-in-alt"></i>
                    Valider et se connecter
                </button>
            </form>

            <button type="button" onclick="basculerModeCode()"
                    class="mt-5 w-full text-sm font-semibold text-primary-DEFAULT hover:underline">
                <span id="lien-recuperation" class="{{ $modeRecuperation ? 'hidden' : '' }}">Utiliser un code de récupération</span>
                <span id="lien-code" class="{{ $modeRecuperation ? '' : 'hidden' }}">Utiliser le code de l'application</span>
            </button>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-xs text-gray-400 hover:text-gray-600">← Revenir à la connexion</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Bascule entre le code de l'application et un code de récupération (un seul champ envoyé)
    function basculerModeCode() {
        ['aide-code', 'aide-recuperation', 'bloc-code', 'bloc-recuperation', 'lien-recuperation', 'lien-code']
            .forEach(id => document.getElementById(id).classList.toggle('hidden'));

        const recuperation = ! document.getElementById('bloc-recuperation').classList.contains('hidden');
        document.getElementById('code').value = '';
        document.getElementById('recovery_code').value = '';
        document.getElementById(recuperation ? 'recovery_code' : 'code').focus();
    }
</script>
@endsection
