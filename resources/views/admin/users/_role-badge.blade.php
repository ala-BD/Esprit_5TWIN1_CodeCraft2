@php
    // Une teinte par rôle : fond clair + texte foncé de la même famille
    [$roleBg, $roleText] = match($role) {
        'DONATEUR'   => ['#e1f3e6', '#17663a'],
        'CLIENT'     => ['#e2ecfb', '#1d4fa3'],
        'COLLECTEUR' => ['#faefd2', '#7a5200'],
        'ATELIER'    => ['#fde7da', '#9a3d0e'],
        'RECYCLEUR'  => ['#daf2ef', '#0b5f58'],
        'ADMIN'      => ['#ebe5fb', '#5a2fb0'],
        default      => ['#eceef3', '#3a4558'],
    };
@endphp
<span class="a-badge" style="background: {{ $roleBg }}; color: {{ $roleText }};">{{ ucfirst(strtolower($role)) }}</span>
