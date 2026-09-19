{{--
    A badge as a round medal: artwork if it exists, else the emoji. Locked
    (not yet earned) badges are greyed out. Sizes are inline on purpose so the
    medal keeps its size even with an older CSS build.
--}}
@props(['badge', 'earned' => true, 'size' => 64])

@php
    $image = $badge->imageUrl();
    $row = $badge->rowNumber();
    $lockedStyle = $earned ? '' : 'filter:grayscale(1);opacity:.4;';
    // A caller-supplied style (e.g. an overlap margin) is appended, not a second style attribute.
    $style = "width:{$size}px;height:{$size}px;{$lockedStyle}".$attributes->get('style');
@endphp

<span
    title="{{ $badge->name }}"
    {{ $attributes->except('style')->merge(['class' => 'relative inline-flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-200 to-orange-300 ring-4 ring-white shadow']) }}
    style="{{ $style }}"
>
    @if ($image)
        <img src="{{ $image }}" alt="" draggable="false" style="width:86%;height:86%;object-fit:contain" class="pointer-events-none select-none">
    @else
        <span style="font-size:{{ round($size * 0.5) }}px;line-height:1" aria-hidden="true">{{ $badge->icon }}</span>
    @endif

    @if ($row)
        <span
            class="absolute -bottom-1 -right-1 flex items-center justify-center rounded-full bg-indigo-600 font-bold text-white ring-2 ring-white"
            style="width:{{ round($size * 0.42) }}px;height:{{ round($size * 0.42) }}px;font-size:{{ round($size * 0.24) }}px"
        >{{ $row }}</span>
    @endif
</span>
