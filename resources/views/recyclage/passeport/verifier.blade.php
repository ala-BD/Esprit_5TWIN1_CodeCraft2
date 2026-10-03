@extends('layouts.app')

@section('title', 'Vérification passeport — ' . $passeport->qr_code)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-green-50/30 py-12 px-4">
<div class="max-w-2xl mx-auto">

    {{-- Badge authentique --}}
    <div class="text-center mb-8 fade-in">
        <div class="inline-flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 font-bold px-5 py-2 rounded-full text-sm mb-4">
            <i class="fas fa-shield-alt"></i> Passeport vérifié et authentique
        </div>
        <h1 class="font-display text-3xl font-bold text-gray-900 mb-2">Passeport Numérique</h1>
        <p class="text-gray-500">Traçabilité RETISS — Économie circulaire du textile</p>
    </div>

    {{-- Card principale --}}
    <div class="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden fade-in">

        {{-- Header --}}
        <div class="bg-gradient-to-r from-primary-dark to-primary-DEFAULT px-8 py-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                    <span class="text-white font-black">R</span>
                </div>
                <div>
                    <p class="font-display font-bold text-white text-xl">RETISS</p>
                    <p class="text-white/70 text-xs">Plateforme du textile circulaire</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-white/60 text-xs">Code QR</p>
                <p class="font-mono font-bold text-white text-sm">{{ $passeport->qr_code }}</p>
            </div>
        </div>

        <div class="p-8">

            {{-- Infos lot --}}
            <h2 class="font-semibold text-gray-900 text-sm uppercase tracking-wider border-b border-gray-100 pb-3 mb-5">
                Informations du lot
            </h2>
            <div class="grid grid-cols-2 gap-4 text-sm mb-8">
                @php $lot = $passeport->lotTextile; @endphp
                <div>
                    <p class="text-gray-500 text-xs mb-1">Référence</p>
                    <p class="font-bold text-gray-900 font-mono">{{ $lot->reference }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs mb-1">Poids</p>
                    <p class="font-bold text-gray-900">{{ $lot->poids_kg }} kg</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs mb-1">Composition</p>
                    <p class="font-semibold text-gray-700">{{ $lot->composition }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs mb-1">Origine</p>
                    <p class="font-semibold text-gray-700">{{ $lot->origine }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs mb-1">Recycleur</p>
                    <p class="font-semibold text-gray-700">{{ $lot->recycleur->nom }}</p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs mb-1">Date d'émission</p>
                    <p class="font-semibold text-gray-700">{{ $passeport->date_emission->format('d/m/Y') }}</p>
                </div>
            </div>

            {{-- Impact --}}
            <h2 class="font-semibold text-gray-900 text-sm uppercase tracking-wider border-b border-gray-100 pb-3 mb-5">
                Impact écologique certifié
            </h2>
            <div class="grid grid-cols-3 gap-4 mb-8">
                <div class="bg-green-50 rounded-2xl p-4 text-center border border-green-100">
                    <i class="fas fa-smog text-green-500 text-xl mb-2 block"></i>
                    <p class="text-xl font-bold text-green-700">{{ $passeport->co2_evite_kg }}</p>
                    <p class="text-green-600 text-xs mt-1 font-medium">kg CO₂ évités</p>
                </div>
                <div class="bg-blue-50 rounded-2xl p-4 text-center border border-blue-100">
                    <i class="fas fa-tint text-blue-500 text-xl mb-2 block"></i>
                    <p class="text-xl font-bold text-blue-700">{{ number_format($passeport->eau_economisee_l, 0, ',', ' ') }}</p>
                    <p class="text-blue-600 text-xs mt-1 font-medium">litres eau</p>
                </div>
                <div class="bg-purple-50 rounded-2xl p-4 text-center border border-purple-100">
                    <i class="fas fa-recycle text-purple-500 text-xl mb-2 block"></i>
                    <p class="text-xl font-bold text-purple-700">{{ $lot->etapeTraitements->count() }}</p>
                    <p class="text-purple-600 text-xs mt-1 font-medium">étapes vérifiées</p>
                </div>
            </div>

            {{-- Hash intégrité --}}
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                <p class="text-xs text-gray-500 mb-2 flex items-center gap-2">
                    <i class="fas fa-lock text-green-500"></i>
                    Signature d'intégrité SHA-256
                </p>
                <p class="font-mono text-xs text-gray-600 break-all">{{ $passeport->hash_integrite }}</p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="bg-gray-50 border-t border-gray-100 px-8 py-4 flex items-center justify-between">
            <p class="text-xs text-gray-400">
                Vérifié le {{ now()->format('d/m/Y à H:i') }}
            </p>
            <span class="bg-green-100 text-green-700 text-xs font-bold px-3 py-1.5 rounded-full">
                <i class="fas fa-check-circle mr-1"></i> Authentique
            </span>
        </div>
    </div>

    {{-- Lien retour --}}
    <div class="text-center mt-8">
        <a href="{{ route('home') }}"
           class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-primary-DEFAULT transition-colors font-medium">
            <i class="fas fa-arrow-left text-xs"></i>
            Retour sur RETISS
        </a>
    </div>

</div>
</div>
@endsection
