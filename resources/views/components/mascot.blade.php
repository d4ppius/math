{{--
    The Rechenfuchs mascot. Decorative only, so hidden from screen readers.

    Size and overlap are set inline (not via Tailwind classes) on purpose: the
    artwork is large, so it must stay the right size even if the built CSS on a
    device is older than this markup.

    overlap > 0 centers the image and pulls the following element that many
    pixels over its bottom edge, so the fox "peeks" over the top of a card.
--}}
@props(['src' => 'images/mascot.png', 'width' => 150, 'height' => 229, 'overlap' => 0])

@php
    $style = "width:{$width}px;max-width:100%;height:auto;";

    if ($overlap > 0) {
        $style .= "display:block;margin:0 auto -{$overlap}px;position:relative;z-index:0;";
    }
@endphp

<img
    src="{{ asset($src) }}?v={{ filemtime(public_path($src)) }}"
    width="{{ $width }}"
    height="{{ $height }}"
    alt=""
    aria-hidden="true"
    draggable="false"
    style="{{ $style }}"
    {{ $attributes->merge(['class' => 'drop-shadow-lg select-none pointer-events-none']) }}
>
