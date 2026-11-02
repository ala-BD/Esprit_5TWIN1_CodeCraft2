@extends('admin.layouts.admin')

@section('title', 'Logistique')

@section('breadcrumb')
    <span class="hidden sm:inline text-slate-300">/</span>
    <span class="text-slate-900 font-medium">Logistique</span>
@endsection

@section('admin-content')

@php
    $avancement = $stats['missions_du_jour'] > 0
        ? round(($stats['terminees'] + $stats['echouees']) / $stats['missions_du_jour'] * 100)
        : 0;
    $enAttente = $aPlanifier['commandes']->count() + $aPlanifier['dons']->count();
@endphp

{{-- Header --}}
<div class="flex flex-wrap items-start justify-between gap-3 mb-4">
  <div>
    <h1 class="text-xl font-semibold text-slate-900 tracking-tight">Logistique</h1>
    <p class="text-slate-500 mt-0.5 num">
        Aujourd'hui : {{ $stats['tournees_du_jour'] }} tournée{{ $stats['tournees_du_jour'] > 1 ? 's' : '' }},
        {{ $stats['missions_du_jour'] }} arrêt{{ $stats['missions_du_jour'] > 1 ? 's' : '' }}, {{ $avancement }} % traités
        ({{ $stats['terminees'] }} réussi{{ $stats['terminees'] > 1 ? 's' : '' }}, {{ $stats['echouees'] }} échoué{{ $stats['echouees'] > 1 ? 's' : '' }}).
    </p>
  </div>
  <a href="{{ route('admin.logistique.carte') }}" class="a-btn a-btn-primary">
      <i class="fas fa-map-location-dot"></i> Carte en direct
  </a>
</div>

@if($stats['non_localisees'] > 0)
    <div class="flex items-center gap-2.5 mb-4 px-3 py-2.5 rounded-md border border-amber-200 bg-amber-50 text-amber-900">
        <i class="fas fa-triangle-exclamation text-amber-600"></i>
        <p>{{ $stats['non_localisees'] }} arrêt{{ $stats['non_localisees'] > 1 ? 's' : '' }} à venir sans position sur la carte : l'adresse est à préciser par le collecteur.</p>
    </div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

    {{-- ===== Tournées ===== --}}
    <div class="a-card xl:col-span-2 self-start">
        <form method="GET" action="{{ route('admin.logistique.index') }}"
              class="flex flex-col md:flex-row md:items-center gap-2 p-3 border-b border-slate-200">
            @include('admin.partials.select', [
                'name'     => 'collecteur',
                'id'       => 'filtre-collecteur',
                'class'    => 'md:w-52 a-select-sm',
                'submit'   => true,
                'selected' => request('collecteur'),
                'options'  => ['' => 'Tous les collecteurs'] + $collecteurs->mapWithKeys(fn ($c) => [$c->id => $c->full_name])->all(),
            ])
            @include('admin.partials.select', [
                'name'     => 'statut',
                'id'       => 'filtre-statut',
                'class'    => 'md:w-44 a-select-sm',
                'submit'   => true,
                'selected' => request('statut'),
                'options'  => ['' => 'Tous les statuts'] + \App\Models\Tournee::statutOptions(),
            ])
            <input type="date" name="date" value="{{ request('date') }}" aria-label="Date"
                   class="a-input md:w-40 num" style="height: 32px;" onchange="this.form.submit()">
            @if(request()->hasAny(['collecteur', 'statut', 'date']))
                <a href="{{ route('admin.logistique.index') }}" class="a-btn" style="border-color: transparent; color: #64748b;">
                    <i class="fas fa-xmark"></i> Réinitialiser
                </a>
            @endif
            <span class="md:ml-auto text-slate-500 num">{{ $tournees->total() }} tournée{{ $tournees->total() > 1 ? 's' : '' }}</span>
        </form>

        @if($tournees->isEmpty())
            <div class="text-center py-14">
                <i class="fas fa-route text-slate-300 text-2xl"></i>
                <h3 class="font-medium text-slate-900 mt-3">Aucune tournée trouvée</h3>
                <p class="text-slate-500 mt-1">Les tournées créées par les collecteurs apparaissent ici.</p>
            </div>
        @else
            <div class="overflow-x-auto {{ $tournees->hasPages() ? '' : 'rounded-b-lg' }}">
                <table class="a-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Collecteur</th>
                            <th>Zone</th>
                            <th>Avancement</th>
                            <th>Statut</th>
                            <th class="w-px"><span class="sr-only">Ouvrir</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tournees as $tournee)
                            @php
                                $faites = $tournee->missions_terminees_count + $tournee->missions_echouees_count;
                                $part   = $tournee->missions_count > 0 ? $faites / $tournee->missions_count * 100 : 0;
                            @endphp
                            <tr class="cursor-pointer" onclick="window.location.href = this.querySelector('a').href">
                                <td class="num">
                                    <a href="{{ route('admin.logistique.show', $tournee) }}" class="font-medium text-slate-900">{{ $tournee->date->format('d/m/Y') }}</a>
                                    @if($tournee->date->isToday()) <span class="text-xs text-primary-dark font-medium ml-1">aujourd'hui</span> @endif
                                </td>
                                <td>
                                    <span class="flex items-center gap-2">
                                        <span class="a-avatar w-6 h-6 text-[10px]">{{ $tournee->user?->initials ?: '?' }}</span>
                                        {{ $tournee->user?->full_name }}
                                    </span>
                                </td>
                                <td class="text-slate-700">{{ $tournee->zone }}</td>
                                <td>
                                    <span class="flex items-center gap-2 num">
                                        <span class="block w-20 h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                            <span class="block h-full bg-primary" style="width: {{ $part }}%"></span>
                                        </span>
                                        <span class="text-slate-600 text-xs">{{ $faites }}/{{ $tournee->missions_count }}</span>
                                        @if($tournee->missions_echouees_count)
                                            <span class="text-xs text-red-700">{{ $tournee->missions_echouees_count }} échec{{ $tournee->missions_echouees_count > 1 ? 's' : '' }}</span>
                                        @endif
                                    </span>
                                </td>
                                <td>@include('logistique.partials.statut-badge', ['badge' => $tournee->statut_badge])</td>
                                <td class="text-slate-400"><i class="fas fa-chevron-right text-[10px]"></i></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($tournees->hasPages())
                <div class="px-3 py-2.5 border-t border-slate-200">
                    {{ $tournees->links('admin.partials.pagination') }}
                </div>
            @endif
        @endif
    </div>

    {{-- ===== À planifier ===== --}}
    <div class="a-card self-start">
        <div class="px-4 py-3 border-b border-slate-200">
            <h2 class="font-semibold text-slate-900">À planifier <span class="text-slate-400 font-normal num">({{ $enAttente }})</span></h2>
            <p class="text-slate-500 mt-0.5">Commandes à livrer et dons à collecter qui ne sont dans aucune tournée.</p>
            @if($enAttente > 0)
                <a href="{{ route('admin.logistique.affectation') }}" class="a-btn a-btn-primary w-full mt-3">
                    <i class="fas fa-wand-magic-sparkles"></i> Répartir automatiquement
                </a>
            @endif
        </div>

        @if($enAttente === 0)
            <p class="text-slate-500 text-center py-10">Tout est planifié.</p>
        @else
            <ul class="divide-y divide-slate-100 max-h-[560px] overflow-y-auto">
                @foreach($aPlanifier['commandes'] as $commande)
                    <li class="px-4 py-2.5 flex items-start gap-3">
                        <i class="fas fa-truck text-slate-400 text-xs mt-1.5"></i>
                        <span class="min-w-0">
                            <span class="block font-medium text-slate-900">Livraison · {{ $commande->numero }}</span>
                            <span class="block text-slate-500 text-xs mt-0.5 truncate">
                                {{ $commande->user?->full_name }} — {{ $commande->adresse?->formatted_address ?? $commande->adresse_snapshot ?? 'adresse inconnue' }}
                            </span>
                        </span>
                    </li>
                @endforeach
                @foreach($aPlanifier['dons'] as $don)
                    <li class="px-4 py-2.5 flex items-start gap-3">
                        <i class="fas fa-box-open text-slate-400 text-xs mt-1.5"></i>
                        <span class="min-w-0">
                            <span class="block font-medium text-slate-900">Collecte · don n° {{ $don->id }} ({{ $don->type }})</span>
                            <span class="block text-slate-500 text-xs mt-0.5 truncate">
                                {{ $don->user?->full_name }} — {{ $don->user?->adresseParDefaut?->formatted_address ?? 'adresse inconnue' }}
                            </span>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

@endsection
