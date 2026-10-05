@extends('admin.layouts.admin')

@section('title', 'Statistiques')

@section('breadcrumb')
    <span class="hidden sm:inline text-slate-300">/</span>
    <span class="text-slate-900 font-medium">Statistiques</span>
@endsection

@section('admin-content')

@php
    $total      = $chiffres['total'];
    $pourcent   = fn (int $n) => $total > 0 ? round($n / $total * 100) : 0;
    $maxRole    = max(1, $parRole->max('nombre'));
    $maxSemaine = max(1, $parSemaine->max('nombre'));
    $totalSemaines = $parSemaine->sum('nombre');
    $evolution  = $chiffres['evolution_30'];
@endphp

{{-- Header --}}
<div class="mb-4">
    <h1 class="text-xl font-semibold text-slate-900 tracking-tight">Statistiques</h1>
    <p class="text-slate-500 mt-0.5">Vue d'ensemble des comptes de la plateforme.</p>
</div>

{{-- Chiffres clés --}}
<dl class="a-card grid grid-cols-2 lg:grid-cols-4 mb-4">
    <div class="s-tile">
        <dt>Utilisateurs</dt>
        <dd class="num">{{ $total }}</dd>
        <p>comptes enregistrés</p>
    </div>
    <div class="s-tile">
        <dt>Nouveaux sur 30 jours</dt>
        <dd class="num">{{ $chiffres['nouveaux_30'] }}</dd>
        <p class="num">
            @if($evolution > 0)
                <i class="fas fa-arrow-up text-[10px]"></i> {{ $evolution }} de plus que les 30 jours précédents
            @elseif($evolution < 0)
                <i class="fas fa-arrow-down text-[10px]"></i> {{ abs($evolution) }} de moins que les 30 jours précédents
            @else
                autant que les 30 jours précédents
            @endif
        </p>
    </div>
    <div class="s-tile">
        <dt>Comptes actifs</dt>
        <dd class="num">{{ $pourcent($chiffres['actifs']) }} %</dd>
        <p class="num">{{ $chiffres['actifs'] }} actifs, {{ $chiffres['inactifs'] }} désactivés</p>
    </div>
    <div class="s-tile">
        <dt>E-mails vérifiés</dt>
        <dd class="num">{{ $pourcent($chiffres['verifies']) }} %</dd>
        <p class="num">{{ $chiffres['verifies'] }} sur {{ $total }} comptes</p>
    </div>
</dl>

<div class="grid grid-cols-1 xl:grid-cols-5 gap-4 mb-4">

    {{-- ===== Inscriptions par semaine ===== --}}
    <figure class="a-card xl:col-span-3 p-5">
        <figcaption class="mb-4">
            <h2 class="font-semibold text-slate-900">Inscriptions par semaine</h2>
            <p class="text-slate-500 mt-0.5 num">{{ $totalSemaines }} inscriptions sur les {{ $parSemaine->count() }} dernières semaines</p>
        </figcaption>

        <div class="s-columns" role="img"
             aria-label="Inscriptions par semaine sur les {{ $parSemaine->count() }} dernières semaines, {{ $totalSemaines }} au total">
            {{-- Graduations --}}
            <div class="s-grid" aria-hidden="true">
                <span class="num">{{ $maxSemaine }}</span>
                <span class="num">{{ $maxSemaine > 1 ? intdiv($maxSemaine, 2) : '' }}</span>
                <span class="num">0</span>
            </div>

            @foreach($parSemaine as $semaine)
                <div class="s-column" tabindex="0"
                     data-tip-titre="Semaine du {{ $semaine['debut']->format('d/m') }} au {{ $semaine['fin']->format('d/m') }}"
                     data-tip-valeur="{{ $semaine['nombre'] }} inscription{{ $semaine['nombre'] > 1 ? 's' : '' }}">
                    <span class="s-column-bar" style="height: {{ $semaine['nombre'] / $maxSemaine * 100 }}%"></span>
                    <span class="s-column-label num">{{ $loop->index % 2 === 0 || $loop->last ? $semaine['debut']->format('d/m') : '' }}</span>
                </div>
            @endforeach
        </div>
    </figure>

    {{-- ===== Répartition par rôle ===== --}}
    <figure class="a-card xl:col-span-2 p-5">
        <figcaption class="mb-4">
            <h2 class="font-semibold text-slate-900">Répartition par rôle</h2>
            <p class="text-slate-500 mt-0.5">Nombre de comptes pour chaque rôle</p>
        </figcaption>

        <div class="space-y-2.5">
            @foreach($parRole as $ligne)
                <div class="s-bar-row" tabindex="0"
                     data-tip-titre="{{ ucfirst(strtolower($ligne['role'])) }}"
                     data-tip-valeur="{{ $ligne['nombre'] }} compte{{ $ligne['nombre'] > 1 ? 's' : '' }}, {{ $pourcent($ligne['nombre']) }} % du total">
                    <span class="s-bar-name">{{ ucfirst(strtolower($ligne['role'])) }}</span>
                    <span class="s-bar-track">
                        <span class="s-bar-fill" style="width: {{ $ligne['nombre'] / $maxRole * 100 }}%"></span>
                    </span>
                    <span class="s-bar-value num">{{ $ligne['nombre'] }}</span>
                </div>
            @endforeach
        </div>
    </figure>
</div>

{{-- ===== Dernières inscriptions ===== --}}
<div class="a-card">
    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-200">
        <h2 class="font-semibold text-slate-900">Dernières inscriptions</h2>
        <a href="{{ route('admin.users.index') }}" class="text-primary-dark hover:underline">Tous les utilisateurs</a>
    </div>

    @if($derniers->isEmpty())
        <p class="text-slate-500 text-center py-10">Aucun utilisateur pour le moment.</p>
    @else
        <div class="overflow-x-auto rounded-b-lg">
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Inscription</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($derniers as $user)
                    <tr>
                        <td>
                            <a href="{{ route('admin.users.show', $user) }}" class="flex items-center gap-2.5 group">
                                <span class="a-avatar w-7 h-7 text-[11px]">{{ $user->initials ?: '?' }}</span>
                                <span class="min-w-0">
                                    <span class="block font-medium text-slate-900 group-hover:text-primary-dark leading-tight">{{ $user->full_name }}</span>
                                    <span class="block text-slate-500 text-xs leading-tight mt-0.5">{{ $user->email }}</span>
                                </span>
                            </a>
                        </td>
                        <td>@include('admin.users._role-badge', ['role' => $user->role])</td>
                        <td>
                            <span class="inline-flex items-center gap-1.5 {{ $user->actif ? 'text-slate-700' : 'text-slate-400' }}">
                                <span class="a-dot" style="background: {{ $user->actif ? '#16a34a' : '#cbd5e1' }}"></span>
                                {{ $user->actif ? 'Actif' : 'Désactivé' }}
                            </span>
                        </td>
                        <td class="num text-slate-600">{{ $user->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Infobulle des graphiques --}}
<div id="s-tip" class="s-tip" role="status" hidden>
    <span class="block text-white/60 text-xs" data-tip-titre></span>
    <span class="block text-white font-medium num" data-tip-valeur></span>
</div>

@endsection

@section('styles')
<style>
    /* ---- Chiffres clés ---- */
    .s-tile { padding: 14px 16px; border-color: var(--line); }
    .s-tile + .s-tile { border-left-width: 1px; }
    .s-tile dt { color: var(--muted); }
    .s-tile dd { font-size: 26px; line-height: 1.2; font-weight: 600; letter-spacing: -.01em; color: var(--ink); margin-top: 2px; }
    .s-tile p  { color: var(--muted); font-size: 12px; margin-top: 2px; }
    @media (max-width: 1023px) {
        .s-tile:nth-child(odd) { border-left-width: 0; }
        .s-tile:nth-child(n+3) { border-top-width: 1px; }
    }

    /* ---- Colonnes : inscriptions par semaine ---- */
    .s-columns {
        position: relative; display: flex; align-items: flex-end; gap: 2px;
        height: 220px; padding: 0 0 22px 28px;
    }
    .s-grid {
        position: absolute; inset: 0 0 22px 0; display: flex; flex-direction: column; justify-content: space-between;
        pointer-events: none;
    }
    .s-grid span { display: block; height: 0; border-top: 1px solid #eceff4; font-size: 11px; color: #8590a3; line-height: 0; }
    .s-grid span:last-child { border-top-color: #cfd5df; }
    .s-column {
        position: relative; z-index: 1; flex: 1; height: 100%;
        display: flex; align-items: flex-end; justify-content: center; cursor: default;
    }
    .s-column:focus-visible, .s-bar-row:focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; border-radius: 4px; }
    .s-column-bar {
        width: 100%; max-width: 28px;
        background: var(--teal); border-radius: 4px 4px 0 0; transition: background .12s;
    }
    .s-column:hover .s-column-bar, .s-column:focus-visible .s-column-bar { background: var(--teal-dark); }
    .s-column-label { position: absolute; top: calc(100% + 6px); font-size: 11px; color: #8590a3; white-space: nowrap; }

    /* ---- Barres : répartition par rôle ---- */
    .s-bar-row { display: grid; grid-template-columns: 86px 1fr 32px; align-items: center; gap: 10px; cursor: default; }
    .s-bar-name  { color: #3a4558; }
    .s-bar-track { display: block; height: 14px; }
    .s-bar-fill  { display: block; height: 100%; background: var(--teal); border-radius: 0 4px 4px 0; transition: background .12s; }
    .s-bar-row:hover .s-bar-fill, .s-bar-row:focus-visible .s-bar-fill { background: var(--teal-dark); }
    .s-bar-value { text-align: right; font-weight: 500; color: var(--ink); }

    /* ---- Infobulle ---- */
    .s-tip {
        position: fixed; z-index: 50; pointer-events: none;
        padding: 6px 10px; border-radius: 6px; background: var(--navy);
        box-shadow: 0 6px 16px rgba(26,32,48,.25); white-space: nowrap;
        transform: translate(-50%, calc(-100% - 10px));
    }
</style>
@endsection

@section('scripts')
<script>
    // ---- Infobulle des graphiques (survol et focus clavier) ----
    (function () {
        const tip    = document.getElementById('s-tip');
        const titre  = tip.querySelector('[data-tip-titre]');
        const valeur = tip.querySelector('[data-tip-valeur]');

        function show(mark, x, y) {
            titre.textContent  = mark.dataset.tipTitre;
            valeur.textContent = mark.dataset.tipValeur;
            tip.hidden = false;
            // Garde l'infobulle dans la fenêtre
            const half = tip.offsetWidth / 2 + 8;
            tip.style.left = Math.max(half, Math.min(window.innerWidth - half, x)) + 'px';
            tip.style.top  = y + 'px';
        }

        document.querySelectorAll('.s-column, .s-bar-row').forEach(function (mark) {
            mark.addEventListener('mousemove', event => show(mark, event.clientX, event.clientY));
            mark.addEventListener('mouseleave', () => tip.hidden = true);
            mark.addEventListener('focus', function () {
                const box = mark.getBoundingClientRect();
                show(mark, box.left + box.width / 2, box.top);
            });
            mark.addEventListener('blur', () => tip.hidden = true);
        });
    })();
</script>
@endsection
