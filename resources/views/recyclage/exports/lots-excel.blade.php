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
                    <x:Name>Lots Textiles</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                        <x:Print>
                            <x:ValidPrinterInfo/>
                            <x:PaperSizeIndex>9</x:PaperSizeIndex>
                            <x:HorizontalResolution>600</x:HorizontalResolution>
                            <x:VerticalResolution>600</x:VerticalResolution>
                        </x:Print>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    @endverbatim
    <style>
        body { font-family: 'Segoe UI', Calibri, Arial, sans-serif; font-size: 11pt; color: #1e293b; margin: 0; padding: 0; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        
        /* ── EN-TÊTE ── */
        .title-brand { font-size: 18pt; font-weight: 800; color: #064e3b; letter-spacing: 1px; }
        .title-sub   { font-size: 12pt; font-weight: 600; color: #0f766e; }
        .meta-label  { font-size: 9.5pt; font-weight: bold; color: #475569; background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 6px 10px; }
        .meta-val    { font-size: 9.5pt; color: #0f172a; border: 1px solid #cbd5e1; padding: 6px 10px; }
        
        /* ── CARTES KPI ── */
        .kpi-th { background-color: #0d9488; color: #ffffff; font-size: 9pt; font-weight: bold; text-align: center; padding: 6px; border: 1px solid #0f766e; }
        .kpi-td { background-color: #f0fdf4; color: #065f46; font-size: 14pt; font-weight: bold; text-align: center; padding: 10px; border: 1px solid #bbf7d0; }
        
        /* ── TABLEAU PRINCIPAL ── */
        th.col-hdr {
            background-color: #0f766e;
            color: #ffffff;
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #0d9488;
            padding: 10px 8px;
            white-space: nowrap;
        }
        td.data-cell {
            font-size: 9.5pt;
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            vertical-align: middle;
        }
        .row-even { background-color: #ffffff; }
        .row-odd  { background-color: #f8fafc; }
        
        /* ── BADGES & FORMATS ── */
        .badge-attente    { background-color: #fef08a; color: #854d0e; font-weight: bold; text-align: center; }
        .badge-traitement { background-color: #dbeafe; color: #1e40af; font-weight: bold; text-align: center; }
        .badge-traite     { background-color: #dcfce7; color: #166534; font-weight: bold; text-align: center; }
        .badge-certifie   { background-color: #f3e8ff; color: #6b21a8; font-weight: bold; text-align: center; }
        
        .filiere-revente   { background-color: #ecfdf5; color: #047857; font-weight: 600; text-align: center; }
        .filiere-upcycling { background-color: #fffbeb; color: #b45309; font-weight: 600; text-align: center; }
        .filiere-fibre     { background-color: #eff6ff; color: #1d4ed8; font-weight: 600; text-align: center; }
        .filiere-energie   { background-color: #fef2f2; color: #b91c1c; font-weight: 600; text-align: center; }
        
        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-bold   { font-weight: bold; }
        
        .total-row {
            background-color: #e2e8f0;
            font-weight: bold;
            font-size: 10.5pt;
            color: #0f172a;
            border-top: 2px solid #0f766e;
            border-bottom: 2px solid #0f766e;
        }
    </style>
</head>
<body>

<table>
    <colgroup>
        <col style="width: 140px;">
        <col style="width: 95px;">
        <col style="width: 160px;">
        <col style="width: 160px;">
        <col style="width: 130px;">
        <col style="width: 120px;">
        <col style="width: 100px;">
        <col style="width: 80px;">
        <col style="width: 150px;">
        <col style="width: 130px;">
        <col style="width: 110px;">
        <col style="width: 130px;">
        <col style="width: 140px;">
        <col style="width: 140px;">
    </colgroup>

    {{-- Ligne 1 : Bannière Corporate RETISS --}}
    <tr>
        <td colspan="14" style="background-color: #064e3b; color: #ffffff; padding: 16px 20px; border: 2px solid #047857;">
            <div style="font-size: 18pt; font-weight: 900; color: #4ade80; letter-spacing: 1.5px;">♻ RETISS — TEXTILE CIRCULAIRE</div>
            <div style="font-size: 11pt; font-weight: 600; color: #ffffff; margin-top: 4px;">Inventaire & Traçabilité des Lots de Recyclage</div>
            <div style="font-size: 9pt; color: #a7f3d0; margin-top: 4px;">Module M4 — Système Officiel d'Économie Circulaire</div>
        </td>
    </tr>

    {{-- Ligne vide --}}
    <tr><td colspan="14" style="height: 10px;"></td></tr>

    {{-- Informations du rapport --}}
    <tr>
        <td class="meta-label" colspan="2">Centre de recyclage :</td>
        <td class="meta-val" colspan="4"><strong>{{ $recycleur->nom }}</strong></td>
        <td class="meta-label" colspan="2">Date d'extraction :</td>
        <td class="meta-val" colspan="2">{{ now()->format('d/m/Y à H:i') }}</td>
        <td class="meta-label" colspan="2">Lots exportés :</td>
        <td class="meta-val text-bold" colspan="2">{{ $lots->count() }} lot(s)</td>
    </tr>
    <tr>
        <td class="meta-label" colspan="2">Numéro d'agrément :</td>
        <td class="meta-val" colspan="4">{{ $recycleur->agrement }}</td>
        <td class="meta-label" colspan="2">Localisation :</td>
        <td class="meta-val" colspan="2">{{ $recycleur->localisation }}</td>
        <td class="meta-label" colspan="2">Généré par :</td>
        <td class="meta-val" colspan="2">{{ Auth::user()->full_name ?? 'Karim Mansouri' }}</td>
    </tr>

    {{-- Ligne vide --}}
    <tr><td colspan="14" style="height: 14px;"></td></tr>

    {{-- Ligne KPI Cards --}}
    <tr>
        <td colspan="3" class="kpi-th">TOTAL LOTS</td>
        <td colspan="3" class="kpi-th">POIDS TOTAL TRAITÉ</td>
        <td colspan="3" class="kpi-th">ÉCONOMIE CO₂ ESTIMÉE</td>
        <td colspan="3" class="kpi-th">EAU PRÉSERVÉE</td>
        <td colspan="2" class="kpi-th">LOTS CERTIFIÉS</td>
    </tr>
    <tr>
        @php
            $totKg = $lots->sum('poids_kg');
            $totCo2 = $totKg * 2.4;
            $totEau = $totKg * 3000;
            $totCert = $lots->where('statut', 'CERTIFIE')->count();
        @endphp
        <td colspan="3" class="kpi-td">{{ $lots->count() }}</td>
        <td colspan="3" class="kpi-td">{{ number_format($totKg, 1, ',', ' ') }} kg</td>
        <td colspan="3" class="kpi-td">{{ number_format($totCo2, 1, ',', ' ') }} kg CO₂</td>
        <td colspan="3" class="kpi-td">{{ number_format($totEau, 0, ',', ' ') }} L</td>
        <td colspan="2" class="kpi-td">{{ $totCert }}</td>
    </tr>

    {{-- Ligne vide --}}
    <tr><td colspan="14" style="height: 18px;"></td></tr>

    {{-- EN-TÊTE DU TABLEAU DE DONNÉES --}}
    <tr>
        <th class="col-hdr" style="width: 140px;">Référence Lot</th>
        <th class="col-hdr" style="width: 95px;">Poids (kg)</th>
        <th class="col-hdr" style="width: 150px;">Composition textile</th>
        <th class="col-hdr" style="width: 150px;">Origine collecte</th>
        <th class="col-hdr" style="width: 130px;">Filière IA</th>
        <th class="col-hdr" style="width: 120px;">Statut actuel</th>
        <th class="col-hdr" style="width: 95px;">Progression</th>
        <th class="col-hdr" style="width: 85px;">Étapes</th>
        <th class="col-hdr" style="width: 140px;">Don rattaché</th>
        <th class="col-hdr" style="width: 130px;">Passeport QR</th>
        <th class="col-hdr" style="width: 105px;">CO₂ évité (kg)</th>
        <th class="col-hdr" style="width: 125px;">Eau sauvée (L)</th>
        <th class="col-hdr" style="width: 135px;">Date création</th>
        <th class="col-hdr" style="width: 135px;">Dernière MAJ</th>
    </tr>

    {{-- LIGNES DE DONNÉES --}}
    @forelse($lots as $i => $lot)
        @php
            $rowClass = ($i % 2 === 0) ? 'row-even' : 'row-odd';
            $co2 = round($lot->poids_kg * 2.4, 2);
            $eau = round($lot->poids_kg * 3000, 0);

            // Badge statut
            $statusClass = match($lot->statut) {
                'EN_ATTENTE'    => 'badge-attente',
                'EN_TRAITEMENT' => 'badge-traitement',
                'TRAITE'        => 'badge-traite',
                'CERTIFIE'      => 'badge-certifie',
                default         => ''
            };

            // Filière label & class
            $filiereClass = match($lot->filiere_recommandee_ia) {
                'REVENTE'           => 'filiere-revente',
                'UPCYCLING'         => 'filiere-upcycling',
                'RECYCLAGE_FIBRE'   => 'filiere-fibre',
                'RECYCLAGE_ENERGIE' => 'filiere-energie',
                default             => ''
            };
            $filiereLabel = match($lot->filiere_recommandee_ia) {
                'REVENTE'           => 'Revente',
                'UPCYCLING'         => 'Upcycling',
                'RECYCLAGE_FIBRE'   => 'Recyclage fibre',
                'RECYCLAGE_ENERGIE' => 'Recyclage énergie',
                default             => $lot->filiere_recommandee_ia ?? '—'
            };
        @endphp
        <tr class="{{ $rowClass }}">
            <td class="data-cell text-bold" style="mso-number-format:'\@';">{{ $lot->reference }}</td>
            <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0';">{{ number_format($lot->poids_kg, 1, '.', '') }}</td>
            <td class="data-cell">{{ $lot->composition }}</td>
            <td class="data-cell">{{ $lot->origine }}</td>
            <td class="data-cell {{ $filiereClass }}">{{ $filiereLabel }}</td>
            <td class="data-cell {{ $statusClass }}">{{ $lot->statut_label }}</td>
            <td class="data-cell text-center" style="mso-number-format:'0\%';">{{ $lot->progression }}%</td>
            <td class="data-cell text-center">{{ $lot->etapeTraitements->count() }}</td>
            <td class="data-cell">
                @if($lot->donVetement)
                    Don #{{ $lot->donVetement->id }} ({{ $lot->donVetement->type }})
                @else
                    —
                @endif
            </td>
            <td class="data-cell text-center" style="mso-number-format:'\@';">
                {{ $lot->passeportNumerique?->qr_code ?? '—' }}
            </td>
            <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0\.0';">{{ number_format($co2, 1, '.', '') }}</td>
            <td class="data-cell text-right" style="mso-number-format:'\#\,\#\#0';">{{ number_format($eau, 0, '.', '') }}</td>
            <td class="data-cell text-center" style="mso-number-format:'dd\/mm\/yyyy hh\:mm';">{{ $lot->created_at->format('d/m/Y H:i') }}</td>
            <td class="data-cell text-center" style="mso-number-format:'dd\/mm\/yyyy hh\:mm';">{{ $lot->updated_at->format('d/m/Y H:i') }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="14" class="data-cell text-center" style="padding: 20px; color: #94a3b8;">
                Aucun lot correspondant aux critères de recherche.
            </td>
        </tr>
    @endforelse

    {{-- LIGNE DE TOTAUX --}}
    <tr class="total-row">
        <td class="data-cell text-bold" style="padding: 10px;">TOTAL ({{ $lots->count() }} lots)</td>
        <td class="data-cell text-right text-bold" style="mso-number-format:'\#\,\#\#0\.0';">{{ number_format($totKg, 1, '.', '') }}</td>
        <td class="data-cell" colspan="8"></td>
        <td class="data-cell text-right text-bold" style="mso-number-format:'\#\,\#\#0\.0';">{{ number_format($totCo2, 1, '.', '') }}</td>
        <td class="data-cell text-right text-bold" style="mso-number-format:'\#\,\#\#0';">{{ number_format($totEau, 0, '.', '') }}</td>
        <td class="data-cell" colspan="2"></td>
    </tr>

    {{-- Signature / Bas de page --}}
    <tr><td colspan="14" style="height: 20px;"></td></tr>
    <tr>
        <td colspan="7" style="font-size: 8.5pt; color: #64748b; font-style: italic;">
            Document officiel généré automatiquement par la plateforme RETISS Textile Cycle — Traçabilité garantie.
        </td>
        <td colspan="7" style="font-size: 8.5pt; color: #64748b; text-align: right;">
            Certifié conforme par {{ $recycleur->nom }} (Agrément {{ $recycleur->agrement }})
        </td>
    </tr>
</table>

</body>
</html>
