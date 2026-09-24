{{--
    Level title plus a progress bar to the next level. $level comes from
    LevelCalculator::forPoints(), usually via ChildExerciseSetting::level().
    :on-color="true" switches to white-on-translucent for use on a coloured
    card (e.g. an exercise choice button) instead of the white background this
    is normally shown on. :show-remaining="false" hides the "Noch X Punkte..."
    line for tighter spots such as that same card.
--}}
@props(['level', 'onColor' => false, 'showRemaining' => true])

<div {{ $attributes }}>
    <div class="font-semibold {{ $onColor ? 'text-white' : 'text-orange-700' }}">
        {{ $level['emoji'] }} {{ __('Level :n', ['n' => $level['level']]) }} · {{ $level['title'] }}
    </div>

    <div class="mt-2 h-3 rounded-full overflow-hidden {{ $onColor ? 'bg-white/25' : 'bg-orange-100' }}" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ round($level['progress'] * 100) }}">
        <div class="h-full rounded-full {{ $onColor ? 'bg-white' : 'bg-orange-500' }}" style="width: {{ round($level['progress'] * 100) }}%"></div>
    </div>

    @if ($showRemaining)
        <div class="mt-1 text-xs {{ $onColor ? 'text-white/80' : 'text-gray-500' }}">
            @if ($level['is_max'])
                {{ __('Höchstes Level erreicht!') }}
            @else
                {{ __('Noch :points Punkte bis Level :next', ['points' => $level['points_to_next'], 'next' => $level['next_level']]) }}
            @endif
        </div>
    @endif
</div>
