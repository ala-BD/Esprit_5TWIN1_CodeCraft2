@extends('layouts.app')

@section('title', 'Accueil')

@section('content')

{{-- =============================================
     SECTION 1 : HERO
     ============================================= --}}
<section class="hero-gradient min-h-screen flex items-center relative overflow-hidden">

    {{-- Cercles décoratifs --}}
    <div class="absolute top-[-80px] right-[-80px] w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
    <div class="absolute bottom-[-60px] left-[-60px] w-72 h-72 bg-secondary-DEFAULT/10 rounded-full blur-3xl"></div>
    <div class="absolute top-1/2 left-1/3 w-48 h-48 bg-accent-DEFAULT/10 rounded-full blur-2xl"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 w-full">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

            {{-- Texte --}}
            <div class="fade-in">
                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 bg-white/10 border border-white/20 text-white/90 text-xs font-semibold px-4 py-2 rounded-full mb-6 backdrop-blur-sm">
                    <span class="w-2 h-2 rounded-full bg-secondary-DEFAULT animate-pulse"></span>
                    Économie circulaire du textile
                </div>

                <h1 class="font-display text-5xl lg:text-6xl xl:text-7xl font-bold text-white leading-tight mb-6">
                    Donnez une
                    <span class="text-secondary-DEFAULT"> seconde vie</span>
                    à vos vêtements
                </h1>

                <p class="text-white/75 text-lg leading-relaxed mb-10 max-w-xl">
                    RETISS connecte donateurs, ateliers d'upcycling et recycleurs pour réduire l'impact de l'industrie textile. Chaque vêtement mérite une nouvelle histoire.
                </p>

                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center justify-center gap-2 bg-white text-primary-dark font-bold px-8 py-4 rounded-2xl hover:shadow-2xl hover:scale-[1.03] transition-all duration-200 text-base">
                        <i class="fas fa-hand-holding-heart"></i>
                        Faire un don
                    </a>
                    <a href="#comment-ca-marche"
                       class="inline-flex items-center justify-center gap-2 border-2 border-white/40 text-white font-semibold px-8 py-4 rounded-2xl hover:bg-white/10 transition-all duration-200 text-base backdrop-blur-sm">
                        <i class="fas fa-play-circle"></i>
                        Découvrir
                    </a>
                </div>

                {{-- Stats --}}
                <div class="mt-14 grid grid-cols-3 gap-6">
                    <div>
                        <p class="text-white font-bold text-3xl">12k+</p>
                        <p class="text-white/60 text-sm mt-1">Vêtements sauvés</p>
                    </div>
                    <div class="border-l border-white/20 pl-6">
                        <p class="text-white font-bold text-3xl">3.2t</p>
                        <p class="text-white/60 text-sm mt-1">CO₂ économisé</p>
                    </div>
                    <div class="border-l border-white/20 pl-6">
                        <p class="text-white font-bold text-3xl">850+</p>
                        <p class="text-white/60 text-sm mt-1">Membres actifs</p>
                    </div>
                </div>
            </div>

            {{-- Illustration / Card décorative --}}
            <div class="hidden lg:flex justify-center items-center fade-in">
                <div class="relative">
                    {{-- Card principale --}}
                    <div class="glass-card rounded-3xl p-8 w-80 shadow-2xl">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-DEFAULT to-secondary-DEFAULT flex items-center justify-center">
                                <i class="fas fa-tshirt text-white text-xl"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">Vêtement reçu</p>
                                <p class="text-gray-500 text-xs">Traitement en cours…</p>
                            </div>
                        </div>

                        {{-- Étapes --}}
                        <div class="space-y-4">
                            <div class="flex items-center gap-3 p-3 bg-green-50 rounded-xl">
                                <div class="w-8 h-8 rounded-full bg-primary-DEFAULT/20 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check text-primary-DEFAULT text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-700">Don déposé</p>
                                    <p class="text-xs text-gray-500">Point de collecte — Tunis</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-xl">
                                <div class="w-8 h-8 rounded-full bg-blue-400/20 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-robot text-blue-500 text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-700">Classification IA</p>
                                    <p class="text-xs text-gray-500">Revente recommandée</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-amber-50 rounded-xl border border-amber-100">
                                <div class="w-8 h-8 rounded-full bg-amber-400/20 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-store text-amber-500 text-xs animate-pulse"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-700">Mise en vente</p>
                                    <p class="text-xs text-gray-500">En attente…</p>
                                </div>
                            </div>
                        </div>

                        {{-- QR Code simulé --}}
                        <div class="mt-6 flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                            <div>
                                <p class="text-xs font-semibold text-gray-700">Passeport numérique</p>
                                <p class="text-xs text-gray-400">#VTM-20261003</p>
                            </div>
                            <div class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center">
                                <i class="fas fa-qrcode text-white"></i>
                            </div>
                        </div>
                    </div>

                    {{-- Badge flottant --}}
                    <div class="absolute -top-4 -right-4 bg-accent-DEFAULT text-white text-xs font-bold px-3 py-2 rounded-xl shadow-lg">
                        <i class="fas fa-leaf mr-1"></i> −2.4 kg CO₂
                    </div>
                    <div class="absolute -bottom-4 -left-4 bg-white text-gray-800 text-xs font-semibold px-3 py-2 rounded-xl shadow-lg border border-gray-100">
                        <i class="fas fa-shield-alt text-primary-DEFAULT mr-1"></i> Traçabilité garantie
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Vague bas --}}
    <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 60 L0 30 Q360 0 720 30 Q1080 60 1440 30 L1440 60 Z" fill="#FAFAF8"/>
        </svg>
    </div>
</section>

{{-- =============================================
     SECTION 2 : COMMENT ÇA MARCHE
     ============================================= --}}
<section id="comment-ca-marche" class="py-24 bg-[#FAFAF8]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Titre --}}
        <div class="text-center mb-16">
            <span class="text-primary-DEFAULT text-sm font-semibold uppercase tracking-widest">Le processus</span>
            <h2 class="font-display text-4xl lg:text-5xl font-bold text-gray-900 mt-2 mb-4">
                Comment ça marche ?
            </h2>
            <p class="text-gray-500 max-w-xl mx-auto text-lg">
                De votre armoire à une nouvelle vie, chaque étape est tracée et certifiée.
            </p>
        </div>

        {{-- Étapes --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 relative">

            {{-- Ligne connecteur desktop --}}
            <div class="hidden lg:block absolute top-10 left-[12.5%] right-[12.5%] h-0.5 bg-gradient-to-r from-primary-DEFAULT via-secondary-dark to-primary-light z-0"></div>

            @php
                $steps = [
                    ['icon' => 'fa-hand-holding-heart', 'num' => '01', 'color' => 'from-primary-dark to-primary-DEFAULT', 'title' => 'Déposer un don', 'desc' => 'Amenez vos vêtements à un point de collecte ou demandez un enlèvement à domicile.'],
                    ['icon' => 'fa-robot',               'num' => '02', 'color' => 'from-blue-500 to-blue-400',           'title' => 'Classification IA', 'desc' => "L'intelligence artificielle analyse l'état et décide de la meilleure filière."],
                    ['icon' => 'fa-route',               'num' => '03', 'color' => 'from-accent-dark to-accent-DEFAULT',  'title' => 'Orientation',      'desc' => 'Revente, upcycling créatif ou recyclage matière selon le potentiel du vêtement.'],
                    ['icon' => 'fa-qrcode',              'num' => '04', 'color' => 'from-primary-DEFAULT to-secondary-dark', 'title' => 'Traçabilité QR', 'desc' => 'Un passeport numérique suit et certifie toute la vie du vêtement.'],
                ];
            @endphp

            @foreach($steps as $step)
            <div class="relative z-10 group">
                <div class="bg-white rounded-2xl p-7 shadow-sm hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 text-center">
                    <div class="relative inline-block mb-5">
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br {{ $step['color'] }} flex items-center justify-center mx-auto shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <i class="fas {{ $step['icon'] }} text-white text-2xl"></i>
                        </div>
                        <span class="absolute -top-2 -right-2 w-7 h-7 rounded-full bg-gray-900 text-white text-xs font-bold flex items-center justify-center shadow">
                            {{ $step['num'] }}
                        </span>
                    </div>
                    <h3 class="font-bold text-gray-900 text-lg mb-3">{{ $step['title'] }}</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">{{ $step['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- =============================================
     SECTION 3 : FILIÈRES
     ============================================= --}}
<section class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-16">
            <span class="text-primary-DEFAULT text-sm font-semibold uppercase tracking-widest">Les filières</span>
            <h2 class="font-display text-4xl lg:text-5xl font-bold text-gray-900 mt-2 mb-4">
                Trois destinations possibles
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

            {{-- Revente --}}
            <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-50 to-green-100 p-8 hover:shadow-xl transition-all duration-300 border border-green-100">
                <div class="absolute top-0 right-0 w-32 h-32 bg-green-200/30 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                <div class="relative">
                    <div class="w-14 h-14 rounded-2xl bg-primary-DEFAULT/10 border border-primary-DEFAULT/20 flex items-center justify-center mb-6 group-hover:bg-primary-DEFAULT group-hover:border-primary-DEFAULT transition-all duration-300">
                        <i class="fas fa-store text-primary-DEFAULT group-hover:text-white text-xl transition-colors duration-300"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 text-xl mb-3">Revente</h3>
                    <p class="text-gray-600 text-sm leading-relaxed mb-5">Les vêtements en bon état rejoignent la marketplace RETISS pour être achetés à prix réduit.</p>
                    <div class="flex items-center gap-2 text-primary-DEFAULT text-sm font-semibold">
                        <span>Accéder à la marketplace</span>
                        <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform duration-200"></i>
                    </div>
                </div>
            </div>

            {{-- Upcycling --}}
            <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-amber-50 to-orange-100 p-8 hover:shadow-xl transition-all duration-300 border border-amber-100">
                <div class="absolute top-0 right-0 w-32 h-32 bg-amber-200/30 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                <div class="relative">
                    <div class="w-14 h-14 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center mb-6 group-hover:bg-amber-500 group-hover:border-amber-500 transition-all duration-300">
                        <i class="fas fa-cut text-amber-600 group-hover:text-white text-xl transition-colors duration-300"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 text-xl mb-3">Upcycling</h3>
                    <p class="text-gray-600 text-sm leading-relaxed mb-5">Des ateliers créatifs transforment vos vêtements en nouvelles pièces uniques à forte valeur ajoutée.</p>
                    <div class="flex items-center gap-2 text-amber-600 text-sm font-semibold">
                        <span>Voir les ateliers</span>
                        <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform duration-200"></i>
                    </div>
                </div>
            </div>

            {{-- Recyclage --}}
            <div class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-50 to-indigo-100 p-8 hover:shadow-xl transition-all duration-300 border border-blue-100">
                <div class="absolute top-0 right-0 w-32 h-32 bg-blue-200/30 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                <div class="relative">
                    <div class="w-14 h-14 rounded-2xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center mb-6 group-hover:bg-blue-500 group-hover:border-blue-500 transition-all duration-300">
                        <i class="fas fa-recycle text-blue-600 group-hover:text-white text-xl transition-colors duration-300"></i>
                    </div>
                    <h3 class="font-bold text-gray-900 text-xl mb-3">Recyclage</h3>
                    <p class="text-gray-600 text-sm leading-relaxed mb-5">Les vêtements trop usés sont recyclés en matière première, évitant la décharge et réduisant le CO₂.</p>
                    <div class="flex items-center gap-2 text-blue-600 text-sm font-semibold">
                        <span>En savoir plus</span>
                        <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform duration-200"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- =============================================
     SECTION 4 : IMPACT CO₂
     ============================================= --}}
<section id="impact" class="py-24 bg-gradient-to-br from-primary-dark via-primary-DEFAULT to-primary-light relative overflow-hidden">

    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-10 left-10 w-64 h-64 bg-white rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 right-10 w-48 h-48 bg-secondary-DEFAULT rounded-full blur-3xl"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">

        <div class="text-center mb-16">
            <span class="text-secondary-DEFAULT text-sm font-semibold uppercase tracking-widest">Notre impact</span>
            <h2 class="font-display text-4xl lg:text-5xl font-bold text-white mt-2 mb-4">
                Chaque geste compte
            </h2>
            <p class="text-white/70 max-w-xl mx-auto text-lg">
                Des chiffres réels générés par notre communauté.
            </p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
            @php
                $stats = [
                    ['icon' => 'fa-tshirt',       'value' => '12 450',  'label' => 'Vêtements sauvés',     'unit' => 'pièces'],
                    ['icon' => 'fa-smog',          'value' => '3.2',     'label' => 'CO₂ économisé',        'unit' => 'tonnes'],
                    ['icon' => 'fa-tint',          'value' => '860 000', 'label' => "Eau économisée",        'unit' => 'litres'],
                    ['icon' => 'fa-users',         'value' => '850+',    'label' => 'Membres actifs',       'unit' => 'personnes'],
                ];
            @endphp

            @foreach($stats as $stat)
            <div class="glass-card rounded-2xl p-7 text-center hover:scale-105 transition-transform duration-300">
                <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center mx-auto mb-4">
                    <i class="fas {{ $stat['icon'] }} text-secondary-DEFAULT text-xl"></i>
                </div>
                <p class="font-display text-3xl font-bold text-white mb-1">{{ $stat['value'] }}</p>
                <p class="text-white/80 text-sm font-semibold">{{ $stat['label'] }}</p>
                <p class="text-white/50 text-xs mt-1">{{ $stat['unit'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- =============================================
     SECTION 5 : ACTEURS / REJOINDRE
     ============================================= --}}
<section id="acteurs" class="py-24 bg-[#FAFAF8]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-16">
            <span class="text-primary-DEFAULT text-sm font-semibold uppercase tracking-widest">Les acteurs</span>
            <h2 class="font-display text-4xl lg:text-5xl font-bold text-gray-900 mt-2 mb-4">
                Rejoignez l'écosystème RETISS
            </h2>
            <p class="text-gray-500 max-w-xl mx-auto text-lg">
                Quelle que soit votre place dans la chaîne textile, RETISS a un rôle pour vous.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @php
                $actors = [
                    ['icon' => 'fa-hand-holding-heart', 'role' => 'Donateur',   'color' => 'text-green-600',  'bg' => 'bg-green-50',  'border' => 'border-green-100', 'desc' => 'Déposez vos vêtements et suivez leur parcours grâce au QR code.'],
                    ['icon' => 'fa-shopping-bag',       'role' => 'Client',     'color' => 'text-blue-600',   'bg' => 'bg-blue-50',   'border' => 'border-blue-100',  'desc' => 'Achetez des vêtements de seconde main ou commandez une pièce upcyclée unique.'],
                    ['icon' => 'fa-truck',              'role' => 'Collecteur', 'color' => 'text-amber-600',  'bg' => 'bg-amber-50',  'border' => 'border-amber-100', 'desc' => 'Gérez vos tournées de collecte avec optimisation d\'itinéraire IA.'],
                    ['icon' => 'fa-cut',                'role' => 'Atelier',    'color' => 'text-purple-600', 'bg' => 'bg-purple-50', 'border' => 'border-purple-100','desc' => 'Recevez des vêtements à transformer et partagez vos créations.'],
                    ['icon' => 'fa-recycle',            'role' => 'Recycleur',  'color' => 'text-cyan-600',   'bg' => 'bg-cyan-50',   'border' => 'border-cyan-100',  'desc' => 'Traitez les lots textiles et générez des certificats d\'impact.'],
                    ['icon' => 'fa-shield-alt',         'role' => 'Admin',      'color' => 'text-gray-600',   'bg' => 'bg-gray-50',   'border' => 'border-gray-200',  'desc' => 'Supervisez la plateforme, gérez les utilisateurs et les statistiques globales.'],
                ];
            @endphp

            @foreach($actors as $actor)
            <div class="group bg-white border {{ $actor['border'] }} rounded-2xl p-6 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                <div class="w-12 h-12 rounded-xl {{ $actor['bg'] }} flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas {{ $actor['icon'] }} {{ $actor['color'] }} text-xl"></i>
                </div>
                <h3 class="font-bold text-gray-900 text-lg mb-2">{{ $actor['role'] }}</h3>
                <p class="text-gray-500 text-sm leading-relaxed mb-5">{{ $actor['desc'] }}</p>
                <a href="{{ route('register') }}"
                   class="inline-flex items-center gap-1 text-sm font-semibold {{ $actor['color'] }} hover:gap-2 transition-all duration-200">
                    S'inscrire comme {{ $actor['role'] }}
                    <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- =============================================
     SECTION 6 : CTA FINAL
     ============================================= --}}
<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="bg-gradient-to-br from-primary-dark to-primary-DEFAULT rounded-3xl p-12 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-48 h-48 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-secondary-DEFAULT/10 rounded-full translate-y-1/2 -translate-x-1/2"></div>
            <div class="relative">
                <i class="fas fa-leaf text-secondary-DEFAULT text-4xl mb-4"></i>
                <h2 class="font-display text-3xl lg:text-4xl font-bold text-white mb-4">
                    Prêt à agir pour la planète ?
                </h2>
                <p class="text-white/70 text-lg mb-8 max-w-xl mx-auto">
                    Rejoignez des milliers de membres qui donnent une seconde vie au textile chaque jour.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center justify-center gap-2 bg-white text-primary-dark font-bold px-8 py-4 rounded-2xl hover:shadow-xl hover:scale-[1.03] transition-all duration-200">
                        <i class="fas fa-user-plus"></i>
                        Créer un compte gratuit
                    </a>
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center justify-center gap-2 border-2 border-white/40 text-white font-semibold px-8 py-4 rounded-2xl hover:bg-white/10 transition-all duration-200">
                        <i class="fas fa-sign-in-alt"></i>
                        Se connecter
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
