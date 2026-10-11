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

    {{-- Pace: correct answers per minute --}}
    <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ __('Tempo: richtige Aufgaben pro Minute') }}</h4>
    @foreach ($trends['scopes'] as $scopeKey => $scope)
        @foreach ($periods as $periodKey => $period)
            @php
                $isWeek = $periodKey === 'week';
                $offset = $totalDays - $period['days'];
                $days = array_slice($trends['days'], $offset);
                $tempo = $scope['tempo'] === null ? [] : array_slice($scope['tempo'], $offset);
                $measured = array_filter($tempo, fn ($value) => $value !== null);
                $maxTempo = $measured === [] ? 1 : max(1, ceil(max($measured)));
                $count = count($days);
                $format = fn ($value) => number_format($value, 1, ',', '');

                // Consecutive measured days form one line; a day without a measurement breaks it.
                $segments = [];
                $run = [];
                foreach ($tempo as $i => $value) {
                    if ($value === null) {
                        $segments[] = $run;
                        $run = [];
                        continue;
                    }
                    $run[] = [round(($i + 0.5) / $count * 100, 2), round(100 - ($value / $maxTempo * 90 + 5), 2)];
                }
                $segments[] = $run;
                $lines = array_filter($segments, fn ($points) => count($points) >= 2);

                $trend = $scope['trend'][$periodKey] ?? null;
            @endphp

            <div x-show="scope === '{{ $scopeKey }}' && period === '{{ $periodKey }}'" @unless ($scopeKey === $startScope && $periodKey === 'week') x-cloak @endunless
                 data-chart="tempo-{{ $scopeKey }}-{{ $periodKey }}">
                @if ($scopeKey === 'all')
                    <p class="text-sm text-gray-500 mb-6">{{ __('Wähle eine Übung, um das Tempo zu sehen. Das Tempo lässt sich nur innerhalb einer Übung vergleichen.') }}</p>
                @elseif ($measured === [])
                    <p class="text-sm text-gray-500 mb-6">{{ __('Noch kein Tempo in diesem Zeitraum. Eine Runde braucht dafür mindestens :count Aufgaben.', ['count' => \App\Services\Statistics\SessionTrends::MIN_ANSWERED_FOR_TEMPO]) }}</p>
                @else
                    <p class="text-sm text-gray-700 mb-3" data-part="trend">
                        {{ __('Ø :value richtige pro Minute', ['value' => $format($trend['current'])]) }}
                        @if ($trend['previous'] !== null)
                            @php $difference = round($trend['current'] - $trend['previous'], 1); @endphp
                            @if ($difference > 0)
                                <span class="font-semibold text-green-700">(+{{ $format($difference) }} {{ $isWeek ? __('gegenüber der Vorwoche') : __('gegenüber dem Vormonat') }})</span>
                            @elseif ($difference < 0)
                                <span class="font-semibold text-orange-700">(−{{ $format(abs($difference)) }} {{ $isWeek ? __('gegenüber der Vorwoche') : __('gegenüber dem Vormonat') }})</span>
                            @else
                                <span class="text-gray-500">({{ $isWeek ? __('gleich wie in der Vorwoche') : __('gleich wie im Vormonat') }})</span>
                            @endif
                        @endif
                    </p>

                    <div class="relative h-32 border-b border-gray-200 mb-1">
                        <span class="absolute left-0 top-0 text-[10px] text-gray-400">{{ $format($maxTempo) }}</span>
                        <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="absolute inset-0 h-full w-full" aria-hidden="true">
                            @foreach ($lines as $points)
                                <polyline fill="none" stroke="#4f46e5" stroke-width="2" stroke-linejoin="round" vector-effect="non-scaling-stroke"
                                          points="{{ implode(' ', array_map(fn ($point) => $point[0].','.$point[1], $points)) }}" />
                            @endforeach
                        </svg>
                        @foreach ($tempo as $i => $value)
                            @if ($value !== null)
                                <span class="absolute h-2 w-2 -translate-x-1/2 translate-y-1/2 rounded-full bg-indigo-600"
                                      style="left: {{ round(($i + 0.5) / $count * 100, 2) }}%; bottom: {{ round($value / $maxTempo * 90 + 5, 2) }}%;"
                                      title="{{ $days[$i]['dm'] }}: {{ $format($value) }} {{ __('pro Minute') }}"></span>
                            @endif
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

                    <p class="text-xs text-gray-500 mb-6">{{ __('Das Tempo schwankt von Tag zu Tag. Schaltest du schwierigere Aufgaben frei, kann es vorübergehend sinken, obwohl das Kind besser wird.') }}</p>
                @endif
            </div>
        @endforeach
    @endforeach
</div>
