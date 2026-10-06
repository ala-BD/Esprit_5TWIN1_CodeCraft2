<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès refusé — RETISS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Sora', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
    <div class="max-w-md w-full text-center">

        {{-- Icône --}}
        <div class="w-20 h-20 rounded-2xl mx-auto mb-6 flex items-center justify-center"
             style="background: linear-gradient(135deg, #1B4332, #0d9488)">
            <i class="fas fa-lock text-white text-3xl"></i>
        </div>

        {{-- Code --}}
        <p class="text-sm font-semibold text-teal-600 tracking-widest uppercase mb-2">Erreur 403</p>

        {{-- Titre --}}
        <h1 class="font-display text-3xl font-bold text-gray-900 mb-3">
            Accès refusé
        </h1>

        {{-- Message --}}
        <p class="text-gray-500 text-sm leading-relaxed mb-8">
            Vous n'avez pas les permissions nécessaires pour accéder à cette page.
            Cette fonctionnalité est réservée à un autre profil utilisateur.
        </p>

        {{-- Actions --}}
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            @auth
                @php
                    $role = Auth::user()->role;
                    $retour = match($role) {
                        'RECYCLEUR'  => route('recyclage.dashboard'),
                        'ADMIN'      => route('admin.users.index'),
                        'COLLECTEUR' => route('logistique.tournees.index'),
                        'ATELIER'    => route('upcycling.dashboard'),
                        default      => route('dashboard'),
                    };
                @endphp
                <a href="{{ $retour }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-white text-sm font-semibold transition-all hover:opacity-90"
                   style="background: linear-gradient(135deg, #0f766e, #0d9488)">
                    <i class="fas fa-arrow-left text-xs"></i>
                    Retour à mon espace
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-white text-sm font-semibold transition-all hover:opacity-90"
                   style="background: linear-gradient(135deg, #0f766e, #0d9488)">
                    <i class="fas fa-sign-in-alt text-xs"></i>
                    Se connecter
                </a>
            @endauth
            <a href="{{ route('home') }}"
               class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-gray-600 text-sm font-semibold border border-gray-200 hover:bg-gray-50 transition-all">
                <i class="fas fa-home text-xs"></i>
                Accueil
            </a>
        </div>

        {{-- Branding --}}
        <div class="mt-10 flex items-center justify-center gap-2">
            <div class="w-6 h-6 rounded-md flex items-center justify-center"
                 style="background: linear-gradient(135deg, #1a2744, #0d9488)">
                <i class="fas fa-recycle text-white text-xs"></i>
            </div>
            <span class="text-xs font-semibold text-gray-400 tracking-wider uppercase">RETISS — Textile Circulaire</span>
        </div>
    </div>
</body>
</html>
