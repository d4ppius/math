<x-app-layout>
    <x-slot name="header">@include('admin._header', ['title' => __('Kinder')])</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin._flash')

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" class="flex gap-2 mb-4">
                    <x-text-input name="q" :value="request('q')" placeholder="{{ __('Kind oder Familie suchen …') }}" class="w-full sm:w-72" />
                    <x-secondary-button type="submit">{{ __('Suchen') }}</x-secondary-button>
                </form>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2 pe-4 font-medium">{{ __('Kind') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Familie') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Punkte') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Status') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Zuletzt aktiv') }}</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($children as $child)
                                <tr class="border-b last:border-0">
                                    <td class="py-2 pe-4 font-medium text-gray-900">{{ $child->name }}</td>
                                    <td class="py-2 pe-4"><a href="{{ route('admin.families.show', $child->family_id) }}" class="text-indigo-600 hover:underline">{{ $child->family->name }}</a></td>
                                    <td class="py-2 pe-4">{{ $child->total_points }}</td>
                                    <td class="py-2 pe-4">{{ $child->active ? __('Aktiv') : __('Deaktiviert') }}</td>
                                    <td class="py-2 pe-4 whitespace-nowrap">{{ $child->last_seen_at?->diffForHumans() ?? '–' }}</td>
                                    <td class="py-2 text-end whitespace-nowrap space-x-3">
                                        <a href="{{ route('parent.children.edit', $child) }}" class="text-indigo-600 hover:underline">{{ __('Einstellungen') }}</a>
                                        <a href="{{ route('parent.children.exercise-settings.edit', $child) }}" class="text-indigo-600 hover:underline">{{ __('Übung') }}</a>
                                        <a href="{{ route('parent.children.statistics', $child) }}" class="text-indigo-600 hover:underline">{{ __('Statistik') }}</a>
                                        <a href="{{ route('admin.sessions.index', ['child' => $child->id]) }}" class="text-indigo-600 hover:underline">{{ __('Sessions') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-4 text-gray-500">{{ __('Keine Kinder gefunden.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $children->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
