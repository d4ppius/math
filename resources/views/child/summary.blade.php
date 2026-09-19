@php
    $accuracy = $session->questions_answered > 0
        ? round($session->questions_correct / $session->questions_answered * 100)
        : 0;
@endphp

<x-child-layout :child="$child">
    <div class="w-full max-w-sm">
        <x-mascot :width="150" :height="229" :overlap="70" class="mascot-hop" />

        <div class="relative z-10 bg-white rounded-3xl shadow-xl p-8 text-center">
            <div class="text-6xl mb-3">🏁</div>
            <h1 class="text-2xl font-bold mb-1">{{ __('Super gemacht, :name!', ['name' => $child->name]) }}</h1>

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

            <a href="{{ route('child.home') }}" class="mt-6 inline-block w-full bg-orange-500 hover:bg-orange-600 text-white text-lg font-bold rounded-2xl py-4">
                {{ __('Zurück') }}
            </a>
        </div>
    </div>
</x-child-layout>
