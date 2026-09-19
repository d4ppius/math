<x-app-layout>
    <x-slot name="header">@include('admin._header', ['title' => __('Administration')])</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin._flash')

            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach ([
                    ['Familien', $counts['families']],
                    ['Eltern-Konten', $counts['parents']],
                    ['Kinder', $counts['children']],
                    ['Sessions gesamt', $counts['sessions']],
                    ['Heute aktiv', $counts['practicedToday']],
                ] as [$label, $value])
                    <div class="bg-white shadow-sm sm:rounded-lg p-5">
                        <div class="text-3xl font-semibold text-gray-900">{{ $value }}</div>
                        <div class="text-sm text-gray-500 mt-1">{{ __($label) }}</div>
                    </div>
                @endforeach
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Letzte Sessions') }}</h3>
                @include('admin.sessions._table', ['sessions' => $recentSessions])
            </div>
        </div>
    </div>
</x-app-layout>
