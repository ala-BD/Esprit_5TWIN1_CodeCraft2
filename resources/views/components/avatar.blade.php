{{-- Photo de profil, ou initiales sur fond dégradé si aucune photo --}}
@props(['user'])

@if($user?->photo_profil)
    <img src="{{ $user->photo_url }}" alt="Photo de {{ $user->full_name }}"
         {{ $attributes->merge(['class' => 'object-cover flex-shrink-0']) }}>
@else
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center font-bold text-white flex-shrink-0']) }}
         style="background: linear-gradient(135deg, #0d9488, #4ade80)">
        {{ $user?->initials ?: '?' }}
    </div>
@endif
