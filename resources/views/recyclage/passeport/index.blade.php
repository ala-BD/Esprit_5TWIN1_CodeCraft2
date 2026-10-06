@extends('recyclage.layouts.recyclage')

@section('title', 'Passeports émis')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Passeports émis</span>
@endsection

@section('recyclage-content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div>
        <h1 class="font-display text-2xl font-bold text-gray-900">Passeports numériques émis</h1>
        <p class="text-gray-500 text-sm mt-1">Tous les certificats de traçabilité générés pour vos lots</p>
    </div>
    <div class="flex items-center gap-2 bg-purple-50 border border-purple-100 rounded-xl px-4 py-2">
        <i class="fas fa-certificate text-purple-500"></i>
        <span class="text-purple-700 font-semibold text-sm">{{ $passeports->total() }} passeport(s)</span>
    </div>
</div>

{{-- Stats rapides --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-5 text-white">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                <i class="fas fa-smog text-white"></i>
            </div>
            <p class="text-white/80 text-sm font-medium">CO₂ total évité</p>
        </div>
        <p class="text-3xl font-bold">{{ number_format($totalCo2, 1) }}</p>
        <p class="text-white/70 text-xs mt-1">kg de CO₂</p>
    </div>

    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-5 text-white">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                <i class="fas fa-tint text-white"></i>
            </div>
            <p class="text-white/80 text-sm font-medium">Eau totale économisée</p>
        </div>
        <p class="text-3xl font-bold">{{ number_format($totalEau / 1000, 1) }}</p>
        <p class="text-white/70 text-xs mt-1">milliers de litres</p>
    </div>

    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-5 text-white">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                <i class="fas fa-certificate text-white"></i>
            </div>
            <p class="text-white/80 text-sm font-medium">Lots certifiés</p>
        </div>
        <p class="text-3xl font-bold">{{ $passeports->total() }}</p>
        <p class="text-white/70 text-xs mt-1">passeports valides</p>
    </div>
</div>

{{-- Liste des passeports --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-900">Liste des passeports</h2>
    </div>

    @if($passeports->isEmpty())
        <div class="text-center py-16">
            <div class="w-20 h-20 rounded-full bg-purple-50 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-qrcode text-purple-300 text-3xl"></i>
            </div>
            <h3 class="font-semibold text-gray-700 mb-2">Aucun passeport émis</h3>
            <p class="text-gray-400 text-sm mb-6 max-w-sm mx-auto">
                Les passeports numériques sont générés automatiquement après la certification d'un lot.
            </p>
            <a href="{{ route('recyclage.lots.index') }}"
               class="inline-flex items-center gap-2 bg-primary-DEFAULT text-white font-semibold px-5 py-2.5 rounded-xl hover:bg-primary-dark transition-colors">
                <i class="fas fa-boxes"></i> Voir mes lots
            </a>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">QR Code</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Lot</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">CO₂ évité</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Eau économisée</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date émission</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($passeports as $passeport)
                    @php $lot = $passeport->lotTextile; @endphp
                    <tr class="hover:bg-gray-50 transition-colors">

                        {{-- QR Code --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-qrcode text-purple-500 text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-mono text-xs font-bold text-gray-900">{{ $passeport->qr_code }}</p>
                                    <span class="inline-flex items-center gap-1 text-xs text-green-600 font-medium mt-0.5">
                                        <i class="fas fa-check-circle text-xs"></i> Valide
                                    </span>
                                </div>
                            </div>
                        </td>

                        {{-- Lot --}}
                        <td class="px-6 py-4">
                            <p class="font-semibold text-gray-900 text-sm font-mono">{{ $lot->reference }}</p>
                            <p class="text-gray-400 text-xs mt-0.5">{{ $lot->poids_kg }} kg — {{ Str::limit($lot->composition, 25) }}</p>
                        </td>

                        {{-- CO2 --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-smog text-green-500 text-xs"></i>
                                <span class="font-semibold text-green-700">{{ $passeport->co2_evite_kg }} kg</span>
                            </div>
                        </td>

                        {{-- Eau --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-tint text-blue-500 text-xs"></i>
                                <span class="font-semibold text-blue-700">{{ number_format($passeport->eau_economisee_l, 0, ',', ' ') }} L</span>
                            </div>
                        </td>

                        {{-- Date --}}
                        <td class="px-6 py-4">
                            <p class="text-gray-700 text-sm">{{ $passeport->date_emission->format('d/m/Y') }}</p>
                            <p class="text-gray-400 text-xs">{{ $passeport->date_emission->format('H:i') }}</p>
                        </td>

                        {{-- Actions --}}
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Voir passeport --}}
                                <a href="{{ route('recyclage.passeport.show', $lot) }}"
                                   class="w-8 h-8 rounded-lg bg-purple-50 hover:bg-purple-500 hover:text-white flex items-center justify-center text-purple-600 transition-all duration-200"
                                   title="Voir le passeport">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                {{-- Télécharger PDF --}}
                                <a href="{{ route('recyclage.passeport.pdf', $lot) }}"
                                   class="w-8 h-8 rounded-lg bg-blue-50 hover:bg-blue-500 hover:text-white flex items-center justify-center text-blue-600 transition-all duration-200"
                                   title="Télécharger PDF">
                                    <i class="fas fa-file-pdf text-xs"></i>
                                </a>
                                {{-- Vérification publique --}}
                                <a href="{{ route('passeport.verifier', $passeport->qr_code) }}"
                                   target="_blank"
                                   class="w-8 h-8 rounded-lg bg-green-50 hover:bg-green-500 hover:text-white flex items-center justify-center text-green-600 transition-all duration-200"
                                   title="Vérifier en ligne">
                                    <i class="fas fa-external-link-alt text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($passeports->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $passeports->links() }}
        </div>
        @endif
    @endif
</div>

@endsection
