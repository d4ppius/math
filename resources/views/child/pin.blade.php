<x-child-layout :child="$child ?? null">
    <div class="bg-white rounded-3xl shadow-xl p-8 w-full max-w-sm text-center">
        <div class="text-6xl mb-2">🔒</div>
        <h1 class="text-2xl font-bold mb-4">{{ __('Gib deinen Code ein') }}</h1>

        <form method="POST" action="{{ route('child.magic-link.pin', ['token' => $token]) }}">
            @csrf
            <input
                type="text"
                name="pin"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="4"
                autofocus
                class="w-full text-center text-4xl tracking-widest border-2 border-orange-300 rounded-2xl py-3 focus:border-orange-500 focus:ring-0"
                placeholder="••••"
            >

            @isset($pinError)
                <p class="text-red-600 mt-3">{{ $pinError }}</p>
            @endisset

            <button type="submit" class="mt-6 w-full bg-orange-500 hover:bg-orange-600 text-white text-xl font-bold rounded-2xl py-3">
                {{ __('Los!') }}
            </button>
        </form>
    </div>
</x-child-layout>
