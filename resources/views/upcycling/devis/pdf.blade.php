<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Devis {{ $numero }}</title>
    {{-- Charte PDF commune RETISS (module M4) --}}
    @include('recyclage.exports.partials.pdf-charte')
    <style>
        .parties td { vertical-align: top; width: 50%; }
        .partie { border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; }
        .partie-titre { font-size: 7.5px; letter-spacing: 1.5px; color: #0d9488; font-weight: bold; text-transform: uppercase; }
        .partie-nom { font-size: 11px; font-weight: bold; color: #0f172a; margin-top: 3px; }
        .total-zone { background: #f0fdfa; border: 1px solid #99f6e4; border-left: 4px solid #0d9488; border-radius: 6px; padding: 12px 14px; }
        .total-montant { font-size: 22px; font-weight: bold; color: #0f766e; }
        .statut-accepte { background: #dcfce7; color: #166534; }
        .statut-attente { background: #fef9c3; color: #854d0e; }
        .statut-refuse  { background: #fee2e2; color: #991b1b; }
        .etapes li { margin: 0 0 3px 14px; }
    </style>
</head>
@php
    $idee   = $projet->idee_retenue;
    $qrSvg  = base64_encode(QrCode::format('svg')->size(110)->margin(0)->color(26, 32, 48)
                ->generate(route('upcycling.projets.show', $projet)));
    $statut = ['ACCEPTE' => ['Accepté', 'statut-accepte'], 'REFUSE' => ['Refusé', 'statut-refuse']][$devis->statut]
              ?? ['En attente de réponse', 'statut-attente'];
    $valide = $devis->date_emission->copy()->addDays(15);
@endphp
<body>

@include('recyclage.exports.partials.pdf-entete', [
    'type'      => 'Devis upcycling',
    'titre'     => $numero,
    'sousTitre' => 'Émis le ' . $devis->date_emission->format('d/m/Y') . ' · valable jusqu\'au ' . $valide->format('d/m/Y'),
])

<div class="page">

    {{-- Parties --}}
    <table class="parties" style="width: 100%; margin-top: 18px; border-collapse: separate; border-spacing: 8px 0; margin-left: -8px;">
        <tr>
            <td>
                <div class="partie">
                    <div class="partie-titre">Atelier</div>
                    <div class="partie-nom">{{ $projet->atelier->nom }}</div>
                    <div class="muted">{{ $projet->atelier->localisation }}</div>
                    <div class="muted">{{ $projet->atelier->user->email }}</div>
                </div>
            </td>
            <td>
                <div class="partie">
                    <div class="partie-titre">Client</div>
                    <div class="partie-nom">{{ $projet->client->full_name }}</div>
                    <div class="muted">{{ $projet->client->email }}</div>
                    @if($projet->client->telephone)<div class="muted">{{ $projet->client->telephone }}</div>@endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Prestation --}}
    <div class="section">Prestation</div>
    <table class="tableau">
        <thead>
            <tr><th>Désignation</th><th>Vêtement d'origine</th><th class="right">Délai</th><th class="right">Montant</th></tr>
        </thead>
        <tbody>
            <tr>
                <td><span class="bold">{{ $projet->produit_final }}</span><br><span class="muted">{{ \App\Models\Atelier::SPECIALITES[$projet->categorie_produit] ?? '' }}</span></td>
                <td>{{ $projet->type_vetement }} en {{ mb_strtolower($projet->matiere) }}{{ $projet->couleur ? ', ' . mb_strtolower($projet->couleur) : '' }}<br>
                    <span class="muted">{{ \App\Models\ProjetUpcycling::ETATS[$projet->etat] ?? $projet->etat }}</span></td>
                <td class="right">{{ $devis->delai_jours }} jour(s)</td>
                <td class="right bold">{{ number_format($devis->montant, 2, ',', ' ') }} DT</td>
            </tr>
            <tr class="total">
                <td colspan="3">Total TTC</td>
                <td class="right">{{ number_format($devis->montant, 2, ',', ' ') }} DT</td>
            </tr>
        </tbody>
    </table>

    @if($devis->message)
        <div class="section">Détail de l'atelier</div>
        <p>{{ $devis->message }}</p>
    @endif

    @if($idee)
        <div class="section">Étapes prévues</div>
        <ol class="etapes">
            @foreach($idee['etapes'] ?? [] as $etape)
                <li>{{ $etape }}</li>
            @endforeach
        </ol>
        @if(!empty($idee['materiaux']))
            <p class="muted" style="margin-top: 6px;">Matériaux : {{ implode(', ', $idee['materiaux']) }}</p>
        @endif
    @endif

    {{-- Total + QR --}}
    <table style="width: 100%; margin-top: 20px;">
        <tr>
            <td style="vertical-align: middle;">
                <div class="total-zone">
                    <div class="muted">Montant du devis</div>
                    <div class="total-montant">{{ number_format($devis->montant, 2, ',', ' ') }} DT</div>
                    <div style="margin-top: 6px;"><span class="pastille {{ $statut[1] }}">{{ $statut[0] }}</span></div>
                    @if($projet->co2_evite_kg)
                        <div class="muted" style="margin-top: 8px;">Impact : environ {{ number_format($projet->co2_evite_kg, 0) }} kg de CO2 et
                            {{ number_format($projet->eau_economisee_l, 0, ',', ' ') }} L d'eau économisés par rapport à un achat neuf.</div>
                    @endif
                </div>
            </td>
            <td style="width: 140px; text-align: center; vertical-align: middle;">
                <img src="data:image/svg+xml;base64,{{ $qrSvg }}" width="100" height="100" alt="QR">
                <div class="muted" style="font-size: 7px; margin-top: 4px;">Suivre le projet en ligne</div>
            </td>
        </tr>
    </table>
</div>

<table class="pied">
    <tr>
        <td>RETISS — Plateforme de l'économie circulaire du textile · Devis {{ $numero }}</td>
        <td class="right">Projet d'upcycling #{{ $projet->id }}</td>
    </tr>
</table>

</body>
</html>
