<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Passeport Numérique — {{ $lot->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1a1a2e; background: #fff; }

        /* ── HEADER ── */
        .header {
            background: linear-gradient(135deg, #064e3b 0%, #0d9488 100%);
            color: white;
            padding: 0;
            display: table;
            width: 100%;
        }
        .header-left  { display: table-cell; padding: 24px 28px; vertical-align: middle; width: 60%; }
        .header-right { display: table-cell; padding: 24px 28px; vertical-align: middle; text-align: right; width: 40%; }
        .brand-logo   { display: table; }
        .brand-dot    { display: table-cell; width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 10px; text-align: center; vertical-align: middle; }
        .brand-dot span { font-size: 20px; font-weight: 900; color: white; }
        .brand-name   { display: table-cell; padding-left: 12px; vertical-align: middle; }
        .brand-name strong { font-size: 22px; font-weight: 900; letter-spacing: 2px; color: white; display: block; }
        .brand-name small  { font-size: 9px; color: rgba(255,255,255,0.7); display: block; margin-top: 2px; }
        .certified-pill { display: inline-block; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: white; font-size: 9px; font-weight: 700; padding: 4px 10px; border-radius: 20px; letter-spacing: 1px; margin-bottom: 8px; }
        .header-ref-label { font-size: 9px; color: rgba(255,255,255,0.6); }
        .header-ref-value { font-size: 18px; font-weight: 900; color: white; font-family: monospace; }
        .header-date  { font-size: 9px; color: rgba(255,255,255,0.6); margin-top: 4px; }

        /* ── BODY ── */
        .body { padding: 24px 28px; }

        /* ── PASSPORT NUMBER ── */
        .passport-number {
            background: #f0fdf4;
            border: 2px solid #bbf7d0;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: table;
            width: 100%;
        }
        .passport-number-left  { display: table-cell; vertical-align: middle; }
        .passport-number-right { display: table-cell; vertical-align: middle; text-align: right; }
        .passport-number-label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; }
        .passport-number-value { font-size: 16px; font-weight: 900; color: #065f46; font-family: monospace; }
        .passport-number-issued { font-size: 9px; color: #6b7280; }

        /* ── TWO COLUMNS ── */
        .two-col { display: table; width: 100%; border-spacing: 16px; }
        .col-left  { display: table-cell; width: 55%; vertical-align: top; padding-right: 12px; }
        .col-right { display: table-cell; width: 45%; vertical-align: top; }

        /* ── SECTION TITLES ── */
        .section-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9ca3af;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }

        /* ── INFO ROWS ── */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .info-table tr { border-bottom: 1px solid #f3f4f6; }
        .info-table tr:last-child { border-bottom: none; }
        .info-table td { padding: 5px 0; font-size: 11px; }
        .info-label { color: #6b7280; width: 40%; }
        .info-value { font-weight: 600; color: #111827; text-align: right; }

        /* ── QR BOX ── */
        .qr-box {
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            padding: 16px;
            text-align: center;
        }
        .qr-box img { display: block; margin: 0 auto; }
        .qr-code-text { font-family: monospace; font-size: 10px; font-weight: 700; color: #111827; margin-top: 8px; }
        .qr-hint { font-size: 8px; color: #9ca3af; margin-top: 4px; }
        .qr-url  { font-size: 8px; color: #0d9488; margin-top: 4px; word-break: break-all; }

        /* ── HASH ── */
        .hash-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; margin-top: 10px; }
        .hash-label { font-size: 8px; color: #6b7280; margin-bottom: 4px; font-weight: 700; }
        .hash-icon  { color: #22c55e; }
        .hash-value { font-family: monospace; font-size: 7.5px; color: #374151; word-break: break-all; line-height: 1.5; }

        /* ── IMPACT ── */
        .impact-section { margin-top: 20px; }
        .impact-grid { display: table; width: 100%; }
        .impact-card { display: table-cell; width: 33.33%; padding: 0 4px; vertical-align: top; }
        .impact-inner { border-radius: 12px; padding: 14px; text-align: center; }
        .impact-inner.green  { background: #f0fdf4; border: 1px solid #bbf7d0; }
        .impact-inner.blue   { background: #eff6ff; border: 1px solid #bfdbfe; }
        .impact-inner.purple { background: #f5f3ff; border: 1px solid #ddd6fe; }
        .impact-icon  { font-size: 18px; margin-bottom: 6px; }
        .impact-value { font-size: 18px; font-weight: 900; }
        .impact-value.green  { color: #15803d; }
        .impact-value.blue   { color: #1d4ed8; }
        .impact-value.purple { color: #6d28d9; }
        .impact-label { font-size: 8px; color: #6b7280; margin-top: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

        /* ── TIMELINE ÉTAPES ── */
        .timeline-section { margin-top: 20px; }
        .timeline-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9ca3af;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
            margin-bottom: 14px;
        }
        .timeline-grid { display: table; width: 100%; }
        .timeline-cell { display: table-cell; width: 16.66%; vertical-align: top; text-align: center; padding: 0 3px; }
        .timeline-connector {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .timeline-dot-wrap { display: table-cell; vertical-align: middle; text-align: center; width: 30%; }
        .timeline-line-wrap { display: table-cell; vertical-align: middle; width: 70%; }
        .timeline-dot-ok { width: 28px; height: 28px; background: #22c55e; border-radius: 50%; margin: 0 auto; display: table; }
        .timeline-dot-no { width: 28px; height: 28px; background: #e5e7eb; border-radius: 50%; margin: 0 auto; display: table; }
        .timeline-dot-inner { display: table-cell; text-align: center; vertical-align: middle; color: white; font-size: 10px; font-weight: 700; }
        .timeline-line-ok { height: 3px; background: #22c55e; border-radius: 2px; }
        .timeline-line-no { height: 3px; background: #e5e7eb; border-radius: 2px; }
        .timeline-label { font-size: 8px; color: #374151; font-weight: 600; text-align: center; line-height: 1.3; margin-top: 6px; }
        .timeline-label.done { color: #15803d; }
        .timeline-label.pending { color: #9ca3af; }
        .timeline-date { font-size: 7px; color: #9ca3af; text-align: center; margin-top: 3px; }

        /* Compact timeline pour PDF */
        .etape-list { width: 100%; border-collapse: collapse; }
        .etape-list tr { border-bottom: 1px solid #f3f4f6; }
        .etape-list td { padding: 5px 0; font-size: 10px; }
        .etape-dot-cell { width: 24px; }
        .dot-ok { width: 16px; height: 16px; background: #22c55e; border-radius: 50%; display: inline-block; text-align: center; line-height: 16px; color: white; font-size: 8px; font-weight: 700; }
        .dot-no { width: 16px; height: 16px; background: #e5e7eb; border-radius: 50%; display: inline-block; }
        .etape-name     { color: #374151; font-weight: 600; }
        .etape-name-no  { color: #9ca3af; }
        .etape-date-cell { text-align: right; color: #9ca3af; font-size: 9px; width: 80px; }

        /* ── FOOTER ── */
        .footer {
            margin-top: 22px;
            padding: 14px 28px;
            background: #f8fafc;
            border-top: 2px solid #e2e8f0;
            display: table;
            width: 100%;
        }
        .footer-left  { display: table-cell; vertical-align: middle; font-size: 8.5px; color: #94a3b8; }
        .footer-right { display: table-cell; vertical-align: middle; text-align: right; }
        .footer-badge { display: inline-block; background: #d1fae5; color: #065f46; font-size: 9px; font-weight: 700; padding: 5px 14px; border-radius: 20px; }

        /* ── PAGE DIVIDER ── */
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 18px 0; }

        .recycleur-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .recycleur-box .section-title { border-color: #fde68a; color: #92400e; }
    </style>
</head>
@php
    $logoPath = public_path('images/logo.png');
    $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
@endphp
<body>

    {{-- ── EN-TÊTE ── --}}
    <div class="header">
        <div class="header-left">
            <table style="border-collapse: collapse;">
                <tr>
                    @if(!empty($logoBase64))
                        <td style="width: 48px; vertical-align: middle;">
                            <img src="{{ $logoBase64 }}" style="width: 42px; height: 42px; border-radius: 8px; background: rgba(255,255,255,0.15); padding: 3px; display: block;" alt="RETISS">
                        </td>
                    @else
                        <td class="brand-dot"><span>R</span></td>
                    @endif
                    <td class="brand-name" style="padding-left: 12px; vertical-align: middle;">
                        <strong>RETISS</strong>
                        <small>Plateforme de l'économie circulaire du textile</small>
                    </td>
                </tr>
            </table>
        </div>
        <div class="header-right">
            <div class="certified-pill">✓ CERTIFIÉ</div>
            <div class="header-ref-label">Référence du lot</div>
            <div class="header-ref-value">{{ $lot->reference }}</div>
            <div class="header-date">Émis le {{ $passeport->date_emission->format('d/m/Y à H:i') }}</div>
        </div>
    </div>

    <div class="body">

        {{-- ── NUMÉRO PASSEPORT ── --}}
        <div class="passport-number">
            <div class="passport-number-left">
                <div class="passport-number-label">Passeport Numérique N°</div>
                <div class="passport-number-value">{{ $passeport->qr_code }}</div>
            </div>
            <div class="passport-number-right">
                <div class="passport-number-issued">Recycleur certifié</div>
                <div style="font-size: 12px; font-weight: 700; color: #065f46;">{{ $lot->recycleur->nom }}</div>
                <div style="font-size: 9px; color: #6b7280;">Agrément : {{ $lot->recycleur->agrement }}</div>
            </div>
        </div>

        {{-- ── DEUX COLONNES ── --}}
        <div class="two-col">

            {{-- Colonne gauche : Infos + Hash --}}
            <div class="col-left">
                <div class="section-title">Informations du lot</div>
                <table class="info-table">
                    <tr>
                        <td class="info-label">Composition</td>
                        <td class="info-value">{{ $lot->composition }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Poids</td>
                        <td class="info-value">{{ $lot->poids_kg }} kg</td>
                    </tr>
                    <tr>
                        <td class="info-label">Origine</td>
                        <td class="info-value">{{ $lot->origine }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Filière recommandée</td>
                        <td class="info-value">{{ $lot->filiere_ia_badge['label'] }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Localisation</td>
                        <td class="info-value">{{ $lot->recycleur->localisation }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Date de création</td>
                        <td class="info-value">{{ $lot->created_at->format('d/m/Y') }}</td>
                    </tr>
                </table>

                <div class="hash-box">
                    <div class="hash-label"><span class="hash-icon">✓</span> Hash d'intégrité SHA-256</div>
                    <div class="hash-value">{{ $passeport->hash_integrite }}</div>
                </div>
            </div>

            {{-- Colonne droite : QR Code --}}
            <div class="col-right">
                <div class="section-title">Code QR de traçabilité</div>
                <div class="qr-box">
                    @php
                        $qrUrl = url('/passeport/' . $passeport->qr_code);
                        $qrSvg = base64_encode(QrCode::format('svg')->size(160)->margin(1)->generate($qrUrl));
                    @endphp
                    <img src="data:image/svg+xml;base64,{{ $qrSvg }}" width="160" height="160" alt="QR Code">
                    <div class="qr-code-text">{{ $passeport->qr_code }}</div>
                    <div class="qr-hint">Scannez pour vérifier l'authenticité en ligne</div>
                    <div class="qr-url">{{ $qrUrl }}</div>
                </div>
            </div>
        </div>

        <hr class="divider">

        {{-- ── IMPACT ÉCOLOGIQUE ── --}}
        <div class="impact-section">
            <div class="section-title">Impact écologique certifié</div>
            <div class="impact-grid">
                <div class="impact-card">
                    <div class="impact-inner green">
                        <div class="impact-icon">🌿</div>
                        <div class="impact-value green">{{ $passeport->co2_evite_kg }}</div>
                        <div class="impact-label">kg CO₂ évités</div>
                    </div>
                </div>
                <div class="impact-card">
                    <div class="impact-inner blue">
                        <div class="impact-icon">💧</div>
                        <div class="impact-value blue">{{ number_format($passeport->eau_economisee_l, 0, ',', ' ') }}</div>
                        <div class="impact-label">litres d'eau économisés</div>
                    </div>
                </div>
                <div class="impact-card">
                    <div class="impact-inner purple">
                        <div class="impact-icon">♻️</div>
                        <div class="impact-value purple">{{ $lot->poids_kg }}</div>
                        <div class="impact-label">kg textiles recyclés</div>
                    </div>
                </div>
            </div>
        </div>

        <hr class="divider">

        {{-- ── TIMELINE ÉTAPES ── --}}
        <div class="timeline-section">
            <div class="timeline-title">Chaîne de traitement vérifiée</div>
            <table class="etape-list">
                @foreach(\App\Models\EtapeTraitement::LABELS as $type => $label)
                    @php $etape = $lot->etapeTraitements->where('type', $type)->first(); @endphp
                    <tr>
                        <td class="etape-dot-cell">
                            @if($etape && $etape->date_fin)
                                <span class="dot-ok">✓</span>
                            @else
                                <span class="dot-no"></span>
                            @endif
                        </td>
                        <td class="{{ ($etape && $etape->date_fin) ? 'etape-name' : 'etape-name-no' }}">
                            {{ $label }}
                        </td>
                        <td class="etape-date-cell">
                            @if($etape && $etape->date_fin)
                                {{ $etape->date_fin->format('d/m/Y') }}
                                @if($etape->duree_heures)
                                    ({{ $etape->duree_heures }}h)
                                @endif
                            @elseif($etape)
                                En cours
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>

    </div>

    {{-- ── PIED DE PAGE ── --}}
    <div class="footer">
        <div class="footer-left">
            RETISS — Plateforme de l'économie circulaire du textile<br>
            Document généré le {{ now()->format('d/m/Y à H:i') }} — Vérifiable sur : {{ url('/passeport/' . $passeport->qr_code) }}
        </div>
        <div class="footer-right">
            <span class="footer-badge">✓ Document authentique</span>
        </div>
    </div>

</body>
</html>
