<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bilan d'activité — {{ $recycleur->nom }}</title>
    @include('recyclage.exports.partials.pdf-charte')
    <style>
        .profil { background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #0d9488; border-radius: 6px; padding: 9px 12px; margin-top: 16px; }
        .profil td { padding: 1.5px 0; font-size: 8.5px; }
        .profil .l { color: #64748b; }
        .mois-barre { background: #f1f5f9; height: 7px; border-radius: 3px; }
        .mois-barre div { background: #0d9488; height: 7px; border-radius: 3px; }
    </style>
</head>
@use('App\Models\LotTextile')
@php
    $mois      = $bilan['mois'];
    $periode   = $mois[0]['label'] . ' — ' . end($mois)['label'];
    $maxKg     = max(1, max(array_column($mois, 'kg')));
    $total     = max(1, $bilan['total_lots']);
    $couleursF = ['REVENTE' => '#059669', 'UPCYCLING' => '#d97706', 'RECYCLAGE_FIBRE' => '#2563eb', 'RECYCLAGE_ENERGIE' => '#dc2626'];
    $statuts   = [
        'EN_ATTENTE'    => ['En attente',    '#f59e0b'],
        'EN_TRAITEMENT' => ['En traitement', '#3b82f6'],
        'TRAITE'        => ['Traité',        '#16a34a'],
        'CERTIFIE'      => ['Certifié',      '#7c3aed'],
    ];
    $capacite  = min(100, $bilan['capacite']);
    $couleurCap = $bilan['capacite'] >= 95 ? '#dc2626' : ($bilan['capacite'] >= 80 ? '#f59e0b' : '#0d9488');
@endphp
<body>

@include('recyclage.exports.partials.pdf-entete', [
    'type'      => 'Rapport — Module recyclage',
    'titre'     => "Bilan d'activité",
    'sousTitre' => 'Période : ' . $periode,
])

<div class="page">

    {{-- ── Profil du centre ── --}}
    <div class="profil">
        <table style="width: 100%;">
            <tr>
                <td style="width: 55%;"><span class="l">Centre agréé :</span> <strong>{{ $recycleur->nom }}</strong></td>
                <td class="right"><span class="l">Capacité nominale :</span> <strong>{{ number_format($recycleur->capacite_kg, 0, ',', ' ') }} kg</strong></td>
            </tr>
            <tr>
                <td><span class="l">Agrément :</span> <strong class="mono">{{ $recycleur->agrement }}</strong></td>
                <td class="right"><span class="l">Statut :</span>
                    <strong style="color: {{ $recycleur->actif ? '#16a34a' : '#dc2626' }};">{{ $recycleur->actif ? 'Actif' : 'Inactif' }}</strong></td>
            </tr>
            <tr>
                <td><span class="l">Localisation :</span> <strong>{{ $recycleur->localisation }}</strong></td>
                <td class="right"><span class="l">Édité le :</span> <strong>{{ now()->format('d/m/Y à H:i') }}</strong></td>
            </tr>
        </table>
    </div>

    {{-- ── Indicateurs ── --}}
    <div class="section">Indicateurs clés</div>
    <table class="grille">
        <tr>
            <td style="width: 25%;"><div class="kpi">
                <div class="kpi-valeur">{{ $bilan['total_lots'] }}</div>
                <div class="kpi-libelle">Lots enregistrés</div>
            </div></td>
            <td style="width: 25%;"><div class="kpi">
                <div class="kpi-valeur">{{ number_format($bilan['total_kg'], 1, ',', ' ') }} <span class="kpi-unite">kg</span></div>
                <div class="kpi-libelle">Textile traité</div>
            </div></td>
            <td style="width: 25%;"><div class="kpi" style="border-top-color: #16a34a;">
                <div class="kpi-valeur" style="color: #15803d;">{{ number_format($bilan['co2'], 1, ',', ' ') }} <span class="kpi-unite">kg</span></div>
                <div class="kpi-libelle">CO&#8322; évité</div>
            </div></td>
            <td style="width: 25%;"><div class="kpi" style="border-top-color: #2563eb;">
                <div class="kpi-valeur" style="color: #1d4ed8;">{{ number_format($bilan['eau'], 0, ',', ' ') }} <span class="kpi-unite">L</span></div>
                <div class="kpi-libelle">Eau économisée</div>
            </div></td>
        </tr>
    </table>

    <table style="width: 100%; margin-top: 10px;">
        <tr>
            <td style="width: 26%; font-size: 8px;" class="muted bold">CAPACITÉ UTILISÉE</td>
            <td><div class="barre"><div style="width: {{ $capacite }}%; background: {{ $couleurCap }};"></div></div></td>
            <td style="width: 16%;" class="right bold">{{ $bilan['capacite'] }} %
                <span class="muted" style="font-weight: normal;">/ {{ number_format($recycleur->capacite_kg, 0, ',', ' ') }} kg</span></td>
        </tr>
    </table>

    {{-- ── Répartitions ── --}}
    <table style="width: 100%;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 12px;">
                <div class="section">Répartition par filière IA</div>
                <table class="tableau">
                    <tr><th>Filière</th><th class="right" style="width: 32px;">Lots</th><th style="width: 42%;">Part</th></tr>
                    @foreach($bilan['filieres'] as $f => $n)
                        @php $pct = round($n / $total * 100); @endphp
                        <tr class="{{ $loop->even ? 'pair' : '' }}">
                            <td class="bold" style="color: {{ $couleursF[$f] }};">{{ LotTextile::libelleFiliere($f) }}</td>
                            <td class="right bold">{{ $n }}</td>
                            <td>
                                <table style="width: 100%;"><tr>
                                    <td style="padding: 0; border: 0; background: transparent;"><div class="barre"><div style="width: {{ $pct }}%; background: {{ $couleursF[$f] }};"></div></div></td>
                                    <td style="padding: 0 0 0 5px; border: 0; width: 28px; background: transparent;" class="right muted">{{ $pct }}%</td>
                                </tr></table>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 12px;">
                <div class="section">État du parc de lots</div>
                <table class="tableau">
                    <tr><th>Statut</th><th class="right" style="width: 32px;">Lots</th><th style="width: 42%;">Part</th></tr>
                    @foreach($statuts as $s => [$libelle, $couleur])
                        @php $n = $bilan['statuts'][$s] ?? 0; $pct = round($n / $total * 100); @endphp
                        <tr class="{{ $loop->even ? 'pair' : '' }}">
                            <td class="bold" style="color: {{ $couleur }};">{{ $libelle }}</td>
                            <td class="right bold">{{ $n }}</td>
                            <td>
                                <table style="width: 100%;"><tr>
                                    <td style="padding: 0; border: 0; background: transparent;"><div class="barre"><div style="width: {{ $pct }}%; background: {{ $couleur }};"></div></div></td>
                                    <td style="padding: 0 0 0 5px; border: 0; width: 28px; background: transparent;" class="right muted">{{ $pct }}%</td>
                                </tr></table>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    {{-- ── Évolution mensuelle ── --}}
    <div class="section">Évolution sur 12 mois</div>
    <table class="tableau">
        <tr>
            <th style="width: 17%;">Mois</th>
            <th class="right" style="width: 11%;">Lots</th>
            <th class="right" style="width: 16%;">Poids (kg)</th>
            <th class="right" style="width: 16%;">CO&#8322; évité (kg)</th>
            <th>Volume traité</th>
        </tr>
        @foreach($mois as $m)
            <tr class="{{ $loop->even ? 'pair' : '' }}">
                <td class="bold">{{ $m['label'] }}</td>
                <td class="right">{{ $m['lots'] }}</td>
                <td class="right">{{ number_format($m['kg'], 1, ',', ' ') }}</td>
                <td class="right" style="color: #15803d;">{{ number_format($m['co2'], 1, ',', ' ') }}</td>
                <td><div class="mois-barre"><div style="width: {{ round($m['kg'] / $maxKg * 100) }}%;"></div></div></td>
            </tr>
        @endforeach
        <tr class="total">
            <td>Total</td>
            <td class="right">{{ array_sum(array_column($mois, 'lots')) }}</td>
            <td class="right">{{ number_format(array_sum(array_column($mois, 'kg')), 1, ',', ' ') }}</td>
            <td class="right">{{ number_format(array_sum(array_column($mois, 'co2')), 1, ',', ' ') }}</td>
            <td></td>
        </tr>
    </table>

    <p class="muted" style="font-size: 7px; margin-top: 8px;">
        Facteurs d'impact appliqués : 2,4 kg de CO&#8322; et 3 000 L d'eau évités par kg de textile valorisé
        (mêmes facteurs que les passeports numériques).
    </p>
</div>

<table class="pied" style="width: 100%;">
    <tr>
        <td>RETISS — Plateforme de l'économie circulaire du textile<br>Bilan généré le {{ now()->format('d/m/Y à H:i') }}</td>
        <td class="right">{{ $recycleur->nom }} — Agrément {{ $recycleur->agrement }}</td>
    </tr>
</table>

</body>
</html>
