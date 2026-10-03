@extends('layouts.app')

@section('body-class', 'no-hero')

@section('styles')
<style>
    .dot-grid {
        background-image: radial-gradient(circle, rgba(45,212,191,0.15) 1px, transparent 1px);
        background-size: 28px 28px;
    }
    .role-radio:checked + .role-label {
        border-color: #0d9488;
        background: rgba(13,148,136,0.06);
    }
    .role-label { transition: all 0.2s; cursor: pointer; }
    .role-label:hover { border-color: #0d9488; }
</style>
@endsection

@section('content')
<div class="min-h-screen flex">

    {{-- ===== PANNEAU GAUCHE — Branding ===== --}}
    <div class="hidden lg:flex lg:w-[42%] relative overflow-hidden flex-col justify-between p-12 auth-panel">

        <div class="absolute inset-0 dot-grid opacity-30"></div>
        <div class="absolute top-0 right-0 w-96 h-96 rounded-full opacity-10 blur-3xl"
             style="background: radial-gradient(circle, #2DD4BF, transparent)"></div>

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
        <div class="relative flex-1 flex flex-col justify-center py-10">
            <h2 class="font-display text-4xl font-bold text-white leading-tight mb-5">
                Rejoignez le<br>
                <span style="color:#2DD4BF">mouvement</span><br>
                circulaire
            </h2>
            <p class="text-white/60 text-base leading-relaxed max-w-sm mb-8">
                Créez votre compte et choisissez votre rôle dans la chaîne de valeur du textile durable.
            </p>

            {{-- Rôles preview --}}
            <div class="space-y-2.5 max-w-xs">
                @foreach([
                    ['fa-hand-holding-heart', 'Donateur — Déposez vos vêtements'],
                    ['fa-shopping-bag',       'Client — Achetez & commandez'],
                    ['fa-cut',                'Atelier — Créez & transformez'],
                    ['fa-recycle',            'Recycleur — Traitez les lots'],
                ] as [$icon, $label])
                <div class="flex items-center gap-3 px-4 py-3 rounded-xl"
                     style="background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.1)">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                         style="background:rgba(13,148,136,0.3)">
                        <i class="fas {{ $icon }} text-xs" style="color:#2DD4BF"></i>
                    </div>
                    <span class="text-white/75 text-sm">{{ $label }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Badge gratuit --}}
        <div class="relative flex items-center gap-3 rounded-2xl p-4"
             style="background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.1)">
            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                 style="background:rgba(74,222,128,0.2)">
                <i class="fas fa-leaf" style="color:#4ade80"></i>
            </div>
            <p class="text-white/70 text-sm">
                Inscription <strong class="text-white">100% gratuite</strong> — Aucune carte requise
            </p>
        </div>
    </div>

    {{-- ===== PANNEAU DROIT — Formulaire ===== --}}
    <div class="w-full lg:w-[58%] flex items-center justify-center p-6 sm:p-10 bg-white overflow-y-auto">
        <div class="w-full max-w-lg fade-up py-6">

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
                <h1 class="font-display text-3xl font-bold text-navy mb-2">Créer un compte</h1>
                <p class="text-slate-500 text-sm">
                    Déjà inscrit ?
                    <a href="{{ route('login') }}" class="font-semibold transition-colors"
                       style="color:#0d9488">Se connecter</a>
                </p>
            </div>

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

            <form method="POST" action="{{ route('register') }}" class="space-y-5" novalidate>
                @csrf

                {{-- Prénom + Nom --}}
                <div class="grid grid-cols-2 gap-4">
                    @foreach([
                        ['prenom', 'Prénom', 'Ali'],
                        ['name',   'Nom',    'Ben Ali'],
                    ] as [$field, $label, $ph])
                    <div>
                        <label for="{{ $field }}" class="block text-sm font-semibold text-slate-700 mb-2">
                            {{ $label }} <span style="color:#ef4444">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2" style="color:#94a3b8">
                                <i class="fas fa-user text-sm"></i>
                            </span>
                            <input type="text" id="{{ $field }}" name="{{ $field }}"
                                   value="{{ old($field) }}"
                                   placeholder="{{ $ph }}"
                                   class="input-retiss {{ $errors->has($field) ? 'error' : '' }}">
                        </div>
                        @error($field)
                            <p class="mt-1 text-xs flex items-center gap-1" style="color:#ef4444">
                                <i class="fas fa-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>
                    @endforeach
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">
                        Adresse e-mail <span style="color:#ef4444">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2" style="color:#94a3b8">
                            <i class="fas fa-envelope text-sm"></i>
                        </span>
                        <input type="text" id="email" name="email"
                               value="{{ old('email') }}"
                               placeholder="vous@exemple.com"
                               class="input-retiss {{ $errors->has('email') ? 'error' : '' }}">
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs flex items-center gap-1" style="color:#ef4444">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Téléphone --}}
                <div>
                    <label for="telephone" class="block text-sm font-semibold text-slate-700 mb-2">
                        Téléphone <span class="text-slate-400 font-normal">(optionnel)</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2" style="color:#94a3b8">
                            <i class="fas fa-phone text-sm"></i>
                        </span>
                        <input type="text" id="telephone" name="telephone"
                               value="{{ old('telephone') }}"
                               placeholder="+216 XX XXX XXX"
                               class="input-retiss">
                    </div>
                </div>

                {{-- Rôle --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-3">
                        Je m'inscris en tant que <span style="color:#ef4444">*</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @php
                            $roles = [
                                ['DONATEUR',   'fa-hand-holding-heart', 'Donateur',   '#dcfce7', '#16a34a'],
                                ['CLIENT',     'fa-shopping-bag',       'Client',     '#dbeafe', '#2563eb'],
                                ['COLLECTEUR', 'fa-truck',              'Collecteur', '#fef9c3', '#ca8a04'],
                                ['ATELIER',    'fa-cut',                'Atelier',    '#fce7f3', '#9d174d'],
                                ['RECYCLEUR',  'fa-recycle',            'Recycleur',  '#ccfbf1', '#0d9488'],
                            ];
                        @endphp

                        @foreach($roles as [$val, $icon, $label, $bg, $color])
                        <label class="role-label flex flex-col items-center gap-2 p-3.5 rounded-2xl border-2 text-center"
                               style="border-color: {{ old('role') === $val ? '#0d9488' : '#e2e8f0' }};
                                      background: {{ old('role') === $val ? 'rgba(13,148,136,0.06)' : 'white' }}">
                            <input type="radio" name="role" value="{{ $val }}"
                                   class="role-radio sr-only"
                                   {{ old('role') === $val ? 'checked' : '' }}>
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                                 style="background: {{ $bg }}">
                                <i class="fas {{ $icon }}" style="color: {{ $color }}"></i>
                            </div>
                            <span class="text-xs font-semibold text-slate-700">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('role')
                        <p class="mt-2 text-xs flex items-center gap-1" style="color:#ef4444">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Mot de passe --}}
                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">
                        Mot de passe <span style="color:#ef4444">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2" style="color:#94a3b8">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input type="password" id="password" name="password"
                               placeholder="8 caractères minimum"
                               class="input-retiss {{ $errors->has('password') ? 'error' : '' }}"
                               style="padding-right:3rem"
                               oninput="checkStrength(this.value)">
                        <button type="button" onclick="togglePwd('password','eyeReg1')"
                                class="absolute right-4 top-1/2 -translate-y-1/2" style="color:#94a3b8">
                            <i id="eyeReg1" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>

                    {{-- Force du mot de passe --}}
                    <div class="flex gap-1 mt-2">
                        @for($i=0;$i<4;$i++)
                        <div id="bar{{ $i }}" class="h-1.5 flex-1 rounded-full bg-slate-200 transition-colors duration-300"></div>
                        @endfor
                    </div>
                    <p id="strengthLabel" class="text-xs text-slate-400 mt-1"></p>

                    @error('password')
                        <p class="mt-1.5 text-xs flex items-center gap-1" style="color:#ef4444">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </p>
                    @enderror
                </div>

                {{-- Confirmation --}}
                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-2">
                        Confirmer le mot de passe <span style="color:#ef4444">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2" style="color:#94a3b8">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               placeholder="Répétez le mot de passe"
                               class="input-retiss"
                               style="padding-right:3rem"
                               oninput="checkMatch()">
                        <button type="button" onclick="togglePwd('password_confirmation','eyeReg2')"
                                class="absolute right-4 top-1/2 -translate-y-1/2" style="color:#94a3b8">
                            <i id="eyeReg2" class="fas fa-eye text-sm"></i>
                        </button>
                    </div>
                    <p id="matchLabel" class="text-xs mt-1 hidden"></p>
                </div>

                {{-- CGU --}}
                <div class="flex items-start gap-3">
                    <input type="checkbox" id="cgu" name="cgu"
                           class="w-4 h-4 mt-0.5 rounded flex-shrink-0 cursor-pointer"
                           style="accent-color:#0d9488">
                    <label for="cgu" class="text-sm text-slate-600 cursor-pointer leading-relaxed">
                        J'accepte les
                        <a href="#" class="font-semibold" style="color:#0d9488">conditions générales</a>
                        et la
                        <a href="#" class="font-semibold" style="color:#0d9488">politique de confidentialité</a>
                        de RETISS.
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn-primary w-full py-4 rounded-2xl text-base mt-2">
                    <i class="fas fa-user-plus"></i>
                    Créer mon compte
                </button>
            </form>

            <p class="text-center text-sm text-slate-500 mt-7">
                Déjà un compte ?
                <a href="{{ route('login') }}" class="font-semibold" style="color:#0d9488">Se connecter</a>
            </p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    /* Toggle password visibility */
    function togglePwd(id, iconId) {
        const i = document.getElementById(id);
        const ic = document.getElementById(iconId);
        i.type = i.type === 'password' ? 'text' : 'password';
        ic.className = i.type === 'password' ? 'fas fa-eye text-sm' : 'fas fa-eye-slash text-sm';
    }

    /* Password strength */
    function checkStrength(val) {
        let score = 0;
        if (val.length >= 8)          score++;
        if (/[A-Z]/.test(val))        score++;
        if (/[0-9]/.test(val))        score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const colors  = ['#ef4444','#f97316','#eab308','#22c55e'];
        const labels  = ['Très faible','Faible','Moyen','Fort ✓'];
        const lColors = ['#ef4444','#f97316','#ca8a04','#16a34a'];

        for (let i = 0; i < 4; i++) {
            const bar = document.getElementById('bar' + i);
            bar.style.background = i < score ? colors[score - 1] : '#e2e8f0';
        }
        const lbl = document.getElementById('strengthLabel');
        if (val.length > 0) {
            lbl.textContent  = labels[score - 1] ?? '';
            lbl.style.color  = lColors[score - 1] ?? '#94a3b8';
        } else {
            lbl.textContent = '';
        }
    }

    /* Password match */
    function checkMatch() {
        const pwd  = document.getElementById('password').value;
        const conf = document.getElementById('password_confirmation').value;
        const lbl  = document.getElementById('matchLabel');
        lbl.classList.remove('hidden');
        if (conf === pwd && conf.length > 0) {
            lbl.textContent = '✓ Les mots de passe correspondent';
            lbl.style.color = '#16a34a';
        } else {
            lbl.textContent = '✗ Les mots de passe ne correspondent pas';
            lbl.style.color = '#ef4444';
        }
    }

    /* Highlight role selected */
    document.querySelectorAll('input[name="role"]').forEach(r => {
        r.addEventListener('change', () => {
            document.querySelectorAll('.role-label').forEach(l => {
                l.style.borderColor = '#e2e8f0';
                l.style.background  = 'white';
            });
            if (r.checked) {
                r.closest('.role-label').style.borderColor = '#0d9488';
                r.closest('.role-label').style.background  = 'rgba(13,148,136,0.06)';
            }
        });
    });
</script>
@endsection
