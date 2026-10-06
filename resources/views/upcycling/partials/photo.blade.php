{{--
    Photo d'un vêtement / produit, ou illustration par défaut.
    Paramètres : $url (?string), $alt (string), $icone (string, optionnel), $classe (string, optionnel)
--}}
@if($url)
    <img src="{{ $url }}" alt="{{ $alt }}" loading="lazy"
         class="w-full h-full object-contain mix-blend-multiply {{ $classe ?? '' }}">
@else
    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary/10 via-white to-accent/10 {{ $classe ?? '' }}">
        <i class="fas {{ $icone ?? 'fa-tshirt' }} text-4xl text-primary/30"></i>
    </div>
@endif
