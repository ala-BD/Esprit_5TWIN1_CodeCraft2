<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RETISS') — Espace {{ Auth::user()->role ?? '' }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy:           { DEFAULT: '#1a2744', light: '#243560', dark: '#111a30' },
                        forest:         { DEFAULT: '#1B4332', light: '#2D6A4F', dark: '#0f2a1e' },
                        teal:           { DEFAULT: '#0d9488', light: '#2DD4BF', dark: '#0f766e' },
                        primary:        { DEFAULT: '#0d9488', light: '#2DD4BF', dark: '#0f766e' },
                        secondary:      { DEFAULT: '#4ade80', light: '#86EFAC', dark: '#16a34a' },
                        'primary-DEFAULT': '#0d9488',
                        'primary-dark':    '#0f766e',
                        'primary-light':   '#2DD4BF',
                        'secondary-DEFAULT': '#4ade80',
                        'secondary-dark':    '#16a34a',
                        'navy-DEFAULT':   '#1a2744',
                        'navy-dark':      '#111a30',
                        'forest-DEFAULT': '#1B4332',
                    },
                    fontFamily: {
                        sans:    ['Inter', 'sans-serif'],
                        display: ['Sora', 'Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <style>
        * { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; background: #f4f6f9; color: #0f172a; }

        /* ---- Topbar ---- */
        #app-topbar {
            height: 56px;
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            padding: 0 1.25rem;
            gap: 1rem;
        }

        /* ---- Animations ---- */
        .fade-in { animation: fadeIn 0.4s ease-out; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .fade-up { animation: fadeUp 0.5s ease-out; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

        /* ---- Scrollbar ---- */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #0d9488; border-radius: 3px; }

        /* ---- Inputs ---- */
        .input-retiss {
            width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem;
            border-radius: 0.75rem; border: 1.5px solid #e2e8f0;
            background: #fff; font-size: 0.875rem; color: #0f172a;
            transition: all 0.2s; outline: none;
        }
        .input-retiss:focus { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.12); }
    </style>

    @yield('styles')
</head>
<body class="min-h-screen antialiased">

    {{-- ===== TOPBAR AUTHENTIFIÉE ===== --}}
    <header id="app-topbar">

        {{-- Logo --}}
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 flex-shrink-0 no-underline">
            @if(file_exists(public_path('images/logo.png')))
                <img src="{{ asset('images/logo.png') }}" alt="RETISS" class="w-8 h-8 object-contain">
            @else
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: linear-gradient(135deg, #1a2744, #0d9488)">
                    <i class="fas fa-recycle text-white text-sm"></i>
                </div>
            @endif
            <div class="leading-tight">
                <span class="block font-display font-bold text-sm text-navy-DEFAULT tracking-wide">RETISS</span>
                <span class="block text-[10px] font-semibold tracking-widest uppercase text-primary-DEFAULT">Textile Circulaire</span>
            </div>
        </a>

        {{-- Breadcrumb / titre page --}}
        <div class="flex-1 min-w-0">
            @hasSection('page-title')
                <span class="text-sm font-semibold text-slate-700">@yield('page-title')</span>
            @endif
        </div>

        {{-- Actions droite --}}
        <div class="flex items-center gap-2 ml-auto">

            {{-- Lien vers son espace module --}}
            @auth
                @if(Auth::user()->role === 'RECYCLEUR')
                    <a href="{{ route('recyclage.dashboard') }}"
                       class="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all">
                        <i class="fas fa-recycle text-primary-DEFAULT text-xs"></i> Espace Recyclage
                    </a>
                @elseif(Auth::user()->role === 'ATELIER')
                    <a href="{{ route('upcycling.dashboard') }}"
                       class="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all">
                        <i class="fas fa-cut text-amber-500 text-xs"></i> Espace Upcycling
                    </a>
                @elseif(Auth::user()->role === 'COLLECTEUR')
                    <a href="{{ route('logistique.tournees.index') }}"
                       class="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all">
                        <i class="fas fa-route text-blue-500 text-xs"></i> Espace Logistique
                    </a>
                @elseif(Auth::user()->role === 'ADMIN')
                    <a href="{{ route('admin.users.index') }}"
                       class="hidden sm:inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all">
                        <i class="fas fa-user-shield text-purple-500 text-xs"></i> Administration
                    </a>
                @endif

                {{-- Notifications placeholder --}}
                {{-- <button class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 transition-colors relative">
                    <i class="fas fa-bell text-sm"></i>
                </button> --}}

                {{-- Avatar dropdown --}}
                <div class="relative" id="user-menu-wrapper">
                    <button onclick="toggleUserMenu()" id="user-menu-btn"
                            class="flex items-center gap-2 px-2.5 py-1.5 rounded-xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-200">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                             style="background: linear-gradient(135deg, #0d9488, #4ade80)">
                            {{ Auth::user()->initials ?? '?' }}
                        </div>
                        <div class="hidden sm:block leading-tight text-left">
                            <p class="text-xs font-semibold text-slate-800">{{ Auth::user()->prenom ?? Auth::user()->name }}</p>
                            <p class="text-[10px] text-slate-400 font-medium">{{ Auth::user()->role }}</p>
                        </div>
                        <i class="fas fa-chevron-down text-slate-400" style="font-size: 9px"></i>
                    </button>

                    {{-- Dropdown menu --}}
                    <div id="user-menu-dropdown"
                         class="hidden absolute right-0 mt-1.5 w-52 bg-white border border-slate-200 rounded-xl shadow-lg py-1.5 z-50">
                        <div class="px-4 py-2.5 border-b border-slate-100">
                            <p class="text-sm font-semibold text-slate-800">{{ Auth::user()->full_name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ Auth::user()->email }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}"
                           class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
                            <i class="fas fa-user-circle w-4 text-center text-slate-400"></i> Mon profil
                        </a>
                        <a href="{{ route('dashboard') }}"
                           class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
                            <i class="fas fa-th-large w-4 text-center text-slate-400"></i> Dashboard général
                        </a>
                        <div class="border-t border-slate-100 mt-1 pt-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="flex items-center gap-2.5 w-full px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition-colors">
                                    <i class="fas fa-sign-out-alt w-4 text-center"></i> Se déconnecter
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endauth
        </div>
    </header>

    {{-- ===== CONTENU ===== --}}
    <div class="min-h-[calc(100vh-56px)]">
        @yield('content')
    </div>

    <script>
        function toggleUserMenu() {
            const dropdown = document.getElementById('user-menu-dropdown');
            dropdown.classList.toggle('hidden');
        }
        // Fermer si clic extérieur
        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('user-menu-wrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                document.getElementById('user-menu-dropdown').classList.add('hidden');
            }
        });
    </script>

    @yield('scripts')
</body>
</html>
