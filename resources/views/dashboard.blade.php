@extends('layouts.app')

@section('body-class', 'no-hero')

@section('content')
<div class="min-h-screen bg-[#FAFAF8] py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-10">
            <h1 class="font-display text-3xl font-bold text-gray-900">
                Bonjour, <span class="text-primary-DEFAULT">{{ Auth::user()->prenom ?? Auth::user()->name }}</span> 👋
            </h1>
            <p class="text-gray-500 mt-1">
                Bienvenue sur votre espace
                <span class="font-semibold text-primary-dark">{{ Auth::user()->role }}</span>
            </p>
        </div>

        {{-- Cards rapides --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="w-11 h-11 rounded-xl bg-green-50 flex items-center justify-center mb-4">
                    <i class="fas fa-tshirt text-primary-DEFAULT text-lg"></i>
                </div>
                <p class="text-2xl font-bold text-gray-900">0</p>
                <p class="text-gray-500 text-sm mt-1">Dons effectués</p>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center mb-4">
                    <i class="fas fa-leaf text-blue-500 text-lg"></i>
                </div>
                <p class="text-2xl font-bold text-gray-900">0 kg</p>
                <p class="text-gray-500 text-sm mt-1">CO₂ économisé</p>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center mb-4">
                    <i class="fas fa-qrcode text-amber-500 text-lg"></i>
                </div>
                <p class="text-2xl font-bold text-gray-900">0</p>
                <p class="text-gray-500 text-sm mt-1">Passeports actifs</p>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center mb-4">
                    <i class="fas fa-star text-purple-500 text-lg"></i>
                </div>
                <p class="text-2xl font-bold text-gray-900">0</p>
                <p class="text-gray-500 text-sm mt-1">Points fidélité</p>
            </div>
        </div>

        {{-- Contenu principal --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
            <div class="w-20 h-20 rounded-full bg-gradient-to-br from-primary-DEFAULT to-secondary-dark flex items-center justify-center mx-auto mb-5">
                <i class="fas fa-check text-white text-2xl"></i>
            </div>
            <h2 class="font-display text-2xl font-bold text-gray-900 mb-3">
                Votre compte est actif !
            </h2>
            <p class="text-gray-500 max-w-md mx-auto mb-6">
                Votre espace <strong>{{ Auth::user()->role }}</strong> est prêt.
                Les fonctionnalités seront disponibles au fur et à mesure du développement.
            </p>
            <a href="{{ route('home') }}"
               class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-dark to-primary-DEFAULT
                      text-white font-semibold px-6 py-3 rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
                <i class="fas fa-home"></i>
                Retour à l'accueil
            </a>
        </div>

    </div>
</div>
@endsection
