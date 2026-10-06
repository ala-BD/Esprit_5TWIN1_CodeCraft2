@extends('recyclage.layouts.recyclage')

@section('title', 'Statistiques')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Statistiques</span>
@endsection

@section('recyclage-content')

{{-- Header --}}
<div class="mb-8">
    <h1 class="font-display text-2xl font-bold text-gray-900">Statistiques de recyclage</h1>
    <p class="text-gray-500 text-sm mt-1">Vue d'ensemble de votre activité — {{ $recycleur->nom }}</p>
</div>

{{-- KPIs principaux --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <div class="w-11 h-11 rounded-xl bg-primary-DEFAULT/10 flex items-center justify-center mb-4">
            <i class="fas fa-boxes text-primary-DEFAULT"></i>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $stats['total_lots'] }}</p>
        <p class="text-gray-500 text-sm mt-1">Lots enregistrés</p>
        <p class="text-xs text-gray-400 mt-2">Depuis le début</p>
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <div class="w-11 h-11 rounded-xl bg-green-50 flex items-center justify-center mb-4">
            <i class="fas fa-weight text-green-500"></i>
        </div>
        <p class="text-3xl font-bold text-green-600">{{ number_format($stats['total_kg'], 1) }}</p>
        <p class="text-gray-500 text-sm mt-1">kg traités</p>
        <p class="text-xs text-gray-400 mt-2">Total cumulé</p>
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center mb-4">
            <i class="fas fa-certificate text-purple-500"></i>
        </div>
        <p class="text-3xl font-bold text-purple-600">{{ $stats['certifies'] }}</p>
        <p class="text-gray-500 text-sm mt-1">Lots certifiés</p>
        <p class="text-xs text-gray-400 mt-2">
            @if($stats['total_lots'] > 0)
                {{ round(($stats['certifies'] / $stats['total_lots']) * 100) }}% du total
            @else 0% @endif
        </p>
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
        <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center mb-4">
            <i class="fas fa-cogs text-blue-500"></i>
        </div>
        <p class="text-3xl font-bold text-blue-600">{{ $stats['en_traitement'] }}</p>
        <p class="text-gray-500 text-sm mt-1">En cours</p>
        <p class="text-xs text-gray-400 mt-2">Actuellement traités</p>
    </div>
</div>

{{-- Impact écologique + Répartition filières --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

    {{-- Impact écologique --}}
    <div class="bg-gradient-to-br from-primary-dark to-primary-DEFAULT rounded-2xl p-6 text-white">
        <div class="flex items-center gap-2 mb-6">
            <i class="fas fa-leaf text-secondary-DEFAULT text-lg"></i>
            <h3 class="font-semibold text-lg">Impact écologique total</h3>
        </div>

        <div class="space-y-4">
            <div class="bg-white/10 rounded-xl p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center">
                        <i class="fas fa-smog text-white"></i>
                    </div>
                    <div>
                        <p class="text-white/70 text-xs">CO₂ évité</p>
                        <p class="font-bold text-xl">{{ number_format($stats['total_kg'] * 2.4, 1) }}</p>
                    </div>
                </div>
                <span class="text-white/70 text-sm">kg</span>
            </div>

            <div class="bg-white/10 rounded-xl p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center">
                        <i class="fas fa-tint text-white"></i>
                    </div>
                    <div>
                        <p class="text-white/70 text-xs">Eau économisée</p>
                        <p class="font-bold text-xl">{{ number_format(($stats['total_kg'] * 3000) / 1000, 0) }}</p>
                    </div>
                </div>
                <span class="text-white/70 text-sm">milliers L</span>
            </div>

            <div class="bg-white/10 rounded-xl p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center">
                        <i class="fas fa-recycle text-white"></i>
                    </div>
                    <div>
                        <p class="text-white/70 text-xs">Capacité utilisée</p>
                        <p class="font-bold text-xl">
                            {{ $recycleur->capacite_kg > 0 ? round(($stats['total_kg'] / $recycleur->capacite_kg) * 100) : 0 }}%
                        </p>
                    </div>
                </div>
                <span class="text-white/70 text-sm">de {{ number_format($recycleur->capacite_kg) }} kg</span>
            </div>
        </div>
    </div>

    {{-- Répartition par filière --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-5 flex items-center gap-2">
            <i class="fas fa-chart-pie text-primary-DEFAULT"></i>
            Répartition par filière IA
        </h3>

        @if(array_sum($stats['filieres']) === 0)
            <div class="text-center py-8 text-gray-400">
                <i class="fas fa-chart-pie text-3xl mb-2 block"></i>
                <p class="text-sm">Aucune donnée disponible</p>
            </div>
        @else
            <div class="space-y-4">
                @php
                    $filieres = [
                        'REVENTE'           => ['label' => 'Revente',           'color' => 'bg-emerald-500', 'light' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'icon' => 'fa-store'],
                        'UPCYCLING'         => ['label' => 'Upcycling',         'color' => 'bg-amber-500',   'light' => 'bg-amber-50',   'text' => 'text-amber-700',   'icon' => 'fa-cut'],
                        'RECYCLAGE_FIBRE'   => ['label' => 'Recyclage fibre',   'color' => 'bg-blue-500',    'light' => 'bg-blue-50',    'text' => 'text-blue-700',    'icon' => 'fa-recycle'],
                        'RECYCLAGE_ENERGIE' => ['label' => 'Recyclage énergie', 'color' => 'bg-red-500',     'light' => 'bg-red-50',     'text' => 'text-red-700',     'icon' => 'fa-bolt'],
                    ];
                    $total = max(array_sum($stats['filieres']), 1);
                @endphp

                @foreach($filieres as $key => $filiere)
                @php $count = $stats['filieres'][$key] ?? 0; $pct = round(($count / $total) * 100); @endphp
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg {{ $filiere['light'] }} flex items-center justify-center">
                                <i class="fas {{ $filiere['icon'] }} {{ $filiere['text'] }} text-xs"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-700">{{ $filiere['label'] }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-gray-900">{{ $count }}</span>
                            <span class="text-xs text-gray-400">({{ $pct }}%)</span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="{{ $filiere['color'] }} h-2 rounded-full transition-all duration-700"
                             style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Répartition par statut --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
    <h3 class="font-semibold text-gray-900 mb-5 flex items-center gap-2">
        <i class="fas fa-chart-bar text-primary-DEFAULT"></i>
        Répartition par statut
    </h3>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
            $statuts = [
                ['label' => 'En attente',    'count' => $stats['en_attente'],    'color' => 'border-yellow-400', 'bg' => 'bg-yellow-50',  'text' => 'text-yellow-700', 'icon' => 'fa-clock'],
                ['label' => 'En traitement', 'count' => $stats['en_traitement'], 'color' => 'border-blue-400',   'bg' => 'bg-blue-50',    'text' => 'text-blue-700',   'icon' => 'fa-cogs'],
                ['label' => 'Traité',        'count' => $stats['traites'],       'color' => 'border-green-400',  'bg' => 'bg-green-50',   'text' => 'text-green-700',  'icon' => 'fa-check'],
                ['label' => 'Certifié',      'count' => $stats['certifies'],     'color' => 'border-purple-400', 'bg' => 'bg-purple-50',  'text' => 'text-purple-700', 'icon' => 'fa-certificate'],
            ];
        @endphp
        @foreach($statuts as $s)
        <div class="border-l-4 {{ $s['color'] }} {{ $s['bg'] }} rounded-xl p-4">
            <div class="flex items-center gap-2 mb-2">
                <i class="fas {{ $s['icon'] }} {{ $s['text'] }} text-sm"></i>
                <span class="text-sm font-medium {{ $s['text'] }}">{{ $s['label'] }}</span>
            </div>
            <p class="text-2xl font-bold {{ $s['text'] }}">{{ $s['count'] }}</p>
            <p class="text-xs text-gray-500 mt-1">
                @if($stats['total_lots'] > 0)
                    {{ round(($s['count'] / $stats['total_lots']) * 100) }}% du total
                @else 0% @endif
            </p>
        </div>
        @endforeach
    </div>
</div>

{{-- Infos recycleur --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-semibold text-gray-900 mb-5 flex items-center gap-2">
        <i class="fas fa-building text-primary-DEFAULT"></i>
        Profil recycleur
    </h3>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
        <div>
            <p class="text-gray-500 text-xs mb-1">Nom</p>
            <p class="font-bold text-gray-900">{{ $recycleur->nom }}</p>
        </div>
        <div>
            <p class="text-gray-500 text-xs mb-1">Agrément</p>
            <p class="font-mono font-bold text-gray-900">{{ $recycleur->agrement }}</p>
        </div>
        <div>
            <p class="text-gray-500 text-xs mb-1">Localisation</p>
            <p class="font-medium text-gray-700">{{ $recycleur->localisation }}</p>
        </div>
        <div>
            <p class="text-gray-500 text-xs mb-1">Capacité totale</p>
            <p class="font-bold text-gray-900">{{ number_format($recycleur->capacite_kg) }} kg</p>
        </div>
        <div>
            <p class="text-gray-500 text-xs mb-1">Capacité utilisée</p>
            <div class="flex items-center gap-2 mt-1">
                <div class="flex-1 bg-gray-100 rounded-full h-2">
                    @php $pctCapacite = $recycleur->capacite_kg > 0 ? min(round(($stats['total_kg'] / $recycleur->capacite_kg) * 100), 100) : 0; @endphp
                    <div class="bg-primary-DEFAULT h-2 rounded-full" style="width: {{ $pctCapacite }}%"></div>
                </div>
                <span class="text-xs font-bold text-gray-700">{{ $pctCapacite }}%</span>
            </div>
        </div>
        <div>
            <p class="text-gray-500 text-xs mb-1">Statut</p>
            @if($recycleur->actif)
                <span class="inline-flex items-center gap-1 text-xs bg-green-100 text-green-700 font-bold px-2 py-1 rounded-lg">
                    <i class="fas fa-circle text-xs"></i> Actif
                </span>
            @else
                <span class="inline-flex items-center gap-1 text-xs bg-red-100 text-red-700 font-bold px-2 py-1 rounded-lg">
                    <i class="fas fa-circle text-xs"></i> Inactif
                </span>
            @endif
        </div>
    </div>
</div>

@endsection
