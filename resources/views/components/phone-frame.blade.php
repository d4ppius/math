{{-- A screenshot in a phone frame. Width/height are set on the image so the layout never jumps. --}}
@props(['src', 'alt', 'width' => 260, 'imageWidth' => 560, 'imageHeight' => 1000, 'eager' => false])

<div {{ $attributes->merge(['class' => 'mx-auto overflow-hidden bg-gray-900 shadow-2xl ring-1 ring-black/10']) }} style="width:{{ $width }}px;max-width:100%;padding:9px;border-radius:2.4rem">
    <img
        src="{{ asset('images/landing/'.$src) }}?v={{ filemtime(public_path('images/landing/'.$src)) }}"
        alt="{{ $alt }}"
        width="{{ $imageWidth }}"
        height="{{ $imageHeight }}"
        @unless ($eager) loading="lazy" @endunless
        decoding="async"
        style="display:block;width:100%;height:auto;border-radius:1.9rem"
    >
</div>
