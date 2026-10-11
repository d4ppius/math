{{--
    Per-day right and wrong tasks (and, further down, pace and latest rounds) for
    the parents. Every combination of period (week/month) and exercise is rendered
    here and only shown or hidden by Alpine, like the heatmap tabs, so no data is
    fetched and nothing is computed in the browser. $trends comes from SessionTrends.
--}}
@props(['trends', 'exercises'])

@php
    $periods = ['week' => ['label' => __('Woche'), 'days' => 7], 'month' => ['label' => __('Monat'), 'days' => 30]];
    $scopeLabels = ['all' => __('Alle')] + $exercises;
    // A single exercise needs no "Alle" choice; with several, the mixed view is the start.
    $hasChoice = count($exercises) > 1;
    $startScope = $hasChoice ? 'all' : array_key_first($exercises);
    $totalDays = count($trends['days']);
@endphp

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="{ period: 'week', scope: '{{ $startScope }}' }">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <h3 class="text-lg font-medium text-gray-900">{{ __('Aufgaben im Verlauf') }}</h3>

        <div class="flex flex-wrap gap-2">
            @foreach ($periods as $periodKey => $period)
                <button type="button" @click="period = '{{ $periodKey }}'"
                        :aria-pressed="(period === '{{ $periodKey }}').toString()"
                        :class="period === '{{ $periodKey }}' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="rounded-full px-4 py-1.5 text-sm font-semibold transition">{{ $period['label'] }}</button>
            @endforeach
        </div>
    </div>

    @if ($hasChoice)
        <div class="flex flex-wrap gap-2 mb-5" role="tablist">
            @foreach ($scopeLabels as $scopeKey => $scopeLabel)
                <button type="button" role="tab" @click="scope = '{{ $scopeKey }}'"
                        :aria-selected="(scope === '{{ $scopeKey }}').toString()"
                        :class="scope === '{{ $scopeKey }}' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="rounded-full px-4 py-1.5 text-sm font-semibold transition">{{ $scopeLabel }}</button>
            @endforeach
        </div>
    @endif

    {{-- Right and wrong tasks per day --}}
    <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('Richtig und falsch pro Tag') }}</h4>
    @foreach ($trends['scopes'] as $scopeKey => $scope)
        @foreach ($periods as $periodKey => $period)
            @php
                $length = $period['days'];
                $offset = $totalDays - $length;
                $days = array_slice($trends['days'], $offset);
                $correct = array_slice($scope['correct'], $offset);
                $wrong = array_slice($scope['wrong'], $offset);
                $totals = array_map(fn ($right, $miss) => $right + $miss, $correct, $wrong);
                $maxTotal = max(1, max($totals));
                $isWeek = $periodKey === 'week';
            @endphp

            <div x-show="scope === '{{ $scopeKey }}' && period === '{{ $periodKey }}'" @unless ($scopeKey === $startScope && $periodKey === 'week') x-cloak @endunless
                 data-chart="tasks-{{ $scopeKey }}-{{ $periodKey }}">
                @if (array_sum($totals) === 0)
                    <p class="text-sm text-gray-500 mb-6">{{ __('Noch keine Runden in diesem Zeitraum.') }}</p>
                @else
                    <div class="flex {{ $isWeek ? 'gap-3' : 'gap-0.5' }} h-40 mb-1">
                        @foreach ($days as $i => $day)
                            @php
                                $rightPct = round($correct[$i] / $maxTotal * 100);
                                $missPct = round($wrong[$i] / $maxTotal * 100);
                            @endphp
                            <div class="flex-1 flex flex-col items-center gap-1 h-full min-w-0">
                                <span class="text-xs text-gray-500 h-4">{{ $isWeek && $totals[$i] > 0 ? $totals[$i] : '' }}</span>
                                {{-- The bar track must fill the column (flex-1): a percentage height needs a definite parent height. --}}
                                <div class="w-full flex-1 min-h-0 bg-gray-100 rounded-t-md flex flex-col justify-end overflow-hidden"
                                     title="{{ $day['dm'] }}: {{ $correct[$i] }} {{ __('richtig') }}, {{ $wrong[$i] }} {{ __('falsch') }}">
                                    @if ($wrong[$i] > 0)
                                        <div class="w-full bg-red-400" style="height: {{ $missPct }}%;" data-part="wrong"></div>
                                    @endif
                                    @if ($correct[$i] > 0)
                                        <div class="w-full bg-green-500" style="height: {{ $rightPct }}%;" data-part="correct"></div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex {{ $isWeek ? 'gap-3' : 'gap-0.5' }} mb-3">
                        @foreach ($days as $i => $day)
                            <div class="flex-1 min-w-0 relative h-4">
                                @if ($isWeek)
                                    <span class="block text-center text-xs text-gray-400 uppercase">{{ $day['weekday'] }}</span>
                                @elseif ($i % 5 === 0)
                                    <span class="absolute left-1/2 -translate-x-1/2 whitespace-nowrap text-[10px] text-gray-400">{{ $day['dm'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-4 mb-6 text-xs text-gray-600">
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-green-500"></span> {{ __('richtig') }}</span>
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-red-400"></span> {{ __('falsch') }}</span>
                    </div>
                @endif
            </div>
        @endforeach
    @endforeach
</div>
