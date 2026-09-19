<x-app-layout>
    <x-slot name="header">@include('admin._header', ['title' => __('Nachrichten')])</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin._flash')

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="mb-4 flex flex-wrap gap-2 text-sm">
                    @foreach (['open' => 'Offen', 'handled' => 'Erledigt', 'all' => 'Alle'] as $key => $label)
                        <a href="{{ route('admin.messages.index', ['show' => $key]) }}" class="rounded-full border px-3 py-1 {{ $filter === $key ? 'border-indigo-500 text-indigo-700' : 'border-gray-200 text-gray-600' }}">
                            {{ $label }}@if ($key === 'open') ({{ $openCount }})@endif
                        </a>
                    @endforeach
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2 pe-4 font-medium">{{ __('Eingegangen') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Von') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Thema') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Nachricht') }}</th>
                                <th class="py-2 pe-4 font-medium">{{ __('Status') }}</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($messages as $message)
                                <tr class="border-b last:border-0 {{ $message->isHandled() ? 'text-gray-400' : '' }}">
                                    <td class="py-2 pe-4 whitespace-nowrap">{{ $message->created_at->format('d.m.Y H:i') }}</td>
                                    <td class="py-2 pe-4">{{ $message->name }}</td>
                                    <td class="py-2 pe-4">{{ $message->topicLabel() }}</td>
                                    <td class="py-2 pe-4">{{ \Illuminate\Support\Str::limit($message->message, 70) }}</td>
                                    <td class="py-2 pe-4">{{ $message->isHandled() ? __('Erledigt') : __('Offen') }}</td>
                                    <td class="py-2 text-end"><a href="{{ route('admin.messages.show', $message) }}" class="text-indigo-600 hover:underline">{{ __('Lesen') }}</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-4 text-gray-500">{{ __('Keine Nachrichten.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $messages->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
