@extends('upcycling.layouts.upcycling')

@php $estAtelier = Auth::user()->role === 'ATELIER'; @endphp

@section('title', $estAtelier ? 'Projets reçus' : 'Mes projets')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Projets</span>
@endsection

@section('upcycling-content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="font-display text-2xl font-bold text-gray-900">{{ $estAtelier ? 'Projets reçus' : 'Mes projets d\'upcycling' }}</h1>
        <p class="text-gray-500 text-sm mt-1">
            {{ $estAtelier ? 'Chiffrez les demandes et faites avancer vos projets' : 'Suivez vos transformations de la demande à la livraison' }}
        </p>
    </div>
    @unless($estAtelier)
        <a href="{{ route('upcycling.projets.create') }}"
           class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-dark to-primary text-white font-semibold px-5 py-2.5 rounded-xl hover:shadow-lg hover:scale-[1.02] transition-all duration-200">
            <i class="fas fa-plus"></i> Nouvelle demande
        </a>
    @endunless
</div>

{{-- Filtres --}}
<form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-col sm:flex-row gap-3">
    <div class="relative flex-1">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-search text-sm"></i></span>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher un vêtement ou un produit…"
               class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary text-sm">
    </div>
    <select name="statut" class="px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary text-sm text-gray-700">
        <option value="">Tous les statuts</option>
        @foreach(\App\Models\ProjetUpcycling::ETAPES + ['ANNULE' => ['label' => 'Annulé']] as $code => $etape)
            <option value="{{ $code }}" @selected(request('statut') === $code)>{{ $etape['label'] }}</option>
        @endforeach
    </select>
    <button class="px-5 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary-dark transition-colors">
        <i class="fas fa-filter mr-1"></i> Filtrer
    </button>
    @if(request()->hasAny(['q', 'statut']))
        <a href="{{ route('upcycling.projets.index') }}" class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-500 text-sm text-center hover:bg-gray-50">Réinitialiser</a>
    @endif
</form>

{{-- Tableau --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-900">Liste des projets</h2>
        <span class="text-sm text-gray-500">{{ $projets->total() }} projet(s)</span>
    </div>

    @if($projets->isEmpty())
        <div class="text-center py-16">
            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-cut text-gray-400 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-700 mb-2">Aucun projet</h3>
            <p class="text-gray-400 text-sm mb-6">
                {{ $estAtelier ? 'Les demandes des clients qui vous choisissent apparaîtront ici.' : 'Donnez une seconde vie à un vêtement : décrivez-le et laissez l\'IA vous inspirer.' }}
            </p>
            @unless($estAtelier)
                <a href="{{ route('upcycling.projets.create') }}"
                   class="inline-flex items-center gap-2 bg-primary text-white font-semibold px-5 py-2.5 rounded-xl hover:bg-primary-dark transition-colors">
                    <i class="fas fa-plus"></i> Nouvelle demande
                </a>
            @endunless
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Projet</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $estAtelier ? 'Client' : 'Atelier' }}</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Progression</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($projets as $projet)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <i class="fas {{ \App\Models\Atelier::ICONS[$projet->categorie_produit] ?? 'fa-tshirt' }} text-primary text-sm"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900 text-sm">{{ $projet->produit_final ?? 'Idée à choisir' }}</p>
                                    <p class="text-gray-400 text-xs">{{ ucfirst($projet->type_vetement) }} en {{ $projet->matiere }} · {{ $projet->created_at->format('d/m/Y') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ $estAtelier ? $projet->client->full_name : ($projet->atelier->nom ?? '—') }}
                        </td>
                        <td class="px-6 py-4">
                            @php $badge = $projet->statut_badge; @endphp
                            <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <div class="bg-gray-200 rounded-full h-1.5 w-20">
                                    <div class="bg-primary h-1.5 rounded-full" style="width: {{ $projet->progression }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500">{{ $projet->progression }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('upcycling.projets.show', $projet) }}" title="Voir"
                                   class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-primary hover:text-white flex items-center justify-center text-gray-600 transition-all">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                                @if(!$estAtelier && $projet->client_id === Auth::id() && $projet->estModifiable())
                                    <a href="{{ route('upcycling.projets.edit', $projet) }}" title="Modifier"
                                       class="w-8 h-8 rounded-lg bg-amber-100 hover:bg-amber-500 hover:text-white flex items-center justify-center text-amber-600 transition-all">
                                        <i class="fas fa-pen text-xs"></i>
                                    </a>
                                @endif
                                @if($projet->client_id === Auth::id() && in_array($projet->statut, ['DEMANDE', 'ANNULE']))
                                    <form method="POST" action="{{ route('upcycling.projets.destroy', $projet) }}"
                                          onsubmit="return confirm('Supprimer cette demande ?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Supprimer"
                                                class="w-8 h-8 rounded-lg bg-red-100 hover:bg-red-500 hover:text-white flex items-center justify-center text-red-600 transition-all">
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

        @if($projets->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">{{ $projets->links() }}</div>
        @endif
    @endif
</div>

@endsection
