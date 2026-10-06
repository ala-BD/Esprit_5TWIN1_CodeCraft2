<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administration') — RETISS @yield('espace-nom', 'Administration')</title>

    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- Tailwind Config — mêmes couleurs que le site RETISS --}}
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy:    { DEFAULT: '#1a2744', light: '#243560', dark: '#111a30' },
                        primary: { DEFAULT: '#0d9488', light: '#2DD4BF', dark: '#0f766e' },
                    },
                    fontFamily: {
                        sans: ['IBM Plex Sans', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <style>
        /* ---- Palette admin ---- */
        :root {
            --navy:        #1a2030;   /* fond du logo */
            --teal:        #0d9488;
            --teal-dark:   #0f766e;
            --ink:         #1a2030;
            --muted:       #5f6b7e;
            --line:        #dfe3ea;
            --surface:     #f4f5f8;
        }

        body {
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 13.5px;
            line-height: 1.45;
            color: var(--ink);
            background: var(--surface);
        }
        .num { font-variant-numeric: tabular-nums; }

        /* ---- Sidebar ---- */
        .a-sidebar { width: 224px; background: var(--navy); }
        .a-nav-link {
            display: flex; align-items: center; gap: 10px;
            height: 32px; padding: 0 10px; border-radius: 6px;
            color: #a3acbd; font-weight: 500;
            transition: background .12s, color .12s;
        }
        .a-nav-link i { width: 14px; text-align: center; font-size: 12px; }
        .a-nav-link:hover  { color: #fff; background: rgba(255,255,255,.06); }
        .a-nav-link.active { color: #fff; background: rgba(255,255,255,.11); }

        /* ---- Surfaces ---- */
        .a-card { background: #fff; border: 1px solid var(--line); border-radius: 8px; }

        /* ---- Boutons ---- */
        .a-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            height: 32px; padding: 0 12px; border-radius: 6px;
            font-size: 13px; font-weight: 500; white-space: nowrap;
            border: 1px solid var(--line); background: #fff; color: #334155;
            transition: background .12s, border-color .12s, color .12s;
        }
        .a-btn i { font-size: 11px; }
        .a-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
        .a-btn:focus-visible, .a-icon-btn:focus-visible { outline: 2px solid var(--teal); outline-offset: 2px; }
        .a-btn-primary { background: var(--teal); border-color: var(--teal); color: #fff; }
        .a-btn-primary:hover { background: var(--teal-dark); border-color: var(--teal-dark); }
        .a-btn-danger { color: #b91c1c; }
        .a-btn-danger:hover { background: #fef2f2; border-color: #fecaca; }

        .a-icon-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 28px; height: 28px; border-radius: 6px; color: #64748b; font-size: 12px;
            transition: background .12s, color .12s;
        }
        .a-icon-btn:hover { background: #f1f5f9; color: var(--ink); }
        .a-icon-btn.danger:hover { background: #fef2f2; color: #b91c1c; }

        /* ---- Champs ---- */
        .a-label { display: block; font-weight: 500; color: #334155; margin-bottom: 5px; }
        .a-input {
            width: 100%; height: 34px; padding: 0 10px; border-radius: 6px;
            border: 1px solid #cbd5e1; background: #fff; color: var(--ink); font-size: 13px;
            transition: border-color .12s, box-shadow .12s;
        }
        .a-input::placeholder { color: #94a3b8; }
        .a-input:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(13,148,136,.15); }
        .a-input.invalid { border-color: #dc2626; }
        .a-input.invalid:focus { box-shadow: 0 0 0 3px rgba(220,38,38,.12); }
        /* ---- Liste déroulante personnalisée ---- */
        .a-select { position: relative; }
        .a-select-trigger {
            display: flex; align-items: center; justify-content: space-between; gap: 8px;
            text-align: left; cursor: pointer;
        }
        .a-select-sm .a-select-trigger { height: 32px; }
        .a-select-trigger:hover { border-color: #aab3c2; }
        .a-select-trigger[aria-expanded="true"] { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(13,148,136,.15); }
        .a-select-chevron { font-size: 10px; color: var(--muted); transition: transform .15s; }
        .a-select-trigger[aria-expanded="true"] .a-select-chevron { transform: rotate(180deg); }
        .a-select-menu {
            position: absolute; z-index: 30; top: calc(100% + 4px); left: 0; min-width: 100%;
            max-height: 264px; overflow-y: auto; padding: 4px;
            background: #fff; border: 1px solid var(--line); border-radius: 8px;
            box-shadow: 0 8px 24px rgba(26,32,48,.12), 0 1px 2px rgba(26,32,48,.06);
            transform-origin: top; animation: a-select-in .12s ease-out;
        }
        .a-select-menu:focus { outline: none; }
        @keyframes a-select-in { from { opacity: 0; transform: translateY(-4px); } }
        @media (prefers-reduced-motion: reduce) { .a-select-menu { animation: none; } .a-select-chevron { transition: none; } }
        .a-select-option {
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
            height: 30px; padding: 0 8px; border-radius: 5px;
            color: #3a4558; white-space: nowrap; cursor: pointer; user-select: none;
        }
        .a-select-option.active { background: #eef0f4; color: var(--ink); }
        .a-select-option[aria-selected="true"] { color: var(--ink); font-weight: 500; }
        .a-select-check { font-size: 10px; color: var(--teal); visibility: hidden; }
        .a-select-option[aria-selected="true"] .a-select-check { visibility: visible; }

        .a-error { margin-top: 5px; color: #b91c1c; font-size: 12px; }
        .a-hint  { margin-top: 5px; color: var(--muted); font-size: 12px; }

        /* ---- Tableau ---- */
        .a-table { width: 100%; border-collapse: collapse; }
        .a-table th {
            text-align: left; font-weight: 500; font-size: 12px; color: var(--muted);
            padding: 0 16px; height: 36px; background: #fafbfc; border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }
        .a-table td { padding: 0 16px; height: 48px; border-bottom: 1px solid #eef1f5; white-space: nowrap; }
        .a-table tbody tr:last-child td { border-bottom: 0; }
        .a-table tbody tr:hover { background: #fafbfc; }

        /* ---- Badges ---- */
        .a-badge {
            display: inline-flex; align-items: center; gap: 6px;
            height: 22px; padding: 0 8px; border-radius: 4px;
            font-size: 12.5px; font-weight: 500; color: #3a4558; background: #eceef3;
        }
        .a-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

        .a-avatar {
            display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
            border-radius: 50%; background: #e4e7ee; color: #3a4558; font-weight: 600;
        }

        /* ---- Fenêtre de confirmation ---- */
        .a-modal {
            position: fixed; inset: 0; z-index: 60;
            display: flex; align-items: center; justify-content: center; padding: 16px;
            background: rgba(26,32,48,0); visibility: hidden;
            transition: background .2s ease, visibility 0s .2s;
        }
        .a-modal.open { background: rgba(26,32,48,.45); visibility: visible; transition: background .2s ease, visibility 0s; }
        .a-modal-panel {
            width: 100%; max-width: 400px; padding: 20px;
            background: #fff; border-radius: 10px;
            box-shadow: 0 24px 48px rgba(26,32,48,.22), 0 2px 6px rgba(26,32,48,.08);
            opacity: 0; transform: translateY(12px) scale(.97);
            transition: opacity .16s ease, transform .16s ease;
        }
        .a-modal.open .a-modal-panel {
            opacity: 1; transform: none;
            transition: opacity .2s ease, transform .28s cubic-bezier(.2,.8,.2,1);
        }
        .a-btn-danger-solid { background: #c62828; border-color: #c62828; color: #fff; }
        .a-btn-danger-solid:hover { background: #a81f1f; border-color: #a81f1f; }
        @media (prefers-reduced-motion: reduce) {
            .a-modal, .a-modal-panel, .a-modal.open .a-modal-panel { transition: none; }
        }
    </style>

    @yield('styles')
</head>
<body class="antialiased">

<div class="min-h-screen lg:flex">

    {{-- ===== SIDEBAR ===== --}}
    <aside id="admin-sidebar"
           class="a-sidebar hidden lg:flex flex-col fixed inset-y-0 left-0 z-40">

        {{-- Marque --}}
        <a href="@yield('espace-accueil', route('admin.users.index'))" class="flex items-center gap-2.5 h-14 px-4">
            <img src="{{ asset('images/logo.png') }}" alt="" class="w-8 h-8">
            <span class="leading-tight">
                <span class="block text-white font-semibold text-sm">RETISS</span>
                <span class="block text-white/50 text-xs">@yield('espace-nom', 'Administration')</span>
            </span>
        </a>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-3 space-y-0.5 overflow-y-auto">
            @section('espace-navigation')
            <p class="px-2.5 pb-1.5 text-xs text-white/35">Gestion</p>

            <a href="{{ route('admin.users.index') }}"
               class="a-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i>
                Utilisateurs
            </a>

            <a href="{{ route('admin.statistiques') }}"
               class="a-nav-link {{ request()->routeIs('admin.statistiques') ? 'active' : '' }}">
                <i class="fas fa-chart-column"></i>
                Statistiques
            </a>

            @show

            <p class="px-2.5 pt-5 pb-1.5 text-xs text-white/35">Plateforme</p>

            <a href="{{ route('home') }}" class="a-nav-link">
                <i class="fas fa-arrow-up-right-from-square"></i>
                Voir le site
            </a>
        </nav>

        {{-- Compte --}}
        <div class="p-3 border-t border-white/10">
            <div class="flex items-center gap-2.5 px-1.5">
                <span class="a-avatar w-8 h-8 text-xs" style="background: rgba(255,255,255,.12); color: #fff;">
                    {{ Auth::user()->initials ?: '?' }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-white font-medium truncate">{{ Auth::user()->full_name }}</p>
                    <p class="text-white/45 text-xs truncate">{{ Auth::user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="a-icon-btn" style="color: #9aa7bf" title="Déconnexion" aria-label="Déconnexion"
                            onmouseover="this.style.background='rgba(255,255,255,.08)';this.style.color='#fff'"
                            onmouseout="this.style.background='';this.style.color='#9aa7bf'">
                        <i class="fas fa-arrow-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Voile mobile --}}
    <div id="admin-overlay" class="hidden fixed inset-0 z-30 bg-slate-900/40 lg:hidden" onclick="toggleAdminSidebar()"></div>

    {{-- ===== CONTENU PRINCIPAL ===== --}}
    <div class="flex-1 min-w-0 lg:ml-[224px]">

        {{-- Barre supérieure --}}
        <header class="sticky top-0 z-20 h-12 bg-white border-b border-slate-200 flex items-center gap-3 px-4 lg:px-5">
            <button type="button" class="a-icon-btn lg:hidden" onclick="toggleAdminSidebar()" aria-label="Menu">
                <i class="fas fa-bars"></i>
            </button>

            {{-- Fil d'Ariane --}}
            <nav class="flex items-center gap-2 text-slate-500 min-w-0">
                <span class="hidden sm:inline">@yield('espace-nom', 'Administration')</span>
                @yield('breadcrumb')
            </nav>
        </header>

        <main class="px-4 lg:px-5 py-5">

            {{-- Alertes flash --}}
            @if (session('success'))
                <div class="flex items-center gap-2.5 mb-4 px-3 py-2.5 rounded-md border border-emerald-200 bg-emerald-50 text-emerald-800">
                    <i class="fas fa-circle-check text-emerald-600"></i>
                    <p class="flex-1">{{ session('success') }}</p>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800" aria-label="Fermer">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="flex items-center gap-2.5 mb-4 px-3 py-2.5 rounded-md border border-red-200 bg-red-50 text-red-800">
                    <i class="fas fa-circle-exclamation text-red-600"></i>
                    <p class="flex-1">{{ session('error') }}</p>
                </div>
            @endif

            @yield('admin-content')
        </main>
    </div>
</div>

{{-- ===== ASSISTANT VOCAL ===== --}}
@include('admin.partials.assistant')

{{-- ===== FENÊTRE DE CONFIRMATION (formulaires [data-confirm-title]) ===== --}}
<div id="admin-confirm" class="a-modal" role="dialog" aria-modal="true"
     aria-labelledby="admin-confirm-title" aria-describedby="admin-confirm-message">
    <div class="a-modal-panel">
        <h2 id="admin-confirm-title" class="text-base font-semibold text-slate-900"></h2>
        <p id="admin-confirm-message" class="text-slate-600 mt-1.5"></p>
        <div class="flex items-center justify-end gap-2 mt-5">
            <button type="button" class="a-btn" data-confirm-cancel>Annuler</button>
            <button type="button" class="a-btn a-btn-danger-solid" data-confirm-accept></button>
        </div>
    </div>
</div>

<script>
    // ---- Fenêtre de confirmation ----
    // Tout formulaire portant data-confirm-title est intercepté : il n'est envoyé qu'après validation.
    (function () {
        const modal   = document.getElementById('admin-confirm');
        const title   = document.getElementById('admin-confirm-title');
        const message = document.getElementById('admin-confirm-message');
        const cancel  = modal.querySelector('[data-confirm-cancel]');
        const accept  = modal.querySelector('[data-confirm-accept]');
        let pendingForm = null, lastFocus = null;

        function open(form) {
            pendingForm = form;
            lastFocus = document.activeElement;
            title.textContent   = form.dataset.confirmTitle;
            message.textContent = form.dataset.confirmMessage || '';
            accept.textContent  = form.dataset.confirmLabel || 'Confirmer';
            modal.classList.add('open');
            cancel.focus();
        }

        function close() {
            modal.classList.remove('open');
            pendingForm = null;
            if (lastFocus) lastFocus.focus();
        }

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!form.dataset.confirmTitle) return;
            event.preventDefault();
            open(form);
        });

        // form.submit() ne redéclenche pas l'événement « submit » : pas de boucle
        accept.addEventListener('click', () => { if (pendingForm) pendingForm.submit(); });
        cancel.addEventListener('click', close);
        modal.addEventListener('mousedown', event => { if (event.target === modal) close(); });

        modal.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') { event.stopPropagation(); close(); }
            // Garde le focus entre les deux boutons
            if (event.key === 'Tab') {
                event.preventDefault();
                (document.activeElement === cancel ? accept : cancel).focus();
            }
        });
    })();

    // ---- Sidebar mobile ----
    function toggleAdminSidebar() {
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('admin-overlay');
        sidebar.classList.toggle('hidden');
        sidebar.classList.toggle('flex');
        overlay.classList.toggle('hidden');
    }
    // ---- Listes déroulantes personnalisées ----
    document.querySelectorAll('[data-select]').forEach(function (root) {
        const input   = root.querySelector('input[type=hidden]');
        const trigger = root.querySelector('.a-select-trigger');
        const label   = root.querySelector('[data-select-label]');
        const menu    = root.querySelector('.a-select-menu');
        const options = Array.from(menu.querySelectorAll('.a-select-option'));
        let active = -1, typed = '', typedTimer;

        const isOpen = () => !menu.hidden;

        function setActive(index) {
            active = Math.max(0, Math.min(options.length - 1, index));
            options.forEach((option, i) => option.classList.toggle('active', i === active));
            trigger.setAttribute('aria-activedescendant', options[active].id);
            options[active].scrollIntoView({ block: 'nearest' });
        }

        function open() {
            menu.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            setActive(Math.max(0, options.findIndex(option => option.getAttribute('aria-selected') === 'true')));
        }

        function close() {
            menu.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
            trigger.removeAttribute('aria-activedescendant');
        }

        function choose(index) {
            const option  = options[index];
            const changed = input.value !== option.dataset.value;
            options.forEach(o => o.setAttribute('aria-selected', o === option ? 'true' : 'false'));
            input.value = option.dataset.value;
            label.textContent = option.textContent.trim();
            close();
            if (changed && root.hasAttribute('data-submit')) input.form.submit();
        }

        trigger.addEventListener('click', () => isOpen() ? close() : open());

        options.forEach(function (option, i) {
            option.addEventListener('mousemove', () => { if (active !== i) setActive(i); });
            // mousedown : évite de retirer le focus du bouton avant la sélection
            option.addEventListener('mousedown', event => event.preventDefault());
            option.addEventListener('click', () => choose(i));
        });

        trigger.addEventListener('keydown', function (event) {
            const key = event.key;

            if (!isOpen()) {
                if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(key)) { event.preventDefault(); open(); }
                return;
            }

            if (key === 'ArrowDown')      { event.preventDefault(); setActive(active + 1); }
            else if (key === 'ArrowUp')   { event.preventDefault(); setActive(active - 1); }
            else if (key === 'Home')      { event.preventDefault(); setActive(0); }
            else if (key === 'End')       { event.preventDefault(); setActive(options.length - 1); }
            else if (key === 'Enter' || key === ' ') { event.preventDefault(); choose(active); }
            else if (key === 'Escape')    { event.preventDefault(); close(); }
            else if (key === 'Tab')       { close(); }
            else if (key.length === 1) {
                // Saisie rapide : va à la première option qui commence par le texte tapé
                clearTimeout(typedTimer);
                typed += key.toLowerCase();
                typedTimer = setTimeout(() => typed = '', 500);
                const match = options.findIndex(o => o.textContent.trim().toLowerCase().startsWith(typed));
                if (match >= 0) setActive(match);
            }
        });

        document.addEventListener('click', event => { if (isOpen() && !root.contains(event.target)) close(); });
        trigger.addEventListener('blur', () => setTimeout(() => { if (!root.contains(document.activeElement)) close(); }, 0));
    });
</script>

@yield('scripts')
</body>
</html>
