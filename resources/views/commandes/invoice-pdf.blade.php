<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $commande->numero }} — RETISS</title>
    <style>
        @page {
            margin: 25mm 20mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11pt;
            color: #1e293b;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: top;
        }
        .logo-title {
            font-size: 24pt;
            font-weight: 800;
            color: #0d9488;
            letter-spacing: -0.5px;
            margin: 0;
        }
        .logo-sub {
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #64748b;
            margin-top: 3px;
        }
        .invoice-title {
            font-size: 18pt;
            font-weight: 700;
            color: #1a2744;
            text-align: right;
            margin: 0;
        }
        .invoice-meta {
            text-align: right;
            font-size: 9pt;
            color: #64748b;
            margin-top: 5px;
        }
        .divider {
            height: 2px;
            background-color: #0d9488;
            margin: 18px 0;
        }
        .info-table td {
            vertical-align: top;
            width: 50%;
            padding: 8px 12px;
            background-color: #f8fafc;
            border-radius: 8px;
        }
        .section-label {
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #0d9488;
            margin-bottom: 6px;
        }
        .info-name {
            font-size: 11pt;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 3px;
        }
        .info-line {
            font-size: 9pt;
            color: #475569;
            margin: 2px 0;
        }
        /* Items table */
        .items-table {
            margin-top: 25px;
            border: 1px solid #e2e8f0;
        }
        .items-table th {
            background-color: #1a2744;
            color: #ffffff;
            font-size: 8.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 10px 12px;
            text-align: left;
        }
        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9.5pt;
            color: #334155;
        }
        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        /* Totals table */
        .totals-table {
            margin-top: 20px;
            width: 55%;
            margin-left: auto;
        }
        .totals-table td {
            padding: 6px 10px;
            font-size: 9.5pt;
        }
        .totals-table .total-row td {
            font-size: 12pt;
            font-weight: 800;
            color: #0d9488;
            border-top: 2px solid #0d9488;
            padding-top: 10px;
        }
        .remise-row td {
            color: #059669;
            font-weight: 600;
        }
        /* Eco Impact Box */
        .eco-box {
            margin-top: 28px;
            padding: 14px 18px;
            background-color: #f0fdf9;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
        }
        .eco-title {
            font-size: 9pt;
            font-weight: 700;
            color: #065f46;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .eco-text {
            font-size: 8.5pt;
            color: #047857;
            margin: 0;
            line-height: 1.4;
        }
        /* Footer */
        .footer {
            margin-top: 35px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            font-size: 8pt;
            color: #94a3b8;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 7.5pt;
            font-weight: 700;
            border-radius: 999px;
            background-color: #e0f2fe;
            color: #0369a1;
        }
    </style>
</head>
<body>

    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td>
                <div class="logo-title">RETISS</div>
                <div class="logo-sub">Plateforme d'Économie Circulaire Textile</div>
                <div style="font-size: 8.5pt; color: #64748b; margin-top: 6px;">
                    contact@retiss.tn · www.retiss.tn<br>
                    Tunis, Tunisie
                </div>
            </td>
            <td>
                <div class="invoice-title">FACTURE</div>
                <div class="invoice-meta">
                    <strong>N° :</strong> {{ $commande->numero }}<br>
                    <strong>Date de commande :</strong> {{ $commande->date_commande->format('d/m/Y') }}<br>
                    <strong>Statut :</strong> <span class="badge">{{ $commande->statutLabel() }}</span><br>
                    <strong>Mode de paiement :</strong> {{ \App\Models\Commande::MODES_PAIEMENT[$commande->mode_paiement] ?? $commande->mode_paiement }}
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    {{-- ADDRESS & CLIENT INFO --}}
    <table class="info-table" style="border-spacing: 12px 0; margin-left: -12px; margin-right: -12px;">
        <tr>
            <td>
                <div class="section-label">Émetteur / Plateforme</div>
                <div class="info-name">RETISS Textile Circulaire</div>
                <div class="info-line">Réseau solidaire d'ateliers et de donateurs</div>
                <div class="info-line">Gestionnaire de marketplace textile</div>
                <div class="info-line">Tunis, République Tunisienne</div>
            </td>
            <td>
                <div class="section-label">Facturé à & Adresse de livraison</div>
                <div class="info-name">{{ $commande->user->full_name ?: $commande->user->name }}</div>
                <div class="info-line"><strong>Rôle :</strong> {{ $commande->user->role }}</div>
                <div class="info-line"><strong>Email :</strong> {{ $commande->user->email }}</div>
                @if($commande->user->telephone)
                    <div class="info-line"><strong>Tél :</strong> {{ $commande->user->telephone }}</div>
                @endif
                <div class="info-line" style="margin-top: 5px;">
                    <strong>Livraison :</strong><br>
                    {{ $commande->adresse_snapshot ?? ($commande->adresse?->formatted_address ?? 'Non renseignée') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- ARTICLES TABLE --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 45%;">Désignation de l'article</th>
                <th style="width: 15%;">Vendeur</th>
                <th class="text-center" style="width: 10%;">Qté</th>
                <th class="text-right" style="width: 15%;">Prix unitaire</th>
                <th class="text-right" style="width: 15%;">Total DT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($commande->lignes as $ligne)
                @php $article = $ligne->article; @endphp
                <tr>
                    <td>
                        <strong>{{ $article->titre ?? 'Article' }}</strong>
                        @if($article && $article->categorie)
                            <div style="font-size: 8pt; color: #64748b;">{{ $article->categorie }}</div>
                        @endif
                    </td>
                    <td>
                        <span style="font-size: 8.5pt;">{{ $article->user->full_name ?? ($article->user->name ?? 'Vendeur') }}</span>
                    </td>
                    <td class="text-center">{{ $ligne->quantite }}</td>
                    <td class="text-right">
                        {{ number_format($ligne->prix_unitaire, 2) }} DT
                        @if($ligne->remise > 0)
                            <div style="font-size: 7.5pt; color: #059669;">- {{ number_format($ligne->remise, 2) }} DT (10%)</div>
                        @endif
                    </td>
                    <td class="text-right">
                        <strong>{{ number_format($ligne->total_ligne, 2) }} DT</strong>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALS BREAKDOWN --}}
    <table class="totals-table">
        <tr>
            <td class="text-right" style="color: #64748b;">Sous-total HT :</td>
            <td class="text-right" style="width: 110px;">{{ number_format($commande->montant_sous_total, 2) }} DT</td>
        </tr>
        @if($commande->remise > 0)
            <tr class="remise-row">
                <td class="text-right">Remise commerciale (10%) :</td>
                <td class="text-right">− {{ number_format($commande->remise, 2) }} DT</td>
            </tr>
        @endif
        <tr>
            <td class="text-right" style="color: #64748b;">Frais de livraison :</td>
            <td class="text-right">{{ number_format($commande->frais_livraison, 2) }} DT</td>
        </tr>
        <tr class="total-row">
            <td class="text-right">Total Net à payer :</td>
            <td class="text-right">{{ number_format($commande->montant_total, 2) }} DT</td>
        </tr>
    </table>

    {{-- ECOLOGICAL IMPACT STATEMENT --}}
    @php $impact = $commande->impactEcologique(); @endphp
    <div class="eco-box">
        <div class="eco-title">Bilan écologique certifié RETISS</div>
        <p class="eco-text">
            En choisissant la mode circulaire sur RETISS, cette commande a permis d'éviter 
            <strong>{{ $impact['co2_kg'] }} kg de CO₂</strong> et de préserver 
            <strong>{{ number_format($impact['eau_litres']) }} litres d'eau</strong>,
            sauvant ainsi {{ $impact['nb_articles'] }} pièce(s) textile(s) de l'enfouissement.
        </p>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        Facture générée automatiquement par la plateforme RETISS · Conforme aux standards d'économie circulaire.<br>
        Pour toute assistance ou suivi de livraison : support@retiss.tn ou scannez le QR code de suivi sur votre espace client.
    </div>

</body>
</html>
