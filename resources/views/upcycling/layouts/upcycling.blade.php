@extends('layouts.app')

@php
    $role = Auth::user()->role;
    $estAtelier = $role === 'ATELIER';
    $monAtelier = $estAtelier ? \App\Models\Atelier::where('user_id', Auth::id())->first() : null;
    $lien = fn ($actif) => 'flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 '
        . ($actif ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white');
@endphp

@section('content')
<div class="min-h-screen bg-gray-50 pt-[72px]">

    <div class="flex">

        {{-- ===== SIDEBAR ===== --}}
        <aside class="hidden lg:flex flex-col w-64 flex-shrink-0 self-start sticky top-[72px] h-[calc(100vh-72px)] overflow-y-auto bg-gradient-to-b from-navy-dark via-navy to-forest text-white pt-6 pb-6 z-30">

            {{-- Titre module --}}
            <div class="px-6 mb-8">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center">
                        <i class="fas fa-cut text-accent text-sm"></i>
                    </div>
                    <span class="font-bold text-white text-sm">Module Upcycling</span>
                </div>
                <p class="text-white/50 text-xs ml-10">M3 — Transformer au lieu de jeter</p>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 px-3 space-y-1">

                <a href="{{ route('upcycling.dashboard') }}" class="{{ $lien(request()->routeIs('upcycling.dashboard')) }}">
                    <i class="fas fa-th-large w-4 text-center"></i>
                    Dashboard
                </a>

                <a href="{{ route('upcycling.projets.index') }}"
                   class="{{ $lien(request()->routeIs('upcycling.projets.*') && !request()->routeIs('upcycling.projets.create')) }}">
                    <i class="fas fa-layer-group w-4 text-center"></i>
                    {{ $estAtelier ? 'Projets reçus' : 'Mes projets' }}
                </a>

                @unless($estAtelier)
                <a href="{{ route('upcycling.projets.create') }}" class="{{ $lien(request()->routeIs('upcycling.projets.create')) }}">
                    <i class="fas fa-plus-circle w-4 text-center"></i>
                    Demander un upcycling
                </a>
                @endunless

                <div class="pt-4 pb-2 px-4">
                    <p class="text-white/30 text-xs uppercase tracking-wider font-semibold">Ateliers</p>
                </div>

                <a href="{{ route('upcycling.ateliers.index') }}" class="{{ $lien(request()->routeIs('upcycling.ateliers.index')) }}">
                    <i class="fas fa-store w-4 text-center"></i>
                    Catalogue des ateliers
                </a>

                @if($estAtelier)
                    @if($monAtelier)
                        <a href="{{ route('upcycling.ateliers.show', $monAtelier) }}"
                           class="{{ $lien(request()->routeIs('upcycling.ateliers.show', 'upcycling.ateliers.edit')) }}">
                            <i class="fas fa-images w-4 text-center"></i>
                            Mon portfolio
                        </a>
                    @else
                        <a href="{{ route('upcycling.ateliers.create') }}" class="{{ $lien(request()->routeIs('upcycling.ateliers.create')) }}">
                            <i class="fas fa-id-card w-4 text-center"></i>
                            Créer mon profil
                        </a>
                    @endif
                @endif
            </nav>

            {{-- Profil bas --}}
            <div class="px-4 mt-auto">
                <div class="bg-white/10 rounded-xl p-3 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                        <span class="text-white font-bold text-sm">{{ Auth::user()->initials }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-white text-xs font-semibold truncate">{{ Auth::user()->full_name }}</p>
                        <p class="text-white/50 text-xs">{{ ucfirst(strtolower($role)) }}</p>
                    </div>
                </div>
            </div>
        </aside>

        {{-- ===== CONTENU PRINCIPAL ===== --}}
        <main class="flex-1 p-6 lg:p-8 min-w-0">

            {{-- Navigation mobile du module --}}
            <div class="lg:hidden flex gap-2 overflow-x-auto pb-4 mb-2 -mx-1 px-1">
                <a href="{{ route('upcycling.dashboard') }}" class="flex-shrink-0 text-xs font-semibold px-3 py-2 rounded-lg bg-white border border-gray-200 text-gray-700">Dashboard</a>
                <a href="{{ route('upcycling.projets.index') }}" class="flex-shrink-0 text-xs font-semibold px-3 py-2 rounded-lg bg-white border border-gray-200 text-gray-700">Projets</a>
                @unless($estAtelier)
                <a href="{{ route('upcycling.projets.create') }}" class="flex-shrink-0 text-xs font-semibold px-3 py-2 rounded-lg bg-white border border-gray-200 text-gray-700">Nouvelle demande</a>
                @endunless
                <a href="{{ route('upcycling.ateliers.index') }}" class="flex-shrink-0 text-xs font-semibold px-3 py-2 rounded-lg bg-white border border-gray-200 text-gray-700">Ateliers</a>
            </div>

            {{-- Breadcrumb --}}
            @hasSection('breadcrumb')
            <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
                <a href="{{ route('upcycling.dashboard') }}" class="hover:text-primary transition-colors">
                    <i class="fas fa-cut"></i> Upcycling
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

            @yield('upcycling-content')
        </main>
    </div>
</div>
@endsection
