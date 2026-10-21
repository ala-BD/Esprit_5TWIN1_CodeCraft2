<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bilan Recyclage — {{ $recycleur->nom }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1e293b; background: #fff; line-height: 1.4; }

        /* ── HEADER ── */
        .header {
            background: linear-gradient(135deg, #064e3b 0%, #0d9488 100%);
            color: #ffffff;
            padding: 20px 25px;
            display: table;
            width: 100%;
        }
        .header-left  { display: table-cell; vertical-align: middle; width: 65%; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; width: 35%; }

        .brand-table { display: table; }
        .brand-logo-cell { display: table-cell; vertical-align: middle; width: 48px; }
        .brand-logo-img  { width: 44px; height: 44px; border-radius: 8px; background: rgba(255,255,255,0.15); padding: 4px; }
        .brand-name-cell { display: table-cell; vertical-align: middle; padding-left: 12px; }
        .brand-name-cell strong { font-size: 18px; font-weight: 800; letter-spacing: 1.5px; display: block; color: #fff; }
        .brand-name-cell small  { font-size: 8.5px; color: rgba(255,255,255,0.75); display: block; margin-top: 2px; }

        .pill-badge { display: inline-block; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: #fff; font-size: 8.5px; font-weight: 700; padding: 3px 10px; border-radius: 12px; margin-bottom: 6px; }
        .header-title-doc { font-size: 14px; font-weight: 800; color: #fff; }
        .header-sub-doc   { font-size: 8px; color: rgba(255,255,255,0.75); margin-top: 3px; }

        .content { padding: 20px 25px; }

        /* ── PROFIL BANNER ── */
        .profile-banner {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #0d9488;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 16px;
            display: table;
            width: 100%;
        }
        .profile-col { display: table-cell; vertical-align: top; width: 50%; }
        .profile-row { font-size: 9.5px; margin-bottom: 3px; }
        .profile-label { color: #64748b; font-weight: 600; }
        .profile-val { color: #0f172a; font-weight: 700; }

        /* ── SECTION TITLES ── */
        .section-title {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0f766e;
            border-bottom: 1.5px solid #ccfbf1;
            padding-bottom: 4px;
            margin-bottom: 10px;
            margin-top: 14px;
        }

        /* ── KPIS ── */
        .kpi-table { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 14px; }
        .kpi-card {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            vertical-align: middle;
            width: 25%;
        }
        .kpi-card.blue   { background: #f0f9ff; border-color: #bae6fd; }
        .kpi-card.purple { background: #faf5ff; border-color: #e9d5ff; }
        .kpi-card.amber  { background: #fffbeb; border-color: #fde68a; }

        .kpi-number { font-size: 18px; font-weight: 800; color: #065f46; }
        .kpi-card.blue   .kpi-number { color: #0369a1; }
        .kpi-card.purple .kpi-number { color: #6b21a8; }
        .kpi-card.amber  .kpi-number { color: #92400e; }

        .kpi-caption { font-size: 8px; font-weight: 700; text-transform: uppercase; color: #475569; margin-top: 2px; }
        .kpi-sub     { font-size: 7.5px; color: #94a3b8; margin-top: 2px; }

        /* ── TABLES ── */
        .data-tbl { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .data-tbl th {
            background-color: #0f766e;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: 700;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #0d9488;
        }
        .data-tbl td {
            font-size: 8.5px;
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        .data-tbl tr.even { background-color: #f8fafc; }

        .badge-status {
            display: inline-block;
            font-size: 7.5px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .badge-green  { background: #dcfce7; color: #166534; }
        .badge-blue   { background: #dbeafe; color: #1e40af; }
        .badge-yellow { background: #fef08a; color: #854d0e; }
        .badge-purple { background: #f3e8ff; color: #6b21a8; }

        /* ── IMPACT BOX ── */
        .impact-box {
            background: linear-gradient(135deg, #064e3b, #047857);
            color: #fff;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 14px;
            display: table;
            width: 100%;
        }
        .impact-item { display: table-cell; width: 33.33%; text-align: center; vertical-align: middle; }
        .impact-val  { font-size: 16px; font-weight: 800; color: #4ade80; }
        .impact-lbl  { font-size: 8px; color: rgba(255,255,255,0.8); margin-top: 2px; }

        /* ── FOOTER & SIGNATURE ── */
        .footer-sig { display: table; width: 100%; margin-top: 15px; }
        .sig-left   { display: table-cell; width: 60%; vertical-align: top; font-size: 8px; color: #94a3b8; }
        .sig-right  { display: table-cell; width: 40%; vertical-align: top; text-align: center; border: 1px dashed #cbd5e1; border-radius: 6px; padding: 10px; background: #fafafa; }
        .sig-title  { font-size: 8.5px; font-weight: 700; color: #475569; margin-bottom: 30px; }
        .sig-name   { font-size: 8.5px; font-weight: 700; color: #0f172a; }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <div class="header">
        <div class="header-left">
            <div class="brand-table">
                @if(!empty($logoBase64))
                    <div class="brand-logo-cell">
                        <img src="{{ $logoBase64 }}" class="brand-logo-img" alt="RETISS">
                    </div>
                @endif
                <div class="brand-name-cell">
                    <strong>RETISS</strong>
                    <small>Plateforme Numérique de Traçabilité & Recyclage Textile</small>
                </div>
            </div>
        </div>
        <div class="header-right">
            <div class="pill-badge">RAPPORT OFFICIEL</div>
            <div class="header-title-doc">BILAN MENSUEL D'ACTIVITÉ</div>
            <div class="header-sub-doc">Édité le {{ now()->format('d/m/Y à H:i') }}</div>
        </div>
    </div>

    <div class="content">

        {{-- PROFIL RECYCLEUR --}}
        <div class="profile-banner">
            <div class="profile-col">
                <div class="profile-row"><span class="profile-label">Centre agréé : </span><span class="profile-val">{{ $recycleur->nom }}</span></div>
                <div class="profile-row"><span class="profile-label">Numéro d'agrément : </span><span class="profile-val">{{ $recycleur->agrement }}</span></div>
                <div class="profile-row"><span class="profile-label">Localisation : </span><span class="profile-val">{{ $recycleur->localisation }}</span></div>
            </div>
            <div class="profile-col" style="text-align: right;">
                <div class="profile-row"><span class="profile-label">Capacité nominale : </span><span class="profile-val">{{ number_format($recycleur->capacite_kg, 0, ',', ' ') }} kg</span></div>
                <div class="profile-row"><span class="profile-label">Statut opérationnel : </span><span class="profile-val" style="color: #16a34a;">● Certifié Actif</span></div>
                <div class="profile-row"><span class="profile-label">Responsable technique : </span><span class="profile-val">{{ Auth::user()->full_name ?? 'Karim Mansouri' }}</span></div>
            </div>
        </div>

        {{-- SECTION 1 : KPIS --}}
        <div class="section-title">1. Indicateurs Globaux d'Activité</div>
        <table class="kpi-table">
            <tr>
                <td class="kpi-card">
                    <div class="kpi-number">{{ $stats['total_lots'] }}</div>
                    <div class="kpi-caption">Lots Enregistrés</div>
                    <div class="kpi-sub">Total cumulé</div>
                </td>
                <td class="kpi-card blue">
                    <div class="kpi-number">{{ number_format($stats['total_kg'], 1) }}</div>
                    <div class="kpi-caption">Kg Traités</div>
                    <div class="kpi-sub">Matière valorisée</div>
                </td>
                <td class="kpi-card purple">
                    <div class="kpi-number">{{ $stats['certifies'] }}</div>
                    <div class="kpi-caption">Lots Certifiés</div>
                    <div class="kpi-sub">{{ $stats['total_lots'] > 0 ? round(($stats['certifies'] / $stats['total_lots']) * 100) : 0 }}% avec passeport</div>
                </td>
                <td class="kpi-card amber">
                    @php $pctCap = $recycleur->capacite_kg > 0 ? round(($stats['total_kg'] / $recycleur->capacite_kg) * 100) : 0; @endphp
                    <div class="kpi-number">{{ $pctCap }}%</div>
                    <div class="kpi-caption">Capacité Utilisée</div>
                    <div class="kpi-sub">Sur {{ number_format($recycleur->capacite_kg) }} kg</div>
                </td>
            </tr>
        </table>

        {{-- SECTION 2 : IMPACT ÉCOLOGIQUE --}}
        <div class="section-title">2. Bilan de Préservation Environnementale</div>
        <div class="impact-box">
            @php
                $co2Tot = $stats['total_kg'] * 2.4;
                $eauTot = $stats['total_kg'] * 3000;
                $arbresTot = round($co2Tot / 25, 1);
            @endphp
            <div class="impact-item">
                <div class="impact-val">{{ number_format($co2Tot, 1) }} kg</div>
                <div class="impact-lbl">Émissions CO₂ évitées</div>
            </div>
            <div class="impact-item">
                <div class="impact-val">{{ number_format($eauTot, 0, ',', ' ') }} L</div>
                <div class="impact-lbl">Eau potable préservée</div>
            </div>
            <div class="impact-item">
                <div class="impact-val">≈ {{ $arbresTot }}</div>
                <div class="impact-lbl">Arbres sauvés équivalents</div>
            </div>
        </div>

        {{-- SECTION 3 : DEUX TABLEAUX CÔTE À CÔTE --}}
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
            <tr>
                <td style="width: 48%; vertical-align: top; padding-right: 10px;">
                    <div class="section-title">3. Répartition par Filière IA</div>
                    <table class="data-tbl">
                        <tr>
                            <th>Filière de Valorisation</th>
                            <th style="text-align: center; width: 50px;">Lots</th>
                            <th style="text-align: right; width: 60px;">Part</th>
                        </tr>
                        @php
                            $totF = max(array_sum($stats['filieres']), 1);
                            $fList = [
                                'Revente'           => $stats['filieres']['REVENTE'] ?? 0,
                                'Upcycling'         => $stats['filieres']['UPCYCLING'] ?? 0,
                                'Recyclage fibre'   => $stats['filieres']['RECYCLAGE_FIBRE'] ?? 0,
                                'Recyclage énergie' => $stats['filieres']['RECYCLAGE_ENERGIE'] ?? 0,
                            ];
                        @endphp
                        @foreach($fList as $labelF => $cntF)
                        <tr class="{{ $loop->even ? 'even' : '' }}">
                            <td><strong>{{ $labelF }}</strong></td>
                            <td style="text-align: center;">{{ $cntF }}</td>
                            <td style="text-align: right; color: #0f766e; font-weight: 700;">{{ round(($cntF / $totF) * 100) }}%</td>
                        </tr>
                        @endforeach
                    </table>
                </td>
                <td style="width: 48%; vertical-align: top; padding-left: 10px;">
                    <div class="section-title">4. État du Parc de Lots</div>
                    <table class="data-tbl">
                        <tr>
                            <th>Statut Opérationnel</th>
                            <th style="text-align: center; width: 50px;">Lots</th>
                            <th style="text-align: right; width: 60px;">Taux</th>
                        </tr>
                        @php
                            $stList = [
                                ['En attente',    $stats['en_attente'],    'badge-yellow'],
                                ['En traitement', $stats['en_traitement'], 'badge-blue'],
                                ['Traité',        $stats['traites'],       'badge-green'],
                                ['Certifié',      $stats['certifies'],     'badge-purple'],
                            ];
                        @endphp
                        @foreach($stList as $stItem)
                        @php $pctSt = $stats['total_lots'] > 0 ? round(($stItem[1] / $stats['total_lots']) * 100) : 0; @endphp
                        <tr class="{{ $loop->even ? 'even' : '' }}">
                            <td><span class="badge-status {{ $stItem[2] }}">{{ $stItem[0] }}</span></td>
                            <td style="text-align: center; font-weight: 700;">{{ $stItem[1] }}</td>
                            <td style="text-align: right; font-weight: 700;">{{ $pctSt }}%</td>
                        </tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        </table>

        {{-- SECTION 4 : HISTORIQUE MENSUEL --}}
        @if(isset($labelsM) && count($labelsM) > 0)
        <div class="section-title">5. Historique Chronologique (12 derniers mois)</div>
        <table class="data-tbl">
            <tr>
                <th>Mois</th>
                <th style="text-align: center;">Lots créés</th>
                <th style="text-align: right;">Poids traité (kg)</th>
                <th style="text-align: right;">Impact CO₂ (kg)</th>
            </tr>
            @foreach($labelsM as $i => $moisNom)
            <tr class="{{ $loop->even ? 'even' : '' }}">
                <td><strong>{{ $moisNom }}</strong></td>
                <td style="text-align: center;">{{ $seriesLots[$i] ?? 0 }}</td>
                <td style="text-align: right;">{{ number_format($seriesPoids[$i] ?? 0, 1) }} kg</td>
                <td style="text-align: right; color: #15803d; font-weight: 700;">{{ number_format($seriesCo2[$i] ?? 0, 1) }} kg CO₂</td>
            </tr>
            @endforeach
        </table>
        @endif

        {{-- FOOTER & SIGNATURE --}}
        <div class="footer-sig">
            <div class="sig-left">
                <p><strong>Note de conformité :</strong></p>
                <p>Ce document certifie l'exactitude des opérations de collecte, tri et valorisation réalisées par l'établissement agréé sous la plateforme RETISS.</p>
                <p style="margin-top: 4px;">Identifiant système : RETISS-M4-{{ strtoupper(substr(md5($recycleur->agrement . now()->format('Ymd')), 0, 12)) }}</p>
            </div>
            <div class="sig-right">
                <div class="sig-title">Cachet & Signature du Responsable</div>
                <div class="sig-name">{{ $recycleur->nom }}</div>
                <div style="font-size: 7.5px; color: #64748b; margin-top: 2px;">Agrément N° {{ $recycleur->agrement }}</div>
            </div>
        </div>

    </div>

</body>
</html>
