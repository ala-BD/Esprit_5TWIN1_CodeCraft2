@extends('layouts.app')

@section('title', 'Accueil')

@section('body-class', 'has-hero')

@section('styles')
<style>
    /* Particules décoratives */
    .dot-grid {
        background-image: radial-gradient(circle, rgba(45,212,191,0.15) 1px, transparent 1px);
        background-size: 28px 28px;
    }

    /* Ligne animée */
    @keyframes lineGrow {
        from { width: 0; }
        to   { width: 100%; }
    }
    .line-animate { animation: lineGrow 1.2s ease-out forwards; }

    /* Compteur animé */
    @keyframes countUp {
        from { opacity:0; transform: translateY(10px); }
        to   { opacity:1; transform: translateY(0); }
    }
    .count-anim { animation: countUp 0.6s ease-out forwards; }

    /* Card hover teal */
    .step-card:hover { border-color: #0d9488; }
    .step-card:hover .step-icon { background: linear-gradient(135deg, #0d9488, #2DD4BF); }

    /* Filière card */
    .filiere-card { transition: all 0.3s cubic-bezier(.4,0,.2,1); }
    .filiere-card:hover { transform: translateY(-6px); }
</style>
@endsection

@section('content')

{{-- ================================================================
     SECTION 1 — HERO
     ================================================================ --}}
<section class="relative min-h-screen flex items-center overflow-hidden hero-gradient">

    {{-- Dot grid overlay --}}
    <div class="absolute inset-0 dot-grid opacity-40"></div>

    {{-- Cercles décoratifs --}}
    <div class="absolute top-20 right-10 w-[500px] h-[500px] rounded-full opacity-10 blur-3xl"
         style="background: radial-gradient(circle, #2DD4BF, transparent)"></div>
    <div class="absolute -bottom-20 -left-20 w-[400px] h-[400px] rounded-full opacity-10 blur-3xl"
         style="background: radial-gradient(circle, #4ade80, transparent)"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 w-full">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

            {{-- Texte gauche --}}
            <div class="fade-up">

                {{-- Badge --}}
                <div class="inline-flex items-center gap-2.5 mb-7 px-4 py-2 rounded-full text-xs font-semibold"
                     style="background: rgba(13,148,136,0.2); border: 1px solid rgba(45,212,191,0.3); color: #2DD4BF;">
                    <span class="w-2 h-2 rounded-full animate-pulse" style="background:#4ade80"></span>
                    Économie circulaire du textile
                </div>

                <h1 class="font-display font-bold leading-[1.1] mb-6 text-white"
                    style="font-size: clamp(2.5rem, 5vw, 4rem)">
                    Donnez une
                    <span class="relative inline-block">
                        <span style="color:#2DD4BF">seconde vie</span>
                        <svg class="absolute -bottom-1 left-0 w-full" height="6" viewBox="0 0 200 6">
                            <path d="M0 5 Q50 0 100 5 Q150 0 200 5" stroke="#4ade80" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <br>à vos vêtements
                </h1>

                <p class="text-white/70 text-lg leading-relaxed mb-10 max-w-lg">
                    RETISS connecte donateurs, ateliers et recycleurs pour transformer le textile usagé en ressource précieuse. L'IA décide de la meilleure filière.
                </p>

                {{-- CTA --}}
                <div class="flex flex-col sm:flex-row gap-4 mb-14">
                    <a href="{{ route('register') }}"
                       class="btn-primary text-base px-8 py-4 rounded-2xl"
                       style="background: linear-gradient(135deg, #0d9488, #2DD4BF); box-shadow: 0 8px 32px rgba(13,148,136,0.4)">
                        <i class="fas fa-hand-holding-heart"></i>
                        Commencer gratuitement
                    </a>
                    <a href="#comment-ca-marche"
                       class="btn-outline-white text-base px-8 py-4 rounded-2xl">
                        <i class="fas fa-play-circle"></i>
                        Découvrir
                    </a>
                </div>

                {{-- Stats --}}
                <div class="grid grid-cols-3 gap-6 pt-6"
                     style="border-top: 1px solid rgba(255,255,255,0.1)">
                    @foreach([
                        ['12k+', 'Vêtements sauvés'],
                        ['3.2t',  'CO₂ économisé'],
                        ['850+', 'Membres actifs'],
                    ] as [$val, $label])
                    <div>
                        <p class="font-display text-3xl font-bold text-white count-anim">{{ $val }}</p>
                        <p class="text-white/50 text-xs mt-1">{{ $label }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Card décorative droite --}}
            <div class="hidden lg:flex justify-center items-center fade-up" style="animation-delay:0.2s">
                <div class="relative w-full max-w-sm">

                    {{-- Card principale --}}
                    <div class="glass rounded-3xl p-7 shadow-2xl"
                         style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12); backdrop-filter: blur(20px);">

                        {{-- Header card --}}
                        <div class="flex items-center gap-3 mb-6 pb-5"
                             style="border-bottom: 1px solid rgba(255,255,255,0.1)">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center"
                                 style="background: linear-gradient(135deg, #1B4332, #0d9488)">
                                <i class="fas fa-tshirt text-white text-xl"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-white text-sm">Vêtement #VTM-2026</p>
                                <p class="text-white/50 text-xs">Traitement en cours…</p>
                            </div>
                            <span class="ml-auto text-xs font-bold px-2.5 py-1 rounded-full"
                                  style="background: rgba(74,222,128,0.2); color: #4ade80">
                                Actif
                            </span>
                        </div>

                        {{-- Étapes --}}
                        <div class="space-y-3 mb-6">
                            @foreach([
                                ['check', 'Don déposé', 'Point Ariana', 'rgba(74,222,128,0.15)', '#4ade80'],
                                ['robot', 'Classification IA', 'Recyclage fibre', 'rgba(45,212,191,0.15)', '#2DD4BF'],
                                ['cogs', 'En traitement', 'En cours…', 'rgba(251,191,36,0.15)', '#fbbf24'],
                            ] as [$icon, $title, $sub, $bg, $color])
                            <div class="flex items-center gap-3 p-3 rounded-xl" style="background: {{ $bg }}">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                     style="background: {{ $bg }}; border: 1px solid {{ $color }}30">
                                    <i class="fas fa-{{ $icon }} text-xs" style="color: {{ $color }}"></i>
                                </div>
                                <div>
                                    <p class="text-white text-xs font-semibold">{{ $title }}</p>
                                    <p class="text-white/50 text-xs">{{ $sub }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        {{-- Impact --}}
                        <div class="flex items-center justify-between p-3.5 rounded-xl"
                             style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08)">
                            <div>
                                <p class="text-white/60 text-xs">Impact estimé</p>
                                <p class="text-white font-bold text-sm">−2.4 kg CO₂</p>
                            </div>
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                                 style="background: linear-gradient(135deg, #1a2744, #0d9488)">
                                <i class="fas fa-qrcode text-white text-sm"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Badges flottants --}}
                    <div class="absolute -top-4 -right-4 flex items-center gap-2 px-3 py-2 rounded-xl shadow-lg text-xs font-bold"
                         style="background: linear-gradient(135deg, #0d9488, #2DD4BF); color: white">
                        <i class="fas fa-leaf"></i> Éco-certifié
                    </div>
                    <div class="absolute -bottom-4 -left-4 flex items-center gap-2 px-3 py-2 rounded-xl shadow-lg text-xs font-semibold bg-white text-navy">
                        <i class="fas fa-shield-alt" style="color:#0d9488"></i> QR Traçabilité
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Vague --}}
    <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0 80 L0 40 Q360 0 720 40 Q1080 80 1440 40 L1440 80 Z" fill="#f8fafc"/>
        </svg>
    </div>
</section>

{{-- ================================================================
     SECTION 2 — COMMENT ÇA MARCHE
     ================================================================ --}}
<section id="comment-ca-marche" class="py-24 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-16">
            <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color:#0d9488">Le processus</p>
            <h2 class="font-display text-4xl lg:text-5xl font-bold text-navy mb-4">
                Comment ça marche ?
            </h2>
            <p class="text-slate-500 max-w-xl mx-auto">
                De votre armoire à une nouvelle vie — chaque étape est tracée et certifiée par RETISS.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 relative">

            {{-- Connecteur desktop --}}
            <div class="hidden lg:block absolute top-10 left-[12.5%] right-[12.5%] h-px z-0"
                 style="background: linear-gradient(to right, #0d9488, #2DD4BF, #4ade80)"></div>

            @php
                $steps = [
                    ['icon' => 'fa-hand-holding-heart', 'num' => '01', 'title' => 'Déposer un don',    'desc' => 'Amenez vos vêtements à un point de collecte ou demandez un enlèvement à domicile.',
                     'grad' => 'linear-gradient(135deg, #1a2744, #1B4332)'],
                    ['icon' => 'fa-robot',               'num' => '02', 'title' => 'Classification IA', 'desc' => "L'IA analyse l'état du vêtement et décide de la meilleure filière en quelques secondes.",
                     'grad' => 'linear-gradient(135deg, #0f766e, #0d9488)'],
                    ['icon' => 'fa-route',               'num' => '03', 'title' => 'Orientation',       'desc' => 'Revente, upcycling créatif ou recyclage matière selon le potentiel du vêtement.',
                     'grad' => 'linear-gradient(135deg, #0d9488, #2DD4BF)'],
                    ['icon' => 'fa-qrcode',              'num' => '04', 'title' => 'Traçabilité QR',    'desc' => 'Un passeport numérique certifie et suit chaque vêtement tout au long de sa vie.',
                     'grad' => 'linear-gradient(135deg, #16a34a, #4ade80)'],
                ];
            @endphp

            @foreach($steps as $step)
            <div class="relative z-10 step-card group bg-white rounded-2xl p-7 border-2 border-transparent shadow-sm hover:shadow-xl transition-all duration-300 text-center"
                 style="border-color: rgba(13,148,136,0.08)">
                <div class="relative inline-flex mb-5">
                    <div class="step-icon w-16 h-16 rounded-2xl flex items-center justify-center mx-auto shadow-lg transition-all duration-300"
                         style="background: {{ $step['grad'] }}">
                        <i class="fas {{ $step['icon'] }} text-white text-xl"></i>
                    </div>
                    <span class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-navy text-white text-xs font-black flex items-center justify-center shadow">
                        {{ $step['num'] }}
                    </span>
                </div>
                <h3 class="font-display font-bold text-navy text-base mb-2">{{ $step['title'] }}</h3>
                <p class="text-slate-500 text-sm leading-relaxed">{{ $step['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================================================================
     SECTION 3 — FILIÈRES
     ================================================================ --}}
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-14">
            <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color:#0d9488">Les filières</p>
            <h2 class="font-display text-4xl lg:text-5xl font-bold text-navy">
                Trois destinations possibles
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

            @php
                $filieres = [
                    [
                        'icon'  => 'fa-store',
                        'title' => 'Revente',
                        'desc'  => 'Les vêtements en bon état rejoignent la marketplace RETISS pour une seconde vie à prix réduit.',
                        'cta'   => 'Accéder à la marketplace',
                        'grad'  => 'linear-gradient(135deg, #0f2a1e, #1B4332)',
                        'light' => '#dcfce7',
                        'color' => '#16a34a',
                        'border'=> '#bbf7d0',
                    ],
                    [
                        'icon'  => 'fa-cut',
                        'title' => 'Upcycling',
                        'desc'  => 'Des ateliers créatifs transforment vos vêtements en pièces uniques à forte valeur ajoutée.',
                        'cta'   => 'Voir les ateliers',
                        'grad'  => 'linear-gradient(135deg, #0f766e, #0d9488)',
                        'light' => '#ccfbf1',
                        'color' => '#0d9488',
                        'border'=> '#99f6e4',
                    ],
                    [
                        'icon'  => 'fa-recycle',
                        'title' => 'Recyclage',
                        'desc'  => 'Les vêtements trop usés sont recyclés en matière première, évitant la décharge et réduisant le CO₂.',
                        'cta'   => 'En savoir plus',
                        'grad'  => 'linear-gradient(135deg, #111a30, #1a2744)',
                        'light' => '#e0f2fe',
                        'color' => '#0284c7',
                        'border'=> '#bae6fd',
                    ],
                ];
            @endphp

            @foreach($filieres as $f)
            <div class="filiere-card rounded-3xl overflow-hidden shadow-sm border"
                 style="border-color: {{ $f['border'] }}">
                {{-- Header coloré --}}
                <div class="p-8 text-white relative overflow-hidden"
                     style="background: {{ $f['grad'] }}">
                    <div class="absolute top-0 right-0 w-32 h-32 rounded-full opacity-10"
                         style="background: radial-gradient(circle, white, transparent); transform: translate(30%, -30%)"></div>
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-5 bg-white/15">
                        <i class="fas {{ $f['icon'] }} text-white text-2xl"></i>
                    </div>
                    <h3 class="font-display text-xl font-bold mb-2">{{ $f['title'] }}</h3>
                    <p class="text-white/70 text-sm leading-relaxed">{{ $f['desc'] }}</p>
                </div>
                {{-- Footer card --}}
                <div class="p-5 flex items-center justify-between" style="background: {{ $f['light'] }}">
                    <span class="text-sm font-semibold" style="color: {{ $f['color'] }}">{{ $f['cta'] }}</span>
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                         style="background: {{ $f['color'] }}20">
                        <i class="fas fa-arrow-right text-xs" style="color: {{ $f['color'] }}"></i>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================================================================
     SECTION 4 — IMPACT CO₂
     ================================================================ --}}
<section id="impact" class="py-24 relative overflow-hidden"
         style="background: linear-gradient(135deg, #111a30 0%, #1a2744 50%, #1B4332 100%)">

    <div class="absolute inset-0 dot-grid opacity-20"></div>
    <div class="absolute top-0 right-0 w-96 h-96 rounded-full opacity-10 blur-3xl"
         style="background: radial-gradient(circle, #2DD4BF, transparent)"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color:#2DD4BF">Notre impact</p>
            <h2 class="font-display text-4xl lg:text-5xl font-bold text-white mb-4">
                Chaque geste compte
            </h2>
            <p class="text-white/60 max-w-xl mx-auto">Des chiffres réels générés par notre communauté.</p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
            @php
                $stats = [
                    ['fa-tshirt',  '12 450',    'Vêtements sauvés',  'pièces',  'linear-gradient(135deg,#0f766e,#0d9488)'],
                    ['fa-smog',    '3.2',        'CO₂ économisé',     'tonnes',  'linear-gradient(135deg,#1a2744,#243560)'],
                    ['fa-tint',    '860 000',    'Eau économisée',    'litres',  'linear-gradient(135deg,#0d9488,#2DD4BF)'],
                    ['fa-users',   '850+',       'Membres actifs',    'personnes','linear-gradient(135deg,#16a34a,#4ade80)'],
                ];
            @endphp

            @foreach($stats as [$icon, $val, $label, $unit, $grad])
            <div class="group rounded-2xl p-6 text-center border transition-all duration-300 hover:scale-105"
                 style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.08);"
                 onmouseover="this.style.borderColor='rgba(45,212,191,0.3)'"
                 onmouseout="this.style.borderColor='rgba(255,255,255,0.08)'">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto mb-4"
                     style="background: {{ $grad }}">
                    <i class="fas {{ $icon }} text-white text-lg"></i>
                </div>
                <p class="font-display text-3xl font-bold text-white mb-1">{{ $val }}</p>
                <p class="text-white/70 text-sm font-semibold">{{ $label }}</p>
                <p class="text-white/40 text-xs mt-0.5">{{ $unit }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================================================================
     SECTION 5 — ACTEURS
     ================================================================ --}}
<section id="acteurs" class="py-24 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-14">
            <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color:#0d9488">Les acteurs</p>
            <h2 class="font-display text-4xl lg:text-5xl font-bold text-navy mb-4">
                Rejoignez l'écosystème RETISS
            </h2>
            <p class="text-slate-500 max-w-xl mx-auto">
                Quelle que soit votre place dans la chaîne textile, RETISS a un rôle pour vous.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @php
                $actors = [
                    ['fa-hand-holding-heart', 'Donateur',   '#dcfce7', '#16a34a', 'Déposez vos vêtements et suivez leur parcours via QR code.'],
                    ['fa-shopping-bag',       'Client',     '#dbeafe', '#2563eb', 'Achetez des vêtements de seconde main ou commandez une pièce unique.'],
                    ['fa-truck',              'Collecteur', '#fef9c3', '#ca8a04', 'Gérez vos tournées de collecte avec optimisation IA.'],
                    ['fa-cut',                'Atelier',    '#fce7f3', '#9d174d', 'Recevez des vêtements à transformer et partagez vos créations.'],
                    ['fa-recycle',            'Recycleur',  '#ccfbf1', '#0d9488', 'Traitez les lots textiles et générez des certificats d\'impact.'],
                    ['fa-shield-alt',         'Admin',      '#f1f5f9', '#475569', 'Supervisez la plateforme, gérez les utilisateurs et les statistiques.'],
                ];
            @endphp

            @foreach($actors as [$icon, $role, $bg, $color, $desc])
            <a href="{{ route('register') }}"
               class="group card rounded-2xl p-6 flex items-start gap-4 block hover:no-underline">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 transition-all duration-300 group-hover:scale-110"
                     style="background: {{ $bg }}">
                    <i class="fas {{ $icon }} text-lg" style="color: {{ $color }}"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1.5">
                        <h3 class="font-display font-bold text-navy">{{ $role }}</h3>
                        <i class="fas fa-arrow-right text-xs opacity-0 group-hover:opacity-100 transition-all duration-200 group-hover:translate-x-1"
                           style="color: {{ $color }}"></i>
                    </div>
                    <p class="text-slate-500 text-sm leading-relaxed">{{ $desc }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ================================================================
     SECTION 6 — CTA FINAL
     ================================================================ --}}
<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="rounded-3xl p-12 lg:p-16 text-center relative overflow-hidden"
             style="background: linear-gradient(135deg, #111a30 0%, #1a2744 40%, #1B4332 75%, #0d9488 100%)">

            <div class="absolute inset-0 dot-grid opacity-20"></div>
            <div class="absolute top-0 right-0 w-80 h-80 rounded-full opacity-10 blur-3xl"
                 style="background: radial-gradient(circle, #2DD4BF, transparent)"></div>

            <div class="relative">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6"
                     style="background: rgba(45,212,191,0.15); border: 1px solid rgba(45,212,191,0.3)">
                    <i class="fas fa-leaf text-2xl" style="color:#4ade80"></i>
                </div>

                <h2 class="font-display text-3xl lg:text-4xl font-bold text-white mb-4">
                    Prêt à agir pour la planète ?
                </h2>
                <p class="text-white/60 text-lg mb-10 max-w-xl mx-auto">
                    Rejoignez des milliers de membres qui donnent une seconde vie au textile chaque jour.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center justify-center gap-2 font-bold px-8 py-4 rounded-2xl transition-all duration-200 text-navy"
                       style="background: linear-gradient(135deg, #4ade80, #2DD4BF)"
                       onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 32px rgba(74,222,128,0.4)'"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        <i class="fas fa-user-plus"></i>
                        Créer un compte gratuit
                    </a>
                    <a href="{{ route('login') }}"
                       class="btn-outline-white px-8 py-4 rounded-2xl">
                        <i class="fas fa-sign-in-alt"></i>
                        Se connecter
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
