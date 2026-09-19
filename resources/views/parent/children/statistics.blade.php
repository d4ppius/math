@php
    $statusClasses = [
        'good' => 'bg-green-600 text-white',
        'warning' => 'bg-amber-400 text-gray-900',
        'serious' => 'bg-orange-500 text-white',
        'critical' => 'bg-red-600 text-white',
        'unseen' => 'bg-gray-100 text-gray-400',
    ];
    $maxWeeklyPoints = max(1, collect($weeklyPoints)->max('points'));
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Statistik für :name', ['name' => $child->name]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="mb-2">
                <a href="{{ route('parent.children.edit', $child) }}" class="text-sm text-indigo-600 underline">
                    ← {{ __('Zurück zu :name', ['name' => $child->name]) }}
                </a>
            </div>

            {{-- Level --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Level') }}</h3>
                <x-level-progress :level="$child->level()" />
                <p class="text-sm text-gray-500 mt-3">{{ __(':points Punkte insgesamt', ['points' => $child->total_points]) }}</p>
            </div>

            {{-- Badges --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Abzeichen') }}</h3>
                <p class="text-sm text-gray-500 mb-4">{{ $earnedBadges->count() }} {{ __('von') }} {{ $badges->count() }} {{ __('verdient') }}</p>
                <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach ($badges as $badge)
                        @php $earned = $earnedBadges->get($badge->id); @endphp
                        <div class="flex flex-col items-center text-center">
                            <x-badge-medal :badge="$badge" :earned="(bool) $earned" :size="56" />
                            <div class="mt-2 text-xs font-medium {{ $earned ? 'text-gray-800' : 'text-gray-400' }}">{{ $badge->name }}</div>
                            <div class="text-xs text-gray-400">
                                {{ $earned ? \Illuminate\Support\Carbon::parse($earned->pivot->earned_at)->format('d.m.Y') : $badge->description }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Weekly points --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Punkte diese Woche') }}</h3>
                <div class="flex items-end gap-3 h-40">
                    @foreach ($weeklyPoints as $day)
                        @php $heightPct = max(4, round($day['points'] / $maxWeeklyPoints * 100)); @endphp
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <span class="text-xs text-gray-500">{{ $day['points'] }}</span>
                            <div class="w-full bg-orange-100 rounded-t-md flex items-end" style="height: 100%;">
                                <div class="w-full bg-orange-500 rounded-t-md" style="height: {{ $heightPct }}%;" title="{{ $day['points'] }} {{ __('Punkte') }}"></div>
                            </div>
                            <span class="text-xs text-gray-400 uppercase">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Goal streak --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Tagesziel erreicht') }}</h3>
                <div class="flex gap-3">
                    @foreach ($goalStreak as $day)
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold {{ $day['met'] ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-400' }}">
                                {{ $day['met'] ? '✓' : '–' }}
                            </div>
                            <span class="text-xs text-gray-400 uppercase">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Heatmap --}}
            @if ($heatmap)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-1">{{ $heatmap['label'] }}</h3>
                    <p class="text-sm text-gray-500 mb-4">{{ __('Prozentzahl = Anteil richtig beantworteter Versuche.') }}</p>

                    <div class="flex flex-wrap items-center gap-4 mb-4 text-xs">
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded {{ $statusClasses['good'] }}"></span> {{ __('sicher') }}</span>
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded {{ $statusClasses['warning'] }}"></span> {{ __('okay') }}</span>
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded {{ $statusClasses['serious'] }}"></span> {{ __('schwierig') }}</span>
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded {{ $statusClasses['critical'] }}"></span> {{ __('sehr schwierig') }}</span>
                        <span class="flex items-center gap-1"><span class="w-3 h-3 rounded {{ $statusClasses['unseen'] }}"></span> {{ __('noch nicht geübt') }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="border-collapse">
                            <thead>
                                <tr>
                                    <th class="w-10"></th>
                                    @foreach ($heatmap['cols'] as $col)
                                        <th class="text-xs text-gray-400 font-normal w-12 pb-1">×{{ $col }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($heatmap['rows'] as $row)
                                    <tr>
                                        <th class="text-xs text-gray-400 font-normal pr-2 text-right">{{ $row }}×</th>
                                        @foreach ($heatmap['cols'] as $col)
                                            @php
                                                $stat = $heatmap['cells']->get($row.'-'.$col);
                                                $status = $stat ? $stat->masteryStatus() : 'unseen';
                                                $accuracyLabel = $stat && $stat->attempts_total > 0
                                                    ? round($stat->accuracy() * 100).'%'
                                                    : '–';
                                            @endphp
                                            <td class="p-0.5">
                                                <div class="w-12 h-10 rounded flex items-center justify-center text-xs font-semibold {{ $statusClasses[$status] }}"
                                                     title="{{ $row }} × {{ $col }} = {{ $row * $col }}">
                                                    {{ $accuracyLabel }}
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-sm text-gray-500">
                    {{ __('Noch keine Übungsdaten vorhanden.') }}
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
