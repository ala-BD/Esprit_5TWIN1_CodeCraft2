{{--
    Centre de notifications (toutes les notifications in-app de l'utilisateur :
    commandes M2, projets d'upcycling M3...). Format attendu : data.message, data.url, data.icone (optionnel).
--}}
@php
    $nonLues = Auth::user()->unreadNotifications()->count();
    $dernieres = Auth::user()->notifications()->latest()->take(8)->get();
@endphp

<div class="relative" id="notif-wrapper">
    <button type="button" onclick="document.getElementById('notif-menu').classList.toggle('hidden')"
            class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 transition-colors relative"
            title="Notifications">
        <i class="fas fa-bell text-sm"></i>
        @if($nonLues > 0)
            <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">
                {{ $nonLues > 9 ? '9+' : $nonLues }}
            </span>
        @endif
    </button>

    <div id="notif-menu" class="hidden absolute right-0 mt-1.5 w-80 bg-white border border-slate-200 rounded-xl shadow-lg z-50 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <p class="text-sm font-semibold text-slate-800">Notifications</p>
            @if($nonLues > 0)
                <form method="POST" action="{{ route('notifications.tout-lire') }}">
                    @csrf
                    <button class="text-xs font-semibold text-primary-DEFAULT hover:underline">Tout marquer comme lu</button>
                </form>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto divide-y divide-slate-50">
            @forelse($dernieres as $notification)
                <a href="{{ route('notifications.lire', $notification->id) }}"
                   class="flex gap-3 px-4 py-3 hover:bg-slate-50 transition-colors {{ $notification->read_at ? '' : 'bg-teal-50/60' }}">
                    <div class="w-8 h-8 rounded-lg bg-white border border-slate-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas {{ $notification->data['icone'] ?? 'fa-bell' }} text-xs text-primary-DEFAULT"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-slate-700 leading-snug">{{ $notification->data['message'] ?? 'Nouvelle notification' }}</p>
                        <p class="text-[10px] text-slate-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    @unless($notification->read_at)
                        <span class="w-2 h-2 rounded-full bg-primary-DEFAULT flex-shrink-0 mt-1.5"></span>
                    @endunless
                </a>
            @empty
                <p class="px-4 py-8 text-center text-xs text-slate-400">Aucune notification pour le moment.</p>
            @endforelse
        </div>
    </div>
</div>

<script>
    document.addEventListener('click', (e) => {
        const wrapper = document.getElementById('notif-wrapper');
        if (wrapper && !wrapper.contains(e.target)) document.getElementById('notif-menu').classList.add('hidden');
    });
</script>
