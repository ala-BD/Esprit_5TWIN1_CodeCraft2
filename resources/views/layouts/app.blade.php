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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- Tailwind Config --}}
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary:   { DEFAULT: '#2D6A4F', light: '#52B788', dark: '#1B4332' },
                        secondary: { DEFAULT: '#B7E4C7', light: '#D8F3DC', dark: '#74C69D' },
                        accent:    { DEFAULT: '#F4A261', dark: '#E76F51' },
                        earth:     { DEFAULT: '#A8936A', light: '#D4C5A9' },
                    },
                    fontFamily: {
                        sans:    ['Inter', 'sans-serif'],
                        display: ['Playfair Display', 'serif'],
                    },
                }
            }
        }
    </script>

    {{-- Styles globaux --}}
    <style>
        * { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #FAFAF8;
            color: #1a1a1a;
        }

        /* Dégradé hero */
        .hero-gradient {
            background: linear-gradient(135deg, #1B4332 0%, #2D6A4F 40%, #52B788 100%);
        }

        /* Glassmorphism card */
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Input focus custom */
        .input-retiss {
            @apply w-full px-4 py-3 rounded-xl border border-gray-200 bg-white
                   focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent
                   transition-all duration-200 text-gray-700 placeholder-gray-400;
        }

        /* Bouton primaire */
        .btn-primary {
            background: linear-gradient(135deg, #2D6A4F, #52B788);
            @apply text-white font-semibold py-3 px-6 rounded-xl
                   transition-all duration-200 hover:shadow-lg hover:scale-[1.02]
                   active:scale-[0.98] w-full;
        }

        /* Bouton outline */
        .btn-outline {
            @apply border-2 border-primary-DEFAULT text-primary-DEFAULT font-semibold
                   py-3 px-6 rounded-xl transition-all duration-200
                   hover:bg-primary-DEFAULT hover:text-white;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #52B788; border-radius: 3px; }
    </style>

    @yield('styles')
</head>
<body class="min-h-screen flex flex-col">

    {{-- Navbar --}}
    @include('components.navbar')

    {{-- Contenu principal --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('components.footer')

    @yield('scripts')
</body>
</html>
