<x-child-layout :child="$child">
    <div class="w-full max-w-sm">
        <x-mascot :width="150" :height="229" :overlap="70" class="mascot-float" />

        <div class="relative z-10 bg-white rounded-3xl shadow-xl p-8 text-center">
            <h1 class="text-2xl font-bold mb-1">🏅 {{ __('Meine Erfolge') }}</h1>
            <p class="text-orange-600 font-semibold mb-4">⭐ {{ $child->total_points }} {{ __('Punkte') }}</p>

            <x-level-progress :level="$level" class="mb-6" />

            <section>
                <h2 class="font-bold text-amber-700">
                    {{ __('Deine Abzeichen') }}
                    <span class="text-gray-400 font-normal text-sm">({{ $earnedBadges->count() }} {{ __('von') }} {{ $total }})</span>
                </h2>

                @if ($earnedBadges->isEmpty())
                    <p class="text-sm text-gray-500 mt-2">{{ __('Noch keins. Übe los und hol dir dein erstes!') }}</p>
                @else
                    <div class="mt-4">
                        <x-badge-sections :badges="$earnedBadges" :earned="$earned" />
                    </div>
                @endif
            </section>

            @if ($lockedBadges->isNotEmpty())
                <section class="mt-8 border-t border-gray-100 pt-6">
                    <h2 class="font-bold text-gray-500">{{ __('Das kannst du noch schaffen') }}</h2>

                    <div class="mt-4">
                        <x-badge-sections :badges="$lockedBadges" :earned="$earned" />
                    </div>
                </section>
            @elseif ($child->show_locked_badges && $earnedBadges->isNotEmpty())
                <p class="mt-6 text-sm font-semibold text-green-600">🎉 {{ __('Wow, du hast alle Abzeichen!') }}</p>
            @endif

            <a href="{{ route('child.home') }}" class="mt-8 inline-block w-full bg-orange-500 hover:bg-orange-600 text-white text-lg font-bold rounded-2xl py-3 active:scale-95 transition">
                {{ __('Zurück') }}
            </a>
        </div>
    </div>
</x-child-layout>
