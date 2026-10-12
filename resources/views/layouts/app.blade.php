<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RETISS') — Économie Circulaire du Textile</title>

    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- Axios & Alpine.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    {{-- SweetAlert2 CDN (Modales & Pop-ups modernes) --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Tailwind Config — Couleurs exactes du logo RETISS --}}
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        // Couleurs extraites du logo
                        navy:    { DEFAULT: '#1a2744', light: '#243560', dark: '#111a30' },
                        forest:  { DEFAULT: '#1B4332', light: '#2D6A4F', dark: '#0f2a1e' },
                        teal:    { DEFAULT: '#0d9488', light: '#2DD4BF', dark: '#0f766e' },
                        lime:    { DEFAULT: '#4ade80', light: '#86EFAC', dark: '#16a34a' },

                        // Alias pratiques
                        brand:   { DEFAULT: '#1a2744', light: '#243560', dark: '#111a30' },
                        primary: { DEFAULT: '#0d9488', light: '#2DD4BF', dark: '#0f766e' },
                        accent:  { DEFAULT: '#4ade80', light: '#86EFAC', dark: '#16a34a' },
                    },
                    fontFamily: {
                        sans:    ['Inter', 'sans-serif'],
                        display: ['Sora', 'Inter', 'sans-serif'],
                    },
                    boxShadow: {
                        'brand': '0 4px 24px rgba(26,39,68,0.15)',
                        'teal':  '0 4px 24px rgba(13,148,136,0.25)',
                    }
                }
            }
        }
    </script>

    <style>
        * { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
        }

        /* ---- Dégradé hero principal (navy → forest) ---- */
        .hero-gradient {
            background: linear-gradient(135deg, #111a30 0%, #1a2744 35%, #1B4332 70%, #0d9488 100%);
        }

        /* ---- Dégradé brand (navy) ---- */
        .brand-gradient {
            background: linear-gradient(135deg, #111a30 0%, #1a2744 100%);
        }

        /* ---- Dégradé teal ---- */
        .teal-gradient {
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #2DD4BF 100%);
        }

        /* ---- Dégradé accent (lime) ---- */
        .accent-gradient {
            background: linear-gradient(135deg, #16a34a 0%, #4ade80 100%);
        }

        /* ---- Glassmorphism ---- */
        .glass {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .glass-light {
            background: rgba(255, 255, 255, 0.90);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        /* ---- Animations ---- */
        .fade-up {
            animation: fadeUp 0.6s ease-out forwards;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        /* ---- Inputs RETISS ---- */
        .input-retiss {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
            border-radius: 0.875rem;
            border: 1.5px solid #e2e8f0;
            background: #fff;
            font-size: 0.9rem;
            color: #0f172a;
            transition: all 0.2s;
            outline: none;
        }
        .input-retiss:focus {
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13,148,136,0.12);
        }
        .input-retiss.error {
            border-color: #ef4444;
            background: #fff5f5;
        }

        /* ---- Boutons ---- */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #0f766e, #0d9488);
            color: #fff;
            font-weight: 700;
            padding: 0.875rem 1.75rem;
            border-radius: 0.875rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
            letter-spacing: 0.01em;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #0d9488, #2DD4BF);
            box-shadow: 0 8px 24px rgba(13,148,136,0.35);
            transform: translateY(-1px);
        }
        .btn-primary:active { transform: translateY(0); }

        .btn-navy {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #111a30, #1a2744);
            color: #fff;
            font-weight: 700;
            padding: 0.875rem 1.75rem;
            border-radius: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        .btn-navy:hover {
            background: linear-gradient(135deg, #1a2744, #243560);
            box-shadow: 0 8px 24px rgba(26,39,68,0.35);
            transform: translateY(-1px);
        }

        .btn-outline-white {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: transparent;
            color: #fff;
            font-weight: 600;
            padding: 0.875rem 1.75rem;
            border-radius: 0.875rem;
            border: 2px solid rgba(255,255,255,0.35);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        .btn-outline-white:hover {
            background: rgba(255,255,255,0.12);
            border-color: rgba(255,255,255,0.6);
        }

        /* ---- Cards ---- */
        .card {
            background: #fff;
            border-radius: 1.25rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 8px rgba(15,23,42,0.06);
            transition: all 0.25s;
        }
        .card:hover {
            box-shadow: 0 8px 32px rgba(15,23,42,0.1);
            transform: translateY(-2px);
        }

        /* ---- Badge statut ---- */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* ---- Scrollbar ---- */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #0d9488; border-radius: 3px; }

        /* ---- Auth panels ---- */
        .auth-panel {
            background: linear-gradient(145deg, #111a30 0%, #1a2744 40%, #1B4332 75%, #0d9488 100%);
        }

        /* ---- Noise texture overlay ---- */
        .noise::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
            pointer-events: none;
            border-radius: inherit;
        }

        /* ---- Espace pour le header et sous-header fixe ---- */
        body:not(.has-hero) main {
            padding-top: {{ auth()->check() ? '120px' : '72px' }};
        }
        .marketplace-page,
        .article-show-page,
        .checkout-page,
        .addresses-page,
        .orders-page,
        .order-show-page {
            padding-top: 0 !important;
        }

        body.has-hero main {
            padding-top: 0 !important;
        }
        body.has-hero #sub-header {
            background: rgba(17, 26, 48, 0.45);
            border-color: rgba(255, 255, 255, 0.08);
        }
        body.has-hero #sub-header .subnav-link:not(.bg-teal-600) {
            color: rgba(255, 255, 255, 0.85);
        }
        body.has-hero #sub-header .subnav-link:not(.bg-teal-600):hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.15);
        }
        body.has-hero.scrolled #sub-header {
            background: rgba(255, 255, 255, 0.97) !important;
            border-color: #e2e8f0 !important;
        }
        body.has-hero.scrolled #sub-header .subnav-link:not(.bg-teal-600) {
            color: #475569;
        }

        /* ---- RETISS Pop-up Modale Personnalisée ---- */
        .retiss-swal-popup {
            border-radius: 1.75rem !important;
            padding: 2.25rem 2rem !important;
            background: #ffffff !important;
            border: 1px solid rgba(226, 232, 240, 0.8) !important;
            box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.25), 0 0 0 1px rgba(13, 148, 136, 0.08) !important;
            font-family: 'Inter', sans-serif !important;
        }
        .retiss-swal-popup .swal2-icon {
            margin: 0.5rem auto 1.25rem auto !important;
            border-width: 3px !important;
            transform: scale(1.05) !important;
        }
        .retiss-swal-popup .swal2-icon.swal2-warning {
            border-color: #f43f5e !important;
            color: #e11d48 !important;
        }
        .retiss-swal-popup .swal2-icon.swal2-success {
            border-color: #0d9488 !important;
            color: #0d9488 !important;
        }
        .retiss-swal-popup .swal2-icon.swal2-info {
            border-color: #0284c7 !important;
            color: #0284c7 !important;
        }
        .retiss-swal-title {
            font-family: 'Sora', sans-serif !important;
            font-size: 1.35rem !important;
            font-weight: 800 !important;
            color: #0f172a !important;
            padding: 0 !important;
            letter-spacing: -0.02em !important;
        }
        .retiss-swal-html {
            font-size: 0.925rem !important;
            color: #64748b !important;
            line-height: 1.6 !important;
            margin: 0.75rem 0 0 0 !important;
        }
        .retiss-swal-popup .swal2-actions {
            margin-top: 1.75rem !important;
            gap: 0.75rem !important;
            width: 100% !important;
            justify-content: center !important;
        }
        .retiss-swal-confirm-btn {
            font-family: 'Inter', sans-serif !important;
            font-weight: 700 !important;
            font-size: 0.875rem !important;
            padding: 0.85rem 1.65rem !important;
            border-radius: 1rem !important;
            cursor: pointer !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.5rem !important;
            border: none !important;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.12) !important;
        }
        .retiss-swal-confirm-btn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.18) !important;
        }
        .retiss-swal-confirm-btn.btn-danger {
            background: linear-gradient(135deg, #f43f5e, #e11d48, #be123c) !important;
            color: #ffffff !important;
        }
        .retiss-swal-confirm-btn.btn-danger:hover {
            background: linear-gradient(135deg, #e11d48, #be123c, #9f1239) !important;
        }
        .retiss-swal-confirm-btn.btn-teal {
            background: linear-gradient(135deg, #0d9488, #0f766e) !important;
            color: #ffffff !important;
        }
        .retiss-swal-confirm-btn.btn-teal:hover {
            background: linear-gradient(135deg, #0f766e, #115e59) !important;
        }
        .retiss-swal-cancel-btn {
            font-family: 'Inter', sans-serif !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            padding: 0.85rem 1.65rem !important;
            border-radius: 1rem !important;
            background: #f8fafc !important;
            color: #475569 !important;
            border: 1px solid #e2e8f0 !important;
            cursor: pointer !important;
            transition: all 0.2s !important;
        }
        .retiss-swal-cancel-btn:hover {
            background: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }
        .retiss-swal-backdrop {
            background: rgba(15, 23, 42, 0.6) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
        }
        .swal2-container.retiss-swal-backdrop.swal2-backdrop-hide {
            background: transparent !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
        .retiss-swal-toast {
            border-radius: 1.25rem !important;
            box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.2) !important;
            border: 1px solid #e2e8f0 !important;
            font-family: 'Inter', sans-serif !important;
            padding: 0.85rem 1.25rem !important;
        }
    </style>

    @yield('styles')
</head>
<body class="min-h-screen flex flex-col antialiased @yield('body-class')">

    @include('components.navbar')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('components.footer')

    @yield('scripts')

    {{-- Script Global de Confirmation Pop-Up RETISS --}}
    <script>
    window.retissConfirm = function({
        title = 'Confirmation requise',
        text = 'Voulez-vous vraiment effectuer cette action ?',
        icon = 'warning',
        confirmButtonText = 'Confirmer',
        cancelButtonText = 'Annuler',
        isDestructive = true
    } = {}) {
        if (typeof Swal === 'undefined') {
            return Promise.resolve(confirm(text));
        }

        const iconHtml = isDestructive
            ? '<i class="fas fa-trash-alt text-xs mr-1.5"></i>'
            : '<i class="fas fa-check text-xs mr-1.5"></i>';

        return Swal.fire({
            title: title,
            html: `<p class="retiss-swal-html">${text}</p>`,
            icon: isDestructive ? 'warning' : (icon || 'question'),
            iconColor: isDestructive ? '#e11d48' : '#0d9488',
            showCancelButton: true,
            confirmButtonText: `${iconHtml}<span>${confirmButtonText}</span>`,
            cancelButtonText: cancelButtonText,
            reverseButtons: true,
            focusCancel: isDestructive,
            allowOutsideClick: true,
            allowEscapeKey: true,
            customClass: {
                popup: 'retiss-swal-popup',
                title: 'retiss-swal-title',
                htmlContainer: 'retiss-swal-html',
                confirmButton: 'retiss-swal-confirm-btn ' + (isDestructive ? 'btn-danger' : 'btn-teal'),
                cancelButton: 'retiss-swal-cancel-btn',
                backdrop: 'retiss-swal-backdrop'
            },
            buttonsStyling: false
        }).then(result => {
            if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                Swal.close();
            }
            return Boolean(result.isConfirmed);
        });
    };

    window.retissToast = function({
        title = '',
        message = '',
        icon = 'success'
    } = {}) {
        if (typeof Swal === 'undefined') {
            alert(message || title);
            return;
        }
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3200,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            },
            customClass: {
                popup: 'retiss-swal-toast'
            }
        });

        Toast.fire({
            icon: icon,
            title: title || message
        });
    };

    window.retissAlert = function({
        title = 'Information',
        text = '',
        icon = 'info',
        confirmButtonText = 'D\'accord'
    } = {}) {
        if (typeof Swal === 'undefined') {
            alert(text);
            return Promise.resolve();
        }
        return Swal.fire({
            title: title,
            html: `<p class="retiss-swal-html">${text}</p>`,
            icon: icon,
            confirmButtonText: `<span class="px-2">${confirmButtonText}</span>`,
            allowOutsideClick: true,
            allowEscapeKey: true,
            customClass: {
                popup: 'retiss-swal-popup',
                title: 'retiss-swal-title',
                htmlContainer: 'retiss-swal-html',
                confirmButton: 'retiss-swal-confirm-btn btn-teal',
                backdrop: 'retiss-swal-backdrop'
            },
            buttonsStyling: false
        }).then(result => {
            if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                Swal.close();
            }
            return result;
        });
    };

    // Interception automatique de tous les formulaires avec confirmation
    document.addEventListener('DOMContentLoaded', function () {
        // Pré-nettoyage des formulaires avec onsubmit confirm
        document.querySelectorAll('form[onsubmit*="confirm("]').forEach(form => {
            const onsubmitAttr = form.getAttribute('onsubmit');
            const match = onsubmitAttr.match(/confirm\(['"`](.*?)['"`]\)/);
            if (match) {
                form.dataset.confirm = match[1];
                form.removeAttribute('onsubmit');
            }
        });

        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form || form.tagName !== 'FORM') return;

            // Déjà confirmé ? Laisser passer
            if (form.dataset.retissConfirmed === 'true') {
                return;
            }

            // 1. Attribut explicite data-confirm
            if (form.dataset.confirm) {
                e.preventDefault();
                e.stopImmediatePropagation();

                const title = form.dataset.confirmTitle || 'Confirmation requise';
                const text = form.dataset.confirm;
                const btnText = form.dataset.confirmButton || 'Oui, confirmer';
                const isDestructive = form.dataset.confirmDanger !== 'false';

                retissConfirm({
                    title: title,
                    text: text,
                    confirmButtonText: btnText,
                    isDestructive: isDestructive
                }).then(confirmed => {
                    if (confirmed) {
                        form.dataset.retissConfirmed = 'true';
                        form.submit();
                    }
                });
                return false;
            }

            // 2. Ancien attribut onsubmit="return confirm(...)" résiduel
            const onsubmitAttr = form.getAttribute('onsubmit');
            if (onsubmitAttr && onsubmitAttr.includes('confirm(')) {
                e.preventDefault();
                e.stopImmediatePropagation();

                const match = onsubmitAttr.match(/confirm\(['"`](.*?)['"`]\)/);
                const text = match ? match[1] : 'Voulez-vous vraiment continuer ?';

                retissConfirm({
                    title: 'Confirmation requise',
                    text: text,
                    confirmButtonText: 'Oui, confirmer',
                    isDestructive: true
                }).then(confirmed => {
                    if (confirmed) {
                        form.removeAttribute('onsubmit');
                        form.dataset.retissConfirmed = 'true';
                        form.submit();
                    }
                });
                return false;
            }
        }, true);
    });
    </script>
</body>
</html>
