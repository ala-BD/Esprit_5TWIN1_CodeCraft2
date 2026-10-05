{{-- Espace Logistique (M5) : réutilise la coque et le design system du layout admin --}}
@extends('admin.layouts.admin')

@section('espace-nom', 'Logistique')
@section('espace-accueil', route('logistique.tournees.index'))

@section('espace-navigation')
    <p class="px-2.5 pb-1.5 text-xs text-white/35">Collecte et livraison</p>

    <a href="{{ route('logistique.tournees.index') }}"
       class="a-nav-link {{ request()->routeIs('logistique.*') && !request()->routeIs('logistique.tournees.create') ? 'active' : '' }}">
        <i class="fas fa-route"></i>
        Mes tournées
    </a>

    <a href="{{ route('logistique.tournees.create') }}"
       class="a-nav-link {{ request()->routeIs('logistique.tournees.create') ? 'active' : '' }}">
        <i class="fas fa-plus"></i>
        Nouvelle tournée
    </a>
@endsection

@section('admin-content')
    @yield('logistique-content')
@endsection
