@extends('admin.layouts.admin')

@section('title', 'Tournée du ' . $tournee->date->format('d/m/Y'))

@section('breadcrumb')
    <span class="hidden sm:inline text-slate-300">/</span>
    <a href="{{ route('admin.logistique.index') }}" class="hover:text-slate-900">Logistique</a>
    <span class="text-slate-300">/</span>
    <span class="text-slate-900 font-medium truncate num">{{ $tournee->zone }}, {{ $tournee->date->format('d/m/Y') }}</span>
@endsection

@section('admin-content')

@php
    $missions = $tournee->missions;
    $faites   = $missions->whereIn('statut', [\App\Models\Mission::STATUT_TERMINEE, \App\Models\Mission::STATUT_ECHOUEE])->count();
@endphp

{{-- Header --}}
<div class="mb-4 flex flex-wrap items-start justify-between gap-3">
  <div>
    <h1 class="text-xl font-semibold text-slate-900 tracking-tight">{{ $tournee->zone }}</h1>
    <div class="flex flex-wrap items-center gap-2 mt-1">
        @include('logistique.partials.statut-badge', ['badge' => $tournee->statut_badge])
        <span class="text-slate-500 num">{{ $tournee->date->format('d/m/Y') }} · {{ $tournee->user?->full_name }} · {{ $tournee->vehicule }}</span>
    </div>
  </div>
  <a href="{{ route('admin.logistique.feuille-de-route', $tournee) }}" class="a-btn">
      <i class="fas fa-print"></i> Feuille de route
  </a>
</div>

<div class="a-card mb-4">
    <dl class="grid grid-cols-2 xl:grid-cols-4 gap-x-8 gap-y-4 p-5">
        <div>
            <dt class="text-slate-500">Collecteur</dt>
            <dd class="font-medium text-slate-900 mt-0.5">{{ $tournee->user?->full_name }}</dd>
        </div>
        <div>
            <dt class="text-slate-500">Arrêts traités</dt>
            <dd class="num font-medium text-slate-900 mt-0.5">{{ $faites }} sur {{ $missions->count() }}</dd>
        </div>
        <div>
            <dt class="text-slate-500">Distance</dt>
            <dd class="num font-medium text-slate-900 mt-0.5">{{ $tournee->distance_km !== null ? $tournee->distance_km . ' km' : '—' }}</dd>
        </div>
        <div>
            <dt class="text-slate-500">Arrêts localisés</dt>
            <dd class="num font-medium text-slate-900 mt-0.5">{{ $missions->filter->estLocalisee()->count() }} sur {{ $missions->count() }}</dd>
        </div>
    </dl>
</div>

<div class="a-card">
    <h2 class="font-semibold text-slate-900 px-4 py-3 border-b border-slate-200">Arrêts</h2>

    @if($missions->isEmpty())
        <p class="text-slate-500 text-center py-10">Aucun arrêt dans cette tournée.</p>
    @else
        <div class="overflow-x-auto rounded-b-lg">
            <table class="a-table">
                <thead>
                    <tr>
                        <th class="w-px">Ordre</th>
                        <th>Heure</th>
                        <th>Type</th>
                        <th>Adresse</th>
                        <th>Lié à</th>
                        <th>Fin</th>
                        <th>Preuve</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($missions as $mission)
                        <tr>
                            <td class="num text-slate-500">{{ $mission->ordre }}</td>
                            <td class="num font-medium text-slate-900">{{ $mission->heure }}</td>
                            <td class="text-slate-700">{{ $mission->type_label }}</td>
                            <td class="text-slate-700" style="white-space: normal; min-width: 220px;">
                                {{ $mission->adresse }}
                                @unless($mission->estLocalisee())
                                    <span class="block text-xs text-amber-700 mt-0.5"><i class="fas fa-triangle-exclamation"></i> Introuvable sur la carte</span>
                                @endunless
                            </td>
                            <td class="num text-slate-600">
                                @if($mission->commande)
                                    Commande {{ $mission->commande->numero }}
                                @elseif($mission->donVetement)
                                    Don n° {{ $mission->donVetement->id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="num text-slate-600">{{ $mission->terminee_le?->format('H:i') ?? '—' }}</td>
                            <td class="text-slate-600" style="white-space: normal;">
                                {{ $mission->preuve_livraison ?: '—' }}
                            @if($mission->photo_preuve)
                                <a href="{{ asset('storage/' . $mission->photo_preuve) }}" target="_blank" rel="noopener" class="block text-xs text-primary-dark mt-0.5"><i class="fas fa-camera"></i> Photo</a>
                            @endif
                            </td>
                            <td>@include('logistique.partials.statut-badge', ['badge' => $mission->statut_badge])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
