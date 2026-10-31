@extends('recyclage.layouts.recyclage')

@section('title', 'IA filière')

@section('breadcrumb')
    <i class="fas fa-chevron-right text-xs"></i>
    <span class="text-gray-700 font-medium">IA filière</span>
@endsection

@section('recyclage-content')
@use('App\Models\LotTextile')
@php
    $pct = fn ($v) => $v === null ? '—' : number_format($v * 100, 1, ',', ' ') . ' %';
    $libellesVariables = [
        'part_coton' => 'Part de coton', 'part_polyester' => 'Part de polyester', 'part_elasthanne' => 'Part d\'élasthanne',
        'part_laine' => 'Part de laine', 'part_viscose' => 'Part de viscose', 'part_nylon' => 'Part de polyamide',
        'part_acrylique' => 'Part d\'acrylique', 'part_lin' => 'Part de lin', 'part_soie' => 'Part de soie',
        'part_cachemire' => 'Part de cachemire', 'part_autre' => 'Fibres non identifiées',
        'part_synthetique' => 'Part synthétique', 'part_naturelle' => 'Part naturelle', 'part_noble' => 'Part de fibres nobles',
        'purete' => 'Pureté (fibre majoritaire)', 'nb_fibres' => 'Nombre de fibres',
        'poids_kg' => 'Poids du lot', 'log_poids' => 'Poids (échelle log)',
        'etat_score' => 'État du vêtement', 'etat_connu' => 'État renseigné',
    ];
@endphp

<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h1 class="font-display text-2xl font-bold text-gray-900 flex items-center gap-2">
            <i class="fas fa-brain text-indigo-500"></i> Moteur IA de filière
        </h1>
        <p class="text-gray-500 text-sm mt-1">
            Modèle entraîné, probabilités calibrées, décisions expliquées (SHAP) et apprentissage à partir de vos validations.
        </p>
    </div>
    <form method="POST" action="{{ route('recyclage.ia.entrainer') }}"
          onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = '<i class=\'fas fa-spinner fa-spin\'></i> Entraînement en cours…';">
        @csrf
        <button type="submit" @disabled(!$metriques)
                class="px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50">
            <i class="fas fa-graduation-cap"></i> Réentraîner avec {{ $totalDecisions }} décision(s) réelle(s)
        </button>
    </form>
</div>

@if(!$metriques)
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6">
        <p class="font-semibold text-amber-800"><i class="fas fa-plug"></i> Service IA arrêté</p>
        <p class="text-amber-700 text-sm mt-1">
            Les recommandations utilisent temporairement les règles métier. Pour démarrer le moteur :
        </p>
        <pre class="mt-3 bg-white rounded-lg p-3 text-xs text-gray-700">cd ai-recyclage
python -m uvicorn app:app --port 8001</pre>
    </div>
@else
    @php $t = $metriques['test']; @endphp

    {{-- ===== KPI ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        @foreach([
            ['Précision (test)', $pct($t['precision']), 'fa-bullseye', 'text-indigo-600', 'sur ' . array_sum(array_map(fn($c) => $c['support'], $t['par_classe'])) . ' lots jamais vus'],
            ['F1 macro', $pct($t['f1_macro']), 'fa-balance-scale', 'text-indigo-600', 'équilibre des 4 filières'],
            ['État du don connu', $pct($t['precision_etat_connu']), 'fa-tshirt', 'text-emerald-600', 'inconnu : ' . $pct($t['precision_etat_inconnu'])],
            ['Top-2', $pct($t['precision_top2']), 'fa-list-ol', 'text-blue-600', 'bonne filière dans les 2 premières'],
            ['Accord recycleurs', $accord['taux'] === null ? '—' : $accord['taux'] . ' %', 'fa-user-check', 'text-teal-600', $accord['confirmees'] . ' / ' . $accord['total'] . ' validations'],
        ] as [$titre, $valeur, $icone, $couleur, $sous])
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                <p class="text-xs text-gray-500 flex items-center gap-2"><i class="fas {{ $icone }} {{ $couleur }}"></i> {{ $titre }}</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">{{ $valeur }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $sous }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- ===== Compétition d'algorithmes ===== --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-900 mb-1">Compétition d'algorithmes</h2>
            <p class="text-xs text-gray-500 mb-4">F1 macro en validation croisée stratifiée (5 plis) — le meilleur est retenu automatiquement.</p>
            <div class="space-y-3">
                @foreach($metriques['comparaison_algorithmes'] as $algo)
                    @php $gagnant = $algo['algorithme'] === $metriques['algorithme']; @endphp
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="{{ $gagnant ? 'font-semibold text-indigo-700' : 'text-gray-600' }}">
                                @if($gagnant)<i class="fas fa-trophy text-amber-500"></i>@endif {{ $algo['algorithme'] }}
                            </span>
                            <span class="font-mono text-gray-700">{{ number_format($algo['f1_macro_cv'], 3) }} <span class="text-gray-400">± {{ number_format($algo['ecart_type'], 3) }}</span></span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div class="{{ $gagnant ? 'bg-indigo-500' : 'bg-gray-300' }} h-2 rounded-full" style="width: {{ $algo['f1_macro_cv'] * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-red-500"><i class="fas fa-history"></i> Ancien système à règles (if/else)</span>
                        <span class="font-mono text-gray-700">{{ number_format($t['f1_macro_ancien_systeme_regles'], 3) }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="bg-red-300 h-2 rounded-full" style="width: {{ $t['f1_macro_ancien_systeme_regles'] * 100 }}%"></div>
                    </div>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-4">
                Précision du modèle : <strong>{{ $pct($t['precision']) }}</strong> contre
                <strong>{{ $pct($t['precision_ancien_systeme_regles']) }}</strong> pour les anciennes règles.
                Log-loss : {{ $t['log_loss_calibre'] }} calibré / {{ $t['log_loss_non_calibre'] }} brut.
            </p>
        </div>

        {{-- ===== Fiabilité de la confiance ===== --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-900 mb-1">Fiabilité de la confiance</h2>
            <p class="text-xs text-gray-500 mb-4">Plus l'IA est sûre d'elle, plus elle a raison : la confiance affichée est exploitable.</p>
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-500 border-b">
                    <th class="py-2">Confiance</th><th>Part des lots</th><th>Précision</th></tr></thead>
                <tbody>
                @foreach($t['precision_par_confiance'] as $ligne)
                    <tr class="border-b border-gray-50">
                        <td class="py-2">≥ {{ $ligne['seuil'] * 100 }} %</td>
                        <td>{{ $pct($ligne['part_des_lots']) }}</td>
                        <td class="font-semibold">{{ $pct($ligne['precision']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <h3 class="font-semibold text-gray-900 text-sm mt-6 mb-3">Variables les plus influentes</h3>
            @foreach($metriques['importance_variables'] as $v)
                <div class="flex items-center gap-3 text-xs mb-1.5">
                    <span class="w-40 text-gray-600 truncate">{{ $libellesVariables[$v['variable']] ?? str_replace('type_', 'Type : ', $v['variable']) }}</span>
                    <div class="flex-1 bg-gray-100 rounded-full h-1.5">
                        <div class="bg-indigo-400 h-1.5 rounded-full" style="width: {{ min(100, $v['importance'] * 300) }}%"></div>
                    </div>
                    <span class="font-mono w-12 text-right text-gray-500">{{ number_format($v['importance'] * 100, 1) }} %</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- ===== Matrice de confusion ===== --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-900 mb-1">Matrice de confusion</h2>
            <p class="text-xs text-gray-500 mb-4">Lignes : vraie filière — colonnes : filière prédite.</p>
            @php
                $mc = $metriques['test']['matrice_confusion'];
                $max = max(array_map('max', $mc['valeurs'])) ?: 1;
            @endphp
            <div class="overflow-x-auto">
                <table class="text-xs w-full">
                    <thead><tr><th></th>
                        @foreach($mc['classes'] as $c)<th class="p-1 text-gray-500 font-medium">{{ LotTextile::libelleFiliere($c) }}</th>@endforeach
                    </tr></thead>
                    <tbody>
                    @foreach($mc['valeurs'] as $i => $ligne)
                        <tr>
                            <th class="p-1 text-left text-gray-500 font-medium whitespace-nowrap">{{ LotTextile::libelleFiliere($mc['classes'][$i]) }}</th>
                            @foreach($ligne as $j => $n)
                                @php $alpha = round(0.08 + 0.85 * $n / $max, 2); @endphp
                                <td class="p-1">
                                    <div class="rounded-md py-2 text-center font-semibold {{ $i === $j ? 'text-white' : 'text-gray-700' }}"
                                         style="background: rgba({{ $i === $j ? '79,70,229' : '239,68,68' }}, {{ $i === $j ? $alpha : min($alpha, 0.5) }})">
                                        {{ $n }}
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ===== Détail par filière ===== --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-900 mb-4">Détail par filière</h2>
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-500 border-b">
                    <th class="py-2">Filière</th><th>Précision</th><th>Rappel</th><th>F1</th><th>Lots</th></tr></thead>
                <tbody>
                @foreach($t['par_classe'] as $classe => $c)
                    <tr class="border-b border-gray-50">
                        <td class="py-2">{{ LotTextile::libelleFiliere($classe) }}</td>
                        <td>{{ $pct($c['precision']) }}</td>
                        <td>{{ $pct($c['rappel']) }}</td>
                        <td class="font-semibold">{{ number_format($c['f1'], 3) }}</td>
                        <td class="text-gray-400">{{ $c['support'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="text-xs text-gray-500 mt-4">
                Lots du module : {{ $sources['modele'] }} prédits par le modèle, {{ $sources['regles'] }} par les règles métier.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ===== Historique des versions ===== --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-900 mb-4">Historique des entraînements</h2>
            <table class="w-full text-sm">
                <thead><tr class="text-left text-xs text-gray-500 border-b">
                    <th class="py-2">Version</th><th>Date</th><th>Algorithme</th><th>Précision</th><th>Décisions réelles</th></tr></thead>
                <tbody>
                @foreach(array_reverse($metriques['historique'] ?? []) as $h)
                    <tr class="border-b border-gray-50 {{ $h['version'] === $metriques['version'] ? 'font-semibold' : '' }}">
                        <td class="py-2">v{{ $h['version'] }}</td>
                        <td>{{ \Carbon\Carbon::parse($h['date'])->format('d/m/Y H:i') }}</td>
                        <td>{{ $h['algorithme'] }}</td>
                        <td>{{ $pct($h['precision']) }}</td>
                        <td>{{ $h['decisions_recycleurs'] }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- ===== Données ===== --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-gray-900 mb-4">Données d'entraînement</h2>
            @php $d = $metriques['donnees']; @endphp
            <p class="text-sm text-gray-600 mb-3">
                <strong>{{ number_format($d['total'], 0, ',', ' ') }}</strong> lots :
                {{ number_format($d['synthetiques'], 0, ',', ' ') }} synthétiques calibrés
                + <strong>{{ $d['decisions_recycleurs'] }}</strong> décisions réelles de recycleurs (pondérées ×3).
            </p>
            <div class="flex h-3 rounded-full overflow-hidden mb-2">
                @foreach($d['repartition'] as $classe => $n)
                    <div class="{{ ['REVENTE' => 'bg-emerald-500', 'UPCYCLING' => 'bg-amber-500', 'RECYCLAGE_FIBRE' => 'bg-blue-500', 'RECYCLAGE_ENERGIE' => 'bg-red-500'][$classe] }}"
                         style="width: {{ $n / max(1, $d['total']) * 100 }}%" title="{{ LotTextile::libelleFiliere($classe) }}"></div>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-3 text-xs text-gray-500 mb-4">
                @foreach($d['repartition'] as $classe => $n)
                    <span>{{ LotTextile::libelleFiliere($classe) }} : {{ round($n / max(1, $d['total']) * 100) }} %</span>
                @endforeach
            </div>
            <p class="text-xs text-gray-500 leading-relaxed">
                Calibrage sur des données réelles : débouchés des textiles triés (Refashion 2023 : 58,6 % réemploi,
                32,5 % recyclage, 8,8 % valorisation énergétique), composition des textiles post-consommation
                (Sorting for Circularity, TU Wien 2024) et contraintes de l'élasthanne sur le recyclage mécanique.
            </p>
        </div>
    </div>
@endif
@endsection
