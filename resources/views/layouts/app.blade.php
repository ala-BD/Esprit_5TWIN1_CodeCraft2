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
</body>
</html>
