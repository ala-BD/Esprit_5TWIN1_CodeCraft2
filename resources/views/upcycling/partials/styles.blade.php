{{-- Styles propres au module Upcycling (le layout commun app-auth ne les définit pas) --}}
<style>
    .hero-gradient {
        background: linear-gradient(135deg, #111a30 0%, #1a2744 35%, #1B4332 70%, #0d9488 100%);
    }

    .noise { position: relative; }
    .noise::after {
        content: '';
        position: absolute;
        inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
        pointer-events: none;
        border-radius: inherit;
    }

    .btn-primary,
    .btn-outline-white {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-weight: 700;
        font-size: 0.9rem;
        padding: 0.875rem 1.75rem;
        border-radius: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-primary {
        background: linear-gradient(135deg, #0f766e, #0d9488);
        color: #fff;
        border: none;
    }
    .btn-primary:hover {
        background: linear-gradient(135deg, #0d9488, #2DD4BF);
        box-shadow: 0 8px 24px rgba(13, 148, 136, 0.35);
        transform: translateY(-1px);
    }
    .btn-primary:active { transform: translateY(0); }
    .btn-primary:disabled { opacity: .6; cursor: wait; transform: none; box-shadow: none; }

    .btn-outline-white {
        background: transparent;
        color: #fff;
        border: 2px solid rgba(255, 255, 255, 0.35);
    }
    .btn-outline-white:hover {
        background: rgba(255, 255, 255, 0.12);
        border-color: rgba(255, 255, 255, 0.6);
    }

    /* Bulles de l'assistant styliste */
    .bulle-ia { border-top-left-radius: .375rem; }
    .bulle-moi { border-top-right-radius: .375rem; }

    @keyframes upcycling-points { 0%, 80%, 100% { opacity: .2 } 40% { opacity: 1 } }
    .points-ia span { animation: upcycling-points 1.2s infinite; }
    .points-ia span:nth-child(2) { animation-delay: .2s; }
    .points-ia span:nth-child(3) { animation-delay: .4s; }

    /* Toasts, modales et chargements AJAX (public/js/upcycling.js) */
    @keyframes upcycling-entree { from { opacity: 0; transform: translateY(10px) scale(.98) } to { opacity: 1; transform: none } }
    .toast-upcycling, .modale-boite, .message-nouveau { animation: upcycling-entree .25s ease-out; }
    .toast-sortie { opacity: 0; transform: translateX(20px); transition: all .25s; }
    .chargement-live { opacity: .45; pointer-events: none; transition: opacity .2s; }

    /* Comparateur avant / après */
    .comparateur { --position: 50%; isolation: isolate; touch-action: pan-y; }
    .comparateur .calque-avant { clip-path: inset(0 calc(100% - var(--position)) 0 0); }
    .comparateur .poignee { left: var(--position); }
    .comparateur input[type=range] { -webkit-appearance: none; appearance: none; background: transparent; cursor: ew-resize; }
    .comparateur input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; width: 44px; height: 100vh; }
    .comparateur input[type=range]::-moz-range-thumb { width: 44px; height: 100vh; border: 0; background: transparent; }
    .comparateur input[type=range]:focus-visible + .poignee .bouton-poignee { outline: 3px solid #2DD4BF; outline-offset: 2px; }

    /* Tableau Kanban de l'atelier */
    .kanban-colonne.cible-autorisee { box-shadow: 0 0 0 2px #0d9488 inset; background: rgba(13, 148, 136, .06); }
    .kanban-colonne.cible-interdite { opacity: .55; }
    .carte-fantome { opacity: .35; }
    .carte-deplacable { cursor: grab; }
    .carte-deplacable:active { cursor: grabbing; }
    .carte-choisie { transform: rotate(2deg); box-shadow: 0 18px 40px rgba(15, 23, 42, .18); }
</style>
