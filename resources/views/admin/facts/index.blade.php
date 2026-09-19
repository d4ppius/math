<x-app-layout>
    <x-slot name="header">@include('admin._header', ['title' => __('Aufgaben: :name', ['name' => $exerciseType->name])])</x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin._flash')

            <a href="{{ route('admin.exercises.index') }}" class="text-sm text-indigo-600 underline">← {{ __('Alle Übungen') }}</a>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap gap-2 mb-4 text-sm">
                    <a href="{{ route('admin.exercises.facts.index', $exerciseType) }}" class="px-3 py-1 rounded-full border {{ request()->filled('group') ? 'border-gray-200 text-gray-600' : 'border-indigo-500 text-indigo-700' }}">{{ __('Alle') }}</a>
                    @foreach ($groups as $group)
                        <a href="{{ route('admin.exercises.facts.index', [$exerciseType, 'group' => $group]) }}" class="px-3 py-1 rounded-full border {{ request('group') !== null && (int) request('group') === $group ? 'border-indigo-500 text-indigo-700' : 'border-gray-200 text-gray-600' }}">{{ __('Gruppe') }} {{ $group }}</a>
                    @endforeach
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2 pe-4 font-medium">{{ __('Wert A') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Wert B') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Antwort') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Gruppe') }}</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($facts as $fact)
                                <tr class="border-b last:border-0">
                                    <td class="py-2 pe-4">{{ $fact->operand_a }}</td>
                                    <td class="py-2 pe-4">{{ $fact->operand_b }}</td>
                                    <td class="py-2 pe-4">{{ $fact->correct_answer }}</td>
                                    <td class="py-2 pe-4">{{ $fact->difficulty_group }}</td>
                                    <td class="py-2 text-end"><a href="{{ route('admin.facts.edit', $fact) }}" class="text-indigo-600 hover:underline">{{ __('Bearbeiten') }}</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $facts->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
