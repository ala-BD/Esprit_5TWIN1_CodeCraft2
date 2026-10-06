{{--
    Liste déroulante personnalisée (JS dans le layout admin).
    Attend : $name, $options [valeur => libellé], $selected
    Options : $id, $label (aria), $invalid, $submit (soumet le formulaire au changement), $class
--}}
@php
    $id       = $id ?? $name;
    $selected = (string) ($selected ?? '');
    $selected = array_key_exists($selected, $options) ? $selected : (string) array_key_first($options);
@endphp
<div class="a-select {{ $class ?? '' }}" data-select @if($submit ?? false) data-submit @endif>
    <input type="hidden" name="{{ $name }}" value="{{ $selected }}">

    <button type="button" id="{{ $id }}" class="a-input a-select-trigger {{ ($invalid ?? false) ? 'invalid' : '' }}"
            aria-haspopup="listbox" aria-expanded="false" aria-controls="{{ $id }}-liste"
            @isset($label) aria-label="{{ $label }}" @endisset>
        <span data-select-label class="truncate">{{ $options[$selected] }}</span>
        <i class="fas fa-chevron-down a-select-chevron" aria-hidden="true"></i>
    </button>

    <ul id="{{ $id }}-liste" class="a-select-menu" role="listbox" tabindex="-1" hidden>
        @foreach($options as $value => $text)
            <li id="{{ $id }}-option-{{ $loop->index }}" class="a-select-option" role="option"
                data-value="{{ $value }}" aria-selected="{{ (string) $value === $selected ? 'true' : 'false' }}">
                <span class="truncate">{{ $text }}</span>
                <i class="fas fa-check a-select-check" aria-hidden="true"></i>
            </li>
        @endforeach
    </ul>
</div>
