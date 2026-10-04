{{-- ============================================================
     NAVBAR RETISS — JS pur, sans Alpine
     ============================================================ --}}

<style>
    /* ---- État par défaut : navbar opaque (toutes les pages sauf hero) ---- */
    #navbar {
        transition: background 0.3s, box-shadow 0.3s, border-color 0.3s;
        background: rgba(255,255,255,0.97);
        backdrop-filter: blur(20px);
        box-shadow: 0 1px 24px rgba(15,23,42,0.08);
        border-bottom: 1px solid #e2e8f0;
    }

    #navbar .nav-link {
        color: #475569;
        font-size: 0.875rem;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 0.75rem;
        transition: all 0.2s;
        text-decoration: none;
    }
    #navbar .nav-link:hover        { color: #0d9488; background: rgba(13,148,136,0.06); }
    #navbar .nav-brand-name        { color: #1a2744; }
    #navbar .nav-brand-sub         { color: #0d9488; }
    #navbar .nav-login             { color: #1a2744; }
    #navbar .nav-login:hover       { color: #0d9488; background: rgba(13,148,136,0.06); }
    #navbar .nav-avatar-wrap       { background: #f8fafc; }
    #navbar .nav-username          { color: #1a2744; }
    #navbar .nav-role              { color: #94a3b8; }
    #navbar .nav-dashboard         { background: #0d9488; color: #fff; }
    #navbar .nav-dashboard:hover   { background: #0f766e; }
    #navbar .nav-logout            { color: #94a3b8; }
    #navbar .nav-logout:hover      { color: #ef4444; background: #fff5f5; }
    #navbar .nav-burger            { color: #1a2744; }

    /* ---- Pages avec hero (welcome) : navbar transparente au sommet ---- */
    body.has-hero #navbar {
        background: transparent;
        box-shadow: none;
        border-bottom: 1px solid transparent;
    }
    body.has-hero #navbar .nav-link        { color: rgba(255,255,255,0.85); }
    body.has-hero #navbar .nav-link:hover  { color: #fff; background: rgba(255,255,255,0.12); }
    body.has-hero #navbar .nav-brand-name  { color: #fff; }
    body.has-hero #navbar .nav-brand-sub   { color: #2DD4BF; }
    body.has-hero #navbar .nav-login       { color: rgba(255,255,255,0.9); }
    body.has-hero #navbar .nav-login:hover { color: #fff; background: rgba(255,255,255,0.1); }
    body.has-hero #navbar .nav-avatar-wrap { background: rgba(255,255,255,0.1); }
    body.has-hero #navbar .nav-username    { color: #fff; }
    body.has-hero #navbar .nav-role        { color: rgba(255,255,255,0.6); }
    body.has-hero #navbar .nav-dashboard   { background: rgba(255,255,255,0.15); color: #fff; }
    body.has-hero #navbar .nav-dashboard:hover { background: rgba(255,255,255,0.25); }
    body.has-hero #navbar .nav-logout      { color: rgba(255,255,255,0.6); }
    body.has-hero #navbar .nav-logout:hover{ color: #fff; background: rgba(255,255,255,0.1); }
    body.has-hero #navbar .nav-burger      { color: #fff; }

    /* ---- Une fois scrollé sur une page hero : redevient opaque ---- */
    body.has-hero #navbar.scrolled {
        background: rgba(255,255,255,0.97) !important;
        backdrop-filter: blur(20px);
        box-shadow: 0 1px 24px rgba(15,23,42,0.08);
        border-bottom: 1px solid #e2e8f0;
    }
    body.has-hero #navbar.scrolled .nav-link        { color: #475569; }
    body.has-hero #navbar.scrolled .nav-link:hover  { color: #0d9488; background: rgba(13,148,136,0.06); }
    body.has-hero #navbar.scrolled .nav-brand-name  { color: #1a2744; }
    body.has-hero #navbar.scrolled .nav-brand-sub   { color: #0d9488; }
    body.has-hero #navbar.scrolled .nav-login       { color: #1a2744; }
    body.has-hero #navbar.scrolled .nav-login:hover { color: #0d9488; background: rgba(13,148,136,0.06); }
    body.has-hero #navbar.scrolled .nav-avatar-wrap { background: #f8fafc; }
    body.has-hero #navbar.scrolled .nav-username    { color: #1a2744; }
    body.has-hero #navbar.scrolled .nav-role        { color: #94a3b8; }
    body.has-hero #navbar.scrolled .nav-dashboard   { background: #0d9488; color: #fff; }
    body.has-hero #navbar.scrolled .nav-dashboard:hover { background: #0f766e; }
    body.has-hero #navbar.scrolled .nav-logout      { color: #94a3b8; }
    body.has-hero #navbar.scrolled .nav-logout:hover{ color: #ef4444; background: #fff5f5; }
    body.has-hero #navbar.scrolled .nav-burger      { color: #1a2744; }

    /* Mobile menu */
    #mobile-menu { display: none; }
    #mobile-menu.open { display: block; }
</style>

<nav id="navbar" class="fixed top-0 left-0 right-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between" style="height:72px">

            {{-- ===== LOGO ===== --}}
            <a href="{{ route('home') }}" class="flex items-center gap-3 flex-shrink-0" style="text-decoration:none">
                @if(file_exists(public_path('images/logo.png')))
                    <img src="{{ asset('images/logo.png') }}" alt="RETISS"
                         class="h-10 w-10 object-contain"
                         style="transition: transform 0.2s"
                         onmouseover="this.style.transform='scale(1.05)'"
                         onmouseout="this.style.transform='scale(1)'">
                @else
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                         style="background: linear-gradient(135deg, #1a2744, #0d9488)">
                        <i class="fas fa-recycle text-white text-lg"></i>
                    </div>
                @endif
                <div class="flex flex-col leading-tight">
                    <span class="nav-brand-name font-display font-bold text-xl tracking-wide">RETISS</span>
                    <span class="nav-brand-sub text-[10px] font-semibold tracking-widest uppercase">Textile Circulaire</span>
                </div>
            </a>

            {{-- ===== NAV DESKTOP ===== --}}
            <div class="hidden lg:flex items-center gap-1">
                <a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'font-semibold' : '' }}">
                    Accueil
                </a>
                <a href="{{ request()->routeIs('home') ? '#comment-ca-marche' : route('home') . '#comment-ca-marche' }}" class="nav-link">
                    Comment ça marche
                </a>
                <a href="{{ request()->routeIs('home') ? '#impact' : route('home') . '#impact' }}" class="nav-link">
                    Impact
                </a>
                <a href="{{ request()->routeIs('home') ? '#acteurs' : route('home') . '#acteurs' }}" class="nav-link">
                    Rejoindre
                </a>
            </div>

            {{-- ===== AUTH BUTTONS ===== --}}
            <div class="hidden lg:flex items-center gap-3">
                @auth
                    {{-- Avatar (cliquable → profil) --}}
                    <a href="{{ route('profile.edit') }}"
                       class="nav-avatar-wrap flex items-center gap-2 px-3 py-1.5 rounded-xl transition-all duration-200"
                       style="text-decoration:none"
                       title="Voir mon profil"
                       onmouseover="this.style.boxShadow='0 0 0 2px #0d9488'" onmouseout="this.style.boxShadow='none'">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                             style="background: linear-gradient(135deg, #0d9488, #4ade80)">
                            {{ Auth::user()->initials }}
                        </div>
                        <div class="leading-tight">
                            <p class="nav-username text-xs font-semibold">{{ Auth::user()->prenom ?? Auth::user()->name }}</p>
                            <p class="nav-role text-[10px]">{{ Auth::user()->role }}</p>
                        </div>
                        <i class="fas fa-chevron-right nav-role" style="font-size:9px;margin-left:2px"></i>
                    </a>

                    {{-- Marketplace --}}
                    <a href="{{ route('articles.index') }}"
                       class="nav-link flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-xl transition-all duration-200 {{ request()->routeIs('articles.*') ? 'text-teal-600 bg-teal-50' : '' }}"
                       title="Marketplace textile">
                        <i class="fas fa-store text-xs text-teal-600"></i> Marketplace
                    </a>

                    {{-- Adresses --}}
                    <a href="{{ route('adresses.index') }}"
                       class="nav-link flex items-center gap-1.5 text-xs font-semibold px-3 py-2 rounded-xl transition-all duration-200 {{ request()->routeIs('adresses.*') ? 'text-teal-600 bg-teal-50' : '' }}"
                       title="Mes adresses">
                        <i class="fas fa-map-marker-alt text-xs text-teal-600"></i> Adresses
                    </a>

                    {{-- Dashboard --}}
                    @if(Auth::user()->role === 'RECYCLEUR')
                        <a href="{{ route('recyclage.dashboard') }}"
                           class="nav-dashboard flex items-center gap-1.5 text-xs font-semibold px-4 py-2 rounded-xl transition-all duration-200">
                            <i class="fas fa-th-large text-xs"></i> Dashboard
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}"
                           class="nav-dashboard flex items-center gap-1.5 text-xs font-semibold px-4 py-2 rounded-xl transition-all duration-200">
                            <i class="fas fa-th-large text-xs"></i> Dashboard
                        </a>
                    @endif

                    {{-- Logout --}}
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="nav-logout flex items-center gap-1.5 text-xs font-medium px-3 py-2 rounded-xl transition-all duration-200">
                            <i class="fas fa-sign-out-alt text-xs"></i>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                       class="nav-login text-sm font-semibold px-5 py-2.5 rounded-xl transition-all duration-200">
                        Connexion
                    </a>
                    <a href="{{ route('register') }}"
                       class="text-sm font-bold px-5 py-2.5 rounded-xl text-white transition-all duration-200"
                       style="background: linear-gradient(135deg, #0d9488, #2DD4BF)"
                       onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 8px 24px rgba(13,148,136,0.4)'"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                        S'inscrire gratuitement
                    </a>
                @endauth
            </div>

            {{-- ===== BURGER MOBILE ===== --}}
            <button id="burger-btn"
                    class="nav-burger lg:hidden p-2.5 rounded-xl transition-colors"
                    onclick="toggleMenu()">
                <i id="burger-icon" class="fas fa-bars text-lg"></i>
            </button>
        </div>
    </div>

    {{-- ===== MENU MOBILE ===== --}}
    <div id="mobile-menu" class="lg:hidden bg-white border-t border-slate-100 shadow-xl">
        <div class="px-4 py-5 space-y-1">
            <a href="{{ route('home') }}"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors"
               style="text-decoration:none">
                <i class="fas fa-home w-4 text-center" style="color:#0d9488"></i> Accueil
            </a>
            <a href="#comment-ca-marche"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors"
               style="text-decoration:none">
                <i class="fas fa-info-circle w-4 text-center" style="color:#0d9488"></i> Comment ça marche
            </a>
            <a href="#impact"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors"
               style="text-decoration:none">
                <i class="fas fa-leaf w-4 text-center" style="color:#0d9488"></i> Impact
            </a>
            <a href="#acteurs"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors"
               style="text-decoration:none">
                <i class="fas fa-users w-4 text-center" style="color:#0d9488"></i> Rejoindre
            </a>

            <div class="pt-4 mt-2 space-y-2" style="border-top: 1px solid #e2e8f0">
                @auth
                    <a href="{{ route('articles.index') }}"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors {{ request()->routeIs('articles.*') ? 'bg-teal-50 text-teal-700 font-bold' : '' }}"
                       style="text-decoration:none">
                        <i class="fas fa-store w-4 text-center" style="color:#0d9488"></i> Marketplace Textile
                    </a>

                    <a href="{{ route('adresses.index') }}"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors {{ request()->routeIs('adresses.*') ? 'bg-teal-50 text-teal-700 font-bold' : '' }}"
                       style="text-decoration:none">
                        <i class="fas fa-map-marker-alt w-4 text-center" style="color:#0d9488"></i> Mes Adresses
                    </a>

                    @if(Auth::user()->role === 'RECYCLEUR')
                        <a href="{{ route('recyclage.dashboard') }}"
                           class="flex items-center justify-center gap-2 w-full py-3 rounded-xl text-white text-sm font-bold"
                           style="background: linear-gradient(135deg, #0d9488, #2DD4BF)">
                            <i class="fas fa-th-large text-xs"></i> Dashboard
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center justify-center gap-2 w-full py-3 rounded-xl text-white text-sm font-bold"
                           style="background: linear-gradient(135deg, #0d9488, #2DD4BF)">
                            <i class="fas fa-th-large text-xs"></i> Dashboard
                        </a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full py-3 text-sm font-medium text-slate-500 hover:text-red-500 transition-colors">
                            <i class="fas fa-sign-out-alt mr-2"></i> Se déconnecter
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                       class="block w-full text-center py-3 rounded-xl text-sm font-semibold text-navy transition-all"
                       style="border: 2px solid #e2e8f0; text-decoration:none">
                        Connexion
                    </a>
                    <a href="{{ route('register') }}"
                       class="block w-full text-center py-3 rounded-xl text-white text-sm font-bold"
                       style="background: linear-gradient(135deg, #0d9488, #2DD4BF); text-decoration:none">
                        S'inscrire gratuitement
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>

{{-- PAS d'espace ici — géré par chaque page --}}

<script>
    // ---- Scroll handler (uniquement sur les pages avec hero) ----
    const navbar = document.getElementById('navbar');
    const hasHero = document.body.classList.contains('has-hero');

    if (hasHero) {
        function updateNavbar() {
            if (window.scrollY > 30) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }
        window.addEventListener('scroll', updateNavbar, { passive: true });
        updateNavbar(); // état initial
    }

    // ---- Mobile menu ----
    function toggleMenu() {
        const menu = document.getElementById('mobile-menu');
        const icon = document.getElementById('burger-icon');
        menu.classList.toggle('open');
        icon.className = menu.classList.contains('open') ? 'fas fa-times text-lg' : 'fas fa-bars text-lg';
    }
</script>
