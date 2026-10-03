{{-- ============================================================
     FOOTER RETISS — Design professionnel couleurs logo
     ============================================================ --}}
<footer style="background: linear-gradient(180deg, #111a30 0%, #0f1a28 100%);" class="text-white relative overflow-hidden">

    {{-- Cercles décoratifs --}}
    <div class="absolute top-0 right-0 w-96 h-96 rounded-full opacity-5"
         style="background: radial-gradient(circle, #0d9488, transparent); transform: translate(30%, -30%)"></div>
    <div class="absolute bottom-0 left-0 w-64 h-64 rounded-full opacity-5"
         style="background: radial-gradient(circle, #4ade80, transparent); transform: translate(-30%, 30%)"></div>

    {{-- Contenu principal --}}
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-14">

            {{-- Colonne 1 : Brand --}}
            <div class="lg:col-span-1">
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-3 mb-5 group">
                    @if(file_exists(public_path('images/logo.png')))
                        <img src="{{ asset('images/logo.png') }}"
                             alt="RETISS"
                             class="h-10 w-10 object-contain group-hover:scale-105 transition-transform">
                    @else
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                             style="background: linear-gradient(135deg, #1a2744, #0d9488)">
                            <i class="fas fa-recycle text-white"></i>
                        </div>
                    @endif
                    <div>
                        <p class="font-display font-bold text-xl text-white tracking-wide">RETISS</p>
                        <p class="text-[10px] text-teal-light font-medium tracking-widest uppercase">Textile Circulaire</p>
                    </div>
                </a>

                <p class="text-slate-400 text-sm leading-relaxed mb-6">
                    Plateforme de l'économie circulaire du textile. Donnez une seconde vie à vos vêtements.
                </p>

                {{-- Réseaux sociaux --}}
                <div class="flex gap-2">
                    @foreach([
                        ['fab fa-facebook-f', '#'],
                        ['fab fa-instagram', '#'],
                        ['fab fa-linkedin-in', '#'],
                        ['fab fa-twitter', '#'],
                    ] as [$icon, $url])
                    <a href="{{ $url }}"
                       class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 transition-all duration-200 hover:text-white"
                       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.08);"
                       onmouseover="this.style.background='linear-gradient(135deg,#0d9488,#2DD4BF)'; this.style.borderColor='transparent'"
                       onmouseout="this.style.background='rgba(255,255,255,0.06)'; this.style.borderColor='rgba(255,255,255,0.08)'">
                        <i class="{{ $icon }} text-xs"></i>
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- Colonne 2 : Plateforme --}}
            <div>
                <h4 class="text-xs font-bold uppercase tracking-widest mb-5" style="color: #2DD4BF;">
                    Plateforme
                </h4>
                <ul class="space-y-3">
                    @foreach([
                        ['Comment ça marche', '#comment-ca-marche'],
                        ['Marketplace', '#'],
                        ['Upcycling', '#'],
                        ['Recyclage', '#'],
                        ['Impact CO₂', '#impact'],
                    ] as [$label, $url])
                    <li>
                        <a href="{{ $url }}"
                           class="flex items-center gap-2.5 text-sm text-slate-400 hover:text-white transition-colors group">
                            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 transition-colors group-hover:bg-teal"
                                  style="background:#2DD4BF; opacity:0.5"></span>
                            {{ $label }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            {{-- Colonne 3 : Rejoindre --}}
            <div>
                <h4 class="text-xs font-bold uppercase tracking-widest mb-5" style="color: #2DD4BF;">
                    Rejoindre en tant que
                </h4>
                <ul class="space-y-3">
                    @foreach([
                        ['fa-hand-holding-heart', 'Donateur'],
                        ['fa-shopping-bag', 'Client'],
                        ['fa-truck', 'Collecteur'],
                        ['fa-cut', 'Atelier'],
                        ['fa-recycle', 'Recycleur'],
                    ] as [$icon, $role])
                    <li>
                        <a href="{{ route('register') }}"
                           class="flex items-center gap-2.5 text-sm text-slate-400 hover:text-white transition-colors group">
                            <i class="fas {{ $icon }} text-xs w-4 text-center transition-colors"
                               style="color: rgba(45,212,191,0.6)"></i>
                            {{ $role }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            {{-- Colonne 4 : Contact + Badge --}}
            <div>
                <h4 class="text-xs font-bold uppercase tracking-widest mb-5" style="color: #2DD4BF;">
                    Contact
                </h4>
                <ul class="space-y-4 mb-6">
                    @foreach([
                        ['fa-map-marker-alt', 'Tunis, Tunisie'],
                        ['fa-envelope', 'contact@retiss.tn'],
                        ['fa-phone', '+216 XX XXX XXX'],
                    ] as [$icon, $text])
                    <li class="flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                             style="background: rgba(13,148,136,0.15)">
                            <i class="fas {{ $icon }} text-xs" style="color: #2DD4BF;"></i>
                        </div>
                        <span class="text-sm text-slate-400">{{ $text }}</span>
                    </li>
                    @endforeach
                </ul>

                {{-- Badge éco --}}
                <div class="rounded-2xl p-4" style="background: rgba(13,148,136,0.1); border: 1px solid rgba(13,148,136,0.2);">
                    <div class="flex items-center gap-2 mb-1.5">
                        <i class="fas fa-leaf text-sm" style="color: #4ade80;"></i>
                        <span class="text-white text-sm font-semibold">Projet éco-responsable</span>
                    </div>
                    <p class="text-slate-400 text-xs leading-relaxed">
                        Chaque vêtement traité réduit l'empreinte carbone collective.
                    </p>
                </div>
            </div>
        </div>

        {{-- Séparateur --}}
        <div class="h-px mb-8" style="background: linear-gradient(to right, transparent, rgba(255,255,255,0.08), transparent)"></div>

        {{-- Barre du bas --}}
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
            <p class="text-slate-500 text-sm">
                © {{ date('Y') }} <span class="text-white font-semibold">RETISS</span>.
                Tous droits réservés.
            </p>
            <div class="flex items-center gap-6">
                @foreach(['Confidentialité', 'CGU', 'Mentions légales'] as $link)
                <a href="#" class="text-slate-500 hover:text-white text-xs transition-colors">
                    {{ $link }}
                </a>
                @endforeach
            </div>
        </div>
    </div>
</footer>
