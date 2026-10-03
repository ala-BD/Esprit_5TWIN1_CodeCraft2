@extends('recyclage.layouts.recyclage')

@section('title', 'Passeport numérique — ' . $lot->reference)

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('recyclage.lots.index') }}" class="hover:text-primary-DEFAULT transition-colors">Lots</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <a href="{{ route('recyclage.lots.show', $lot) }}" class="hover:text-primary-DEFAULT transition-colors">{{ $lot->reference }}</a>
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Passeport</span>
@endsection

@section('recyclage-content')

@if(!$passeport)
{{-- ===== PAS ENCORE DE PASSEPORT ===== --}}
<div class="max-w-2xl mx-auto text-center py-12">

    <div class="w-24 h-24 rounded-3xl bg-purple-50 border-2 border-purple-100 flex items-center justify-center mx-auto mb-6">
        <i class="fas fa-qrcode text-purple-400 text-4xl"></i>
    </div>

    <h1 class="font-display text-2xl font-bold text-gray-900 mb-3">Générer le passeport numérique</h1>
    <p class="text-gray-500 leading-relaxed mb-3 max-w-md mx-auto">
        Le lot <strong class="text-gray-900">{{ $lot->reference }}</strong> est traité et prêt à être certifié.
        Le passeport numérique garantit la traçabilité complète et calcule l'impact écologique réel.
    </p>

    @if($lot->statut !== 'TRAITE')
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-8 max-w-md mx-auto flex items-start gap-3 text-left">
            <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="font-semibold text-amber-800 text-sm">Lot non encore traité</p>
                <p class="text-amber-600 text-xs mt-1">
                    Toutes les étapes de traitement doivent être terminées avant de générer le passeport.
                    Statut actuel : <strong>{{ $lot->statut_badge['label'] }}</strong>
                </p>
            </div>
        </div>
        <a href="{{ route('recyclage.lots.show', $lot) }}"
           class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-dark to-primary-DEFAULT
                  text-white font-bold px-8 py-3.5 rounded-2xl hover:shadow-lg transition-all duration-200">
            <i class="fas fa-arrow-left"></i> Retour au lot
        </a>
    @else
        {{-- Impact estimé --}}
        <div class="grid grid-cols-3 gap-4 mb-8 max-w-md mx-auto">
            <div class="bg-green-50 border border-green-100 rounded-2xl p-4 text-center">
                <i class="fas fa-smog text-green-500 text-xl mb-2 block"></i>
                <p class="text-lg font-bold text-green-700">{{ number_format($lot->poids_kg * 2.4, 1) }}</p>
                <p class="text-green-600 text-xs">kg CO₂ évités</p>
            </div>
            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 text-center">
                <i class="fas fa-tint text-blue-500 text-xl mb-2 block"></i>
                <p class="text-lg font-bold text-blue-700">{{ number_format($lot->poids_kg * 3000, 0) }}</p>
                <p class="text-blue-600 text-xs">litres eau</p>
            </div>
            <div class="bg-purple-50 border border-purple-100 rounded-2xl p-4 text-center">
                <i class="fas fa-weight text-purple-500 text-xl mb-2 block"></i>
                <p class="text-lg font-bold text-purple-700">{{ $lot->poids_kg }}</p>
                <p class="text-purple-600 text-xs">kg recyclés</p>
            </div>
        </div>

        <form method="POST" action="{{ route('recyclage.passeport.generer', $lot) }}">
            @csrf
            <button type="submit"
                    class="inline-flex items-center gap-3 bg-gradient-to-r from-purple-600 to-purple-500
                           text-white font-bold px-10 py-4 rounded-2xl hover:shadow-xl hover:scale-[1.02]
                           active:scale-[0.98] transition-all duration-200 text-lg">
                <i class="fas fa-certificate text-xl"></i>
                Générer & Certifier le passeport
            </button>
        </form>
        <p class="text-gray-400 text-xs mt-4">
            <i class="fas fa-lock mr-1"></i> Le passeport sera signé avec un hash SHA-256
        </p>
    @endif
</div>

@else
{{-- ===== PASSEPORT EXISTANT ===== --}}
<div class="max-w-3xl mx-auto">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="bg-purple-100 text-purple-700 text-xs font-bold px-3 py-1 rounded-full">
                    <i class="fas fa-certificate mr-1"></i> CERTIFIÉ
                </span>
            </div>
            <h1 class="font-display text-2xl font-bold text-gray-900">Passeport numérique</h1>
            <p class="text-gray-500 text-sm">Lot {{ $lot->reference }} — Émis le {{ $passeport->date_emission->format('d/m/Y à H:i') }}</p>
        </div>
        <a href="{{ route('recyclage.passeport.pdf', $lot) }}"
           class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-blue-500
                  text-white font-semibold px-5 py-2.5 rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
            <i class="fas fa-file-pdf"></i>
            Télécharger PDF
        </a>
    </div>

    {{-- Card passeport principale --}}
    <div class="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden mb-6">

        {{-- Entête colorée --}}
        <div class="bg-gradient-to-r from-primary-dark to-primary-DEFAULT px-8 py-6 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                        <span class="text-white font-black text-sm">R</span>
                    </div>
                    <span class="font-display font-bold text-white text-xl">RETISS</span>
                </div>
                <p class="text-white/70 text-sm">Passeport Numérique du Textile Circulaire</p>
            </div>
            <div class="text-right">
                <p class="text-white/60 text-xs mb-1">Référence lot</p>
                <p class="text-white font-bold font-mono text-lg">{{ $lot->reference }}</p>
            </div>
        </div>

        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                {{-- QR Code --}}
                <div class="flex flex-col items-center">
                    <div class="bg-white border-2 border-gray-100 rounded-2xl p-4 shadow-sm mb-4">
                        {!! QrCode::size(180)->generate(url('/passeport/' . $passeport->qr_code)) !!}
                    </div>
                    <p class="font-mono text-sm font-bold text-gray-900 text-center">{{ $passeport->qr_code }}</p>
                    <p class="text-gray-400 text-xs mt-1 text-center">Scannez pour vérifier l'authenticité</p>

                    {{-- Lien vérification --}}
                    <a href="{{ route('passeport.verifier', $passeport->qr_code) }}"
                       target="_blank"
                       class="mt-3 text-xs text-primary-DEFAULT hover:underline flex items-center gap-1">
                        <i class="fas fa-external-link-alt text-xs"></i>
                        Vérifier en ligne
                    </a>
                </div>

                {{-- Infos lot --}}
                <div class="space-y-4">
                    <h3 class="font-semibold text-gray-900 text-sm uppercase tracking-wider border-b border-gray-100 pb-2">
                        Informations du lot
                    </h3>
                    <div class="space-y-2.5 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Composition</span>
                            <span class="font-medium text-gray-800">{{ $lot->composition }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Poids</span>
                            <span class="font-medium text-gray-800">{{ $lot->poids_kg }} kg</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Origine</span>
                            <span class="font-medium text-gray-800">{{ $lot->origine }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Filière</span>
                            @php $filiere = $lot->filiere_ia_badge; @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-xs font-semibold {{ $filiere['class'] }}">
                                <i class="fas {{ $filiere['icon'] }} text-xs"></i> {{ $filiere['label'] }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Recycleur</span>
                            <span class="font-medium text-gray-800">{{ $lot->recycleur->nom }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Agrément</span>
                            <span class="font-mono text-xs text-gray-700">{{ $lot->recycleur->agrement }}</span>
                        </div>
                    </div>

                    {{-- Hash intégrité --}}
                    <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                        <p class="text-xs text-gray-500 mb-1"><i class="fas fa-shield-alt text-green-500 mr-1"></i> Hash SHA-256</p>
                        <p class="font-mono text-xs text-gray-600 break-all">{{ $passeport->hash_integrite }}</p>
                    </div>
                </div>
            </div>

            {{-- Impact écologique --}}
            <div class="mt-8 pt-6 border-t border-gray-100">
                <h3 class="font-semibold text-gray-900 text-sm uppercase tracking-wider mb-4">
                    <i class="fas fa-leaf text-green-500 mr-2"></i>Impact écologique certifié
                </h3>
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-green-50 rounded-2xl p-5 text-center border border-green-100">
                        <i class="fas fa-smog text-green-500 text-2xl mb-2 block"></i>
                        <p class="text-2xl font-bold text-green-700">{{ $passeport->co2_evite_kg }}</p>
                        <p class="text-green-600 text-xs mt-1 font-medium">kg CO₂ évités</p>
                    </div>
                    <div class="bg-blue-50 rounded-2xl p-5 text-center border border-blue-100">
                        <i class="fas fa-tint text-blue-500 text-2xl mb-2 block"></i>
                        <p class="text-2xl font-bold text-blue-700">{{ number_format($passeport->eau_economisee_l, 0, ',', ' ') }}</p>
                        <p class="text-blue-600 text-xs mt-1 font-medium">litres eau économisés</p>
                    </div>
                    <div class="bg-purple-50 rounded-2xl p-5 text-center border border-purple-100">
                        <i class="fas fa-recycle text-purple-500 text-2xl mb-2 block"></i>
                        <p class="text-2xl font-bold text-purple-700">{{ $lot->etapeTraitements->count() }}</p>
                        <p class="text-purple-600 text-xs mt-1 font-medium">étapes certifiées</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pied de page --}}
        <div class="bg-gray-50 border-t border-gray-100 px-8 py-4 flex items-center justify-between">
            <p class="text-xs text-gray-400">
                Émis par RETISS — {{ $passeport->date_emission->format('d/m/Y à H:i') }}
            </p>
            <span class="bg-green-100 text-green-700 text-xs font-bold px-3 py-1 rounded-full">
                <i class="fas fa-check-circle mr-1"></i> Valide
            </span>
        </div>
    </div>
</div>
@endif

@endsection
