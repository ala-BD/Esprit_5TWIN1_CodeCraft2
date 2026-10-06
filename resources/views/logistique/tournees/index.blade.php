@extends('logistique.layouts.logistique')

@section('title', 'Mes tournées')

@section('breadcrumb')
    <span class="hidden sm:inline text-slate-300">/</span>
    <span class="text-slate-900 font-medium">Tournées</span>
@endsection

@section('logistique-content')

{{-- Header --}}
<div class="flex flex-wrap items-center justify-between gap-4 mb-4">
    <div>
        <h1 class="text-xl font-semibold text-slate-900 tracking-tight">Mes tournées</h1>
        <p class="text-slate-500 mt-0.5 num">
            {{ $stats['total'] }} tournée{{ $stats['total'] > 1 ? 's' : '' }},
            dont {{ $stats['planifiees'] }} planifiée{{ $stats['planifiees'] > 1 ? 's' : '' }}
            et {{ $stats['en_cours'] }} en cours.
        </p>
    </div>
    <a href="{{ route('logistique.tournees.create') }}" class="a-btn a-btn-primary">
        <i class="fas fa-plus"></i>
        Nouvelle tournée
    </a>
</div>

{{-- Tableau des tournées --}}
<div class="a-card">

    {{-- Recherche + filtres --}}
    <form method="GET" action="{{ route('logistique.tournees.index') }}"
          class="flex flex-col md:flex-row md:items-center gap-2 p-3 border-b border-slate-200">
        <div class="relative flex-1 md:max-w-xs">
            <i class="fas fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="search" name="q" value="{{ request('q') }}"
                   placeholder="Rechercher par zone ou véhicule"
                   aria-label="Rechercher"
                   class="a-input" style="padding-left: 30px; height: 32px;">
        </div>
        @include('admin.partials.select', [
            'name'     => 'statut',
            'id'       => 'filtre-statut',
            'class'    => 'md:w-44 a-select-sm',
            'submit'   => true,
            'selected' => request('statut'),
            'options'  => ['' => 'Tous les statuts'] + \App\Models\Tournee::statutOptions(),
        ])
        <button type="submit" class="a-btn">Filtrer</button>
        @if(request()->hasAny(['q', 'statut']))
            <a href="{{ route('logistique.tournees.index') }}" class="a-btn" style="border-color: transparent; color: #64748b;">
                <i class="fas fa-xmark"></i> Réinitialiser
            </a>
        @endif
        <span class="md:ml-auto text-slate-500 num">{{ $tournees->total() }} résultat{{ $tournees->total() > 1 ? 's' : '' }}</span>
    </form>

    @if($tournees->isEmpty())
        <div class="text-center py-14">
            <i class="fas fa-route text-slate-300 text-2xl"></i>
            <h3 class="font-medium text-slate-900 mt-3">Aucune tournée trouvée</h3>
            <p class="text-slate-500 mt-1">Planifiez une tournée pour y ajouter vos collectes et livraisons.</p>
        </div>
    @else
        <div class="overflow-x-auto {{ $tournees->hasPages() ? '' : 'rounded-b-lg' }}">
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Zone</th>
                        <th>Véhicule</th>
                        <th>Missions</th>
                        <th>Distance</th>
                        <th>Statut</th>
                        <th class="w-px"><span class="sr-only">Ouvrir</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tournees as $tournee)
                    {{-- Ligne cliquable : ouvre le détail de la tournée --}}
                    <tr class="l-row" onclick="window.location.href = this.querySelector('a').href">
                        <td class="num">
                            <a href="{{ route('logistique.tournees.show', $tournee) }}" class="font-medium text-slate-900">
                                {{ $tournee->date->format('d/m/Y') }}
                            </a>
                        </td>
                        <td class="text-slate-700">{{ $tournee->zone }}</td>
                        <td class="text-slate-600">{{ $tournee->vehicule }}</td>
                        <td class="num text-slate-600">{{ $tournee->missions_count }}</td>
                        <td class="num text-slate-600">{{ $tournee->distance_km !== null ? $tournee->distance_km . ' km' : '—' }}</td>
                        <td>@include('logistique.partials.statut-badge', ['badge' => $tournee->statut_badge])</td>
                        <td class="text-slate-400"><i class="fas fa-chevron-right text-[10px]"></i></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($tournees->hasPages())
        <div class="px-3 py-2.5 border-t border-slate-200">
            {{ $tournees->links('admin.partials.pagination') }}
        </div>
        @endif
    @endif
</div>

@endsection

@section('styles')
<style>
    .l-row { cursor: pointer; }
    .l-row a:focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; border-radius: 3px; }
</style>
@endsection
