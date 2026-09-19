{{-- Icon plus two-tone wordmark, echoing the logo. --}}
@props(['size' => 36])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <img src="{{ asset('images/icons/icon-192.png') }}?v=2" alt="" width="{{ $size }}" height="{{ $size }}" style="width:{{ $size }}px;height:{{ $size }}px" class="rounded-xl">
    <span class="font-display text-2xl font-bold leading-none tracking-tight"><span class="text-blue-900">Rechen</span><span class="text-orange-500">fuchs</span></span>
</span>
