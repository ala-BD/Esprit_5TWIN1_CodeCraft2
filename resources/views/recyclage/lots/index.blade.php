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
    <div class="flex items-center gap-3">
        {{-- Export --}}
        <div class="relative" id="export-wrapper">
            <button onclick="toggleExport()"
                    class="inline-flex items-center gap-2 border-2 border-gray-200 text-gray-600 font-semibold
                           px-4 py-2.5 rounded-xl hover:border-primary-DEFAULT hover:text-primary-DEFAULT transition-all">
                <i class="fas fa-download text-sm"></i>
                Exporter
                <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div id="export-menu"
                 class="hidden absolute right-0 mt-2 w-52 bg-white border border-gray-200 rounded-xl shadow-lg py-1.5 z-20">
                <a href="{{ route('recyclage.lots.export', array_merge(request()->except('page'), ['format' => 'csv'])) }}"
                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-file-csv text-green-500 w-4"></i>
                    Exporter en CSV
                </a>
                <a href="{{ route('recyclage.lots.export', array_merge(request()->except('page'), ['format' => 'excel'])) }}"
                   class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-file-excel text-green-600 w-4"></i>
                    Ouvrir dans Excel
                </a>
            </div>
        </div>

        <a href="{{ route('recyclage.lots.create') }}"
           class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-dark to-primary-DEFAULT
                  text-white font-semibold px-5 py-2.5 rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
            <i class="fas fa-plus"></i>
            Enregistrer un lot
        </a>
    </div>
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

{{-- ===== PANNEAU FILTRES AVANCÉS ===== --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6">

    {{-- Toggle filtres --}}
    <div class="px-6 py-4 flex items-center justify-between cursor-pointer select-none"
         id="filtres-toggle" onclick="toggleFiltres()">
        <div class="flex items-center gap-2">
            <i class="fas fa-filter text-primary-DEFAULT text-sm"></i>
            <span class="font-semibold text-gray-800 text-sm">Filtres & Recherche</span>
            @if($filtresActifs > 0)
                <span class="bg-primary-DEFAULT text-white text-xs font-bold px-2 py-0.5 rounded-full">
                    {{ $filtresActifs }} actif{{ $filtresActifs > 1 ? 's' : '' }}
                </span>
            @endif
        </div>
        <div class="flex items-center gap-3">
            @if($filtresActifs > 0)
                <a href="{{ route('recyclage.lots.index') }}"
                   class="text-xs text-red-500 hover:text-red-700 font-medium flex items-center gap-1"
                   onclick="event.stopPropagation()">
                    <i class="fas fa-times"></i> Réinitialiser
                </a>
            @endif
            <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform duration-200" id="filtres-icon"></i>
        </div>
    </div>

    {{-- Corps filtres --}}
    <div id="filtres-body" class="{{ $filtresActifs > 0 ? '' : 'hidden' }} border-t border-gray-100 px-6 py-5">
        <form method="GET" action="{{ route('recyclage.lots.index') }}" id="filtres-form">

            {{-- Ligne 1 : recherche + statut + filière --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">

                {{-- Recherche texte --}}
                <div class="sm:col-span-1">
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">
                        Recherche
                    </label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" name="q" value="{{ request('q') }}"
                               placeholder="Référence, composition, origine..."
                               class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT/30 focus:border-primary-DEFAULT transition-all">
                    </div>
                </div>

                {{-- Filtre Statut --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">
                        Statut
                    </label>
                    <select name="statut"
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT/30 focus:border-primary-DEFAULT transition-all appearance-none bg-white">
                        <option value="">Tous les statuts</option>
                        <option value="EN_ATTENTE"    {{ request('statut') === 'EN_ATTENTE'    ? 'selected' : '' }}>En attente</option>
                        <option value="EN_TRAITEMENT" {{ request('statut') === 'EN_TRAITEMENT' ? 'selected' : '' }}>En traitement</option>
                        <option value="TRAITE"        {{ request('statut') === 'TRAITE'        ? 'selected' : '' }}>Traité</option>
                        <option value="CERTIFIE"      {{ request('statut') === 'CERTIFIE'      ? 'selected' : '' }}>Certifié</option>
                    </select>
                </div>

                {{-- Filtre Filière --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">
                        Filière IA
                    </label>
                    <select name="filiere"
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT/30 focus:border-primary-DEFAULT transition-all appearance-none bg-white">
                        <option value="">Toutes les filières</option>
                        <option value="REVENTE"           {{ request('filiere') === 'REVENTE'           ? 'selected' : '' }}>Revente</option>
                        <option value="UPCYCLING"         {{ request('filiere') === 'UPCYCLING'         ? 'selected' : '' }}>Upcycling</option>
                        <option value="RECYCLAGE_FIBRE"   {{ request('filiere') === 'RECYCLAGE_FIBRE'   ? 'selected' : '' }}>Recyclage fibre</option>
                        <option value="RECYCLAGE_ENERGIE" {{ request('filiere') === 'RECYCLAGE_ENERGIE' ? 'selected' : '' }}>Recyclage énergie</option>
                    </select>
                </div>
            </div>

            {{-- Ligne 2 : période + poids + tri --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">

                {{-- Date début --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">
                        Créé après
                    </label>
                    <input type="date" name="date_debut" value="{{ request('date_debut') }}"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT/30 focus:border-primary-DEFAULT transition-all">
                </div>

                {{-- Date fin --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">
                        Créé avant
                    </label>
                    <input type="date" name="date_fin" value="{{ request('date_fin') }}"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT/30 focus:border-primary-DEFAULT transition-all">
                </div>

                {{-- Poids min --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">
                        Poids min (kg)
                    </label>
                    <input type="number" name="poids_min" value="{{ request('poids_min') }}"
                           min="0" step="0.1" placeholder="0"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT/30 focus:border-primary-DEFAULT transition-all">
                </div>

                {{-- Poids max --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">
                        Poids max (kg)
                    </label>
                    <input type="number" name="poids_max" value="{{ request('poids_max') }}"
                           min="0" step="0.1" placeholder="∞"
                           class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT/30 focus:border-primary-DEFAULT transition-all">
                </div>
            </div>

            {{-- Ligne 3 : tri + boutons --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-end gap-4">

                {{-- Tri --}}
                <div class="flex gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">
                            Trier par
                        </label>
                        <select name="sort"
                                class="px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT/30 appearance-none bg-white">
                            <option value="created_at" {{ request('sort') === 'created_at' ? 'selected' : '' }}>Date création</option>
                            <option value="reference"  {{ request('sort') === 'reference'  ? 'selected' : '' }}>Référence</option>
                            <option value="poids_kg"   {{ request('sort') === 'poids_kg'   ? 'selected' : '' }}>Poids</option>
                            <option value="statut"     {{ request('sort') === 'statut'     ? 'selected' : '' }}>Statut</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1.5 uppercase tracking-wider">
                            Ordre
                        </label>
                        <select name="direction"
                                class="px-3 py-2.5 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-DEFAULT/30 appearance-none bg-white">
                            <option value="desc" {{ request('direction') === 'desc' ? 'selected' : '' }}>Décroissant</option>
                            <option value="asc"  {{ request('direction') === 'asc'  ? 'selected' : '' }}>Croissant</option>
                        </select>
                    </div>
                </div>

                {{-- Boutons --}}
                <div class="flex gap-3 sm:ml-auto">
                    <a href="{{ route('recyclage.lots.index') }}"
                       class="px-4 py-2.5 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                        <i class="fas fa-undo text-xs mr-1.5"></i> Réinitialiser
                    </a>
                    <button type="submit"
                            class="px-5 py-2.5 text-sm font-semibold text-white bg-primary-DEFAULT hover:bg-primary-dark rounded-xl transition-colors flex items-center gap-2">
                        <i class="fas fa-search text-xs"></i> Appliquer
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

{{-- Tableau des lots --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <h2 class="font-semibold text-gray-900">Liste des lots</h2>
            @if($filtresActifs > 0)
                <span class="text-xs text-primary-DEFAULT bg-primary-DEFAULT/10 px-2.5 py-1 rounded-lg font-medium">
                    {{ $lots->total() }} résultat(s) filtrés
                </span>
            @else
                <span class="text-sm text-gray-400">{{ $lots->total() }} lot(s)</span>
            @endif
        </div>

        {{-- Tri rapide en-têtes cliquables --}}
        @php
            $sortField = request('sort', 'created_at');
            $sortDir   = request('direction', 'desc');
            $sortUrl   = fn($col) => route('recyclage.lots.index', array_merge(request()->except(['sort','direction','page']), [
                'sort'      => $col,
                'direction' => ($sortField === $col && $sortDir === 'asc') ? 'desc' : 'asc',
            ]));
        @endphp
    </div>

    @if($lots->isEmpty())
        <div class="text-center py-16">
            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-search text-gray-400 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-700 mb-2">
                {{ $filtresActifs > 0 ? 'Aucun lot ne correspond aux filtres' : 'Aucun lot enregistré' }}
            </h3>
            <p class="text-gray-400 text-sm mb-6">
                {{ $filtresActifs > 0 ? 'Modifiez ou réinitialisez les filtres.' : 'Commencez par enregistrer votre premier lot textile.' }}
            </p>
            @if($filtresActifs > 0)
                <a href="{{ route('recyclage.lots.index') }}"
                   class="inline-flex items-center gap-2 bg-gray-100 text-gray-700 font-semibold px-5 py-2.5 rounded-xl hover:bg-gray-200 transition-colors">
                    <i class="fas fa-undo"></i> Réinitialiser les filtres
                </a>
            @else
                <a href="{{ route('recyclage.lots.create') }}"
                   class="inline-flex items-center gap-2 bg-primary-DEFAULT text-white font-semibold px-5 py-2.5 rounded-xl hover:bg-primary-dark transition-colors">
                    <i class="fas fa-plus"></i> Enregistrer un lot
                </a>
            @endif
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        {{-- En-tête cliquable pour tri --}}
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <a href="{{ $sortUrl('reference') }}" class="flex items-center gap-1 hover:text-primary-DEFAULT transition-colors">
                                Référence
                                @if($sortField === 'reference')
                                    <i class="fas fa-sort-{{ $sortDir === 'asc' ? 'up' : 'down' }} text-primary-DEFAULT"></i>
                                @else
                                    <i class="fas fa-sort text-gray-300"></i>
                                @endif
                            </a>
                        </th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <a href="{{ $sortUrl('poids_kg') }}" class="flex items-center gap-1 hover:text-primary-DEFAULT transition-colors">
                                Poids
                                @if($sortField === 'poids_kg')
                                    <i class="fas fa-sort-{{ $sortDir === 'asc' ? 'up' : 'down' }} text-primary-DEFAULT"></i>
                                @else
                                    <i class="fas fa-sort text-gray-300"></i>
                                @endif
                            </a>
                        </th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Composition</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Filière IA</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <a href="{{ $sortUrl('statut') }}" class="flex items-center gap-1 hover:text-primary-DEFAULT transition-colors">
                                Statut
                                @if($sortField === 'statut')
                                    <i class="fas fa-sort-{{ $sortDir === 'asc' ? 'up' : 'down' }} text-primary-DEFAULT"></i>
                                @else
                                    <i class="fas fa-sort text-gray-300"></i>
                                @endif
                            </a>
                        </th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Progression</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <a href="{{ $sortUrl('created_at') }}" class="flex items-center gap-1 hover:text-primary-DEFAULT transition-colors">
                                Date
                                @if($sortField === 'created_at')
                                    <i class="fas fa-sort-{{ $sortDir === 'asc' ? 'up' : 'down' }} text-primary-DEFAULT"></i>
                                @else
                                    <i class="fas fa-sort text-gray-300"></i>
                                @endif
                            </a>
                        </th>
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
                                <p class="font-semibold text-gray-900 text-sm">{{ $lot->reference }}</p>
                            </div>
                        </td>

                        {{-- Poids --}}
                        <td class="px-6 py-4">
                            <span class="font-semibold text-gray-900">{{ $lot->poids_kg }}</span>
                            <span class="text-gray-400 text-xs ml-1">kg</span>
                        </td>

                        {{-- Composition --}}
                        <td class="px-6 py-4">
                            <span class="text-gray-600 text-sm truncate max-w-[150px] block" title="{{ $lot->composition }}">
                                {{ $lot->composition }}
                            </span>
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
                                $barColor  = $prog === 0 ? 'bg-gray-300' : ($prog < 50 ? 'bg-yellow-400' : ($prog < 100 ? 'bg-blue-400' : 'bg-green-500'));
                                $textColor = $prog === 0 ? 'text-gray-400' : ($prog < 50 ? 'text-yellow-600' : ($prog < 100 ? 'text-blue-600' : 'text-green-600'));
                            @endphp
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-100 rounded-full h-2 w-20 overflow-hidden">
                                    <div class="{{ $barColor }} h-2 rounded-full transition-all duration-500"
                                         style="width: {{ $prog }}%"></div>
                                </div>
                                <span class="text-xs font-semibold {{ $textColor }}">{{ $prog }}%</span>
                            </div>
                        </td>

                        {{-- Date --}}
                        <td class="px-6 py-4">
                            <span class="text-gray-500 text-xs">{{ $lot->created_at->format('d/m/Y') }}</span>
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
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
            <p class="text-sm text-gray-500">
                Affichage de <span class="font-semibold text-gray-800">{{ $lots->firstItem() }}</span>
                à <span class="font-semibold text-gray-800">{{ $lots->lastItem() }}</span>
                sur <span class="font-semibold text-gray-800">{{ $lots->total() }}</span> lots
            </p>
            {{ $lots->links() }}
        </div>
        @endif
    @endif
</div>

@push('scripts')
<script>
function toggleFiltres() {
    const body = document.getElementById('filtres-body');
    const icon = document.getElementById('filtres-icon');
    body.classList.toggle('hidden');
    icon.classList.toggle('rotate-180');
}

function toggleExport() {
    document.getElementById('export-menu').classList.toggle('hidden');
}

// Fermer menus si clic extérieur
document.addEventListener('click', function(e) {
    const ew = document.getElementById('export-wrapper');
    if (ew && !ew.contains(e.target)) {
        document.getElementById('export-menu').classList.add('hidden');
    }
});

// Auto-submit sur changement de select
document.querySelectorAll('#filtres-form select').forEach(sel => {
    sel.addEventListener('change', () => document.getElementById('filtres-form').submit());
});
</script>
@endpush

@endsection
