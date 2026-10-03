@extends('layouts.app')

@section('body-class', 'no-hero')

@section('styles')
<style>
    .dot-grid {
        background-image: radial-gradient(circle, rgba(45,212,191,0.15) 1px, transparent 1px);
        background-size: 28px 28px;
    }
</style>
@endsection

@section('content')
<div class="min-h-screen flex">

    {{-- ===== PANNEAU GAUCHE — Branding ===== --}}
    <div class="hidden lg:flex lg:w-[45%] relative overflow-hidden flex-col justify-between p-12 auth-panel">

        <div class="absolute inset-0 dot-grid opacity-30"></div>
        <div class="absolute top-0 right-0 w-96 h-96 rounded-full opacity-10 blur-3xl"
             style="background: radial-gradient(circle, #2DD4BF, transparent)"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 rounded-full opacity-10 blur-3xl"
             style="background: radial-gradient(circle, #4ade80, transparent)"></div>

        {{-- Logo --}}
        <div class="relative flex items-center gap-3">
            @if(file_exists(public_path('images/logo.png')))
                <img src="{{ asset('images/logo.png') }}" alt="RETISS" class="h-10 w-10 object-contain">
            @else
                <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                     style="background: rgba(255,255,255,0.15)">
                    <i class="fas fa-recycle text-white"></i>
                </div>
            @endif
            <div>
                <p class="font-display font-bold text-xl text-white tracking-wide">RETISS</p>
                <p class="text-[10px] tracking-widest uppercase font-medium" style="color:#2DD4BF">Textile Circulaire</p>
            </div>
        </div>

        {{-- Contenu central --}}
        <div class="relative flex-1 flex flex-col justify-center py-12">
            <h2 class="font-display text-4xl xl:text-5xl font-bold text-white leading-tight mb-5">
                Bienvenue<br>
                sur la plateforme<br>
                <span style="color:#2DD4BF">du textile circulaire</span>
            </h2>
            <p class="text-white/60 text-base leading-relaxed max-w-sm mb-10">
                Connectez-vous pour accéder à votre espace et continuer à faire la différence.
            </p>

            {{-- Stats --}}
            <div class="grid grid-cols-2 gap-4 max-w-xs">
                @foreach([['12k+','Vêtements sauvés'],['3.2t','CO₂ économisé']] as [$v,$l])
                <div class="rounded-2xl p-5" style="background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.1)">
                    <p class="font-display text-2xl font-bold text-white">{{ $v }}</p>
                    <p class="text-white/50 text-xs mt-1">{{ $l }}</p>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Citation --}}
        <div class="relative rounded-2xl p-5" style="background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.1)">
            <i class="fas fa-quote-left text-xl mb-3 block" style="color:#2DD4BF"></i>
            <p class="text-white/70 text-sm italic leading-relaxed">
                "Chaque vêtement donné évite en moyenne 2.4 kg de CO₂ et préserve 3 000 litres d'eau."
            </p>
        </div>
    </div>

    {{-- ===== PANNEAU DROIT — Formulaire ===== --}}
    <div class="w-full lg:w-[55%] flex items-center justify-center p-6 sm:p-12 bg-white">
        <div class="w-full max-w-md fade-up">

            {{-- Header mobile --}}
            <div class="flex items-center gap-2 mb-8 lg:hidden">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                     style="background: linear-gradient(135deg, #1a2744, #0d9488)">
                    <i class="fas fa-recycle text-white text-xs"></i>
                </div>
                <span class="font-display font-bold text-xl text-navy">RETISS</span>
            </div>

            {{-- Titre --}}
            <div class="mb-8">
                <h1 class="font-display text-3xl font-bold text-navy mb-2">Connexion</h1>
                <p class="text-slate-500 text-sm">
                    Pas encore de compte ?
                    <a href="{{ route('register') }}" class="font-semibold transition-colors"
                       style="color:#0d9488" onmouseover="this.style.color='#0f766e'" onmouseout="this.style.color='#0d9488'">
                        S'inscrire gratuitement
                    </a>
                </p>
            </div>

            {{-- Message succès inscription --}}
            @if (session('status'))
                <div class="rounded-2xl p-4 mb-6 flex items-start gap-3"
                     style="background:#f0fdf4; border:1px solid #bbf7d0">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                         style="background:#dcfce7">
                        <i class="fas fa-check text-sm" style="color:#16a34a"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm" style="color:#166534">Inscription réussie !</p>
                        <p class="text-sm mt-0.5" style="color:#15803d">{{ session('status') }}</p>
                    </div>
                </div>
            @endif

            {{-- Erreurs --}}
            @if ($errors->any())
                <div class="rounded-2xl p-4 mb-6 flex items-start gap-3"
                     style="background:#fff5f5; border:1px solid #fecaca">
                    <i class="fas fa-exclamation-circle mt-0.5 flex-shrink-0" style="color:#ef4444"></i>
                    <div>
                        @foreach ($errors->all() as $error)
                            <p class="text-sm" style="color:#dc2626">{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Formulaire --}}
            <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">
                        Adresse e-mail
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2" style="color:#94a3b8">
                            <i class="fas fa-envelope text-sm"></i>
                        </span>
                        <input type="text" id="email" name="email"
                               value="{{ old('email') }}"
                               placeholder="vous@exemple.com"
                               autofocus
                               class="input-retiss {{ $errors->has('email') ? 'error' : '' }}">
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs flex items-center gap-1" style="color:#ef4444">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Mot de passe --}}
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label for="password" class="text-sm font-semibold text-slate-700">
                            Mot de passe
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                               class="text-xs font-medium transition-colors"
                               style="color:#0d9488">
                                Mot de passe oublié ?
                            </a>
                        @endif
                    </div>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2" style="color:#94a3b8">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input type="password" id="password" name="password"
                               placeholder="••••••••"
                               class="input-retiss {{ $errors->has('password') ? 'error' : '' }}"
                               style="padding-right: 3rem">
                        <button type="button" onclick="togglePwd('password','eyeLogin')"
                                class="absolute right-4 top-1/2 -translate-y-1/2 transition-colors"
                                style="color:#94a3b8">
                            <i id="eyeLogin" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs flex items-center gap-1" style="color:#ef4444">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Remember --}}
                <div class="flex items-center gap-3">
                    <input type="checkbox" id="remember" name="remember"
                           class="w-4 h-4 rounded cursor-pointer" style="accent-color:#0d9488">
                    <label for="remember" class="text-sm text-slate-600 cursor-pointer select-none">
                        Se souvenir de moi
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn-primary w-full py-4 rounded-2xl text-base mt-2">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </button>
            </form>

            {{-- Séparateur --}}
            <div class="flex items-center gap-4 my-7">
                <div class="flex-1 h-px bg-slate-200"></div>
                <span class="text-xs text-slate-400 font-medium">ou</span>
                <div class="flex-1 h-px bg-slate-200"></div>
            </div>

            <p class="text-center text-sm text-slate-500">
                Vous n'avez pas de compte ?
                <a href="{{ route('register') }}" class="font-semibold transition-colors"
                   style="color:#0d9488">
                    Créer un compte
                </a>
            </p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function togglePwd(id, iconId) {
        const i = document.getElementById(id);
        const ic = document.getElementById(iconId);
        i.type = i.type === 'password' ? 'text' : 'password';
        ic.className = i.type === 'password' ? 'fas fa-eye text-sm' : 'fas fa-eye-slash text-sm';
    }
</script>
@endsection
