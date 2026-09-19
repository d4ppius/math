<x-app-layout>
    <x-slot name="header">@include('admin._header', ['title' => __('Sessions')])</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin._flash')

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if (request()->filled('child'))
                    <p class="text-sm text-gray-600 mb-4">
                        {{ __('Gefiltert nach einem Kind.') }}
                        <a href="{{ route('admin.sessions.index') }}" class="text-indigo-600 underline">{{ __('Filter entfernen') }}</a>
                    </p>
                @endif

                @include('admin.sessions._table', ['sessions' => $sessions])

                <div class="mt-4">{{ $sessions->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
