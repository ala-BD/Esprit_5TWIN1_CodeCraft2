<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Feuille de route — {{ $tournee->zone }}</title>
    <style>
        @page { margin: 22mm 16mm 20mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1a2030; }
        h1 { font-size: 18px; margin: 0; }
        .entete { border-bottom: 2px solid #0d9488; padding-bottom: 8px; margin-bottom: 12px; }
        .marque { color: #0d9488; font-weight: bold; font-size: 11px; letter-spacing: .5px; }
        .infos { width: 100%; margin-bottom: 14px; }
        .infos td { padding: 2px 10px 2px 0; vertical-align: top; }
        .libelle { color: #5f6b7e; }
        table.arrets { width: 100%; border-collapse: collapse; }
        table.arrets th { text-align: left; font-size: 9px; color: #5f6b7e; border-bottom: 1px solid #cfd5df; padding: 5px 4px; }
        table.arrets td { border-bottom: 1px solid #e4e7ee; padding: 7px 4px; vertical-align: top; }
        .num { font-weight: bold; text-align: center; width: 22px; }
        .heure { font-weight: bold; white-space: nowrap; width: 40px; }
        .petit { color: #5f6b7e; font-size: 9px; }
        .case { width: 120px; }
        .case div { border: 1px solid #cfd5df; height: 30px; border-radius: 3px; }
        .fait { color: #17663a; font-weight: bold; }
        .echec { color: #a3261c; font-weight: bold; }
        .pied { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 8px; color: #8590a3; }
    </style>
</head>
<body>

<div class="entete">
    <div class="marque">RETISS · LOGISTIQUE</div>
    <h1>Feuille de route — {{ $tournee->zone }}</h1>
</div>

<table class="infos">
    <tr>
        <td><span class="libelle">Date</span><br><strong>{{ $tournee->date->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</strong></td>
        <td><span class="libelle">Collecteur</span><br><strong>{{ $tournee->user?->full_name }}</strong>{{ $tournee->user?->telephone ? ' · ' . $tournee->user->telephone : '' }}</td>
        <td><span class="libelle">Véhicule</span><br><strong>{{ $tournee->vehicule }}</strong></td>
        <td><span class="libelle">Arrêts</span><br><strong>{{ $tournee->missions->count() }}</strong>{{ $tournee->distance_km ? ' · ' . $tournee->distance_km . ' km estimés' : '' }}</td>
    </tr>
    <tr>
        <td colspan="4" style="padding-top: 6px;"><span class="libelle">Départ :</span> {{ $depot['nom'] }}</td>
    </tr>
</table>

<table class="arrets">
    <thead>
        <tr>
            <th>N°</th>
            <th>Heure</th>
            <th>Arrêt</th>
            <th>Contact</th>
            <th class="case">Signature / remarque</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tournee->missions as $mission)
            @php $contact = $mission->contact(); @endphp
            <tr>
                <td class="num">{{ $mission->ordre }}</td>
                <td class="heure">{{ $mission->heure }}</td>
                <td>
                    <strong>{{ $mission->type_label }}</strong>
                    @if($mission->commande)
                        · commande {{ $mission->commande->numero }} ({{ $mission->commande->lignes->sum('quantite') }} article(s))
                    @elseif($mission->donVetement)
                        · don n° {{ $mission->donVetement->id }} ({{ $mission->donVetement->type }}, {{ $mission->donVetement->matiere }})
                    @endif
                    <br>{{ $mission->adresse }}
                    @if($mission->statut === 'TERMINEE')
                        <br><span class="fait">Fait</span> <span class="petit">{{ $mission->terminee_le?->format('H:i') }} {{ $mission->preuve_livraison }}</span>
                    @elseif($mission->statut === 'ECHOUEE')
                        <br><span class="echec">Échec</span> <span class="petit">{{ $mission->preuve_livraison }}</span>
                    @endif
                </td>
                <td>
                    @if($contact)
                        {{ $contact->full_name }}<br><span class="petit">{{ $contact->telephone ?: 'pas de téléphone' }}</span>
                    @else
                        <span class="petit">—</span>
                    @endif
                </td>
                <td class="case"><div></div></td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="pied">
    Feuille de route générée le {{ now()->format('d/m/Y à H:i') }} · Heures indicatives, calculées sur une vitesse moyenne en ville.
</div>

</body>
</html>
