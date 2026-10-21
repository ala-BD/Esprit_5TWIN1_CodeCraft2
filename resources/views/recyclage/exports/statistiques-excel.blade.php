<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    @verbatim
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Bilan Recyclage</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    @endverbatim
    <style>
        body { font-family: 'Segoe UI', Calibri, Arial, sans-serif; font-size: 11pt; color: #1e293b; margin: 0; padding: 0; }
        table { border-collapse: collapse; table-layout: fixed; }
        
        /* ── EN-TÊTE CORPORATE ── */
        .brand-banner {
            background-color: #064e3b;
            color: #ffffff;
            padding: 16px 20px;
            vertical-align: middle;
            border: 2px solid #047857;
        }
        .brand-title { font-size: 18pt; font-weight: 900; color: #4ade80; letter-spacing: 1.5px; }
        .brand-subtitle { font-size: 11pt; font-weight: 600; color: #ffffff; margin-top: 4px; }
        .brand-meta { font-size: 9pt; color: #a7f3d0; margin-top: 4px; }
        
        /* ── TITRES DE SECTIONS ── */
        .section-header {
            background-color: #0f766e;
            color: #ffffff;
            font-size: 11pt;
            font-weight: bold;
            padding: 9px 14px;
            border: 1px solid #0d9488;
            letter-spacing: 0.5px;
        }
        
        /* ── MÉTADONNÉES / PROFIL ── */
        .meta-label {
            font-size: 9.5pt;
            font-weight: bold;
            color: #334155;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 7px 12px;
            vertical-align: middle;
            white-space: nowrap;
        }
        .meta-val {
            font-size: 9.5pt;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            padding: 7px 12px;
            vertical-align: middle;
        }
        
        /* ── CARTES KPIS ── */
        .kpi-hdr {
            background-color: #0d9488;
            color: #ffffff;
            font-size: 9.5pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            padding: 8px 6px;
            border: 1px solid #0f766e;
            white-space: nowrap;
        }
        .kpi-val {
            background-color: #f0fdf4;
            color: #065f46;
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            padding: 12px 6px;
            border: 1px solid #86efac;
        }
        
        /* ── TABLEAUX DE DONNÉES ── */
        th.sub-hdr {
            background-color: #1e293b;
            color: #ffffff;
            font-size: 9.5pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #334155;
            padding: 8px 10px;
        }
        td.cell {
            font-size: 9.5pt;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            vertical-align: middle;
        }
        
        .row-even { background-color: #ffffff; }
        .row-odd  { background-color: #f8fafc; }
        
        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-bold   { font-weight: bold; }
        
        .total-row {
            background-color: #e2e8f0;
            font-weight: bold;
            color: #0f172a;
            border-top: 2px solid #0f766e;
            border-bottom: 2px solid #0f766e;
        }
    </style>
</head>
<body>

<table style="width: 840px;">
    {{-- Largeurs fixes strictes pour éviter tout tronquage --}}
    <colgroup>
        <col style="width: 200px;">
        <col style="width: 220px;">
        <col style="width: 200px;">
        <col style="width: 220px;">
    </colgroup>

    {{-- BANDEAU OFFICIEL RETISS --}}
    <tr>
        <td colspan="4" class="brand-banner">
            <div class="brand-title">♻ RETISS — TEXTILE CIRCULAIRE</div>
            <div class="brand-subtitle">Rapport d'Activité & Bilan Environnemental de Recyclage</div>
            <div class="brand-meta">Module M4 — Gestion et Traçabilité des Lots | Système Officiel</div>
        </td>
    </tr>

    <tr><td colspan="4" style="height: 10px;"></td></tr>

    {{-- 1. PROFIL DU CENTRE DE RECYCLAGE --}}
    <tr>
        <td colspan="4" class="section-header">1. PROFIL DU CENTRE DE RECYCLAGE</td>
    </tr>
    <tr>
        <td class="meta-label">Centre agréé :</td>
        <td class="meta-val"><strong>{{ $recycleur->nom }}</strong></td>
        <td class="meta-label">Agrément officiel :</td>
        <td class="meta-val"><strong>{{ $recycleur->agrement }}</strong></td>
    </tr>
    <tr>
        <td class="meta-label">Localisation :</td>
        <td class="meta-val">{{ $recycleur->localisation }}</td>
        <td class="meta-label">Capacité de traitement :</td>
        <td class="meta-val">{{ number_format($recycleur->capacite_kg, 0, ',', ' ') }} kg</td>
    </tr>
    <tr>
        <td class="meta-label">Statut opérationnel :</td>
        <td class="meta-val" style="color: {{ $recycleur->actif ? '#15803d' : '#dc2626' }}; font-weight: bold;">
            {{ $recycleur->actif ? '● Actif' : '○ Inactif' }}
        </td>
        <td class="meta-label">Date d'édition :</td>
        <td class="meta-val">{{ now()->format('d/m/Y à H:i') }}</td>
    </tr>

    <tr><td colspan="4" style="height: 14px;"></td></tr>

    {{-- 2. INDICATEURS CLÉS DE PERFORMANCE (KPIs) --}}
    <tr>
        <td colspan="4" class="section-header">2. INDICATEURS CLÉS DE PERFORMANCE (KPIs)</td>
    </tr>
    <tr>
        <th class="kpi-hdr">TOTAL LOTS</th>
        <th class="kpi-hdr">POIDS CUMULÉ (KG)</th>
        <th class="kpi-hdr">LOTS CERTIFIÉS</th>
        <th class="kpi-hdr">CAPACITÉ UTILISÉE</th>
    </tr>
    <tr>
        @php
            $pctCap = $recycleur->capacite_kg > 0 ? round(($stats['total_kg'] / $recycleur->capacite_kg) * 100) : 0;
            $pctCert = $stats['total_lots'] > 0 ? round(($stats['certifies'] / $stats['total_lots']) * 100) : 0;
        @endphp
        <td class="kpi-val">{{ $stats['total_lots'] }}</td>
        <td class="kpi-val">{{ number_format($stats['total_kg'], 1, ',', ' ') }} kg</td>
        <td class="kpi-val">{{ $stats['certifies'] }} ({{ $pctCert }}%)</td>
        <td class="kpi-val">{{ $pctCap }}%</td>
    </tr>

    <tr><td colspan="4" style="height: 14px;"></td></tr>

    {{-- 3. IMPACT ÉCOLOGIQUE ET PRÉSERVATION DES RESSOURCES --}}
    <tr>
        <td colspan="4" class="section-header">3. IMPACT ÉCOLOGIQUE ET PRÉSERVATION DES RESSOURCES</td>
    </tr>
    <tr>
        <th class="kpi-hdr" style="background-color: #047857;">ÉMISSIONS CO₂ ÉVITÉES</th>
        <th class="kpi-hdr" style="background-color: #0284c7;">EAU PRÉSERVÉE</th>
        <th class="kpi-hdr" style="background-color: #65a30d;">ARBRES ÉQUIVALENTS</th>
        <th class="kpi-hdr" style="background-color: #b45309;">MATIÈRE VALORISÉE</th>
    </tr>
    <tr>
        @php
            $co2Total = $stats['total_kg'] * 2.4;
            $eauTotal = $stats['total_kg'] * 3000;
            $arbres = round($co2Total / 25, 1);
        @endphp
        <td class="kpi-val" style="background-color: #ecfdf5; color: #065f46; border-color: #6ee7b7;">
            {{ number_format($co2Total, 1, ',', ' ') }} kg CO₂
        </td>
        <td class="kpi-val" style="background-color: #f0f9ff; color: #0369a1; border-color: #7dd3fc;">
            {{ number_format($eauTotal, 0, ',', ' ') }} Litres
        </td>
        <td class="kpi-val" style="background-color: #f7fee7; color: #3f6212; border-color: #bef264;">
            ≈ {{ $arbres }} arbres/an
        </td>
        <td class="kpi-val" style="background-color: #fffbeb; color: #92400e; border-color: #fde68a;">
            {{ number_format($stats['total_kg'], 1, ',', ' ') }} kg
        </td>
    </tr>

    <tr><td colspan="4" style="height: 14px;"></td></tr>

    {{-- 4. RÉPARTITION PAR FILIÈRE DE VALORISATION (IA) --}}
    <tr>
        <td colspan="4" class="section-header">4. RÉPARTITION PAR FILIÈRE DE VALORISATION (IA)</td>
    </tr>
    <tr>
        <th class="sub-hdr" colspan="2">Filière recommandée</th>
        <th class="sub-hdr">Nombre de lots</th>
        <th class="sub-hdr">Part du total (%)</th>
    </tr>
    @php
        $totF = max(array_sum($stats['filieres']), 1);
        $filieres = [
            'Revente (Seconde main)'     => $stats['filieres']['REVENTE'] ?? 0,
            'Upcycling (Transformation)' => $stats['filieres']['UPCYCLING'] ?? 0,
            'Recyclage matière (Fibre)'  => $stats['filieres']['RECYCLAGE_FIBRE'] ?? 0,
            'Recyclage énergie'          => $stats['filieres']['RECYCLAGE_ENERGIE'] ?? 0,
        ];
    @endphp
    @foreach($filieres as $nomF => $cnt)
        <tr class="{{ $loop->even ? 'row-even' : 'row-odd' }}">
            <td class="cell text-bold" colspan="2">{{ $nomF }}</td>
            <td class="cell text-center">{{ $cnt }}</td>
            <td class="cell text-center" style="mso-number-format:'0\%';">{{ round(($cnt / $totF) * 100) }}%</td>
        </tr>
    @endforeach
    <tr class="total-row">
        <td class="cell text-bold" colspan="2">TOTAL FILIÈRES</td>
        <td class="cell text-center text-bold">{{ array_sum($stats['filieres']) }}</td>
        <td class="cell text-center text-bold">100%</td>
    </tr>

    <tr><td colspan="4" style="height: 14px;"></td></tr>

    {{-- 5. ÉTAT D'AVANCEMENT DU PARC DE LOTS --}}
    <tr>
        <td colspan="4" class="section-header">5. ÉTAT D'AVANCEMENT DU PARC DE LOTS</td>
    </tr>
    <tr>
        <th class="sub-hdr" colspan="2">Statut opérationnel</th>
        <th class="sub-hdr">Nombre de lots</th>
        <th class="sub-hdr">Proportion (%)</th>
    </tr>
    @php
        $statutsRows = [
            ['En attente de traitement',    $stats['en_attente'],    '#fef08a', '#854d0e'],
            ['En cours de traitement',      $stats['en_traitement'], '#dbeafe', '#1e40af'],
            ['Traitement terminé',          $stats['traites'],       '#dcfce7', '#166534'],
            ['Certifié (Passeport émis)',   $stats['certifies'],     '#f3e8ff', '#6b21a8'],
        ];
    @endphp
    @foreach($statutsRows as $st)
        @php $pctS = $stats['total_lots'] > 0 ? round(($st[1] / $stats['total_lots']) * 100) : 0; @endphp
        <tr class="{{ $loop->even ? 'row-even' : 'row-odd' }}">
            <td class="cell text-bold" colspan="2">{{ $st[0] }}</td>
            <td class="cell text-center" style="background-color: {{ $st[2] }}; color: {{ $st[3] }}; font-weight: bold;">
                {{ $st[1] }}
            </td>
            <td class="cell text-center" style="mso-number-format:'0\%';">{{ $pctS }}%</td>
        </tr>
    @endforeach

    <tr><td colspan="4" style="height: 14px;"></td></tr>

    {{-- 6. HISTORIQUE SUR LES 12 DERNIERS MOIS --}}
    @if(isset($labelsM) && count($labelsM) > 0)
        <tr>
            <td colspan="4" class="section-header">6. ÉVOLUTION CHRONOLOGIQUE SUR LES 12 DERNIERS MOIS</td>
        </tr>
        <tr>
            <th class="sub-hdr">Mois</th>
            <th class="sub-hdr">Lots enregistrés</th>
            <th class="sub-hdr">Poids traité (kg)</th>
            <th class="sub-hdr">CO₂ évité (kg)</th>
        </tr>
        @foreach($labelsM as $idx => $moisNom)
            @php
                $nbL  = $seriesLots[$idx] ?? 0;
                $pds  = $seriesPoids[$idx] ?? 0;
                $co2M = $seriesCo2[$idx] ?? 0;
            @endphp
            <tr class="{{ $loop->even ? 'row-even' : 'row-odd' }}">
                <td class="cell text-bold text-center">{{ $moisNom }}</td>
                <td class="cell text-center">{{ $nbL }}</td>
                <td class="cell text-right" style="mso-number-format:'\#\,\#\#0\.0';">{{ number_format($pds, 1, '.', '') }}</td>
                <td class="cell text-right" style="mso-number-format:'\#\,\#\#0\.0';">{{ number_format($co2M, 1, '.', '') }}</td>
            </tr>
        @endforeach
        <tr class="total-row">
            <td class="cell text-bold text-center">CUMUL 12 MOIS</td>
            <td class="cell text-center text-bold">{{ array_sum($seriesLots) }}</td>
            <td class="cell text-right text-bold" style="mso-number-format:'\#\,\#\#0\.0';">{{ number_format(array_sum($seriesPoids), 1, '.', '') }}</td>
            <td class="cell text-right text-bold" style="mso-number-format:'\#\,\#\#0\.0';">{{ number_format(array_sum($seriesCo2), 1, '.', '') }}</td>
        </tr>
    @endif

    <tr><td colspan="4" style="height: 18px;"></td></tr>

    {{-- PIED DE PAGE ET CERTIFICATION --}}
    <tr>
        <td colspan="2" style="font-size: 8.5pt; color: #64748b; font-style: italic;">
            Rapport certifié édité par le système de traçabilité RETISS.
        </td>
        <td colspan="2" style="font-size: 8.5pt; color: #64748b; text-align: right;">
            Centre : {{ $recycleur->nom }} | Agrément : {{ $recycleur->agrement }}
        </td>
    </tr>
</table>

</body>
</html>
