@extends('recyclage.layouts.recyclage')

@section('title', 'Statistiques')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">Statistiques</span>
@endsection

@section('recyclage-content')

{{-- Header --}}
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="font-display text-2xl font-bold text-gray-900">Statistiques de recyclage</h1>
        <p class="text-gray-500 text-sm mt-1">Vue d'ensemble de votre activité — {{ $recycleur->nom }}</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('recyclage.statistiques.export') }}"
           class="inline-flex items-center gap-2 border-2 border-green-200 bg-green-50/50 text-green-700 font-semibold
                  px-4 py-2.5 rounded-xl hover:bg-green-100 hover:border-green-300 transition-all shadow-sm">
            <i class="fas fa-file-excel text-green-600"></i>
            Exporter Excel
        </a>
        <a href="{{ route('recyclage.statistiques.export-pdf') }}"
           class="inline-flex items-center gap-2 border-2 border-red-200 bg-red-50/50 text-red-700 font-semibold
                  px-4 py-2.5 rounded-xl hover:bg-red-100 hover:border-red-300 transition-all shadow-sm">
            <i class="fas fa-file-pdf text-red-600"></i>
            Rapport PDF
        </a>
    </div>
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
        <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center mb-4">
            <i class="fas fa-smog text-emerald-500"></i>
        </div>
        <p class="text-3xl font-bold text-emerald-600">{{ number_format($stats['total_kg'] * 2.4, 1) }}</p>
        <p class="text-gray-500 text-sm mt-1">kg CO₂ évités</p>
        <p class="text-xs text-gray-400 mt-2">Impact total</p>
    </div>
</div>

{{-- ===== GRAPHIQUE 1 : Évolution lots + poids (12 mois) ===== --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-chart-line text-primary-DEFAULT"></i>
                Évolution sur les 12 derniers mois
            </h3>
            <p class="text-xs text-gray-400 mt-0.5">Nombre de lots enregistrés par mois</p>
        </div>
        {{-- Toggle lots / poids --}}
        <div class="flex bg-gray-100 rounded-xl p-1 text-xs font-semibold">
            <button id="btn-lots"
                    onclick="switchChart('lots')"
                    class="px-3 py-1.5 rounded-lg bg-white text-gray-800 shadow-sm transition-all">
                Lots
            </button>
            <button id="btn-poids"
                    onclick="switchChart('poids')"
                    class="px-3 py-1.5 rounded-lg text-gray-500 transition-all">
                Poids (kg)
            </button>
            <button id="btn-co2"
                    onclick="switchChart('co2')"
                    class="px-3 py-1.5 rounded-lg text-gray-500 transition-all">
                CO₂
            </button>
        </div>
    </div>
    <div class="relative h-64">
        <canvas id="chartEvolution"></canvas>
    </div>
</div>

{{-- ===== GRAPHIQUES 2 + 3 côte à côte ===== --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- Donut Filières --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-1 flex items-center gap-2">
            <i class="fas fa-chart-pie text-primary-DEFAULT"></i>
            Répartition par filière IA
        </h3>
        <p class="text-xs text-gray-400 mb-5">Recommandation automatique par composition</p>

        @if(array_sum($filieresData) === 0)
            <div class="flex flex-col items-center justify-center h-48 text-gray-300">
                <i class="fas fa-chart-pie text-4xl mb-3"></i>
                <p class="text-sm text-gray-400">Aucune donnée disponible</p>
            </div>
        @else
            <div class="flex items-center gap-6">
                <div class="relative w-44 h-44 flex-shrink-0">
                    <canvas id="chartFilieres"></canvas>
                </div>
                <div class="flex-1 space-y-2.5">
                    @php
                        $filieresMeta = [
                            ['label' => 'Revente',           'color' => 'bg-emerald-500', 'text' => 'text-emerald-700'],
                            ['label' => 'Upcycling',         'color' => 'bg-amber-500',   'text' => 'text-amber-700'],
                            ['label' => 'Recyclage fibre',   'color' => 'bg-blue-500',    'text' => 'text-blue-700'],
                            ['label' => 'Recyclage énergie', 'color' => 'bg-red-500',     'text' => 'text-red-700'],
                        ];
                        $totalFilieres = max(array_sum($filieresData), 1);
                    @endphp
                    @foreach($filieresMeta as $i => $f)
                    @php $count = $filieresData[$i] ?? 0; $pct = round(($count / $totalFilieres) * 100); @endphp
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full {{ $f['color'] }} flex-shrink-0"></div>
                        <span class="text-xs text-gray-600 flex-1">{{ $f['label'] }}</span>
                        <span class="text-xs font-bold {{ $f['text'] }}">{{ $count }}</span>
                        <span class="text-xs text-gray-400">({{ $pct }}%)</span>
                    </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Donut Statuts --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-1 flex items-center gap-2">
            <i class="fas fa-chart-donut text-primary-DEFAULT"></i>
            Répartition par statut
        </h3>
        <p class="text-xs text-gray-400 mb-5">État actuel du parc de lots</p>

        @if($stats['total_lots'] === 0)
            <div class="flex flex-col items-center justify-center h-48 text-gray-300">
                <i class="fas fa-chart-pie text-4xl mb-3"></i>
                <p class="text-sm text-gray-400">Aucun lot enregistré</p>
            </div>
        @else
            <div class="flex items-center gap-6">
                <div class="relative w-44 h-44 flex-shrink-0">
                    <canvas id="chartStatuts"></canvas>
                </div>
                <div class="flex-1 space-y-2.5">
                    @php
                        $statutsMeta = [
                            ['label' => 'En attente',    'color' => 'bg-yellow-400', 'text' => 'text-yellow-600'],
                            ['label' => 'En traitement', 'color' => 'bg-blue-400',   'text' => 'text-blue-600'],
                            ['label' => 'Traité',        'color' => 'bg-green-500',  'text' => 'text-green-600'],
                            ['label' => 'Certifié',      'color' => 'bg-purple-500', 'text' => 'text-purple-600'],
                        ];
                        $totalStatuts = max($stats['total_lots'], 1);
                    @endphp
                    @foreach($statutsMeta as $i => $s)
                    @php $count = $statutsData[$i] ?? 0; $pct = round(($count / $totalStatuts) * 100); @endphp
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full {{ $s['color'] }} flex-shrink-0"></div>
                        <span class="text-xs text-gray-600 flex-1">{{ $s['label'] }}</span>
                        <span class="text-xs font-bold {{ $s['text'] }}">{{ $count }}</span>
                        <span class="text-xs text-gray-400">({{ $pct }}%)</span>
                    </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

{{-- ===== GRAPHIQUE 4 : Impact écologique cumulé ===== --}}
<div class="bg-gradient-to-br from-primary-dark to-primary-DEFAULT rounded-2xl p-6 mb-6">
    <h3 class="font-semibold text-white mb-1 flex items-center gap-2">
        <i class="fas fa-leaf text-secondary-DEFAULT"></i>
        Impact écologique — CO₂ évité par mois
    </h3>
    <p class="text-white/50 text-xs mb-5">Estimation : 1 kg textile ≈ 2.4 kg CO₂ évité</p>
    <div class="relative h-48">
        <canvas id="chartCo2Bars"></canvas>
    </div>
</div>

{{-- ===== GRAPHIQUE 5 : Jauge capacité + Infos recycleur ===== --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

    {{-- Jauge capacité semi-donut --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col items-center">
        <h3 class="font-semibold text-gray-900 mb-3 text-center flex items-center gap-2">
            <i class="fas fa-tachometer-alt text-primary-DEFAULT"></i>
            Capacité utilisée
        </h3>
        @php $pctCapacite = $recycleur->capacite_kg > 0 ? min(round(($stats['total_kg'] / $recycleur->capacite_kg) * 100), 100) : 0; @endphp
        {{-- Semi-donut via canvas + overlay --}}
        <div class="relative" style="width:176px; height:96px; overflow:hidden;">
            <canvas id="chartCapacite" width="176" height="176" style="position:absolute;top:0;left:0;"></canvas>
            <div class="absolute inset-x-0 bottom-0 flex flex-col items-center justify-end pb-1">
                <p class="text-3xl font-bold {{ $pctCapacite >= 95 ? 'text-red-600' : ($pctCapacite >= 80 ? 'text-amber-500' : 'text-primary-DEFAULT') }}">{{ $pctCapacite }}%</p>
                <p class="text-xs text-gray-400">utilisé</p>
            </div>
        </div>
        {{-- Graduation labels --}}
        <div class="flex justify-between w-44 text-xs text-gray-400 mt-1 mb-3">
            <span>0%</span>
            <span>50%</span>
            <span>100%</span>
        </div>
        {{-- Barre textuelle --}}
        <div class="w-full">
            <div class="w-full bg-gray-100 rounded-full h-1.5 mb-2">
                <div class="h-1.5 rounded-full transition-all duration-700
                    {{ $pctCapacite >= 95 ? 'bg-red-500' : ($pctCapacite >= 80 ? 'bg-amber-400' : 'bg-primary-DEFAULT') }}"
                     style="width: {{ $pctCapacite }}%"></div>
            </div>
            <div class="flex justify-between text-xs">
                <span class="text-gray-500 font-semibold">{{ number_format($stats['total_kg'], 1) }} kg utilisés</span>
                <span class="text-gray-400">/ {{ number_format($recycleur->capacite_kg) }} kg</span>
            </div>
            @if($pctCapacite >= 80)
                <div class="mt-2 flex items-center gap-1.5 text-xs {{ $pctCapacite >= 95 ? 'text-red-600' : 'text-amber-600' }}">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>{{ $pctCapacite >= 95 ? 'Capacité critique !' : 'Capacité bientôt saturée' }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Infos recycleur --}}
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-5 flex items-center gap-2">
            <i class="fas fa-building text-primary-DEFAULT"></i>
            Profil recycleur
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-industry text-gray-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Nom</p>
                    <p class="font-bold text-gray-900">{{ $recycleur->nom }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-id-card text-gray-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Agrément</p>
                    <p class="font-mono font-bold text-gray-900">{{ $recycleur->agrement }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-map-marker-alt text-gray-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Localisation</p>
                    <p class="font-medium text-gray-700">{{ $recycleur->localisation }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl {{ $recycleur->actif ? 'bg-green-50' : 'bg-red-50' }} flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-circle {{ $recycleur->actif ? 'text-green-500' : 'text-red-500' }} text-xs"></i>
                </div>
                <div>
                    <p class="text-gray-400 text-xs">Statut</p>
                    <p class="font-bold {{ $recycleur->actif ? 'text-green-600' : 'text-red-600' }}">
                        {{ $recycleur->actif ? 'Actif' : 'Inactif' }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3 sm:col-span-2">
                <div class="w-9 h-9 rounded-xl bg-teal-50 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-tint text-teal-500 text-sm"></i>
                </div>
                <div class="flex-1">
                    <p class="text-gray-400 text-xs mb-1">Eau économisée (total)</p>
                    <p class="font-bold text-teal-600">{{ number_format(($stats['total_kg'] * 3000) / 1000, 0) }} milliers de litres</p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Données passées depuis PHP ──────────────────────────────────────────
const labelsM       = @json($labelsM);
const seriesLots    = @json($seriesLots);
const seriesPoids   = @json($seriesPoids);
const seriesCo2     = @json($seriesCo2);
const filieresLabels = @json($filieresLabels);
const filieresData   = @json($filieresData);
const statutsLabels  = @json($statutsLabels);
const statutsData    = @json($statutsData);

// ── Palette de couleurs ─────────────────────────────────────────────────
const colors = {
    primary:   '#0d9488',
    secondary: '#4ade80',
    amber:     '#f59e0b',
    blue:      '#3b82f6',
    red:       '#ef4444',
    purple:    '#a855f7',
    emerald:   '#10b981',
    yellow:    '#eab308',
    green:     '#22c55e',
};

Chart.defaults.font.family = 'Inter, sans-serif';
Chart.defaults.font.size   = 11;

// ── 1. Graphique Évolution (ligne) ──────────────────────────────────────
const ctxEvol = document.getElementById('chartEvolution').getContext('2d');

const gradientEvol = ctxEvol.createLinearGradient(0, 0, 0, 250);
gradientEvol.addColorStop(0, 'rgba(13,148,136,0.3)');
gradientEvol.addColorStop(1, 'rgba(13,148,136,0)');

const datasetsMap = {
    lots:  { label: 'Nombre de lots', data: seriesLots,  borderColor: colors.primary,  backgroundColor: gradientEvol, yLabel: 'Lots' },
    poids: { label: 'Poids (kg)',      data: seriesPoids, borderColor: colors.emerald,  backgroundColor: 'rgba(16,185,129,0.15)', yLabel: 'kg' },
    co2:   { label: 'CO₂ évité (kg)',  data: seriesCo2,   borderColor: colors.amber,    backgroundColor: 'rgba(245,158,11,0.15)',  yLabel: 'kg CO₂' },
};

let activeKey = 'lots';

const chartEvolution = new Chart(ctxEvol, {
    type: 'line',
    data: {
        labels: labelsM,
        datasets: [{
            ...datasetsMap.lots,
            tension: 0.4,
            borderWidth: 2.5,
            pointBackgroundColor: colors.primary,
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                titleColor: '#94a3b8',
                bodyColor: '#f1f5f9',
                padding: 12,
                cornerRadius: 10,
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { color: '#94a3b8' } },
            y: { grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8' }, beginAtZero: true }
        }
    }
});

function switchChart(key) {
    if (activeKey === key) return;
    activeKey = key;

    // Mettre à jour les boutons
    ['lots','poids','co2'].forEach(k => {
        const btn = document.getElementById('btn-' + k);
        btn.classList.toggle('bg-white', k === key);
        btn.classList.toggle('text-gray-800', k === key);
        btn.classList.toggle('shadow-sm', k === key);
        btn.classList.toggle('text-gray-500', k !== key);
    });

    const ds = datasetsMap[key];
    chartEvolution.data.datasets[0].label           = ds.label;
    chartEvolution.data.datasets[0].data            = ds.data;
    chartEvolution.data.datasets[0].borderColor     = ds.borderColor;
    chartEvolution.data.datasets[0].backgroundColor = ds.backgroundColor;
    chartEvolution.update('active');
}

// ── 2. Donut Filières ───────────────────────────────────────────────────
@if(array_sum($filieresData) > 0)
new Chart(document.getElementById('chartFilieres').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: filieresLabels,
        datasets: [{
            data: filieresData,
            backgroundColor: [colors.emerald, colors.amber, colors.blue, colors.red],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 6,
        }]
    },
    options: {
        cutout: '68%',
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                bodyColor: '#f1f5f9',
                padding: 10,
                cornerRadius: 8,
            }
        }
    }
});
@endif

// ── 3. Donut Statuts ────────────────────────────────────────────────────
@if($stats['total_lots'] > 0)
new Chart(document.getElementById('chartStatuts').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: statutsLabels,
        datasets: [{
            data: statutsData,
            backgroundColor: [colors.yellow, colors.blue, colors.green, colors.purple],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 6,
        }]
    },
    options: {
        cutout: '68%',
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                bodyColor: '#f1f5f9',
                padding: 10,
                cornerRadius: 8,
            }
        }
    }
});
@endif

// ── 4. Barres CO₂ (fond sombre) ─────────────────────────────────────────
const ctxCo2 = document.getElementById('chartCo2Bars').getContext('2d');
const gradientCo2 = ctxCo2.createLinearGradient(0, 0, 0, 180);
gradientCo2.addColorStop(0, 'rgba(74,222,128,0.9)');
gradientCo2.addColorStop(1, 'rgba(74,222,128,0.3)');

new Chart(ctxCo2, {
    type: 'bar',
    data: {
        labels: labelsM,
        datasets: [{
            label: 'kg CO₂ évité',
            data: seriesCo2,
            backgroundColor: gradientCo2,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.7)',
                bodyColor: '#4ade80',
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    label: ctx => ` ${ctx.parsed.y} kg CO₂`,
                }
            }
        },
        scales: {
            x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.4)' } },
            y: { grid: { color: 'rgba(255,255,255,0.07)' }, ticks: { color: 'rgba(255,255,255,0.4)' }, beginAtZero: true }
        }
    }
});

// ── 5. Jauge capacité (vrai demi-donut) ────────────────────────────────
@php $pctCapaciteJs = $recycleur->capacite_kg > 0 ? min(round(($stats['total_kg'] / $recycleur->capacite_kg) * 100), 100) : 0; @endphp
const gaugeColor = '{{ $pctCapaciteJs >= 95 ? "#ef4444" : ($pctCapaciteJs >= 80 ? "#f59e0b" : "#0d9488") }}';
new Chart(document.getElementById('chartCapacite').getContext('2d'), {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [{{ $pctCapaciteJs }}, {{ 100 - $pctCapaciteJs }}],
            backgroundColor: [gaugeColor, '#f1f5f9'],
            borderWidth: 0,
            hoverOffset: 0,
        }]
    },
    options: {
        cutout: '72%',
        circumference: 180,
        rotation: -90,
        responsive: false,
        maintainAspectRatio: false,
        animation: { duration: 1000, easing: 'easeOutQuart' },
        plugins: { legend: { display: false }, tooltip: { enabled: false } }
    }
});
</script>
@endpush

@endsection
