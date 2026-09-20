@php
    $accuracy = $session->questions_answered > 0
        ? round($session->questions_correct / $session->questions_answered * 100)
        : 0;
@endphp

<x-child-layout :child="$child">
    {{-- Confetti only if something was actually solved; extra cannons for a new badge. --}}
    <div
        class="w-full max-w-sm"
        @if ($session->questions_correct > 0)
            x-data
            x-init="celebrate({ big: {{ $newBadges->isNotEmpty() ? 'true' : 'false' }} })"
        @endif
    >
        <x-mascot :width="150" :height="229" :overlap="70" class="mascot-hop" />

        <div class="relative z-10 bg-white rounded-3xl shadow-xl p-8 text-center">
            <div class="text-6xl mb-3">🏁</div>
            <h1 class="text-2xl font-bold mb-1">{{ __('Super gemacht, :name!', ['name' => $child->name]) }}</h1>
                <p class="text-sm text-gray-500">{{ $session->exerciseType->name }}</p>

            <div class="grid grid-cols-2 gap-4 mt-6">
                <div class="bg-orange-50 rounded-2xl p-4">
                    <div class="text-3xl font-bold text-orange-600">{{ $session->total_points }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ __('Punkte') }}</div>
                </div>
                <div class="bg-sky-50 rounded-2xl p-4">
                    <div class="text-3xl font-bold text-sky-600">{{ $accuracy }}%</div>
                    <div class="text-xs text-gray-500 mt-1">{{ __('richtig') }}</div>
                </div>
            </div>

            <p class="text-sm text-gray-500 mt-4">
                {{ __(':count Aufgaben gelöst', ['count' => $session->questions_answered]) }}
            </p>

            @if ($newBadges->isNotEmpty())
                <div class="mt-6 rounded-2xl bg-amber-50 p-4">
                    <div class="font-bold text-amber-700 mb-3">🏅 {{ trans_choice('Neues Abzeichen!|Neue Abzeichen!', $newBadges->count()) }}</div>
                    <div class="space-y-3">
                        @foreach ($newBadges as $badge)
                            <div class="flex items-center gap-3 text-left">
                                <x-badge-medal :badge="$badge" :size="56" />
                                <div>
                                    <div class="font-semibold text-gray-800">{{ $badge->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $badge->description }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <a href="{{ route('child.home') }}" class="mt-6 inline-block w-full bg-orange-500 hover:bg-orange-600 text-white text-lg font-bold rounded-2xl py-4">
                {{ __('Zurück') }}
            </a>
        </div>
    </div>
</x-child-layout>
