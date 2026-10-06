@extends('recyclage.layouts.recyclage')

@section('title', 'Mes lots textiles')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Lots textiles</span>
@endsection

@section('recyclage-content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div>
        <h1 class="font-display text-2xl font-bold text-gray-900">Mes lots textiles</h1>
        <p class="text-gray-500 text-sm mt-1">Gérez et suivez tous vos lots de recyclage</p>
    </div>
    <a href="{{ route('recyclage.lots.create') }}"
       class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-dark to-primary-DEFAULT
              text-white font-semibold px-5 py-2.5 rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
        <i class="fas fa-plus"></i>
        Enregistrer un lot
    </a>
</div>

{{-- Stats cards --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center mb-3">
            <i class="fas fa-boxes text-gray-600"></i>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
        <p class="text-gray-500 text-xs mt-0.5">Total lots</p>
    </div>
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <div class="w-10 h-10 rounded-xl bg-yellow-50 flex items-center justify-center mb-3">
            <i class="fas fa-clock text-yellow-500"></i>
        </div>
        <p class="text-2xl font-bold text-yellow-600">{{ $stats['en_attente'] }}</p>
        <p class="text-gray-500 text-xs mt-0.5">En attente</p>
    </div>
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center mb-3">
            <i class="fas fa-cogs text-blue-500"></i>
        </div>
        <p class="text-2xl font-bold text-blue-600">{{ $stats['en_traitement'] }}</p>
        <p class="text-gray-500 text-xs mt-0.5">En traitement</p>
    </div>
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center mb-3">
            <i class="fas fa-certificate text-purple-500"></i>
        </div>
        <p class="text-2xl font-bold text-purple-600">{{ $stats['certifies'] }}</p>
        <p class="text-gray-500 text-xs mt-0.5">Certifiés</p>
    </div>
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 col-span-2 lg:col-span-1">
        <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center mb-3">
            <i class="fas fa-weight text-green-500"></i>
        </div>
        <p class="text-2xl font-bold text-green-600">{{ number_format($stats['total_kg'], 1) }}</p>
        <p class="text-gray-500 text-xs mt-0.5">kg traités</p>
    </div>
</div>

{{-- Tableau des lots --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-900">Liste des lots</h2>
        <span class="text-sm text-gray-500">{{ $lots->total() }} lot(s)</span>
    </div>

    @if($lots->isEmpty())
        <div class="text-center py-16">
            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-box-open text-gray-400 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-700 mb-2">Aucun lot enregistré</h3>
            <p class="text-gray-400 text-sm mb-6">Commencez par enregistrer votre premier lot textile.</p>
            <a href="{{ route('recyclage.lots.create') }}"
               class="inline-flex items-center gap-2 bg-primary-DEFAULT text-white font-semibold px-5 py-2.5 rounded-xl hover:bg-primary-dark transition-colors">
                <i class="fas fa-plus"></i> Enregistrer un lot
            </a>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Référence</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Poids</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Composition</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Filière IA</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Progression</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($lots as $lot)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">

                        {{-- Référence --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-primary-DEFAULT/10 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-recycle text-primary-DEFAULT text-xs"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-sm">{{ $lot->reference }}</p>
                                    <p class="text-gray-400 text-xs">{{ $lot->created_at->format('d/m/Y') }}</p>
                                </div>
                            </div>
                        </td>

                        {{-- Poids --}}
                        <td class="px-6 py-4">
                            <span class="font-semibold text-gray-900">{{ $lot->poids_kg }}</span>
                            <span class="text-gray-400 text-xs ml-1">kg</span>
                        </td>

                        {{-- Composition --}}
                        <td class="px-6 py-4">
                            <span class="text-gray-600 text-sm truncate max-w-[150px] block">{{ $lot->composition }}</span>
                        </td>

                        {{-- Filière IA --}}
                        <td class="px-6 py-4">
                            @php $filiere = $lot->filiere_ia_badge; @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold {{ $filiere['class'] }}">
                                <i class="fas {{ $filiere['icon'] }} text-xs"></i>
                                {{ $filiere['label'] }}
                            </span>
                        </td>

                        {{-- Statut --}}
                        <td class="px-6 py-4">
                            @php $badge = $lot->statut_badge; @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ $badge['class'] }}">
                                {{ $badge['label'] }}
                            </span>
                        </td>

                        {{-- Progression --}}
                        <td class="px-6 py-4">
                            @php
                                $prog = $lot->progression;
                                if ($prog === 0) {
                                    $barColor  = 'bg-gray-300';
                                    $textColor = 'text-gray-400';
                                } elseif ($prog < 50) {
                                    $barColor  = 'bg-yellow-400';
                                    $textColor = 'text-yellow-600';
                                } elseif ($prog < 100) {
                                    $barColor  = 'bg-blue-400';
                                    $textColor = 'text-blue-600';
                                } else {
                                    $barColor  = 'bg-green-500';
                                    $textColor = 'text-green-600';
                                }
                            @endphp
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-100 rounded-full h-2 w-24 overflow-hidden">
                                    <div class="{{ $barColor }} h-2 rounded-full transition-all duration-500"
                                         style="width: {{ $prog }}%"></div>
                                </div>
                                <span class="text-xs font-semibold {{ $textColor }}">{{ $prog }}%</span>
                            </div>
                        </td>

                        {{-- Actions --}}
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('recyclage.lots.show', $lot) }}"
                                   class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-primary-DEFAULT hover:text-white flex items-center justify-center text-gray-600 transition-all duration-200"
                                   title="Voir">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                @if($lot->statut !== 'CERTIFIE')
                                <a href="{{ route('recyclage.lots.edit', $lot) }}"
                                   class="w-8 h-8 rounded-lg bg-amber-50 hover:bg-amber-500 hover:text-white flex items-center justify-center text-amber-600 transition-all duration-200"
                                   title="Modifier">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>
                                @endif
                                @if($lot->statut === 'TRAITE' && !$lot->passeportNumerique)
                                <a href="{{ route('recyclage.passeport.show', $lot) }}"
                                   class="w-8 h-8 rounded-lg bg-purple-100 hover:bg-purple-500 hover:text-white flex items-center justify-center text-purple-600 transition-all duration-200"
                                   title="Générer passeport">
                                    <i class="fas fa-qrcode text-xs"></i>
                                </a>
                                @endif
                                @if($lot->passeportNumerique)
                                <a href="{{ route('recyclage.passeport.pdf', $lot) }}"
                                   class="w-8 h-8 rounded-lg bg-blue-100 hover:bg-blue-500 hover:text-white flex items-center justify-center text-blue-600 transition-all duration-200"
                                   title="Télécharger PDF">
                                    <i class="fas fa-file-pdf text-xs"></i>
                                </a>
                                @endif
                                @if($lot->statut !== 'CERTIFIE')
                                <form method="POST" action="{{ route('recyclage.lots.destroy', $lot) }}"
                                      onsubmit="return confirm('Supprimer le lot {{ $lot->reference }} ?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="w-8 h-8 rounded-lg bg-red-100 hover:bg-red-500 hover:text-white flex items-center justify-center text-red-600 transition-all duration-200"
                                            title="Supprimer">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($lots->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $lots->links() }}
        </div>
        @endif
    @endif
</div>

@endsection
