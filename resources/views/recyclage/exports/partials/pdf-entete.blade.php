{{-- En-tête RETISS : $type (petit libellé), $titre, $sousTitre --}}
@php
    $logo = public_path('images/logo.png');
    $logoData = is_file($logo) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logo)) : null;
@endphp
<table class="entete" style="width: 100%;">
    <tr>
        <td class="logo">
            @if($logoData)<img src="{{ $logoData }}" alt="RETISS">@endif
        </td>
        <td class="marque">
            <div class="marque-nom">RETISS</div>
            <div class="marque-sous">Plateforme de l'économie circulaire du textile</div>
        </td>
        <td class="doc">
            <div class="doc-type">{{ mb_strtoupper($type) }}</div>
            <div class="doc-titre">{{ $titre }}</div>
            <div class="doc-sous">{{ $sousTitre }}</div>
        </td>
    </tr>
</table>
<table class="accent" style="width: 100%;">
    <tr><td style="background: #0d9488; width: 72%;"></td><td style="background: #4ade80;"></td></tr>
</table>
