@extends('layouts.app-auth')

@php
    /** @var \App\Models\User $user */
    $user = $user ?? Auth::user();
@endphp

@section('title', 'Mon Profil')

@section('styles')
<style>
    /* ============================================================
       PROFILE PAGE — RETISS Design System
       ============================================================ */

    .profile-page {
        min-height: 100vh;
        background: linear-gradient(160deg, #f0fdf9 0%, #f8fafc 40%, #f0f4ff 100%);
        padding-top: 72px;
    }

    /* ---- Hero Banner ---- */
    .profile-hero {
        background: linear-gradient(135deg, #111a30 0%, #1a2744 35%, #1B4332 70%, #0d9488 100%);
        position: relative;
        overflow: hidden;
        padding: 3rem 0 6rem;
    }
    .profile-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            radial-gradient(ellipse at 80% 50%, rgba(13,148,136,0.25) 0%, transparent 60%),
            radial-gradient(ellipse at 20% 80%, rgba(74,222,128,0.1) 0%, transparent 50%);
        pointer-events: none;
    }
    .profile-hero-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
        background-size: 40px 40px;
        pointer-events: none;
    }
    .profile-hero-orbs {
        position: absolute;
        inset: 0;
        pointer-events: none;
    }
    .profile-hero-orbs::before {
        content: '';
        position: absolute;
        width: 300px; height: 300px;
        top: -80px; right: -60px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(45,212,191,0.15) 0%, transparent 70%);
    }
    .profile-hero-orbs::after {
        content: '';
        position: absolute;
        width: 200px; height: 200px;
        bottom: -40px; left: 10%;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(74,222,128,0.1) 0%, transparent 70%);
    }

    /* ---- Identity card (floating) ---- */
    .profile-card-wrap {
        margin-top: -5rem;
        position: relative;
        z-index: 10;
    }
    .profile-card {
        background: #fff;
        border-radius: 2rem;
        border: 1px solid rgba(226,232,240,0.8);
        box-shadow: 0 24px 64px rgba(15,23,42,0.12), 0 4px 16px rgba(13,148,136,0.06);
        overflow: hidden;
        transition: box-shadow 0.3s;
    }

    /* ---- Avatar ---- */
    .profile-avatar {
        width: 100px; height: 100px;
        border-radius: 1.5rem;
        background: linear-gradient(135deg, #0d9488, #4ade80);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 800;
        color: #fff;
        letter-spacing: -1px;
        box-shadow: 0 8px 32px rgba(13,148,136,0.35);
        flex-shrink: 0;
        position: relative;
    }
    .profile-avatar-ring {
        position: absolute;
        inset: -4px;
        border-radius: 1.75rem;
        background: linear-gradient(135deg, #0d9488, #4ade80);
        z-index: -1;
        opacity: 0.3;
    }

    /* ---- Role badge ---- */
    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.85rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .role-badge.donateur  { background: #f0fdf4; color: #15803d; border: 1.5px solid #86efac; }
    .role-badge.client    { background: #eff6ff; color: #1d4ed8; border: 1.5px solid #93c5fd; }
    .role-badge.collecteur{ background: #fef3c7; color: #b45309; border: 1.5px solid #fcd34d; }
    .role-badge.atelier   { background: #f5f3ff; color: #7c3aed; border: 1.5px solid #c4b5fd; }
    .role-badge.recycleur { background: #f0fdf9; color: #0f766e; border: 1.5px solid #5eead4; }
    .role-badge.admin     { background: #fff1f2; color: #be123c; border: 1.5px solid #fda4af; }

    /* ---- Status dot ---- */
    .status-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #4ade80;
        box-shadow: 0 0 0 0 rgba(74,222,128,0.4);
        animation: pulse-dot 2s infinite;
        display: inline-block;
    }
    @keyframes pulse-dot {
        0%   { box-shadow: 0 0 0 0 rgba(74,222,128,0.4); }
        70%  { box-shadow: 0 0 0 6px rgba(74,222,128,0); }
        100% { box-shadow: 0 0 0 0 rgba(74,222,128,0); }
    }

    /* ---- Stat chips ---- */
    .stat-chip {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0.75rem 1.25rem;
        border-radius: 1rem;
        background: linear-gradient(135deg, #f0fdf9, #f8fafc);
        border: 1px solid #e2e8f0;
        min-width: 80px;
        transition: all 0.2s;
    }
    .stat-chip:hover {
        border-color: #0d9488;
        box-shadow: 0 4px 16px rgba(13,148,136,0.1);
        transform: translateY(-2px);
    }
    .stat-chip .val  { font-size: 1.35rem; font-weight: 800; color: #0d9488; line-height: 1; }
    .stat-chip .lbl  { font-size: 0.65rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; margin-top: 2px; }

    /* ---- Tab nav ---- */
    .profile-tabs {
        display: flex;
        gap: 0.25rem;
        background: #f1f5f9;
        padding: 0.35rem;
        border-radius: 1rem;
        width: fit-content;
    }
    .tab-btn {
        padding: 0.6rem 1.4rem;
        border-radius: 0.75rem;
        font-size: 0.85rem;
        font-weight: 600;
        border: none;
        background: transparent;
        color: #64748b;
        cursor: pointer;
        transition: all 0.22s;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        white-space: nowrap;
    }
    .tab-btn:hover { color: #0d9488; background: rgba(13,148,136,0.06); }
    .tab-btn.active {
        background: #fff;
        color: #0d9488;
        box-shadow: 0 2px 8px rgba(15,23,42,0.08);
    }

    /* ---- Tab panels ---- */
    .tab-panel { display: none; }
    .tab-panel.active { display: block; animation: fadeUp 0.35s ease-out; }

    /* ---- Form labels / inputs styled ---- */
    .form-label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        letter-spacing: 0.01em;
    }
    .form-input {
        width: 100%;
        padding: 0.85rem 1rem 0.85rem 2.8rem;
        border-radius: 0.875rem;
        border: 1.5px solid #e2e8f0;
        background: #fff;
        font-size: 0.9rem;
        color: #0f172a;
        transition: all 0.2s;
        outline: none;
        font-family: 'Inter', sans-serif;
    }
    .form-input:focus {
        border-color: #0d9488;
        box-shadow: 0 0 0 3px rgba(13,148,136,0.1);
    }
    .form-input.no-icon {
        padding-left: 1rem;
    }
    .input-wrap { position: relative; }
    .input-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.85rem;
        pointer-events: none;
        transition: color 0.2s;
    }
    .input-wrap:focus-within .input-icon { color: #0d9488; }

    /* Read-only info card */
    .info-card {
        background: linear-gradient(135deg, #f0fdf9 0%, #f8fafc 100%);
        border: 1px solid #d1fae5;
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.85rem;
    }
    .info-card-icon {
        width: 36px; height: 36px;
        border-radius: 0.625rem;
        background: linear-gradient(135deg, #0d9488, #4ade80);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    /* ---- Strength meter ---- */
    .strength-track {
        height: 5px;
        border-radius: 9999px;
        background: #e2e8f0;
        overflow: hidden;
        margin-top: 0.5rem;
    }
    .strength-bar {
        height: 100%;
        border-radius: 9999px;
        transition: width 0.4s ease, background 0.4s ease;
        width: 0%;
    }

    /* ---- Danger zone ---- */
    .danger-zone {
        border: 1.5px solid #fecaca;
        border-radius: 1.25rem;
        background: linear-gradient(135deg, #fff5f5 0%, #fff 100%);
        padding: 1.5rem;
    }
    .btn-danger {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #dc2626, #ef4444);
        color: #fff;
        font-weight: 700;
        padding: 0.8rem 1.5rem;
        border-radius: 0.875rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 0.875rem;
    }
    .btn-danger:hover {
        background: linear-gradient(135deg, #b91c1c, #dc2626);
        box-shadow: 0 8px 24px rgba(220,38,38,0.35);
        transform: translateY(-1px);
    }

    /* ---- Modal ---- */
    .profile-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15,23,42,0.6);
        z-index: 999;
        backdrop-filter: blur(4px);
        align-items: center;
        justify-content: center;
    }
    .profile-modal-overlay.open { display: flex; animation: fadeIn 0.2s ease-out; }
    .profile-modal {
        background: #fff;
        border-radius: 1.5rem;
        width: 100%;
        max-width: 440px;
        margin: 1rem;
        box-shadow: 0 24px 64px rgba(15,23,42,0.2);
        overflow: hidden;
        animation: slideUp 0.3s ease-out;
    }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ---- Save success toast ---- */
    .toast {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: linear-gradient(135deg, #0d9488, #4ade80);
        color: #fff;
        padding: 1rem 1.5rem;
        border-radius: 1rem;
        font-weight: 600;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 8px 32px rgba(13,148,136,0.4);
        z-index: 9999;
        animation: toastIn 0.4s ease-out forwards;
    }
    @keyframes toastIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .toast.hiding { animation: toastOut 0.4s ease-in forwards; }
    @keyframes toastOut {
        from { opacity: 1; transform: translateY(0); }
        to   { opacity: 0; transform: translateY(10px); }
    }

    /* ---- Role cards ---- */
    .role-card {
        background: #fff;
        border: 1.5px solid #e2e8f0;
        border-radius: 1.25rem;
        padding: 1.35rem;
        transition: all 0.25s ease;
        position: relative;
        display: flex;
        flex-direction: column;
        user-select: none;
    }
    .role-card:not(.role-card-active):hover {
        border-color: #0d9488;
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(13, 148, 136, 0.12);
    }
    .role-card-active {
        border-color: #0d9488;
        background: linear-gradient(145deg, #f0fdf9 0%, #ffffff 100%);
        box-shadow: 0 6px 20px rgba(13, 148, 136, 0.1);
    }
    .role-icon {
        width: 44px;
        height: 44px;
        border-radius: 0.875rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.1rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        flex-shrink: 0;
    }
    .role-select-btn {
        margin-top: auto;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        padding: 0.65rem 1rem;
        border-radius: 0.75rem;
        font-size: 0.8rem;
        font-weight: 700;
        background: #f1f5f9;
        color: #475569;
        transition: all 0.2s ease;
    }
    .role-card:not(.role-card-active):hover .role-select-btn {
        background: #0d9488;
        color: #fff;
        box-shadow: 0 4px 14px rgba(13, 148, 136, 0.3);
    }
    .role-select-btn-active {
        background: #ccfbf1;
        color: #0f766e;
    }
</style>
@endsection

@section('content')
<div class="profile-page">

    {{-- ================================================================
         HERO BANNER
         ================================================================ --}}
    <div class="profile-hero">
        <div class="profile-hero-grid"></div>
        <div class="profile-hero-orbs"></div>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="flex items-center gap-2 mb-3">
                <a href="{{ route('home') }}" class="text-white/50 hover:text-white/80 text-sm transition-colors" style="text-decoration:none">
                    <i class="fas fa-home"></i>
                </a>
                <i class="fas fa-chevron-right text-white/30" style="font-size:0.6rem"></i>
                <span class="text-white/70 text-sm">Mon profil</span>
            </div>
            <h1 class="font-display text-3xl font-bold text-white mb-1">
                Bonjour, {{ Auth::user()->prenom ?? Auth::user()->name }}
            </h1>
            <p class="text-white/60 text-sm">Gérez vos informations personnelles et la sécurité de votre compte.</p>
        </div>
    </div>

    {{-- ================================================================
         FLOATING IDENTITY CARD
         ================================================================ --}}
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 profile-card-wrap">
        <div class="profile-card p-6 sm:p-8 fade-up">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">

                {{-- Avatar --}}
                <div class="profile-avatar">
                    <div class="profile-avatar-ring"></div>
                    {{ Auth::user()->initials }}
                </div>

                {{-- Identity info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <h2 class="font-display text-xl font-bold text-slate-900">
                            {{ Auth::user()->full_name }}
                        </h2>
                        @php
                            $role = strtolower(Auth::user()->role);
                            $roleLabel = Auth::user()->role;
                        @endphp
                        <span class="role-badge {{ $role }}">
                            <span class="status-dot"></span>
                            {{ $roleLabel }}
                        </span>
                    </div>
                    <p class="text-slate-500 text-sm mb-3">{{ Auth::user()->email }}</p>
                    <div class="flex items-center gap-1.5 text-xs text-slate-400">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Membre depuis {{ Auth::user()->created_at->format('F Y') }}</span>
                        @if(Auth::user()->telephone)
                            <span class="mx-2">•</span>
                            <i class="fas fa-phone"></i>
                            <span>{{ Auth::user()->telephone }}</span>
                        @endif
                    </div>
                </div>

                {{-- Quick stats --}}
                @php
                    try {
                        $donCount = ($user instanceof \App\Models\User) ? $user->dons()->count() : 0;
                    } catch (\Exception $e) {
                        $donCount = 0;
                    }
                @endphp
                <div class="flex gap-2 flex-shrink-0">
                    <div class="stat-chip">
                        <span class="val">{{ $donCount }}</span>
                        <span class="lbl">Dons</span>
                    </div>
                    <div class="stat-chip">
                        <span class="val" style="color:#1B4332">{{ number_format($donCount * 2.1, 1) }}</span>
                        <span class="lbl">kg CO₂</span>
                    </div>
                    <div class="stat-chip">
                        <span class="val" style="color:#4ade80">{{ $donCount * 10 }}</span>
                        <span class="lbl">Points</span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ================================================================
         MAIN CONTENT — TABS
         ================================================================ --}}
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Flash messages --}}
        @if(session('status') === 'profile-updated')
            <div id="flash-profile" class="toast">
                <i class="fas fa-check-circle"></i> Profil mis à jour avec succès !
            </div>
        @endif
        @if(session('status') === 'password-updated')
            <div id="flash-password" class="toast">
                <i class="fas fa-check-circle"></i> Mot de passe modifié avec succès !
            </div>
        @endif
        @if(session('status') === 'role-updated')
            <div id="flash-role" class="toast">
                <i class="fas fa-user-tag"></i> Rôle mis à jour avec succès !
            </div>
        @endif

        {{-- Tab navigation --}}
        <div class="profile-tabs mb-8" role="tablist">
            <button class="tab-btn active" onclick="switchTab('info', this)" id="tab-info-btn" role="tab" aria-selected="true">
                <i class="fas fa-user"></i> Informations
            </button>
            <button class="tab-btn" onclick="switchTab('password', this)" id="tab-password-btn" role="tab" aria-selected="false">
                <i class="fas fa-lock"></i> Mot de passe
            </button>
            <button class="tab-btn" onclick="switchTab('role', this)" id="tab-role-btn" role="tab" aria-selected="false">
                <i class="fas fa-user-tag"></i> Mon Rôle
            </button>
            <button class="tab-btn" onclick="switchTab('security', this)" id="tab-security-btn" role="tab" aria-selected="false">
                <i class="fas fa-shield-alt"></i> Sécurité
            </button>
            <a href="{{ route('adresses.index') }}" class="tab-btn" style="text-decoration:none">
                <i class="fas fa-map-marker-alt"></i> Mes Adresses
            </a>
        </div>

        {{-- ================================================================
             TAB 1 — INFORMATIONS
             ================================================================ --}}
        <div id="tab-info" class="tab-panel active" role="tabpanel">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Form --}}
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #0d9488, #4ade80)">
                                <i class="fas fa-user-edit text-white text-sm"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-800 text-sm">Informations personnelles</h3>
                                <p class="text-slate-400 text-xs">Modifiez vos informations de base</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                            @csrf
                            @method('patch')

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {{-- Prénom --}}
                                <div>
                                    <label for="prenom" class="form-label">Prénom</label>
                                    <div class="input-wrap">
                                        <i class="fas fa-user input-icon"></i>
                                        <input id="prenom" name="prenom" type="text"
                                               class="form-input {{ $errors->has('prenom') ? 'border-red-400 bg-red-50' : '' }}"
                                               value="{{ old('prenom', $user->prenom) }}"
                                               placeholder="Votre prénom"
                                               autocomplete="given-name">
                                    </div>
                                    @error('prenom')
                                        <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                        </p>
                                    @enderror
                                </div>

                                {{-- Nom --}}
                                <div>
                                    <label for="name" class="form-label">Nom de famille</label>
                                    <div class="input-wrap">
                                        <i class="fas fa-id-card input-icon"></i>
                                        <input id="name" name="name" type="text"
                                               class="form-input {{ $errors->has('name') ? 'border-red-400 bg-red-50' : '' }}"
                                               value="{{ old('name', $user->name) }}"
                                               placeholder="Votre nom"
                                               required autocomplete="family-name">
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
                                <label for="email" class="form-label">Adresse e-mail</label>
                                <div class="input-wrap">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input id="email" name="email" type="email"
                                           class="form-input {{ $errors->has('email') ? 'border-red-400 bg-red-50' : '' }}"
                                           value="{{ old('email', $user->email) }}"
                                           placeholder="votre@email.com"
                                           required autocomplete="email">
                                </div>
                                @error('email')
                                    <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </p>
                                @enderror
                                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                                    <div class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-2">
                                        <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5 text-sm flex-shrink-0"></i>
                                        <div>
                                            <p class="text-xs text-amber-700">Votre adresse e-mail n'est pas vérifiée.</p>
                                            <form id="send-verification" method="post" action="{{ route('verification.send') }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs text-teal-600 font-semibold hover:underline">
                                                    Renvoyer l'e-mail de vérification →
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    @if (session('status') === 'verification-link-sent')
                                        <p class="mt-2 text-xs text-teal-600 font-medium flex items-center gap-1">
                                            <i class="fas fa-check-circle"></i> Lien de vérification envoyé !
                                        </p>
                                    @endif
                                @endif
                            </div>

                            {{-- Téléphone --}}
                            <div>
                                <label for="telephone" class="form-label">Téléphone <span class="text-slate-400 font-normal">(optionnel)</span></label>
                                <div class="input-wrap">
                                    <i class="fas fa-phone input-icon"></i>
                                    <input id="telephone" name="telephone" type="tel"
                                           class="form-input {{ $errors->has('telephone') ? 'border-red-400 bg-red-50' : '' }}"
                                           value="{{ old('telephone', $user->telephone) }}"
                                           placeholder="+216 XX XXX XXX"
                                           autocomplete="tel">
                                </div>
                                @error('telephone')
                                    <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="btn-primary w-full sm:w-auto">
                                    <i class="fas fa-save"></i>
                                    Enregistrer les modifications
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Account info sidebar --}}
                <div class="space-y-4">
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Informations du compte</h4>
                        <div class="space-y-3">
                            <div class="info-card">
                                <div class="info-card-icon"><i class="fas fa-user-tag"></i></div>
                                <div>
                                    <p class="text-xs text-slate-400">Rôle</p>
                                    <p class="text-sm font-semibold text-slate-700">{{ Auth::user()->role }}</p>
                                </div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-icon"><i class="fas fa-calendar"></i></div>
                                <div>
                                    <p class="text-xs text-slate-400">Membre depuis</p>
                                    <p class="text-sm font-semibold text-slate-700">{{ Auth::user()->created_at->format('d M Y') }}</p>
                                </div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-icon" style="background: {{ Auth::user()->actif ? 'linear-gradient(135deg,#16a34a,#4ade80)' : 'linear-gradient(135deg,#9ca3af,#d1d5db)' }}">
                                    <i class="fas fa-{{ Auth::user()->actif ? 'check' : 'times' }}"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400">Statut</p>
                                    <p class="text-sm font-semibold {{ Auth::user()->actif ? 'text-emerald-600' : 'text-slate-400' }}">
                                        {{ Auth::user()->actif ? 'Compte actif' : 'Compte inactif' }}
                                    </p>
                                </div>
                            </div>
                            <div class="info-card">
                                <div class="info-card-icon" style="background: linear-gradient(135deg, #0d9488, #2DD4BF)">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-slate-400">Adresses</p>
                                    <p class="text-sm font-semibold text-slate-700">
                                        {{ ($user instanceof \App\Models\User) ? $user->adresses()->count() : 0 }} enregistrée(s)
                                    </p>
                                </div>
                                <a href="{{ route('adresses.index') }}" class="text-xs font-bold text-teal-600 hover:text-teal-700 ml-auto" style="text-decoration:none">
                                    Gérer →
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Tips card --}}
                    <div class="rounded-2xl p-5" style="background: linear-gradient(135deg, #0d9488, #1B4332)">
                        <i class="fas fa-lightbulb text-yellow-300 mb-2 text-lg"></i>
                        <h4 class="text-white font-bold text-sm mb-1">Conseil RETISS</h4>
                        <p class="text-white/70 text-xs leading-relaxed">
                            Complétez votre profil pour débloquer toutes les fonctionnalités et maximiser votre impact environnemental.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================================================================
             TAB 2 — MOT DE PASSE
             ================================================================ --}}
        <div id="tab-password" class="tab-panel" role="tabpanel">
            <div class="max-w-xl">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 sm:p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #1a2744, #1B4332)">
                            <i class="fas fa-lock text-white text-sm"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-800 text-sm">Changer le mot de passe</h3>
                            <p class="text-slate-400 text-xs">Utilisez un mot de passe long et aléatoire</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('password.update') }}" class="space-y-5" id="password-form">
                        @csrf
                        @method('put')

                        {{-- Current password --}}
                        <div>
                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                            <div class="input-wrap">
                                <i class="fas fa-key input-icon"></i>
                                <input id="current_password" name="current_password" type="password"
                                       class="form-input {{ $errors->updatePassword->has('current_password') ? 'border-red-400 bg-red-50' : '' }}"
                                       placeholder="••••••••"
                                       autocomplete="current-password">
                            </div>
                            @error('current_password', 'updatePassword')
                                <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- New password --}}
                        <div>
                            <label for="password" class="form-label">Nouveau mot de passe</label>
                            <div class="input-wrap">
                                <i class="fas fa-lock input-icon"></i>
                                <input id="password" name="password" type="password"
                                       class="form-input {{ $errors->updatePassword->has('password') ? 'border-red-400 bg-red-50' : '' }}"
                                       placeholder="••••••••"
                                       autocomplete="new-password"
                                       oninput="checkStrength(this.value)">
                            </div>
                            {{-- Strength meter --}}
                            <div class="strength-track mt-1.5">
                                <div id="strength-bar" class="strength-bar"></div>
                            </div>
                            <p id="strength-label" class="text-xs text-slate-400 mt-1"></p>
                            @error('password', 'updatePassword')
                                <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Confirm password --}}
                        <div>
                            <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                            <div class="input-wrap">
                                <i class="fas fa-check-double input-icon"></i>
                                <input id="password_confirmation" name="password_confirmation" type="password"
                                       class="form-input {{ $errors->updatePassword->has('password_confirmation') ? 'border-red-400 bg-red-50' : '' }}"
                                       placeholder="••••••••"
                                       autocomplete="new-password">
                            </div>
                            @error('password_confirmation', 'updatePassword')
                                <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i> {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="btn-primary w-full sm:w-auto">
                                <i class="fas fa-shield-alt"></i>
                                Mettre à jour le mot de passe
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Password tips --}}
                <div class="mt-4 bg-slate-50 border border-slate-200 rounded-2xl p-5">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
                        <i class="fas fa-info-circle text-teal-500 mr-1"></i> Bonnes pratiques
                    </h4>
                    <ul class="space-y-2 text-xs text-slate-500">
                        <li class="flex items-start gap-2"><i class="fas fa-check text-teal-500 mt-0.5 flex-shrink-0"></i> Au moins 8 caractères</li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-teal-500 mt-0.5 flex-shrink-0"></i> Mélangez majuscules, minuscules et chiffres</li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-teal-500 mt-0.5 flex-shrink-0"></i> Ajoutez des caractères spéciaux (!, @, #…)</li>
                        <li class="flex items-start gap-2"><i class="fas fa-check text-teal-500 mt-0.5 flex-shrink-0"></i> Évitez les informations personnelles</li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- ================================================================
             TAB 3 — MON RÔLE
             ================================================================ --}}
        <div id="tab-role" class="tab-panel" role="tabpanel">
            @php
                $roles = [
                    'DONATEUR'   => [
                        'icon'  => 'fa-hand-holding-heart',
                        'color' => 'linear-gradient(135deg,#16a34a,#4ade80)',
                        'badge' => 'donateur',
                        'title' => 'Donateur',
                        'desc'  => 'Donnez une seconde vie à vos vêtements en les offrant à ceux qui en ont besoin.',
                        'perks' => ['Déposez des dons de vêtements', 'Suivez l\'impact environnemental de vos dons', 'Gagnez des points verts'],
                    ],
                    'CLIENT'     => [
                        'icon'  => 'fa-shopping-bag',
                        'color' => 'linear-gradient(135deg,#1d4ed8,#60a5fa)',
                        'badge' => 'client',
                        'title' => 'Client',
                        'desc'  => 'Achetez des vêtements recyclés et contribuez à l\'économie circulaire.',
                        'perks' => ['Accès à la boutique de vêtements', 'Tarifs préférentiels', 'Historique d\'achats'],
                    ],
                    'COLLECTEUR' => [
                        'icon'  => 'fa-truck',
                        'color' => 'linear-gradient(135deg,#b45309,#fbbf24)',
                        'badge' => 'collecteur',
                        'title' => 'Collecteur',
                        'desc'  => 'Organisez et gérez la collecte de vêtements dans votre zone.',
                        'perks' => ['Gérez les points de collecte', 'Planifiez les ramassages', 'Statistiques de collecte'],
                    ],
                    'ATELIER'    => [
                        'icon'  => 'fa-cut',
                        'color' => 'linear-gradient(135deg,#7c3aed,#c4b5fd)',
                        'badge' => 'atelier',
                        'title' => 'Atelier',
                        'desc'  => 'Transformez et réparez les vêtements collectés pour les remettre en circulation.',
                        'perks' => ['Accès aux lots textiles', 'Gestion des transformations', 'Suivi qualité'],
                    ],
                    'RECYCLEUR'  => [
                        'icon'  => 'fa-recycle',
                        'color' => 'linear-gradient(135deg,#0f766e,#2DD4BF)',
                        'badge' => 'recycleur',
                        'title' => 'Recycleur',
                        'desc'  => 'Prenez en charge le recyclage industriel des textiles non réutilisables.',
                        'perks' => ['Dashboard recyclage avancé', 'Passeports numériques', 'Gestion des lots textiles'],
                    ],
                ];
                $currentRole = Auth::user()->role;
            @endphp

            <div class="mb-6">
                <h3 class="font-display font-bold text-slate-800 text-lg mb-1">Changer de rôle</h3>
                <p class="text-slate-500 text-sm">Sélectionnez le rôle qui correspond le mieux à votre activité sur RETISS. Votre rôle détermine les fonctionnalités auxquelles vous avez accès.</p>
            </div>

            {{-- Current role banner --}}
            @if(isset($roles[$currentRole]))
            <div class="mb-6 p-4 rounded-2xl border-2 flex items-center gap-4"
                 style="background: linear-gradient(135deg, #f0fdf9, #f8fafc); border-color: #0d9488">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background: {{ $roles[$currentRole]['color'] }}">
                    <i class="fas {{ $roles[$currentRole]['icon'] }} text-white"></i>
                </div>
                <div class="flex-1">
                    <p class="text-xs text-slate-400 font-medium">Rôle actuel</p>
                    <p class="font-bold text-slate-800">{{ $roles[$currentRole]['title'] }}</p>
                </div>
                <span class="text-xs font-bold text-teal-600 bg-teal-100 px-3 py-1 rounded-full">
                    <i class="fas fa-check mr-1"></i>Actif
                </span>
            </div>
            @endif

            {{-- Role cards grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($roles as $roleKey => $roleData)
                    @php $isCurrent = ($currentRole === $roleKey); @endphp
                    <div class="role-card {{ $isCurrent ? 'role-card-active' : '' }}"
                         onclick="{{ $isCurrent ? '' : 'selectRole(\''. $roleKey .'\', \''. $roleData['title'] .'\')' }}"
                         style="{{ $isCurrent ? 'cursor:default' : 'cursor:pointer' }}">

                        {{-- Card top --}}
                        <div class="flex items-start justify-between mb-3">
                            <div class="role-icon" style="background: {{ $roleData['color'] }}">
                                <i class="fas {{ $roleData['icon'] }}"></i>
                            </div>
                            @if($isCurrent)
                                <span class="text-[10px] font-bold text-teal-600 bg-teal-100 px-2 py-0.5 rounded-full">
                                    <i class="fas fa-check"></i> Actuel
                                </span>
                            @endif
                        </div>

                        <h4 class="font-bold text-slate-800 mb-1">{{ $roleData['title'] }}</h4>
                        <p class="text-xs text-slate-500 mb-3 leading-relaxed">{{ $roleData['desc'] }}</p>

                        <ul class="space-y-1.5 mb-4">
                            @foreach($roleData['perks'] as $perk)
                                <li class="flex items-start gap-1.5 text-xs text-slate-600">
                                    <i class="fas fa-check-circle text-teal-500 mt-0.5 flex-shrink-0" style="font-size:0.7rem"></i>
                                    {{ $perk }}
                                </li>
                            @endforeach
                        </ul>

                        @if(!$isCurrent)
                            <div class="role-select-btn">
                                <i class="fas fa-arrow-right" style="font-size:0.7rem"></i>
                                Choisir ce rôle
                            </div>
                        @else
                            <div class="role-select-btn role-select-btn-active">
                                <i class="fas fa-check"></i>
                                Rôle actuel
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Note --}}
            <div class="mt-5 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
                <i class="fas fa-info-circle text-amber-500 mt-0.5 flex-shrink-0"></i>
                <p class="text-xs text-amber-700 leading-relaxed">
                    <strong>Note :</strong> Changer de rôle modifie immédiatement votre accès aux fonctionnalités de la plateforme.
                    Le rôle <strong>Administrateur</strong> ne peut être accordé que par un administrateur système.
                </p>
            </div>
        </div>

        {{-- ================================================================
             TAB 4 — SÉCURITÉ (Suppression compte)
             ================================================================ --}}
        <div id="tab-security" class="tab-panel" role="tabpanel">
            <div class="max-w-xl">

                {{-- Account status info --}}
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 mb-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #0d9488, #4ade80)">
                            <i class="fas fa-shield-alt text-white text-sm"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-800 text-sm">Statut de sécurité</h3>
                            <p class="text-slate-400 text-xs">Vue d'ensemble de la sécurité de votre compte</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-emerald-50 border border-emerald-100 rounded-xl">
                            <div class="flex items-center gap-2 text-sm text-emerald-700">
                                <i class="fas fa-check-circle"></i>
                                <span>Mot de passe configuré</span>
                            </div>
                            <span class="text-xs font-semibold text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded-full">Actif</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-100 rounded-xl">
                            <div class="flex items-center gap-2 text-sm text-slate-600">
                                <i class="fas fa-envelope-open-text text-slate-400"></i>
                                <span>Email vérifié</span>
                            </div>
                            @if($user->email_verified_at)
                                <span class="text-xs font-semibold text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded-full">Vérifié</span>
                            @else
                                <span class="text-xs font-semibold text-amber-600 bg-amber-100 px-2 py-0.5 rounded-full">En attente</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Danger zone --}}
                <div class="danger-zone">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fas fa-exclamation-triangle text-red-500"></i>
                        <h3 class="font-bold text-red-700 text-sm">Zone de danger</h3>
                    </div>
                    <p class="text-sm text-red-600/80 leading-relaxed mb-4">
                        La suppression de votre compte est irréversible. Toutes vos données, dons et historiques seront définitivement effacés. Cette action ne peut pas être annulée.
                    </p>
                    <button type="button" class="btn-danger" onclick="openDeleteModal()">
                        <i class="fas fa-trash-alt"></i>
                        Supprimer mon compte
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ================================================================
     DELETE CONFIRMATION MODAL
     ================================================================ --}}
<div id="delete-modal" class="profile-modal-overlay" onclick="closeDeleteModalOutside(event)">
    <div class="profile-modal">
        {{-- Modal header --}}
        <div class="p-6 pb-0">
            <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center mb-4">
                <i class="fas fa-trash-alt text-red-500 text-xl"></i>
            </div>
            <h3 class="font-bold text-slate-800 text-lg mb-1">Supprimer mon compte ?</h3>
            <p class="text-slate-500 text-sm leading-relaxed">
                Cette action est permanente et irréversible. Toutes vos données seront effacées. Entrez votre mot de passe pour confirmer.
            </p>
        </div>

        {{-- Modal form --}}
        <form method="POST" action="{{ route('profile.destroy') }}" class="p-6 pt-5">
            @csrf
            @method('delete')

            <div class="mb-5">
                <label for="delete_password" class="form-label">Mot de passe de confirmation</label>
                <div class="input-wrap">
                    <i class="fas fa-lock input-icon"></i>
                    <input id="delete_password" name="password" type="password"
                           class="form-input {{ $errors->userDeletion->has('password') ? 'border-red-400 bg-red-50' : '' }}"
                           placeholder="Entrez votre mot de passe"
                           autocomplete="current-password">
                </div>
                @error('password', 'userDeletion')
                    <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                        <i class="fas fa-exclamation-circle"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            <div class="flex gap-3">
                <button type="button" onclick="closeDeleteModal()"
                        class="flex-1 py-3 rounded-xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 transition-colors">
                    Annuler
                </button>
                <button type="submit" class="btn-danger flex-1 justify-center">
                    <i class="fas fa-trash-alt"></i>
                    Supprimer définitivement
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ================================================================
     ROLE CONFIRMATION MODAL
     ================================================================ --}}
<div id="role-modal" class="profile-modal-overlay" onclick="closeRoleModalOutside(event)">
    <div class="profile-modal">
        {{-- Modal header --}}
        <div class="p-6 pb-0">
            <div class="w-14 h-14 bg-teal-100 text-teal-600 rounded-2xl flex items-center justify-center mb-4">
                <i class="fas fa-user-tag text-xl"></i>
            </div>
            <h3 class="font-bold text-slate-800 text-lg mb-1">Confirmer le changement de rôle</h3>
            <p class="text-slate-500 text-sm leading-relaxed">
                Voulez-vous vraiment changer votre rôle pour : <strong id="role-modal-title" class="text-teal-700"></strong> ?
                Vos permissions et votre espace de travail seront adaptés en conséquence.
            </p>
        </div>

        {{-- Modal form --}}
        <form method="POST" action="{{ route('profile.role') }}" class="p-6 pt-5">
            @csrf
            @method('patch')

            <input type="hidden" name="role" id="role-input" value="">

            <div class="flex gap-3">
                <button type="button" onclick="closeRoleModal()"
                        class="flex-1 py-3 rounded-xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 transition-colors">
                    Annuler
                </button>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="fas fa-check"></i>
                    Confirmer le rôle
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    /* ---- Tab switching ---- */
    function switchTab(name, btn) {
        // Hide all panels
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active');
            b.setAttribute('aria-selected', 'false');
        });
        // Show selected panel
        document.getElementById('tab-' + name).classList.add('active');
        btn.classList.add('active');
        btn.setAttribute('aria-selected', 'true');
    }

    /* ---- Auto-activate tab if there are errors ---- */
    document.addEventListener('DOMContentLoaded', function () {
        @if($errors->updatePassword->any())
            switchTab('password', document.getElementById('tab-password-btn'));
        @elseif($errors->userDeletion->any())
            switchTab('security', document.getElementById('tab-security-btn'));
            setTimeout(openDeleteModal, 400);
        @elseif(session('status') === 'role-updated')
            switchTab('role', document.getElementById('tab-role-btn'));
        @endif

        // Auto-hide toasts
        document.querySelectorAll('.toast').forEach(function(toast) {
            setTimeout(function() {
                toast.classList.add('hiding');
                setTimeout(() => toast.remove(), 400);
            }, 3500);
        });
    });

    /* ---- Role picker ---- */
    let pendingRole = null;

    function selectRole(roleKey, roleTitle) {
        pendingRole = roleKey;
        document.getElementById('role-modal-title').textContent = roleTitle;
        document.getElementById('role-input').value = roleKey;
        openRoleModal();
    }

    function openRoleModal() {
        document.getElementById('role-modal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeRoleModal() {
        document.getElementById('role-modal').classList.remove('open');
        document.body.style.overflow = '';
        pendingRole = null;
    }
    function closeRoleModalOutside(e) {
        if (e.target === document.getElementById('role-modal')) closeRoleModal();
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { closeDeleteModal(); closeRoleModal(); }
    });

    /* ---- Password strength meter ---- */
    function checkStrength(val) {
        const bar = document.getElementById('strength-bar');
        const lbl = document.getElementById('strength-label');
        if (!val) { bar.style.width = '0%'; lbl.textContent = ''; return; }

        let score = 0;
        if (val.length >= 8)  score++;
        if (val.length >= 12) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const levels = [
            { w: '20%', bg: '#ef4444', t: 'Très faible' },
            { w: '40%', bg: '#f97316', t: 'Faible' },
            { w: '60%', bg: '#eab308', t: 'Moyen' },
            { w: '80%', bg: '#22c55e', t: 'Fort' },
            { w: '100%', bg: '#0d9488', t: 'Très fort' },
        ];
        const lvl = levels[Math.min(score - 1, 4)] || levels[0];
        bar.style.width = lvl.w;
        bar.style.background = lvl.bg;
        lbl.textContent = lvl.t;
        lbl.style.color = lvl.bg;
    }

    /* ---- Delete modal ---- */
    function openDeleteModal() {
        document.getElementById('delete-modal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeDeleteModal() {
        document.getElementById('delete-modal').classList.remove('open');
        document.body.style.overflow = '';
    }
    function closeDeleteModalOutside(e) {
        if (e.target === document.getElementById('delete-modal')) closeDeleteModal();
    }
    // ESC to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDeleteModal();
    });
</script>
@endsection
