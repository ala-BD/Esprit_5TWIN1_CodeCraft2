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
    $restants  = $missions->whereIn('statut', [\App\Models\Mission::STATUT_A_FAIRE, \App\Models\Mission::STATUT_EN_COURS])->count();
    $arretsCarte = $missions->filter->estLocalisee()->map(fn ($m) => [
        'lat' => $m->latitude, 'lng' => $m->longitude, 'ordre' => $m->ordre, 'statut' => $m->statut, 'adresse' => $m->adresse, 'heure' => $m->heure,
    ])->values();
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
        @if($restants > 0)
            <a href="{{ route('logistique.tournees.conduite', $tournee) }}" class="a-btn a-btn-primary">
                <i class="fas fa-mobile-screen"></i> Mode tournée
            </a>
        @endif
        @if($restants >= 2)
            <a href="{{ route('logistique.tournees.optimisation', $tournee) }}" class="a-btn">
                <i class="fas fa-wand-magic-sparkles"></i> Optimiser l'itinéraire
            </a>
        @endif
        <a href="{{ route('logistique.tournees.feuille-de-route', $tournee) }}" class="a-btn" title="Feuille de route imprimable (PDF)">
            <i class="fas fa-print"></i> <span class="hidden xl:inline">Feuille de route</span>
        </a>
        <a href="{{ route('logistique.tournees.edit', $tournee) }}" class="a-btn">
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

{{-- Carte des arrêts --}}
@if($arretsCarte->isNotEmpty())
    <div class="a-card overflow-hidden mb-4">
        <div id="carte-tournee" class="cr-carte" style="height: 300px;"></div>
        <div class="flex flex-wrap items-center gap-x-5 gap-y-1 px-4 py-2 border-t border-slate-200 text-xs text-slate-600">
            @foreach(\App\Models\Mission::STATUTS as $code => $statut)
                <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-full" data-couleur-statut="{{ $code }}"></span> {{ $statut['label'] }}</span>
            @endforeach
        </div>
    </div>
@endif

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
                        <th>Lié à</th>
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
                        <td class="text-slate-600" style="white-space: normal;">
                            {{ $mission->preuve_livraison ?: '—' }}
                            @if($mission->photo_preuve)
                                <a href="{{ asset('storage/' . $mission->photo_preuve) }}" target="_blank" rel="noopener" class="block text-xs text-primary-dark mt-0.5"><i class="fas fa-camera"></i> Photo</a>
                            @endif
                        </td>
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

@section('styles')
@include('logistique.partials.carte', ['partie' => 'styles'])
@endsection

@section('scripts')
@include('logistique.partials.carte', ['partie' => 'scripts'])
<script>
    (function () {
        const arrets = @json($arretsCarte);
        if (!window.CarteRetiss || !arrets.length) return;

        document.querySelectorAll('[data-couleur-statut]').forEach(e => e.style.background = CarteRetiss.COULEURS[e.dataset.couleurStatut]);

        const carte = CarteRetiss.creer('carte-tournee');
        const depot = @json(['lat' => config('logistique.depot.latitude'), 'lng' => config('logistique.depot.longitude'), 'nom' => config('logistique.depot.nom')]);
        CarteRetiss.depot(carte, depot);
        CarteRetiss.trace(carte, [depot, ...arrets]);
        arrets.forEach(a => CarteRetiss.arret(carte, a));
        carte.fitBounds([[depot.lat, depot.lng], ...arrets.map(a => [a.lat, a.lng])], { padding: [30, 30] });
    })();
</script>
@endsection
