@extends('admin.layouts.admin')

@section('title', 'Utilisateurs')

@section('breadcrumb')
    <span class="hidden sm:inline text-slate-300">/</span>
    <span class="text-slate-900 font-medium">Utilisateurs</span>
@endsection

@section('admin-content')

{{-- Header --}}
<div class="flex flex-wrap items-center justify-between gap-4 mb-4">
    <div>
        <h1 class="text-xl font-semibold text-slate-900 tracking-tight">Utilisateurs</h1>
        <p class="text-slate-500 mt-0.5 num">
            {{ $stats['total'] }} compte{{ $stats['total'] > 1 ? 's' : '' }},
            dont {{ $stats['actifs'] }} actif{{ $stats['actifs'] > 1 ? 's' : '' }},
            {{ $stats['inactifs'] }} désactivé{{ $stats['inactifs'] > 1 ? 's' : '' }}
            et {{ $stats['admins'] }} administrateur{{ $stats['admins'] > 1 ? 's' : '' }}.
        </p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="a-btn a-btn-primary">
        <i class="fas fa-plus"></i>
        Ajouter un utilisateur
    </a>
</div>

{{-- Tableau des utilisateurs --}}
<div class="a-card">

    {{-- Recherche + filtres --}}
    <form method="GET" action="{{ route('admin.users.index') }}"
          class="flex flex-col md:flex-row md:items-center gap-2 p-3 border-b border-slate-200">
        <div class="relative flex-1 md:max-w-xs">
            <i class="fas fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            <input type="search" name="q" value="{{ request('q') }}"
                   placeholder="Rechercher par nom, prénom ou e-mail"
                   aria-label="Rechercher"
                   class="a-input" style="padding-left: 30px; height: 32px;">
        </div>
        @include('admin.partials.select', [
            'name'     => 'role',
            'id'       => 'filtre-role',
            'class'    => 'md:w-44 a-select-sm',
            'submit'   => true,
            'selected' => request('role'),
            'options'  => ['' => 'Tous les rôles'] + collect(\App\Models\User::ROLES)->mapWithKeys(fn ($role) => [$role => ucfirst(strtolower($role))])->all(),
        ])
        @include('admin.partials.select', [
            'name'     => 'actif',
            'id'       => 'filtre-actif',
            'class'    => 'md:w-44 a-select-sm',
            'submit'   => true,
            'selected' => request('actif'),
            'options'  => ['' => 'Tous les statuts', '1' => 'Actifs', '0' => 'Désactivés'],
        ])
        <button type="submit" class="a-btn">Filtrer</button>
        @if(request()->hasAny(['q', 'role', 'actif']))
            <a href="{{ route('admin.users.index') }}" class="a-btn" style="border-color: transparent; color: #64748b;">
                <i class="fas fa-xmark"></i> Réinitialiser
            </a>
        @endif
        <span class="md:ml-auto text-slate-500 num">{{ $users->total() }} résultats</span>
    </form>

    @if($users->isEmpty())
        <div class="text-center py-14">
            <i class="fas fa-user-slash text-slate-300 text-2xl"></i>
            <h3 class="font-medium text-slate-900 mt-3">Aucun utilisateur trouvé</h3>
            <p class="text-slate-500 mt-1">Modifiez vos critères de recherche ou ajoutez un utilisateur.</p>
        </div>
    @else
        <div class="overflow-x-auto {{ $users->hasPages() ? '' : 'rounded-b-lg' }}">
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Téléphone</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Inscription</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    {{-- Ligne cliquable : ouvre la barre d'actions --}}
                    <tr class="a-row" tabindex="0" role="button" aria-haspopup="true"
                        aria-label="Actions pour {{ $user->full_name }}"
                        data-nom="{{ $user->full_name }}"
                        data-email="{{ $user->email }}"
                        data-initiales="{{ $user->initials ?: '?' }}"
                        data-show="{{ route('admin.users.show', $user) }}"
                        data-edit="{{ route('admin.users.edit', $user) }}"
                        @unless($user->is(Auth::user())) data-destroy="{{ route('admin.users.destroy', $user) }}" @endunless>

                        {{-- Utilisateur --}}
                        <td>
                            <div class="flex items-center gap-2.5">
                                <span class="a-avatar w-7 h-7 text-[11px]">{{ $user->initials ?: '?' }}</span>
                                <span class="min-w-0">
                                    <span class="block font-medium text-slate-900 leading-tight">
                                        {{ $user->full_name }}
                                        @if($user->is(Auth::user()))
                                            <span class="text-slate-400 font-normal">(vous)</span>
                                        @endif
                                    </span>
                                    <span class="block text-slate-500 text-xs leading-tight mt-0.5">{{ $user->email }}</span>
                                </span>
                            </div>
                        </td>

                        {{-- Téléphone --}}
                        <td class="num text-slate-600">{{ $user->telephone ?: '—' }}</td>

                        {{-- Rôle --}}
                        <td>@include('admin.users._role-badge', ['role' => $user->role])</td>

                        {{-- Statut --}}
                        <td>
                            <span class="inline-flex items-center gap-1.5 {{ $user->actif ? 'text-slate-700' : 'text-slate-400' }}">
                                <span class="a-dot" style="background: {{ $user->actif ? '#16a34a' : '#cbd5e1' }}"></span>
                                {{ $user->actif ? 'Actif' : 'Désactivé' }}
                            </span>
                        </td>

                        {{-- Inscription --}}
                        <td class="num text-slate-600">{{ $user->created_at->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
        <div class="px-3 py-2.5 border-t border-slate-200">
            {{ $users->links('admin.partials.pagination') }}
        </div>
        @endif
    @endif
</div>

{{-- Réserve de défilement : les dernières lignes restent visibles au-dessus de la barre --}}
<div class="h-16"></div>

{{-- ===== BARRE D'ACTIONS (glisse depuis le bas au clic sur une ligne) ===== --}}
<div id="user-actions" class="a-actionbar" role="toolbar" aria-label="Actions sur l'utilisateur" aria-hidden="true">

    {{-- Utilisateur sélectionné --}}
    <div class="a-actionbar-identity" data-identity>
        <span class="a-avatar a-actionbar-avatar" data-initiales></span>
        <span class="min-w-0">
            <span class="flex items-center gap-2">
                <span class="text-white font-medium leading-tight truncate" data-nom></span>
                <span data-role></span>
            </span>
            <span class="block text-white/50 text-xs leading-tight mt-1 truncate" data-email></span>
        </span>
    </div>

    {{-- Utilisateur précédent / suivant --}}
    <div class="a-actionbar-stepper">
        <button type="button" data-action="prev" aria-label="Utilisateur précédent" title="Précédent (↑)">
            <i class="fas fa-chevron-up"></i>
        </button>
        <button type="button" data-action="next" aria-label="Utilisateur suivant" title="Suivant (↓)">
            <i class="fas fa-chevron-down"></i>
        </button>
    </div>

    <span class="a-actionbar-sep"></span>

    {{-- Actions --}}
    <a href="#" class="a-actionbar-btn" data-action="show">
        <i class="fas fa-eye"></i> <span>Voir</span> <kbd>V</kbd>
    </a>
    <button type="button" class="a-actionbar-btn danger" data-action="destroy">
        <i class="fas fa-trash-can"></i> <span>Supprimer</span> <kbd>Suppr</kbd>
    </button>
    <a href="#" class="a-actionbar-btn primary" data-action="edit">
        <i class="fas fa-pen"></i> <span>Modifier</span> <kbd>M</kbd>
    </a>

    <span class="a-actionbar-sep"></span>

    <button type="button" class="a-actionbar-btn icon" data-action="close" aria-label="Fermer" title="Fermer (Échap)">
        <i class="fas fa-xmark"></i>
    </button>
</div>

{{-- Formulaire de suppression partagé (l'action est renseignée par la barre) --}}
<form id="user-destroy-form" method="POST" action="#" hidden
      data-confirm-title="Supprimer cet utilisateur ?"
      data-confirm-label="Supprimer">
    @csrf @method('DELETE')
</form>

@endsection

@section('styles')
<style>
    /* ---- Lignes cliquables ---- */
    .a-row { cursor: pointer; transition: background .12s; }
    .a-row:focus-visible { outline: 2px solid var(--teal); outline-offset: -2px; }
    .a-table tbody tr.a-row.selected { background: #eaf4f3; }

    /* ---- Barre d'actions ---- */
    .a-actionbar {
        position: fixed; z-index: 40; bottom: 24px; left: 50%;
        display: flex; align-items: center; gap: 6px;
        max-width: calc(100vw - 24px); padding: 8px;
        background: #1c2233; border: 1px solid rgba(255,255,255,.09); border-radius: 16px;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,.07),
            0 1px 2px rgba(15,20,32,.30),
            0 12px 28px -6px rgba(15,20,32,.38),
            0 32px 64px -12px rgba(15,20,32,.32);
        opacity: 0; visibility: hidden;
        transform: translate(-50%, calc(100% + 32px)) scale(.96);
        transition: transform .22s cubic-bezier(.4,0,1,1), opacity .18s ease, visibility 0s .22s;
    }
    /* Centrée sur la zone de contenu, à droite de la sidebar */
    @media (min-width: 1024px) { .a-actionbar { left: calc(50% + 112px); } }
    .a-actionbar.open {
        opacity: 1; visibility: visible; transform: translate(-50%, 0) scale(1);
        transition: transform .42s cubic-bezier(.16,1,.3,1), opacity .2s ease, visibility 0s;
    }

    /* Utilisateur sélectionné */
    .a-actionbar-identity { display: flex; align-items: center; gap: 11px; min-width: 0; max-width: 340px; padding: 0 6px 0 4px; }
    .a-actionbar-avatar { width: 38px; height: 38px; font-size: 13px; box-shadow: 0 0 0 2px #1c2233, 0 0 0 3px rgba(255,255,255,.16); }
    .a-actionbar-identity .a-badge { height: 19px; padding: 0 6px; font-size: 11.5px; flex-shrink: 0; }
    .a-actionbar-identity.swap { animation: a-identity-swap .24s cubic-bezier(.16,1,.3,1); }
    @keyframes a-identity-swap { from { opacity: 0; transform: translateY(6px); } }

    /* Précédent / suivant */
    .a-actionbar-stepper { display: flex; flex-direction: column; flex-shrink: 0; border-radius: 8px; overflow: hidden; background: rgba(255,255,255,.06); }
    .a-actionbar-stepper button {
        display: flex; align-items: center; justify-content: center;
        width: 26px; height: 19px; color: #9ba5b8; font-size: 9px;
        transition: background .12s, color .12s;
    }
    .a-actionbar-stepper button + button { border-top: 1px solid rgba(255,255,255,.07); }
    .a-actionbar-stepper button:hover:not(:disabled) { background: rgba(255,255,255,.10); color: #fff; }
    .a-actionbar-stepper button:disabled { opacity: .3; cursor: default; }
    .a-actionbar-stepper button:focus-visible { outline: 2px solid #5eead4; outline-offset: -2px; }

    .a-actionbar-sep { width: 1px; height: 26px; background: rgba(255,255,255,.10); margin: 0 4px; flex-shrink: 0; }

    /* Actions */
    .a-actionbar-btn {
        display: inline-flex; align-items: center; gap: 8px; flex-shrink: 0;
        height: 38px; padding: 0 10px 0 12px; border-radius: 10px;
        color: #dfe3ec; font-weight: 500; white-space: nowrap;
        transition: background .14s, color .14s, transform .12s, box-shadow .14s;
    }
    .a-actionbar-btn i { font-size: 12px; opacity: .85; }
    .a-actionbar-btn kbd {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 19px; height: 19px; padding: 0 5px; border-radius: 5px;
        font: 500 10.5px/1 'IBM Plex Sans', sans-serif; color: #aab3c5;
        background: rgba(255,255,255,.08); box-shadow: inset 0 -1px 0 rgba(0,0,0,.25);
    }
    .a-actionbar-btn:hover { background: rgba(255,255,255,.09); color: #fff; }
    .a-actionbar-btn:active { transform: scale(.96); }
    .a-actionbar-btn:focus-visible { outline: 2px solid #5eead4; outline-offset: 2px; }
    .a-actionbar-btn.icon { width: 38px; padding: 0; justify-content: center; color: #9ba5b8; }
    .a-actionbar-btn[hidden] { display: none; }

    .a-actionbar-btn.primary {
        background: var(--teal); color: #fff;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.18), 0 1px 2px rgba(0,0,0,.25);
    }
    .a-actionbar-btn.primary:hover { background: #10a698; }
    .a-actionbar-btn.primary kbd { color: #d9f5f1; background: rgba(0,0,0,.16); box-shadow: none; }

    .a-actionbar-btn.danger { color: #ff9f97; }
    .a-actionbar-btn.danger:hover { background: rgba(255,99,88,.15); color: #ffbcb6; }
    .a-actionbar-btn.danger kbd { color: #e7968f; background: rgba(255,99,88,.12); }

    /* Les actions arrivent l'une après l'autre à l'ouverture */
    .a-actionbar.open .a-actionbar-btn { animation: a-action-in .36s cubic-bezier(.16,1,.3,1) backwards; }
    .a-actionbar.open .a-actionbar-btn[data-action="show"]    { animation-delay: .07s; }
    .a-actionbar.open .a-actionbar-btn[data-action="destroy"] { animation-delay: .11s; }
    .a-actionbar.open .a-actionbar-btn[data-action="edit"]    { animation-delay: .15s; }
    .a-actionbar.open .a-actionbar-btn[data-action="close"]   { animation-delay: .19s; }
    @keyframes a-action-in { from { opacity: 0; transform: translateY(8px); } }

    @media (max-width: 767px) {
        .a-actionbar-btn kbd, .a-actionbar-stepper, .a-actionbar-identity [data-role] { display: none; }
    }
    @media (max-width: 639px) {
        .a-actionbar-identity { max-width: 120px; }
        .a-actionbar-btn:not(.icon) { width: 38px; padding: 0; justify-content: center; }
        .a-actionbar-btn span { display: none; }
    }
    @media (prefers-reduced-motion: reduce) {
        .a-actionbar, .a-actionbar.open { transition: opacity .15s ease, visibility 0s; transform: translate(-50%, 0); }
        .a-actionbar.open .a-actionbar-btn, .a-actionbar-identity.swap { animation: none; }
    }
</style>
@endsection

@section('scripts')
<script>
    // ---- Lignes cliquables + barre d'actions ----
    (function () {
        const bar = document.getElementById('user-actions');
        const form = document.getElementById('user-destroy-form');
        const modal = document.getElementById('admin-confirm');
        const rows = Array.from(document.querySelectorAll('.a-row'));
        if (!rows.length) return;

        const identity = bar.querySelector('[data-identity]');
        const avatar   = bar.querySelector('[data-initiales]');
        const role     = bar.querySelector('[data-role]');
        const show     = bar.querySelector('[data-action="show"]');
        const edit     = bar.querySelector('[data-action="edit"]');
        const destroy  = bar.querySelector('[data-action="destroy"]');
        const prev     = bar.querySelector('[data-action="prev"]');
        const next     = bar.querySelector('[data-action="next"]');
        let current = null;

        function open(row, viaKeyboard) {
            const wasOpen = bar.classList.contains('open');
            if (current) current.classList.remove('selected');
            current = row;
            row.classList.add('selected');

            // Identité : reprend le badge de rôle de la ligne et sa teinte pour l'avatar
            const badge = row.querySelector('.a-badge');
            avatar.textContent = row.dataset.initiales;
            avatar.style.background = badge.style.background;
            avatar.style.color = badge.style.color;
            role.replaceChildren(badge.cloneNode(true));
            bar.querySelector('[data-nom]').textContent   = row.dataset.nom;
            bar.querySelector('[data-email]').textContent = row.dataset.email;

            show.href = row.dataset.show;
            edit.href = row.dataset.edit;
            destroy.hidden = !row.dataset.destroy;   // pas de suppression de son propre compte

            const index = rows.indexOf(row);
            prev.disabled = index === 0;
            next.disabled = index === rows.length - 1;

            if (wasOpen) {
                // Changement de ligne : seule l'identité se rafraîchit
                identity.classList.remove('swap');
                void identity.offsetWidth;
                identity.classList.add('swap');
            }

            bar.classList.add('open');
            bar.setAttribute('aria-hidden', 'false');
            row.scrollIntoView({ block: 'nearest' });
            if (viaKeyboard && !wasOpen) edit.focus();
        }

        function close(restoreFocus) {
            if (!current) return;
            const row = current;
            bar.classList.remove('open');
            bar.setAttribute('aria-hidden', 'true');
            row.classList.remove('selected');
            current = null;
            if (restoreFocus) row.focus();
        }

        function step(delta) {
            const target = rows[rows.indexOf(current) + delta];
            if (target) open(target, false);
        }

        function askDestroy() {
            if (!current.dataset.destroy) return;
            form.action = current.dataset.destroy;
            form.dataset.confirmMessage = 'Le compte de ' + current.dataset.nom + ' sera supprimé définitivement. Cette action est irréversible.';
            form.requestSubmit();   // intercepté par la fenêtre de confirmation du layout
        }

        rows.forEach(function (row) {
            row.addEventListener('click', () => current === row ? close(false) : open(row, false));
            row.addEventListener('keydown', function (event) {
                if (event.target === row && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); open(row, true); }
            });
        });

        bar.querySelector('[data-action="close"]').addEventListener('click', () => close(true));
        prev.addEventListener('click', () => step(-1));
        next.addEventListener('click', () => step(1));
        destroy.addEventListener('click', askDestroy);

        // Raccourcis clavier, actifs tant que la barre est ouverte
        document.addEventListener('keydown', function (event) {
            if (!current || modal.classList.contains('open')) return;
            if (event.ctrlKey || event.metaKey || event.altKey) return;
            if (event.target.closest('input, textarea, select, [role="listbox"], .a-select')) return;

            const key = event.key.toLowerCase();
            if (key === 'escape')         { close(true); }
            else if (key === 'arrowup')   { event.preventDefault(); step(-1); }
            else if (key === 'arrowdown') { event.preventDefault(); step(1); }
            else if (key === 'v')         { window.location.href = show.href; }
            else if (key === 'm')         { window.location.href = edit.href; }
            else if (key === 'delete')    { askDestroy(); }
        });

        // Clic en dehors de la barre et du tableau : on referme
        document.addEventListener('click', function (event) {
            if (current && !event.target.closest('.a-row, #user-actions, #admin-confirm')) close(false);
        });
    })();
</script>
@endsection
