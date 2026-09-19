<x-app-layout>
    <x-slot name="header">@include('admin._header', ['title' => __('Familien')])</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin._flash')

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="flex gap-2 mb-4">
                    <x-text-input name="q" :value="request('q')" placeholder="{{ __('Familie suchen …') }}" class="w-full sm:w-72" />
                    <x-secondary-button type="submit">{{ __('Suchen') }}</x-secondary-button>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2 pe-4 font-medium">{{ __('Familie') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Eltern') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Kinder') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Angelegt') }}</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($families as $family)
                                <tr class="border-b last:border-0">
                                    <td class="py-2 pe-4 font-medium text-gray-900">{{ $family->name }}</td>
                                    <td class="py-2 pe-4">{{ $family->users_count }}</td>
                                    <td class="py-2 pe-4">{{ $family->children_count }}</td>
                                    <td class="py-2 pe-4 whitespace-nowrap">{{ $family->created_at?->format('d.m.Y') }}</td>
                                    <td class="py-2 text-end"><a href="{{ route('admin.families.show', $family) }}" class="text-indigo-600 hover:underline">{{ __('Öffnen') }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-4 text-gray-500">{{ __('Keine Familien gefunden.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $families->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
