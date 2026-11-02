@extends('layouts.app-auth')

@section('title', 'Authentification à deux facteurs')
@section('page-title', 'Authentification à deux facteurs')

@section('content')
@php
    $erreurCode = $errors->confirmTwoFactorAuthentication->first('code') ?: $errors->first('code');
    $messages = [
        'deux-facteurs-active'    => 'Scannez le QR code puis saisissez le code affiché pour terminer l\'activation.',
        'deux-facteurs-confirme'  => 'Authentification à deux facteurs activée. Conservez vos codes de récupération.',
        'codes-regeneres'         => 'Nouveaux codes de récupération générés : les anciens ne fonctionnent plus.',
        'deux-facteurs-desactive' => 'Authentification à deux facteurs désactivée.',
    ];
@endphp
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <a href="{{ route('profile.edit') }}" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-teal-700" style="text-decoration:none">
        <i class="fas fa-arrow-left"></i> Retour au profil
    </a>

    @if(isset($messages[session('status')]))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center gap-2">
            <i class="fas fa-check-circle"></i> {{ $messages[session('status')] }}
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #1a2744, #0d9488)">
                    <i class="fas fa-mobile-alt text-white"></i>
                </div>
                <div>
                    <h1 class="font-semibold text-slate-800">Authentification à deux facteurs</h1>
                    <p class="text-slate-400 text-xs">Un code temporaire (TOTP) est demandé à chaque connexion, en plus du mot de passe.</p>
                </div>
            </div>
            @if($active)
                <span class="text-xs font-semibold text-emerald-600 bg-emerald-100 px-3 py-1 rounded-full whitespace-nowrap">Activée</span>
            @elseif($enCours)
                <span class="text-xs font-semibold text-amber-600 bg-amber-100 px-3 py-1 rounded-full whitespace-nowrap">À confirmer</span>
            @else
                <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-full whitespace-nowrap">Désactivée</span>
            @endif
        </div>

        {{-- 1. Désactivée --}}
        @if(! $active && ! $enCours)
            <p class="text-sm text-slate-600 leading-relaxed mb-5">
                Installez une application d'authentification (Google Authenticator, Microsoft Authenticator, FreeOTP…)
                sur votre téléphone, puis activez la protection.
            </p>
            <form method="POST" action="{{ route('deux-facteurs.store') }}">
                @csrf
                <button type="submit" class="btn-primary inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold"
                        style="background: linear-gradient(135deg, #1B4332, #0d9488)">
                    <i class="fas fa-shield-alt"></i> Activer la double authentification
                </button>
            </form>
        @endif

        {{-- 2. En attente de confirmation : QR code + clé + code --}}
        @if($enCours)
            <div class="grid sm:grid-cols-2 gap-6 items-start">
                <div class="flex flex-col items-center gap-3">
                    <div class="p-3 bg-white border border-slate-200 rounded-xl">{!! $user->twoFactorQrCodeSvg() !!}</div>
                    <p class="text-xs text-slate-500 text-center">Scannez ce QR code avec votre application.</p>
                </div>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 mb-1">Ou saisissez cette clé manuellement :</p>
                        <code class="block p-3 bg-slate-50 border border-slate-200 rounded-lg text-sm font-mono break-all">{{ $cle }}</code>
                    </div>
                    <form method="POST" action="{{ route('deux-facteurs.confirmer') }}" class="space-y-3" novalidate>
                        @csrf
                        <label for="code" class="block text-sm font-semibold text-slate-700">Code à 6 chiffres</label>
                        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus
                               placeholder="123456"
                               class="w-full px-4 py-2.5 rounded-xl border text-center tracking-[0.4em] font-semibold {{ $erreurCode ? 'border-red-400 bg-red-50' : 'border-slate-200' }}">
                        @if($erreurCode)
                            <p class="text-xs text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $erreurCode }}</p>
                        @endif
                        <button type="submit" class="w-full inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-semibold"
                                style="background: linear-gradient(135deg, #1B4332, #0d9488)">
                            <i class="fas fa-check"></i> Confirmer l'activation
                        </button>
                    </form>
                    <form method="POST" action="{{ route('deux-facteurs.destroy') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full text-xs text-slate-500 hover:text-red-600">Annuler l'activation</button>
                    </form>
                </div>
            </div>
        @endif

        {{-- 3. Activée : codes de récupération --}}
        @if($active)
            <div class="space-y-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800 mb-1">Codes de récupération</h2>
                    <p class="text-xs text-slate-500 mb-3">
                        Gardez-les en lieu sûr : chacun permet de se connecter une seule fois si vous perdez votre téléphone.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 p-4 bg-slate-50 border border-slate-200 rounded-xl">
                        @foreach($codes as $code)
                            <code class="text-sm font-mono text-slate-700">{{ $code }}</code>
                        @endforeach
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('deux-facteurs.codes') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            <i class="fas fa-sync-alt"></i> Régénérer les codes
                        </button>
                    </form>
                    <form method="POST" action="{{ route('deux-facteurs.destroy') }}"
                          onsubmit="return confirm('Désactiver la double authentification ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-red-200 text-sm font-semibold text-red-600 hover:bg-red-50">
                            <i class="fas fa-times-circle"></i> Désactiver
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
