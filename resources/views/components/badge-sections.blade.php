{{--
    Badges as medal grids, split into sections per exercise (headings only appear
    when more than one exercise has badges in the list). $earned maps badge id =>
    the earned badge with its pivot; a badge not in it is shown locked.
--}}
@props(['badges', 'earned', 'size' => 64, 'gridClass' => 'grid-cols-3'])

@php
    $sections = app(\App\Services\Gamification\BadgeSections::class)->group($badges);
    $headings = collect($sections)->where('exercise', true)->count() > 1;
@endphp

<div class="space-y-6">
    @foreach ($sections as $section)
        <div>
            @if ($headings)
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-400">{{ $section['label'] }}</h3>
            @endif

            <div class="grid {{ $gridClass }} gap-x-2 gap-y-5">
                @foreach ($section['badges'] as $badge)
                    @php $isEarned = $earned->has($badge->id); @endphp
                    <div class="flex flex-col items-center text-center">
                        <x-badge-medal :badge="$badge" :earned="$isEarned" :size="$size" />
                        <div class="mt-2 text-xs font-semibold leading-tight {{ $isEarned ? 'text-gray-800' : 'text-gray-500' }}">{{ $badge->name }}</div>
                        @if ($isEarned)
                            <div class="text-[11px] text-gray-400">{{ \Illuminate\Support\Carbon::parse($earned[$badge->id]->pivot->earned_at)->format('d.m.Y') }}</div>
                        @else
                            <div class="mt-0.5 text-[11px] leading-tight text-gray-400">{{ $badge->description }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
