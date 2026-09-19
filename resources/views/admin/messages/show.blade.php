<x-app-layout>
    <x-slot name="header">@include('admin._header', ['title' => __('Nachricht')])</x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin._flash')

            <a href="{{ route('admin.messages.index') }}" class="text-sm text-indigo-600 underline">← {{ __('Alle Nachrichten') }}</a>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div><dt class="text-gray-500">{{ __('Von') }}</dt><dd class="font-medium">{{ $message->name }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('E-Mail') }}</dt><dd class="font-medium">{{ $message->email }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('Thema') }}</dt><dd class="font-medium">{{ $message->topicLabel() }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('Eingegangen') }}</dt><dd class="font-medium">{{ $message->created_at->format('d.m.Y H:i') }}</dd></div>
                </dl>

                <div class="mt-6 whitespace-pre-line rounded-lg bg-gray-50 p-4 text-gray-800">{{ $message->message }}</div>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->topicLabel().' ('.config('app.name').')') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-indigo-700">{{ __('Antworten') }}</a>

                    <form method="POST" action="{{ route('admin.messages.update', $message) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="handled" value="{{ $message->isHandled() ? 0 : 1 }}">
                        <x-secondary-button type="submit">{{ $message->isHandled() ? __('Wieder öffnen') : __('Als erledigt markieren') }}</x-secondary-button>
                    </form>

                    <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" onsubmit="return confirm('{{ __('Diese Nachricht endgültig löschen?') }}')">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>{{ __('Löschen') }}</x-danger-button>
                    </form>
                </div>

                @if ($message->isHandled())
                    <p class="mt-4 text-xs text-gray-400">{{ __('Erledigt am :date. Erledigte Nachrichten werden nach :months Monaten automatisch gelöscht.', ['date' => $message->handled_at->format('d.m.Y'), 'months' => config('contact.retention_months')]) }}</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
