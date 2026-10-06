<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Passeport — {{ $lot->reference }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; background: #fff; }

        .header { background: #1B4332; color: white; padding: 24px 32px; display: flex; justify-content: space-between; align-items: center; }
        .brand  { font-size: 22px; font-weight: 900; letter-spacing: 2px; }
        .subtitle { font-size: 10px; color: rgba(255,255,255,0.7); margin-top: 4px; }
        .ref    { text-align: right; font-size: 10px; color: rgba(255,255,255,0.7); }
        .ref strong { font-size: 16px; color: white; display: block; font-family: monospace; }

        .body   { padding: 28px 32px; }
        .certified-badge { display: inline-block; background: #7C3AED; color: white; font-size: 10px; font-weight: 700; padding: 4px 12px; border-radius: 20px; margin-bottom: 16px; letter-spacing: 1px; }

        h2 { font-size: 18px; font-weight: 700; color: #1B4332; margin-bottom: 4px; }
        .date { font-size: 10px; color: #6b7280; margin-bottom: 20px; }

        .grid-2 { display: table; width: 100%; }
        .col    { display: table-cell; width: 50%; vertical-align: top; padding-right: 20px; }
        .col:last-child { padding-right: 0; }

        .section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; margin-bottom: 12px; }

        .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 11px; }
        .info-label { color: #6b7280; }
        .info-value { font-weight: 600; color: #111827; text-align: right; max-width: 200px; }

        .qr-box { text-align: center; border: 2px solid #e5e7eb; border-radius: 12px; padding: 16px; }
        .qr-code-label { font-family: monospace; font-size: 11px; font-weight: 700; margin-top: 8px; color: #111827; }
        .qr-hint { font-size: 9px; color: #9ca3af; margin-top: 4px; }

        .hash-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; margin-top: 12px; }
        .hash-label { font-size: 9px; color: #6b7280; margin-bottom: 4px; }
        .hash-value { font-family: monospace; font-size: 8px; color: #374151; word-break: break-all; }

        .impact-section { margin-top: 24px; }
        .impact-grid { display: table; width: 100%; border-collapse: separate; border-spacing: 8px; }
        .impact-card { display: table-cell; width: 33.33%; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 14px; text-align: center; }
        .impact-card.blue  { background: #eff6ff; border-color: #bfdbfe; }
        .impact-card.purple { background: #f5f3ff; border-color: #ddd6fe; }
        .impact-value { font-size: 20px; font-weight: 800; color: #15803d; }
        .impact-card.blue  .impact-value  { color: #1d4ed8; }
        .impact-card.purple .impact-value { color: #6d28d9; }
        .impact-label { font-size: 9px; color: #6b7280; margin-top: 4px; font-weight: 600; }

        .etapes-section { margin-top: 24px; }
        .etape-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; font-size: 11px; }
        .etape-dot-ok  { width: 16px; height: 16px; background: #22c55e; border-radius: 50%; flex-shrink: 0; text-align: center; line-height: 16px; color: white; font-size: 8px; }
        .etape-dot-no  { width: 16px; height: 16px; background: #e5e7eb; border-radius: 50%; flex-shrink: 0; }
        .etape-label   { color: #374151; font-weight: 600; flex: 1; }
        .etape-date    { color: #9ca3af; font-size: 10px; }

        .footer { margin-top: 28px; padding: 16px 32px; background: #f9fafb; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
        .footer-left  { font-size: 9px; color: #9ca3af; }
        .footer-right { background: #d1fae5; color: #065f46; font-size: 10px; font-weight: 700; padding: 4px 12px; border-radius: 20px; }
    </style>
</head>
<body>

    {{-- En-tête --}}
    <div class="header">
        <div>
            <div class="brand">RETISS</div>
            <div class="subtitle">Passeport Numérique du Textile Circulaire</div>
        </div>
        <div class="ref">
            Référence lot
            <strong>{{ $lot->reference }}</strong>
        </div>
    </div>

    <div class="body">
        <span class="certified-badge">✓ CERTIFIÉ</span>
        <h2>Passeport Numérique N° {{ $passeport->qr_code }}</h2>
        <p class="date">Émis le {{ $passeport->date_emission->format('d/m/Y à H:i') }}</p>

        {{-- Deux colonnes --}}
        <div class="grid-2">

            {{-- Infos lot --}}
            <div class="col">
                <p class="section-title">Informations du lot</p>
                <div class="info-row">
                    <span class="info-label">Composition</span>
                    <span class="info-value">{{ $lot->composition }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Poids</span>
                    <span class="info-value">{{ $lot->poids_kg }} kg</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Origine</span>
                    <span class="info-value">{{ $lot->origine }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Filière recommandée</span>
                    <span class="info-value">{{ $lot->filiere_ia_badge['label'] }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Recycleur</span>
                    <span class="info-value">{{ $lot->recycleur->nom }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Agrément</span>
                    <span class="info-value">{{ $lot->recycleur->agrement }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Localisation</span>
                    <span class="info-value">{{ $lot->recycleur->localisation }}</span>
                </div>

                <div class="hash-box">
                    <div class="hash-label">Hash d'intégrité SHA-256</div>
                    <div class="hash-value">{{ $passeport->hash_integrite }}</div>
                </div>
            </div>

            {{-- QR Code --}}
            <div class="col">
                <p class="section-title">Code QR de traçabilité</p>
                <div class="qr-box">
                    @php
                        // Générer le QR code en SVG (ne nécessite pas Imagick)
                        $qrUrl  = url('/passeport/' . $passeport->qr_code);
                        $qrSvg  = base64_encode(QrCode::format('svg')->size(150)->margin(1)->generate($qrUrl));
                    @endphp
                    <img src="data:image/svg+xml;base64,{{ $qrSvg }}" width="150" height="150">
                    <div class="qr-code-label">{{ $passeport->qr_code }}</div>
                    <div class="qr-hint">Scannez pour vérifier l'authenticité en ligne</div>
                </div>
            </div>
        </div>

        {{-- Impact --}}
        <div class="impact-section">
            <p class="section-title">Impact écologique certifié</p>
            <div class="impact-grid">
                <div class="impact-card">
                    <div class="impact-value">{{ $passeport->co2_evite_kg }}</div>
                    <div class="impact-label">kg CO₂ évités</div>
                </div>
                <div class="impact-card blue">
                    <div class="impact-value">{{ number_format($passeport->eau_economisee_l, 0) }}</div>
                    <div class="impact-label">litres eau économisés</div>
                </div>
                <div class="impact-card purple">
                    <div class="impact-value">{{ $lot->poids_kg }}</div>
                    <div class="impact-label">kg textiles recyclés</div>
                </div>
            </div>
        </div>

        {{-- Étapes --}}
        <div class="etapes-section">
            <p class="section-title">Chaîne de traitement vérifiée</p>
            @foreach(\App\Models\EtapeTraitement::LABELS as $type => $label)
                @php $etape = $lot->etapeTraitements->where('type', $type)->first(); @endphp
                <div class="etape-row">
                    @if($etape && $etape->date_fin)
                        <div class="etape-dot-ok">✓</div>
                        <span class="etape-label">{{ $label }}</span>
                        <span class="etape-date">{{ $etape->date_fin->format('d/m/Y') }}</span>
                    @else
                        <div class="etape-dot-no"></div>
                        <span class="etape-label" style="color:#9ca3af">{{ $label }}</span>
                        <span class="etape-date">—</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Pied de page --}}
    <div class="footer">
        <div class="footer-left">
            RETISS — Plateforme de l'économie circulaire du textile<br>
            Document généré le {{ now()->format('d/m/Y à H:i') }} — Vérifiable sur retiss.tn/passeport/{{ $passeport->qr_code }}
        </div>
        <div class="footer-right">✓ Document authentique</div>
    </div>

</body>
</html>
