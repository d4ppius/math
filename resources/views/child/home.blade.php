<x-child-layout :child="$child" :token="$token ?? null">
    @php
        $avatarEmoji = ['fox' => '🦊', 'owl' => '🦉', 'cat' => '🐱', 'bear' => '🐻', 'rabbit' => '🐰', 'panda' => '🐼'];
    @endphp

    <x-mascot class="mascot-float mb-4" />

    <div class="bg-white rounded-3xl shadow-xl p-8 w-full max-w-sm text-center">
        <div class="text-7xl mb-2">{{ $avatarEmoji[$child->avatar] ?? '⭐' }}</div>
        <h1 class="text-3xl font-bold mb-1">{{ __('Hallo :name!', ['name' => $child->name]) }}</h1>
        <p class="text-orange-600 font-semibold mb-6">⭐ {{ $child->total_points }} {{ __('Punkte') }}</p>

        @if ($errors->any())
            <p class="text-red-600 text-sm mb-4">{{ $errors->first('exercise') }}</p>
        @endif

        <form method="POST" action="{{ route('child.sessions.start') }}">
            @csrf
            <button type="submit" class="w-full bg-orange-500 hover:bg-orange-600 text-white text-2xl font-bold rounded-2xl py-5 shadow-lg active:scale-95 transition">
                🚀 {{ __('Los geht\'s!') }}
            </button>
        </form>

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
</x-child-layout>
