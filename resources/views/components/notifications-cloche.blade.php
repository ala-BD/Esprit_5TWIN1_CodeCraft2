{{--
    Centre de notifications (toutes les notifications in-app de l'utilisateur :
    commandes M2, projets d'upcycling M3...). Format attendu : data.message, data.url, data.icone (optionnel).
    Le compteur et la liste se mettent à jour toutes les 30 s (GET notifications/resume).
--}}
@php
    $nonLues = Auth::user()->unreadNotifications()->count();
    $dernieres = Auth::user()->notifications()->latest()->take(8)->get();
@endphp

<div class="relative" id="notif-wrapper" data-url-resume="{{ route('notifications.resume') }}" data-non-lues="{{ $nonLues }}">
    <button type="button" id="notif-bouton" aria-haspopup="true" aria-expanded="false"
            class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 transition-colors relative"
            title="Notifications">
        <i class="fas fa-bell text-sm" id="notif-icone"></i>
        <span id="notif-badge"
              class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center {{ $nonLues > 0 ? '' : 'hidden' }}">
            {{ $nonLues > 9 ? '9+' : $nonLues }}
        </span>
    </button>

    <div id="notif-menu" class="hidden absolute right-0 mt-1.5 w-80 bg-white border border-slate-200 rounded-xl shadow-lg z-50 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <p class="text-sm font-semibold text-slate-800">Notifications</p>
            <form method="POST" action="{{ route('notifications.tout-lire') }}" id="notif-tout-lire" class="{{ $nonLues > 0 ? '' : 'hidden' }}">
                @csrf
                <button class="text-xs font-semibold text-primary-DEFAULT hover:underline">Tout marquer comme lu</button>
            </form>
        </div>

        <div class="max-h-96 overflow-y-auto divide-y divide-slate-50" id="notif-liste">
            @include('components.notifications-liste', ['dernieres' => $dernieres])
        </div>
    </div>
</div>

<script>
(() => {
    const wrapper = document.getElementById('notif-wrapper');
    const menu = document.getElementById('notif-menu');
    const bouton = document.getElementById('notif-bouton');
    const badge = document.getElementById('notif-badge');
    const titreInitial = document.title;
    let nonLues = Number(wrapper.dataset.nonLues);

    bouton.addEventListener('click', () => {
        menu.classList.toggle('hidden');
        bouton.setAttribute('aria-expanded', String(!menu.classList.contains('hidden')));
    });
    document.addEventListener('click', (e) => { if (!wrapper.contains(e.target)) menu.classList.add('hidden'); });

    const afficher = (nombre) => {
        badge.textContent = nombre > 9 ? '9+' : nombre;
        badge.classList.toggle('hidden', nombre === 0);
        document.getElementById('notif-tout-lire').classList.toggle('hidden', nombre === 0);
        document.title = (nombre > 0 ? `(${nombre}) ` : '') + titreInitial;
    };
    afficher(nonLues);

    const actualiser = async () => {
        if (document.hidden) return;
        try {
            const reponse = await fetch(wrapper.dataset.urlResume, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!reponse.ok) return;
            const donnees = await reponse.json();

            if (donnees.non_lues > nonLues) {
                document.getElementById('notif-icone').classList.add('fa-shake');
                setTimeout(() => document.getElementById('notif-icone').classList.remove('fa-shake'), 2000);
                if (window.Upcycling && donnees.derniere) Upcycling.toast(donnees.derniere, 'info', 6000);
            }
            nonLues = donnees.non_lues;
            afficher(nonLues);
            if (menu.classList.contains('hidden')) document.getElementById('notif-liste').innerHTML = donnees.html;
        } catch { /* hors ligne : prochain essai dans 30 s */ }
    };
    setInterval(actualiser, 30000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) actualiser(); });
})();
</script>
