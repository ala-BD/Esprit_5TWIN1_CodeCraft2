@extends('layouts.app-auth')

@section('content')
<div class="min-h-screen bg-gray-50">

    {{-- Sidebar + Contenu --}}
    <div class="flex">

        {{-- ===== SIDEBAR ===== --}}
        <aside class="hidden lg:flex flex-col w-64 min-h-[calc(100vh-56px)] bg-primary-dark text-white fixed top-14 left-0 pt-6 pb-10 z-30">

            {{-- Titre module --}}
            <div class="px-6 mb-8">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center">
                        <i class="fas fa-recycle text-secondary-DEFAULT text-sm"></i>
                    </div>
                    <span class="font-bold text-white text-sm">Module Recyclage</span>
                </div>
                <p class="text-white/50 text-xs ml-10">M4 — Gestion des lots</p>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 space-y-1">

                <a href="{{ route('recyclage.dashboard') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('recyclage.dashboard') ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-th-large w-4 text-center"></i>
                    Dashboard
                </a>

                <a href="{{ route('recyclage.lots.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('recyclage.lots.*') ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-boxes w-4 text-center"></i>
                    Mes lots textiles
                </a>

                <a href="{{ route('recyclage.lots.create') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          text-white/70 hover:bg-white/10 hover:text-white">
                    <i class="fas fa-plus-circle w-4 text-center"></i>
                    Enregistrer un lot
                </a>

                <div class="pt-4 pb-2 px-4">
                    <p class="text-white/30 text-xs uppercase tracking-wider font-semibold">Traçabilité</p>
                </div>

                <a href="{{ route('recyclage.passeports.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('recyclage.passeports.*') ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-qrcode w-4 text-center"></i>
                    Passeports émis
                </a>

                <a href="{{ route('recyclage.statistiques') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('recyclage.statistiques') ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                    <i class="fas fa-chart-bar w-4 text-center"></i>
                    Statistiques
                </a>
            </nav>

            {{-- Profil bas --}}
            <div class="px-4 mt-auto">
                <div class="bg-white/10 rounded-xl p-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-secondary-DEFAULT/30 flex items-center justify-center flex-shrink-0">
                        <span class="text-white font-bold text-sm">{{ Auth::user()->initials }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-white text-xs font-semibold truncate">{{ Auth::user()->full_name }}</p>
                        <p class="text-white/50 text-xs">Recycleur</p>
                    </div>
                </div>
            </div>
        </aside>

        {{-- ===== CONTENU PRINCIPAL ===== --}}
        <main class="flex-1 lg:ml-64 p-6 lg:p-8">

            {{-- Breadcrumb --}}
            @hasSection('breadcrumb')
            <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
                <a href="{{ route('recyclage.dashboard') }}" class="hover:text-primary-DEFAULT transition-colors">
                    <i class="fas fa-recycle"></i> Recyclage
                </a>
                @yield('breadcrumb')
            </nav>
            @endif

            {{-- Alertes flash --}}
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 flex items-center gap-3 fade-in">
                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-check text-green-600 text-sm"></i>
                    </div>
                    <p class="text-green-700 text-sm font-medium">{{ session('success') }}</p>
                    <button onclick="this.parentElement.remove()" class="ml-auto text-green-400 hover:text-green-600">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                    <p class="text-red-700 text-sm font-medium">{{ session('error') }}</p>
                </div>
            @endif

            @yield('recyclage-content')
        </main>
    </div>
</div>
@endsection
