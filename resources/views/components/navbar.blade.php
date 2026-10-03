<nav class="bg-white/95 backdrop-blur-md shadow-sm sticky top-0 z-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                {{-- Placeholder logo — sera remplacé par l'image --}}
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-dark to-primary-light flex items-center justify-center shadow-md group-hover:scale-105 transition-transform duration-200">
                    <span class="text-white font-black text-sm">R</span>
                </div>
                <span class="font-display font-bold text-xl text-primary-dark tracking-tight">
                    RETISS
                </span>
            </a>

            {{-- Navigation Desktop --}}
            <div class="hidden md:flex items-center gap-8">
                <a href="{{ route('home') }}"
                   class="text-sm font-medium text-gray-600 hover:text-primary-DEFAULT transition-colors duration-200 {{ request()->routeIs('home') ? 'text-primary-DEFAULT font-semibold' : '' }}">
                    Accueil
                </a>
                <a href="#comment-ca-marche"
                   class="text-sm font-medium text-gray-600 hover:text-primary-DEFAULT transition-colors duration-200">
                    Comment ça marche
                </a>
                <a href="#impact"
                   class="text-sm font-medium text-gray-600 hover:text-primary-DEFAULT transition-colors duration-200">
                    Impact
                </a>
                <a href="#acteurs"
                   class="text-sm font-medium text-gray-600 hover:text-primary-DEFAULT transition-colors duration-200">
                    Rejoindre
                </a>
            </div>

            {{-- Boutons Auth --}}
            <div class="hidden md:flex items-center gap-3">
                @auth
                    {{-- Utilisateur connecté --}}
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-600">
                            Bonjour, <span class="font-semibold text-primary-dark">{{ Auth::user()->name }}</span>
                        </span>
                        <a href="{{ route('dashboard') }}"
                           class="bg-primary-DEFAULT text-white text-sm font-semibold px-4 py-2 rounded-xl hover:bg-primary-dark transition-all duration-200 hover:shadow-md">
                            <i class="fas fa-th-large mr-1"></i> Dashboard
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="text-sm text-gray-500 hover:text-red-500 transition-colors duration-200 font-medium">
                                <i class="fas fa-sign-out-alt mr-1"></i> Déconnexion
                            </button>
                        </form>
                    </div>
                @else
                    {{-- Visiteur --}}
                    <a href="{{ route('login') }}"
                       class="text-sm font-semibold text-primary-DEFAULT hover:text-primary-dark transition-colors duration-200 px-4 py-2 rounded-xl hover:bg-secondary-light">
                        Connexion
                    </a>
                    <a href="{{ route('register') }}"
                       class="bg-gradient-to-r from-primary-dark to-primary-DEFAULT text-white text-sm font-semibold px-5 py-2.5 rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
                        S'inscrire
                    </a>
                @endauth
            </div>

            {{-- Burger Menu Mobile --}}
            <button id="menu-toggle"
                    class="md:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors duration-200">
                <i class="fas fa-bars text-lg" id="menu-icon"></i>
            </button>
        </div>
    </div>

    {{-- Menu Mobile --}}
    <div id="mobile-menu" class="hidden md:hidden border-t border-gray-100 bg-white">
        <div class="px-4 py-4 space-y-3">
            <a href="{{ route('home') }}"
               class="block text-sm font-medium text-gray-700 hover:text-primary-DEFAULT py-2 transition-colors">
                <i class="fas fa-home w-5 mr-2"></i> Accueil
            </a>
            <a href="#comment-ca-marche"
               class="block text-sm font-medium text-gray-700 hover:text-primary-DEFAULT py-2 transition-colors">
                <i class="fas fa-info-circle w-5 mr-2"></i> Comment ça marche
            </a>
            <a href="#impact"
               class="block text-sm font-medium text-gray-700 hover:text-primary-DEFAULT py-2 transition-colors">
                <i class="fas fa-leaf w-5 mr-2"></i> Impact
            </a>
            <a href="#acteurs"
               class="block text-sm font-medium text-gray-700 hover:text-primary-DEFAULT py-2 transition-colors">
                <i class="fas fa-users w-5 mr-2"></i> Rejoindre
            </a>
            <div class="pt-3 border-t border-gray-100 flex flex-col gap-2">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="block text-center bg-primary-DEFAULT text-white text-sm font-semibold px-4 py-2.5 rounded-xl">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="block text-center border-2 border-primary-DEFAULT text-primary-DEFAULT text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-primary-DEFAULT hover:text-white transition-all duration-200">
                        Connexion
                    </a>
                    <a href="{{ route('register') }}"
                       class="block text-center bg-gradient-to-r from-primary-dark to-primary-DEFAULT text-white text-sm font-semibold px-4 py-2.5 rounded-xl">
                        S'inscrire
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>

<script>
    const toggle = document.getElementById('menu-toggle');
    const menu   = document.getElementById('mobile-menu');
    const icon   = document.getElementById('menu-icon');
    toggle.addEventListener('click', () => {
        menu.classList.toggle('hidden');
        icon.className = menu.classList.contains('hidden')
            ? 'fas fa-bars text-lg'
            : 'fas fa-times text-lg';
    });
</script>
