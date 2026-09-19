{{-- Level title plus a progress bar to the next level. $level comes from Child::level(). --}}
@props(['level'])

<div {{ $attributes }}>
    <div class="font-semibold text-orange-700">
        {{ $level['emoji'] }} {{ __('Level :n', ['n' => $level['level']]) }} · {{ $level['title'] }}
    </div>

    <div class="mt-2 h-3 rounded-full bg-orange-100 overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($level['progress'] * 100) }}">
        <div class="h-full rounded-full bg-orange-500" style="width: {{ round($level['progress'] * 100) }}%"></div>
    </div>

    <div class="mt-1 text-xs text-gray-500">
        @if ($level['is_max'])
            {{ __('Höchstes Level erreicht!') }}
        @else
            {{ __('Noch :points Punkte bis Level :next', ['points' => $level['points_to_next'], 'next' => $level['next_level']]) }}
        @endif
    </div>
</div>
