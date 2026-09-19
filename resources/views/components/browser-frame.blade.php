{{-- A screenshot in a simple browser window. --}}
@props(['src', 'alt', 'imageWidth', 'imageHeight'])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black/10']) }}>
    <div class="flex items-center gap-1.5 border-b border-gray-200 bg-gray-100 px-3 py-2" aria-hidden="true">
        <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
        <span class="h-2.5 w-2.5 rounded-full bg-yellow-400"></span>
        <span class="h-2.5 w-2.5 rounded-full bg-green-400"></span>
    </div>
    <img
        src="{{ asset('images/landing/'.$src) }}?v={{ filemtime(public_path('images/landing/'.$src)) }}"
        alt="{{ $alt }}"
        width="{{ $imageWidth }}"
        height="{{ $imageHeight }}"
        loading="lazy"
        decoding="async"
        style="display:block;width:100%;height:auto"
    >
</div>
