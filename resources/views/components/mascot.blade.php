{{-- The Rechenfuchs mascot. Decorative only, so it is hidden from screen readers. --}}
@props(['size' => 'w-28 h-28', 'file' => 'icon-512'])

<img
    src="{{ asset('images/icons/'.$file.'.png') }}?v=2"
    alt=""
    aria-hidden="true"
    draggable="false"
    {{ $attributes->merge(['class' => $size.' drop-shadow-lg select-none pointer-events-none']) }}
>
