{{-- Bulle d'un message de la discussion. Paramètres : $message, $monRole (CLIENT|ATELIER), $nouveau (bool, optionnel) --}}
@php $estMoi = $message->auteur === $monRole; @endphp

<div class="flex gap-3 {{ $estMoi ? 'flex-row-reverse' : '' }} {{ ($nouveau ?? false) ? 'message-nouveau' : '' }}" data-message-id="{{ $message->id }}">
    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 text-xs font-bold
        {{ $message->est_ia ? 'bg-gradient-to-br from-violet-600 to-pink-500 text-white' : ($estMoi ? 'bg-primary text-white' : 'bg-white border border-gray-200 text-gray-600') }}">
        @if($message->est_ia)
            <i class="fas fa-wand-magic-sparkles"></i>
        @else
            {{ $message->user?->initials ?? '?' }}
        @endif
    </div>
    <div class="max-w-[75%] {{ $estMoi ? 'text-right' : '' }}">
        <p class="text-[11px] text-gray-400 mb-1">
            <span class="font-semibold {{ $message->est_ia ? 'text-violet-600' : 'text-gray-600' }}">{{ $message->nom_auteur }}</span>
            · <time datetime="{{ $message->created_at->toIso8601String() }}" title="{{ $message->created_at->format('d/m/Y H:i') }}">{{ $message->created_at->diffForHumans() }}</time>
        </p>
        <div class="inline-block text-left text-sm leading-relaxed px-4 py-3 rounded-2xl
            {{ $message->est_ia ? 'bulle-ia bg-violet-50 border border-violet-100 text-gray-800'
                : ($estMoi ? 'bulle-moi bg-primary text-white' : 'bulle-ia bg-white border border-gray-100 text-gray-700') }}">
            {!! nl2br(e($message->contenu)) !!}
        </div>
    </div>
</div>
