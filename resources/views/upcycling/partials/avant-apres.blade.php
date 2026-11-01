{{--
    Comparateur avant / après : un curseur à glisser révèle le vêtement d'origine sur le produit fini.
    Paramètres : $avant (URL), $apres (URL), $legendeAvant, $legendeApres, $hauteur (classe Tailwind, optionnel)
--}}
<div class="comparateur relative select-none overflow-hidden {{ $hauteur ?? 'aspect-[4/3]' }}" data-comparateur>
    {{-- Après (dessous) --}}
    <div class="absolute inset-0 p-4 bg-gradient-to-br from-slate-50 to-slate-100 isolate">
        @include('upcycling.partials.photo', ['url' => $apres, 'alt' => $legendeApres])
    </div>
    {{-- Avant (dessus, découpé selon la position du curseur) --}}
    <div class="calque-avant absolute inset-0 p-4 bg-gradient-to-br from-slate-100 to-slate-200 isolate">
        @include('upcycling.partials.photo', ['url' => $avant, 'alt' => $legendeAvant])
    </div>

    <span class="absolute top-3 left-3 z-10 text-[11px] font-bold text-white px-2.5 py-1 rounded-lg bg-gray-900/70 pointer-events-none">{{ $legendeAvant }}</span>
    <span class="absolute top-3 right-3 z-10 text-[11px] font-bold text-white px-2.5 py-1 rounded-lg bg-primary pointer-events-none">{{ $legendeApres }}</span>

    <input type="range" min="0" max="100" value="50" step="1" aria-label="Comparer avant et après"
           class="absolute inset-0 z-20 w-full h-full opacity-0 m-0">
    <div class="poignee absolute inset-y-0 z-10 w-0.5 -ml-px bg-white shadow-[0_0_0_1px_rgba(0,0,0,.08)] pointer-events-none">
        <span class="bouton-poignee absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white shadow-lg flex items-center justify-center text-primary text-xs gap-0.5">
            <i class="fas fa-chevron-left"></i><i class="fas fa-chevron-right"></i>
        </span>
    </div>
</div>

@once
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-comparateur]').forEach((comparateur) => {
        const curseur = comparateur.querySelector('input[type=range]');
        const placer = (valeur) => comparateur.style.setProperty('--position', valeur + '%');
        curseur.addEventListener('input', () => placer(curseur.value));

        // Petite démonstration à la première apparition pour montrer que l'image se compare
        if (!('IntersectionObserver' in window) || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const observateur = new IntersectionObserver(([entree]) => {
            if (!entree.isIntersecting) return;
            observateur.disconnect();
            const debut = performance.now();
            const animer = (t) => {
                const p = Math.min(1, (t - debut) / 1400);
                placer(50 - Math.sin(p * Math.PI * 2) * 22);
                if (p < 1 && document.activeElement !== curseur) requestAnimationFrame(animer);
                else placer(curseur.value);
            };
            requestAnimationFrame(animer);
        }, { threshold: 0.6 });
        observateur.observe(comparateur);
    });
});
</script>
@endonce
