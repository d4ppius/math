<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Übersicht') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Kinder') }}</h3>
                        <p class="text-sm text-gray-500">{{ __('Familie') }}: {{ $family->name }}</p>
                    </div>
                    <a href="{{ route('parent.children.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        {{ __('Kind hinzufügen') }}
                    </a>
                </div>

                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse ($children as $child)
                        <a href="{{ route('parent.children.edit', $child) }}" class="block border rounded-lg p-4 hover:border-indigo-400 transition">
                            <div class="flex items-center gap-3">
                                <div class="text-3xl">{{ ['fox' => '🦊', 'owl' => '🦉', 'cat' => '🐱', 'bear' => '🐻', 'rabbit' => '🐰', 'panda' => '🐼'][$child->avatar] ?? '⭐' }}</div>
                                <div>
                                    <div class="font-semibold text-gray-900">{{ $child->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $child->total_points }} {{ __('Punkte') }}</div>
                                </div>
                            </div>
                            @unless ($child->active)
                                <div class="mt-2 text-xs text-red-600">{{ __('Deaktiviert') }}</div>
                            @endunless
                        </a>
                    @empty
                        <p class="text-sm text-gray-500">{{ __('Noch keine Kinder angelegt.') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Partner einladen') }}</h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ __('Teile diesen Link mit dem anderen Elternteil, damit er/sie sich für dieselbe Familie registrieren kann.') }}
                </p>
                <div class="mt-3 flex items-center gap-2">
                    <input type="text" readonly value="{{ $inviteUrl }}" class="flex-1 border-gray-300 rounded-md shadow-sm text-sm" onclick="this.select()">
                </div>
            </div>

            <div
                x-data="{
                    status: 'idle',
                    vapidPublicKey: '{{ config('webpush.vapid.public_key') }}',
                    subscribeUrl: '{{ route('parent.push-subscriptions.store') }}',
                    csrfToken: document.querySelector('meta[name=csrf-token]').content,
                    async subscribe() {
                        this.status = 'loading';
                        const result = await window.subscribeToPush(this.vapidPublicKey, this.subscribeUrl, this.csrfToken);
                        this.status = result.ok ? 'subscribed' : (result.reason || 'error');
                    },
                }"
                class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"
            >
                <h3 class="text-lg font-medium text-gray-900">{{ __('Benachrichtigungen') }}</h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ __('Erhalte eine Push-Benachrichtigung, wenn dein Kind die Tagesübung erledigt hat. Auf dem iPhone/iPad funktioniert das nur, wenn diese Seite über "Zum Home-Bildschirm hinzufügen" installiert wurde.') }}
                </p>

                <button
                    type="button"
                    @click="subscribe()"
                    :disabled="status === 'loading' || status === 'subscribed'"
                    class="mt-3 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50"
                >
                    <span x-show="status !== 'subscribed'">{{ __('Benachrichtigungen aktivieren') }}</span>
                    <span x-show="status === 'subscribed'">{{ __('Aktiviert ✓') }}</span>
                </button>

                <p class="text-sm text-red-600 mt-2" x-show="status === 'denied'">
                    {{ __('Berechtigung wurde abgelehnt. Du kannst sie in den Browser-Einstellungen wieder erlauben.') }}
                </p>
                <p class="text-sm text-red-600 mt-2" x-show="status === 'unsupported'">
                    {{ __('Dieser Browser unterstützt keine Push-Benachrichtigungen.') }}
                </p>
                <p class="text-sm text-red-600 mt-2" x-show="status === 'server_error'">
                    {{ __('Etwas ist schiefgelaufen. Bitte später erneut versuchen.') }}
                </p>
            </div>

        </div>
    </div>
</x-app-layout>
