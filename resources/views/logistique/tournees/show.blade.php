@extends('logistique.layouts.logistique')

@section('title', 'Tournée du ' . $tournee->date->format('d/m/Y'))

@section('breadcrumb')
    <span class="hidden sm:inline text-slate-300">/</span>
    <a href="{{ route('logistique.tournees.index') }}" class="hover:text-slate-900">Tournées</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-900 font-medium truncate num">{{ $tournee->date->format('d/m/Y') }}</span>
@endsection

@section('logistique-content')

@php
    $missions  = $tournee->missions;
    $terminees = $missions->where('statut', \App\Models\Mission::STATUT_TERMINEE)->count();
@endphp

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-4">
    <div class="min-w-0 flex-1">
        <h1 class="text-xl font-semibold text-slate-900 tracking-tight truncate">{{ $tournee->zone }}</h1>
        <div class="flex flex-wrap items-center gap-2 mt-1">
            @include('logistique.partials.statut-badge', ['badge' => $tournee->statut_badge])
            <span class="text-slate-500 num">Tournée du {{ $tournee->date->format('d/m/Y') }}</span>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <form method="POST" action="{{ route('logistique.tournees.destroy', $tournee) }}"
              data-confirm-title="Supprimer cette tournée ?"
              data-confirm-message="La tournée du {{ $tournee->date->format('d/m/Y') }} et ses {{ $missions->count() }} mission(s) seront supprimées définitivement."
              data-confirm-label="Supprimer">
            @csrf @method('DELETE')
            <button type="submit" class="a-btn a-btn-danger">
                <i class="fas fa-trash-can"></i> Supprimer
            </button>
        </form>
        <a href="{{ route('logistique.tournees.edit', $tournee) }}" class="a-btn a-btn-primary">
            <i class="fas fa-pen"></i> Modifier
        </a>
    </div>
</div>

{{-- Informations --}}
<div class="a-card mb-4">
    <dl class="grid grid-cols-2 xl:grid-cols-4 gap-x-8 gap-y-4 p-5">
        <div>
            <dt class="text-slate-500">Date</dt>
            <dd class="num font-medium text-slate-900 mt-0.5">{{ $tournee->date->format('d/m/Y') }}</dd>
        </div>
        <div>
            <dt class="text-slate-500">Véhicule</dt>
            <dd class="font-medium text-slate-900 mt-0.5">{{ $tournee->vehicule }}</dd>
        </div>
        <div>
            <dt class="text-slate-500">Distance</dt>
            <dd class="num font-medium text-slate-900 mt-0.5">{{ $tournee->distance_km !== null ? $tournee->distance_km . ' km' : '—' }}</dd>
        </div>
        <div>
            <dt class="text-slate-500">Avancement</dt>
            <dd class="num font-medium text-slate-900 mt-0.5">{{ $terminees }} mission{{ $terminees > 1 ? 's' : '' }} terminée{{ $terminees > 1 ? 's' : '' }} sur {{ $missions->count() }}</dd>
        </div>
    </dl>
</div>

{{-- ===== Missions de la tournée ===== --}}
<div class="a-card">
    <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-200">
        <div>
            <h2 class="font-semibold text-slate-900">Missions</h2>
            <p class="text-slate-500 mt-0.5">Collectes et livraisons, dans l'ordre de passage.</p>
        </div>
        <a href="{{ route('logistique.tournees.missions.create', $tournee) }}" class="a-btn">
            <i class="fas fa-plus"></i> Ajouter une mission
        </a>
    </div>

    @if($missions->isEmpty())
        <div class="text-center py-12">
            <i class="fas fa-location-dot text-slate-300 text-2xl"></i>
            <h3 class="font-medium text-slate-900 mt-3">Aucune mission pour cette tournée</h3>
            <p class="text-slate-500 mt-1">Ajoutez une collecte ou une livraison pour commencer.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-b-lg">
            <table class="a-table">
                <thead>
                    <tr>
                        <th class="w-px">Ordre</th>
                        <th>Heure</th>
                        <th>Type</th>
                        <th>Adresse</th>
                        <th>Don lié</th>
                        <th>Preuve</th>
                        <th>Statut</th>
                        <th class="w-px"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($missions as $mission)
                    <tr>
                        <td class="num text-slate-500">{{ $mission->ordre }}</td>
                        <td class="num font-medium text-slate-900">{{ $mission->heure }}</td>
                        <td class="text-slate-700">
                            <i class="fas {{ $mission->type === \App\Models\Mission::TYPE_COLLECTE ? 'fa-box-open' : 'fa-truck' }} text-slate-400 text-xs mr-1.5"></i>{{ $mission->type_label }}
                        </td>
                        <td class="text-slate-700" style="white-space: normal; min-width: 220px;">{{ $mission->adresse }}</td>
                        <td class="num text-slate-600">{{ $mission->donVetement ? 'Don n° ' . $mission->donVetement->id : '—' }}</td>
                        <td class="text-slate-600">{{ $mission->preuve_livraison ?: '—' }}</td>
                        <td>@include('logistique.partials.statut-badge', ['badge' => $mission->statut_badge])</td>
                        <td>
                            <div class="flex items-center justify-end gap-0.5">
                                <a href="{{ route('logistique.missions.edit', $mission) }}" class="a-icon-btn" title="Modifier" aria-label="Modifier la mission {{ $mission->ordre }}">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('logistique.missions.destroy', $mission) }}"
                                      data-confirm-title="Supprimer cette mission ?"
                                      data-confirm-message="La {{ strtolower($mission->type_label) }} prévue à {{ $mission->heure }} ({{ $mission->adresse }}) sera supprimée définitivement."
                                      data-confirm-label="Supprimer">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="a-icon-btn danger" title="Supprimer" aria-label="Supprimer la mission {{ $mission->ordre }}">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
