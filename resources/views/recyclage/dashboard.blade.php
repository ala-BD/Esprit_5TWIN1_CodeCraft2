@extends('recyclage.layouts.recyclage')

@section('title', 'Dashboard Recycleur')

@section('recyclage-content')

{{-- Header --}}
<div class="mb-8">
    <h1 class="font-display text-2xl font-bold text-gray-900">
        Bonjour, <span class="text-primary-DEFAULT">{{ Auth::user()->prenom ?? Auth::user()->name }}</span>
    </h1>
    <p class="text-gray-500 text-sm mt-1">Tableau de bord — Module Recyclage M4</p>
</div>

@if(!isset($recycleur))
{{-- Profil recycleur non encore créé --}}
<div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-8 flex items-start gap-4">
    <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
        <i class="fas fa-exclamation-triangle text-amber-500"></i>
    </div>
    <div>
        <p class="font-semibold text-amber-800">Profil recycleur incomplet</p>
        <p class="text-amber-600 text-sm mt-1">Votre profil de recycleur n'est pas encore configuré. Contactez l'administrateur.</p>
    </div>
</div>
@else

{{-- ===== ALERTES MÉTIER ===== --}}
@if($alertes->isNotEmpty())
<div class="space-y-3 mb-8">
    @foreach($alertes as $alerte)
    @php
        $config = match($alerte['niveau']) {
            'danger'  => ['bg' => 'bg-red-50',    'border' => 'border-red-200',    'icon_bg' => 'bg-red-100',    'icon_color' => 'text-red-500',    'title_color' => 'text-red-800',    'text_color' => 'text-red-600',    'btn' => 'bg-red-100 hover:bg-red-200 text-red-700',    'close' => 'text-red-400 hover:text-red-600'],
            'warning' => ['bg' => 'bg-amber-50',  'border' => 'border-amber-200',  'icon_bg' => 'bg-amber-100',  'icon_color' => 'text-amber-500',  'title_color' => 'text-amber-800',  'text_color' => 'text-amber-600',  'btn' => 'bg-amber-100 hover:bg-amber-200 text-amber-700',  'close' => 'text-amber-400 hover:text-amber-600'],
            default   => ['bg' => 'bg-blue-50',   'border' => 'border-blue-200',   'icon_bg' => 'bg-blue-100',   'icon_color' => 'text-blue-500',   'title_color' => 'text-blue-800',   'text_color' => 'text-blue-600',   'btn' => 'bg-blue-100 hover:bg-blue-200 text-blue-700',   'close' => 'text-blue-400 hover:text-blue-600'],
        };
    @endphp
    <div class="{{ $config['bg'] }} border {{ $config['border'] }} rounded-2xl p-4 flex items-start gap-4 alerte-item fade-in">
        <div class="w-9 h-9 rounded-xl {{ $config['icon_bg'] }} flex items-center justify-center flex-shrink-0">
            <i class="fas {{ $alerte['icone'] }} {{ $config['icon_color'] }} text-sm"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-semibold {{ $config['title_color'] }} text-sm">{{ $alerte['titre'] }}</p>
            <p class="{{ $config['text_color'] }} text-xs mt-0.5 leading-relaxed">{{ $alerte['message'] }}</p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <a href="{{ $alerte['lien'] }}"
               class="{{ $config['btn'] }} text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap">
                {{ $alerte['lien_label'] }}
            </a>
            <button onclick="this.closest('.alerte-item').remove()"
                    class="{{ $config['close'] }} w-6 h-6 flex items-center justify-center rounded-lg transition-colors"
                    title="Fermer">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Stats principales --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-primary-DEFAULT/10 flex items-center justify-center">
                <i class="fas fa-boxes text-primary-DEFAULT"></i>
            </div>
            <span class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded-lg">Total</span>
        </div>
        <p class="text-3xl font-bold text-gray-900">{{ $stats['total'] }}</p>
        <p class="text-gray-500 text-sm mt-1">Lots enregistrés</p>
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-yellow-50 flex items-center justify-center">
                <i class="fas fa-clock text-yellow-500"></i>
            </div>
            <span class="text-xs text-yellow-600 bg-yellow-50 px-2 py-1 rounded-lg">Attente</span>
        </div>
        <p class="text-3xl font-bold text-yellow-600">{{ $stats['en_attente'] }}</p>
        <p class="text-gray-500 text-sm mt-1">En attente</p>
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center">
                <i class="fas fa-cogs text-blue-500"></i>
            </div>
            <span class="text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded-lg">Actif</span>
        </div>
        <p class="text-3xl font-bold text-blue-600">{{ $stats['en_traitement'] }}</p>
        <p class="text-gray-500 text-sm mt-1">En traitement</p>
    </div>

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center">
                <i class="fas fa-certificate text-purple-500"></i>
            </div>
            <span class="text-xs text-purple-600 bg-purple-50 px-2 py-1 rounded-lg">Certifié</span>
        </div>
        <p class="text-3xl font-bold text-purple-600">{{ $stats['certifies'] }}</p>
        <p class="text-gray-500 text-sm mt-1">Certifiés</p>
    </div>
</div>

{{-- Impact + Actions rapides --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

    {{-- Impact écologique --}}
    <div class="lg:col-span-2 bg-gradient-to-br from-primary-dark to-primary-DEFAULT rounded-2xl p-6 text-white">
        <div class="flex items-center gap-2 mb-5">
            <i class="fas fa-leaf text-secondary-DEFAULT"></i>
            <h3 class="font-semibold">Mon impact écologique total</h3>
        </div>
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/15">
                <p class="text-2xl font-bold">{{ number_format($stats['total_kg'] * 2.4, 1) }}</p>
                <p class="text-white/70 text-xs mt-1">kg CO₂ évités</p>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/15">
                <p class="text-2xl font-bold">{{ number_format($stats['total_kg'] * 3000, 0, ',', ' ') }}</p>
                <p class="text-white/70 text-xs mt-1">litres eau économisés</p>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 text-center border border-white/15">
                <p class="text-2xl font-bold">{{ number_format($stats['total_kg'], 1) }}</p>
                <p class="text-white/70 text-xs mt-1">kg traités</p>
            </div>
        </div>
    </div>

    {{-- Actions rapides --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Actions rapides</h3>
        <div class="space-y-3">
            <a href="{{ route('recyclage.lots.create') }}"
               class="flex items-center gap-3 p-3 bg-primary-DEFAULT/5 hover:bg-primary-DEFAULT/10 rounded-xl transition-colors group">
                <div class="w-9 h-9 rounded-lg bg-primary-DEFAULT/10 group-hover:bg-primary-DEFAULT flex items-center justify-center flex-shrink-0 transition-colors">
                    <i class="fas fa-plus text-primary-DEFAULT group-hover:text-white text-sm transition-colors"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">Nouveau lot</p>
                    <p class="text-xs text-gray-500">Enregistrer un lot</p>
                </div>
            </a>
            <a href="{{ route('recyclage.lots.index') }}"
               class="flex items-center gap-3 p-3 bg-gray-50 hover:bg-gray-100 rounded-xl transition-colors group">
                <div class="w-9 h-9 rounded-lg bg-gray-100 group-hover:bg-gray-200 flex items-center justify-center flex-shrink-0 transition-colors">
                    <i class="fas fa-list text-gray-500 text-sm"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">Mes lots</p>
                    <p class="text-xs text-gray-500">Voir tous les lots</p>
                </div>
            </a>
        </div>
    </div>
</div>

{{-- Mini sparkline 6 mois --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-3">
            <div>
                <p class="font-semibold text-gray-900 text-sm">Tendance des lots</p>
                <p class="text-xs text-gray-400 mt-0.5">6 derniers mois</p>
            </div>
            <a href="{{ route('recyclage.statistiques') }}"
               class="text-xs text-primary-DEFAULT hover:underline flex items-center gap-1">
                Détails <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>
        <div class="h-24">
            <canvas id="chartSparkline"></canvas>
        </div>
        @php
            $lotsTotal = $stats['total'];
            $certTaux  = $lotsTotal > 0 ? round(($stats['certifies'] / $lotsTotal) * 100) : 0;
        @endphp
        <div class="flex items-center gap-2 mt-2">
            <span class="text-xs text-gray-500">Taux de certification :</span>
            <span class="text-xs font-bold {{ $certTaux >= 50 ? 'text-green-600' : 'text-amber-600' }}">{{ $certTaux }}%</span>
        </div>
    </div>

    {{-- Derniers lots (col-span-2) --}}
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-900">Derniers lots enregistrés</h3>
        <a href="{{ route('recyclage.lots.index') }}" class="text-sm text-primary-DEFAULT hover:text-primary-dark font-medium transition-colors">
            Voir tout <i class="fas fa-arrow-right text-xs ml-1"></i>
        </a>
    </div>

    @if($lots->isEmpty())
        <div class="text-center py-12">
            <i class="fas fa-box-open text-gray-300 text-3xl mb-3 block"></i>
            <p class="text-gray-400 text-sm">Aucun lot enregistré pour l'instant.</p>
            <a href="{{ route('recyclage.lots.create') }}"
               class="inline-flex items-center gap-2 mt-4 bg-primary-DEFAULT text-white text-sm font-semibold px-4 py-2 rounded-xl hover:bg-primary-dark transition-colors">
                <i class="fas fa-plus text-xs"></i> Enregistrer un lot
            </a>
        </div>
    @else
        <div class="divide-y divide-gray-50">
            @foreach($lots as $lot)
            <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-primary-DEFAULT/10 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-recycle text-primary-DEFAULT text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm">{{ $lot->reference }}</p>
                        <p class="text-gray-400 text-xs">{{ $lot->poids_kg }} kg — {{ $lot->composition }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @php $badge = $lot->statut_badge; @endphp
                    <span class="px-2.5 py-1 rounded-lg text-xs font-semibold {{ $badge['class'] }}">
                        {{ $badge['label'] }}
                    </span>
                    <a href="{{ route('recyclage.lots.show', $lot) }}"
                       class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-primary-DEFAULT hover:text-white flex items-center justify-center text-gray-500 transition-all">
                        <i class="fas fa-eye text-xs"></i>
                    </a>
                </div>
            </div>
            @endforeach
            </div>{{-- /divide-y --}}
        @endif
    </div>{{-- /derniers lots --}}

</div>{{-- /grid sparkline+lots --}}
@endif


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Mini sparkline 6 derniers mois
@php
    $sparkData   = [];
    $sparkLabels = [];
    $recycleurIdSpark = isset($recycleur) ? $recycleur->id : 0;
    for ($i = 5; $i >= 0; $i--) {
        $moisSpark     = now()->subMonths($i);
        $sparkLabels[] = $moisSpark->translatedFormat('M');
        $sparkData[]   = \App\Models\LotTextile::where('recycleur_id', $recycleurIdSpark)
            ->whereYear('created_at',  $moisSpark->year)
            ->whereMonth('created_at', $moisSpark->month)
            ->count();
    }
@endphp
const sparkLabels = @json($sparkLabels);
const sparkData   = @json($sparkData);

const ctxSpark = document.getElementById('chartSparkline');
if (ctxSpark) {
    const gradSpark = ctxSpark.getContext('2d').createLinearGradient(0, 0, 0, 96);
    gradSpark.addColorStop(0, 'rgba(13,148,136,0.25)');
    gradSpark.addColorStop(1, 'rgba(13,148,136,0)');
    new Chart(ctxSpark, {
        type: 'line',
        data: {
            labels: sparkLabels,
            datasets: [{
                data: sparkData,
                borderColor: '#0d9488',
                backgroundColor: gradSpark,
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 3,
                pointBackgroundColor: '#0d9488',
                pointHoverRadius: 5,
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
                    bodyColor: '#f1f5f9',
                    padding: 8,
                    cornerRadius: 8,
                    callbacks: { label: ctx => ` ${ctx.parsed.y} lot(s)` }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 } } },
                y: { display: false, beginAtZero: true }
            }
        }
    });
}
</script>
@endpush

@endsection
