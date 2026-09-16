<x-child-layout :child="$child">
    @php
        $avatarEmoji = ['fox' => '🦊', 'owl' => '🦉', 'cat' => '🐱', 'bear' => '🐻', 'rabbit' => '🐰', 'panda' => '🐼'];
    @endphp

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

        <form method="POST" action="{{ route('child.logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="text-sm text-gray-400 underline">{{ __('Abmelden') }}</button>
        </form>
    </div>
</x-child-layout>
