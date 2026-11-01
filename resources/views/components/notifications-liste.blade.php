{{-- Liste des dernières notifications (cloche + actualisation AJAX). Paramètre : $dernieres --}}
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
