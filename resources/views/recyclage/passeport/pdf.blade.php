<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Passeport numérique — {{ $lot->reference }}</title>
    @include('recyclage.exports.partials.pdf-charte')
    <style>
        .titre-zone td { vertical-align: middle; }
        .surtitre { font-size: 8px; letter-spacing: 2px; color: #0d9488; font-weight: bold; }
        .titre-lot { font-size: 21px; font-weight: bold; color: #0f172a; margin-top: 2px; }
        .certifie { background: #dcfce7; color: #166534; border: 1px solid #86efac; padding: 5px 12px; border-radius: 12px;
                    font-size: 9px; font-weight: bold; letter-spacing: 1px; }
        .numero { background: #f0fdfa; border: 1px solid #99f6e4; border-left: 4px solid #0d9488; border-radius: 6px; padding: 8px 12px; margin-top: 12px; }
        .numero td { vertical-align: middle; }
        .qr { border: 1px solid #e2e8f0; border-top: 3px solid #1a2030; border-radius: 8px; padding: 12px 10px 10px; text-align: center; }
        .qr-code { font-size: 8.5px; font-weight: bold; color: #0f172a; margin-top: 6px; }
        .qr-aide { font-size: 7px; color: #64748b; margin-top: 3px; }
        .qr-url { font-size: 6.5px; color: #0d9488; margin-top: 3px; }
        .impact { border-radius: 8px; padding: 11px 8px; text-align: center; }
        .impact-valeur { font-size: 19px; font-weight: bold; }
        .impact-libelle { font-size: 7px; letter-spacing: 1px; text-transform: uppercase; font-weight: bold; margin-top: 3px; }
        .ok { color: #16a34a; font-size: 11px; font-weight: bold; }
        .attente { color: #cbd5e1; font-size: 11px; }
        .sceau { border: 1.5px dashed #0d9488; border-radius: 8px; padding: 10px 12px; }
    </style>
</head>
@php
    $circuit   = $lot->circuit_etapes;   // circuit de la filière + étapes effectuées hors circuit
    $etapes    = $lot->etapeTraitements->keyBy('type');
    $qrUrl     = url('/passeport/' . $passeport->qr_code);
    $qrSvg     = base64_encode(QrCode::format('svg')->size(150)->margin(0)->color(26, 32, 48)->generate($qrUrl));
    $filiere   = \App\Models\LotTextile::libelleFiliere($lot->filiere_finale);
@endphp
<body>

@include('recyclage.exports.partials.pdf-entete', [
    'type'      => 'Passeport numérique',
    'titre'     => 'Certificat de traçabilité',
    'sousTitre' => 'Émis le ' . $passeport->date_emission->format('d/m/Y à H:i'),
])

<div class="page">

    {{-- ── Titre ── --}}
    <table class="titre-zone" style="width: 100%; margin-top: 18px;">
        <tr>
            <td>
                <div class="surtitre">CERTIFICAT DE VALORISATION TEXTILE</div>
                <div class="titre-lot">Lot {{ $lot->reference }}</div>
            </td>
            <td class="right"><span class="certifie">&#10003; CERTIFIÉ</span></td>
        </tr>
    </table>

    <div class="numero">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="muted" style="font-size: 7px; letter-spacing: 1.5px; font-weight: bold;">N° DE PASSEPORT</div>
                    <div class="mono bold" style="font-size: 12px; color: #0f766e;">{{ $passeport->qr_code }}</div>
                </td>
                <td class="right">
                    <div class="muted" style="font-size: 7px; letter-spacing: 1.5px; font-weight: bold;">FILIÈRE RETENUE</div>
                    <div class="bold" style="font-size: 12px; color: #0f172a;">{{ $filiere }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── Identification + QR ── --}}
    <table style="width: 100%; margin-top: 4px;">
        <tr>
            <td style="width: 63%; vertical-align: top; padding-right: 18px;">
                <div class="section">Identification du lot</div>
                <table class="infos">
                    <tr><td class="libelle">Composition</td><td class="valeur">{{ $lot->composition }}</td></tr>
                    <tr><td class="libelle">Poids</td><td class="valeur">{{ number_format($lot->poids_kg, 1, ',', ' ') }} kg</td></tr>
                    <tr><td class="libelle">Origine</td><td class="valeur">{{ $lot->origine }}</td></tr>
                    @if($lot->donVetement)
                        <tr><td class="libelle">Don d'origine</td><td class="valeur">N°{{ $lot->donVetement->id }} — {{ $lot->donVetement->type }}</td></tr>
                    @endif
                    <tr>
                        <td class="libelle">Recommandation IA</td>
                        <td class="valeur">
                            {{ \App\Models\LotTextile::libelleFiliere($lot->filiere_recommandee_ia) }}
                            @if($lot->score_confiance_ia !== null)
                                <span class="muted" style="font-weight: normal;">({{ round($lot->score_confiance_ia * 100) }} %)</span>
                            @endif
                        </td>
                    </tr>
                    <tr><td class="libelle">Enregistré le</td><td class="valeur">{{ $lot->created_at->format('d/m/Y') }}</td></tr>
                </table>

                <div class="section">Centre de recyclage</div>
                <table class="infos">
                    <tr><td class="libelle">Recycleur</td><td class="valeur">{{ $lot->recycleur->nom }}</td></tr>
                    <tr><td class="libelle">Agrément</td><td class="valeur mono">{{ $lot->recycleur->agrement }}</td></tr>
                    <tr><td class="libelle">Localisation</td><td class="valeur">{{ $lot->recycleur->localisation }}</td></tr>
                </table>
            </td>
            <td style="width: 37%; vertical-align: top;">
                <div class="section">Vérification</div>
                <div class="qr">
                    <img src="data:image/svg+xml;base64,{{ $qrSvg }}" width="138" height="138" alt="QR">
                    <div class="qr-code mono">{{ $passeport->qr_code }}</div>
                    <div class="qr-aide">Scannez pour vérifier l'authenticité en ligne</div>
                    <div class="qr-url">{{ $qrUrl }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ── Impact ── --}}
    <div class="section">Impact écologique certifié</div>
    <table class="grille">
        <tr>
            <td style="width: 33.3%;">
                <div class="impact" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
                    <div class="impact-valeur" style="color: #15803d;">{{ number_format($passeport->co2_evite_kg, 1, ',', ' ') }} <span style="font-size: 10px;">kg</span></div>
                    <div class="impact-libelle" style="color: #166534;">CO&#8322; évité</div>
                </div>
            </td>
            <td style="width: 33.3%;">
                <div class="impact" style="background: #eff6ff; border: 1px solid #bfdbfe;">
                    <div class="impact-valeur" style="color: #1d4ed8;">{{ number_format($passeport->eau_economisee_l, 0, ',', ' ') }} <span style="font-size: 10px;">L</span></div>
                    <div class="impact-libelle" style="color: #1e40af;">Eau économisée</div>
                </div>
            </td>
            <td style="width: 33.3%;">
                <div class="impact" style="background: #f0fdfa; border: 1px solid #99f6e4;">
                    <div class="impact-valeur" style="color: #0f766e;">{{ number_format($lot->poids_kg, 1, ',', ' ') }} <span style="font-size: 10px;">kg</span></div>
                    <div class="impact-libelle" style="color: #115e59;">Textile valorisé</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ── Chaîne de traitement ── --}}
    <div class="section">Chaîne de traitement — circuit {{ $filiere }}</div>
    <table class="tableau">
        <tr>
            <th style="width: 22px;"></th>
            <th>Étape</th>
            <th>Début</th>
            <th>Fin</th>
            <th class="right">Durée</th>
            <th class="right">Poids sortant</th>
        </tr>
        @foreach($circuit as $i => $type)
            @php $e = $etapes->get($type); @endphp
            <tr class="{{ $i % 2 ? 'pair' : '' }}">
                <td class="center">@if($e && $e->date_fin)<span class="ok">&#10003;</span>@else<span class="attente">&#9675;</span>@endif</td>
                <td class="bold">{{ \App\Models\EtapeTraitement::LABELS[$type] }}</td>
                <td>{{ $e?->date_debut?->format('d/m/Y H:i') ?? '—' }}</td>
                <td>{{ $e?->date_fin?->format('d/m/Y H:i') ?? '—' }}</td>
                <td class="right">{{ $e?->duree_heures !== null ? $e->duree_heures . ' h' : '—' }}</td>
                <td class="right">{{ $e?->poids_sortant_kg ? number_format($e->poids_sortant_kg, 1, ',', ' ') . ' kg' : '—' }}</td>
            </tr>
        @endforeach
    </table>

    {{-- ── Intégrité + sceau ── --}}
    <table style="width: 100%; margin-top: 16px;">
        <tr>
            <td style="width: 60%; vertical-align: top; padding-right: 14px;">
                <div class="carte" style="padding: 9px 11px; background: #f8fafc;">
                    <div style="font-size: 7px; letter-spacing: 1.5px; font-weight: bold; color: #0f766e;">&#10003; EMPREINTE D'INTÉGRITÉ SHA-256</div>
                    <div class="mono" style="font-size: 7px; color: #334155; margin-top: 4px; word-wrap: break-word;">{{ $passeport->hash_integrite }}</div>
                </div>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <div class="sceau">
                    <div style="font-size: 7px; letter-spacing: 1.5px; font-weight: bold; color: #0f766e;">DÉLIVRÉ PAR</div>
                    <div class="bold" style="font-size: 10px; margin-top: 2px;">{{ $lot->recycleur->nom }}</div>
                    <div class="muted" style="font-size: 7.5px;">le {{ $passeport->date_emission->format('d/m/Y') }} — via la plateforme RETISS</div>
                </div>
            </td>
        </tr>
    </table>
</div>

<table class="pied" style="width: 100%;">
    <tr>
        <td>RETISS — Plateforme de l'économie circulaire du textile<br>Document généré le {{ now()->format('d/m/Y à H:i') }}</td>
        <td class="right">Vérifiable en ligne : <span style="color: #0d9488;">{{ $qrUrl }}</span></td>
    </tr>
</table>

</body>
</html>
