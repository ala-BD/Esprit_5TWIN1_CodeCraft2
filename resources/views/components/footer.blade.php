<footer class="bg-primary-dark text-white">

    {{-- Section principale --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">

            {{-- Colonne 1 : Brand --}}
            <div class="lg:col-span-1">
                <div class="flex items-center gap-2 mb-5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-secondary-dark to-secondary-DEFAULT flex items-center justify-center shadow-md">
                        <span class="text-primary-dark font-black text-sm">R</span>
                    </div>
                    <span class="font-display font-bold text-2xl tracking-tight">RETISS</span>
                </div>
                <p class="text-gray-300 text-sm leading-relaxed mb-6">
                    Donnez une seconde vie à vos vêtements. Chaque don est une action concrète pour réduire l'impact de l'industrie textile.
                </p>
                {{-- Réseaux sociaux --}}
                <div class="flex gap-3">
                    <a href="#" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-secondary-DEFAULT hover:text-primary-dark flex items-center justify-center transition-all duration-200">
                        <i class="fab fa-facebook-f text-sm"></i>
                    </a>
                    <a href="#" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-secondary-DEFAULT hover:text-primary-dark flex items-center justify-center transition-all duration-200">
                        <i class="fab fa-instagram text-sm"></i>
                    </a>
                    <a href="#" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-secondary-DEFAULT hover:text-primary-dark flex items-center justify-center transition-all duration-200">
                        <i class="fab fa-linkedin-in text-sm"></i>
                    </a>
                    <a href="#" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-secondary-DEFAULT hover:text-primary-dark flex items-center justify-center transition-all duration-200">
                        <i class="fab fa-twitter text-sm"></i>
                    </a>
                </div>
            </div>

            {{-- Colonne 2 : Plateforme --}}
            <div>
                <h4 class="font-semibold text-secondary-DEFAULT mb-5 uppercase tracking-wider text-xs">
                    Plateforme
                </h4>
                <ul class="space-y-3">
                    <li><a href="#comment-ca-marche" class="text-gray-300 hover:text-white text-sm transition-colors duration-200 flex items-center gap-2"><i class="fas fa-chevron-right text-xs text-secondary-dark"></i> Comment ça marche</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white text-sm transition-colors duration-200 flex items-center gap-2"><i class="fas fa-chevron-right text-xs text-secondary-dark"></i> Marketplace</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white text-sm transition-colors duration-200 flex items-center gap-2"><i class="fas fa-chevron-right text-xs text-secondary-dark"></i> Upcycling</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white text-sm transition-colors duration-200 flex items-center gap-2"><i class="fas fa-chevron-right text-xs text-secondary-dark"></i> Recyclage</a></li>
                    <li><a href="#impact" class="text-gray-300 hover:text-white text-sm transition-colors duration-200 flex items-center gap-2"><i class="fas fa-chevron-right text-xs text-secondary-dark"></i> Impact CO₂</a></li>
                </ul>
            </div>

            {{-- Colonne 3 : Acteurs --}}
            <div>
                <h4 class="font-semibold text-secondary-DEFAULT mb-5 uppercase tracking-wider text-xs">
                    Rejoindre en tant que
                </h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('register') }}" class="text-gray-300 hover:text-white text-sm transition-colors duration-200 flex items-center gap-2"><i class="fas fa-hand-holding-heart text-xs text-secondary-dark"></i> Donateur</a></li>
                    <li><a href="{{ route('register') }}" class="text-gray-300 hover:text-white text-sm transition-colors duration-200 flex items-center gap-2"><i class="fas fa-shopping-bag text-xs text-secondary-dark"></i> Client</a></li>
                    <li><a href="{{ route('register') }}" class="text-gray-300 hover:text-white text-sm transition-colors duration-200 flex items-center gap-2"><i class="fas fa-truck text-xs text-secondary-dark"></i> Collecteur</a></li>
                    <li><a href="{{ route('register') }}" class="text-gray-300 hover:text-white text-sm transition-colors duration-200 flex items-center gap-2"><i class="fas fa-cut text-xs text-secondary-dark"></i> Atelier</a></li>
                    <li><a href="{{ route('register') }}" class="text-gray-300 hover:text-white text-sm transition-colors duration-200 flex items-center gap-2"><i class="fas fa-recycle text-xs text-secondary-dark"></i> Recycleur</a></li>
                </ul>
            </div>

            {{-- Colonne 4 : Contact --}}
            <div>
                <h4 class="font-semibold text-secondary-DEFAULT mb-5 uppercase tracking-wider text-xs">
                    Contact
                </h4>
                <ul class="space-y-4">
                    <li class="flex items-start gap-3">
                        <i class="fas fa-map-marker-alt text-secondary-dark mt-0.5"></i>
                        <span class="text-gray-300 text-sm">Tunis, Tunisie</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i class="fas fa-envelope text-secondary-dark mt-0.5"></i>
                        <span class="text-gray-300 text-sm">contact@retiss.tn</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i class="fas fa-phone text-secondary-dark mt-0.5"></i>
                        <span class="text-gray-300 text-sm">+216 XX XXX XXX</span>
                    </li>
                </ul>

                {{-- Badge éco --}}
                <div class="mt-6 bg-white/10 rounded-xl p-4 border border-white/10">
                    <div class="flex items-center gap-2 mb-1">
                        <i class="fas fa-leaf text-secondary-DEFAULT"></i>
                        <span class="text-white text-sm font-semibold">Projet éco-responsable</span>
                    </div>
                    <p class="text-gray-400 text-xs">Chaque vêtement traité réduit notre empreinte carbone collective.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Barre du bas --}}
    <div class="border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col md:flex-row justify-between items-center gap-3">
            <p class="text-gray-400 text-sm">
                © {{ date('Y') }} <span class="text-white font-semibold">RETISS</span>. Tous droits réservés.
            </p>
            <div class="flex gap-5">
                <a href="#" class="text-gray-400 hover:text-white text-xs transition-colors duration-200">Politique de confidentialité</a>
                <a href="#" class="text-gray-400 hover:text-white text-xs transition-colors duration-200">CGU</a>
                <a href="#" class="text-gray-400 hover:text-white text-xs transition-colors duration-200">Mentions légales</a>
            </div>
        </div>
    </div>

</footer>
