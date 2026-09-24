<x-child-layout :child="$child" :token="$token ?? null">
    @php
        $avatarEmoji = ['fox' => '🦊', 'owl' => '🦉', 'cat' => '🐱', 'bear' => '🐻', 'rabbit' => '🐰', 'panda' => '🐼'];
    @endphp

    <div class="w-full max-w-sm">
        <x-mascot :width="150" :height="229" :overlap="70" class="mascot-float" />

        <div class="relative z-10 bg-white rounded-3xl shadow-xl p-8 text-center">
            <div class="text-7xl mb-2">{{ $avatarEmoji[$child->avatar] ?? '⭐' }}</div>
            <h1 class="text-3xl font-bold mb-1">{{ __('Hallo :name!', ['name' => $child->name]) }}</h1>
            <p class="text-orange-600 font-semibold mb-3">⭐ {{ $child->total_points }} {{ __('Punkte') }}</p>

            @php
                $exercises = $child->availableExercises();
                $cardColors = ['bg-orange-500 hover:bg-orange-600', 'bg-sky-500 hover:bg-sky-600', 'bg-emerald-500 hover:bg-emerald-600', 'bg-purple-500 hover:bg-purple-600'];
            @endphp

            @if ($exercises->count() === 1)
                <x-level-progress :level="$exercises->first()->level()" class="mb-6" />
            @endif

            @if ($errors->any())
                <p class="text-red-600 text-sm mb-4">{{ $errors->first('exercise') }}</p>
            @endif

            <form method="POST" action="{{ route('child.sessions.start') }}" class="{{ $exercises->count() > 1 ? 'space-y-3' : '' }}">
                @csrf

                @if ($exercises->count() > 1)
                    <p class="text-sm font-semibold text-orange-700">{{ __('Was möchtest du üben?') }}</p>

                    @foreach ($exercises as $exercise)
                        <button type="submit" name="exercise" value="{{ $exercise->exerciseType->key }}" class="w-full {{ $cardColors[$loop->index % count($cardColors)] }} text-white text-2xl font-bold rounded-2xl py-4 px-5 shadow-lg active:scale-95 transition">
                            {{ $exercise->implementation->emoji() }} {{ $exercise->exerciseType->name }}
                            <x-level-progress :level="$exercise->level()" :on-color="true" :show-remaining="false" class="mt-2 text-base" />
                            @if ($child->resumableSessionFor($exercise->exercise_type_id))
                                <span class="block text-sm font-semibold opacity-90 mt-1">{{ __('Weiter üben') }}</span>
                            @endif
                        </button>
                    @endforeach
                @else
                    @if ($exercises->count() === 1)
                        <input type="hidden" name="exercise" value="{{ $exercises->first()->exerciseType->key }}">
                    @endif
                    <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white text-2xl font-bold rounded-2xl py-5 shadow-lg active:scale-95 transition">
                        🚀 {{ __('Los geht\'s!') }}
                    </button>
                @endif
            </form>

            @php
                $earnedBadges = $child->badges;
                $visibleBadges = $child->show_locked_badges ? $child->attainableBadges() : $earnedBadges;
            @endphp
            {{-- The details live on the achievements page; only shown if there is something to see. --}}
            @if ($child->show_locked_badges || $earnedBadges->isNotEmpty())
                <a href="{{ route('child.achievements') }}" class="mt-6 flex items-center justify-between gap-3 rounded-2xl bg-amber-50 px-4 py-3 text-left hover:bg-amber-100 active:scale-95 transition">
                    <div>
                        <div class="font-semibold text-amber-800">🏅 {{ __('Meine Abzeichen') }}</div>
                        <div class="text-xs text-gray-500">{{ $earnedBadges->count() }} {{ __('von') }} {{ $visibleBadges->count() }}</div>
                    </div>
                    <div class="flex items-center">
                        @foreach ($earnedBadges->sortByDesc('pivot.earned_at')->take(3) as $badge)
                            <x-badge-medal :badge="$badge" :size="36" style="margin-left:-6px" />
                        @endforeach
                        <span class="ms-3 text-amber-600 text-xl" aria-hidden="true">›</span>
                    </div>
                </a>
            @endif

            <div
                x-data="{
                    status: 'idle',
                    vapidPublicKey: '{{ config('webpush.vapid.public_key') }}',
                    subscribeUrl: '{{ route('child.push-subscriptions.store') }}',
                    csrfToken: document.querySelector('meta[name=csrf-token]').content,
                    async subscribe() {
                        this.status = 'loading';
                        const result = await window.subscribeToPush(this.vapidPublicKey, this.subscribeUrl, this.csrfToken);
                        this.status = result.ok ? 'subscribed' : (result.reason || 'error');
                    },
                }"
                class="mt-6"
            >
                <button
                    type="button"
                    @click="subscribe()"
                    x-show="status !== 'subscribed'"
                    :disabled="status === 'loading'"
                    class="text-sm text-orange-500 underline disabled:opacity-50"
                >
                    🔔 {{ __('Erinnere mich ans Üben!') }}
                </button>
                <p class="text-sm text-green-600" x-show="status === 'subscribed'">
                    🔔 {{ __('Erledigt! Du bekommst eine Erinnerung.') }}
                </p>
                <p class="text-xs text-gray-400 mt-1" x-show="status === 'denied' || status === 'unsupported'">
                    {{ __('Das funktioniert nur, wenn dieses Icon vom Home-Bildschirm gestartet wurde.') }}
                </p>
            </div>

            <form method="POST" action="{{ route('child.logout') }}" class="mt-4">
                @csrf
                <button type="submit" class="text-sm text-gray-400 underline">{{ __('Abmelden') }}</button>
            </form>
        </div>
    </div>
</x-child-layout>
