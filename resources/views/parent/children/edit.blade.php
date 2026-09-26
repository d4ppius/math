<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Einstellungen für :name', ['name' => $child->name]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg p-4">
                    {{ session('status') }}
                </div>
            @endif

            @if ($plainLoginToken)
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6">
                    <h3 class="font-semibold text-indigo-900">{{ __('Homescreen-Icon für :name einrichten', ['name' => $child->name]) }}</h3>
                    <p class="text-sm text-indigo-800 mt-2">
                        {{ __('Öffne diesen Link auf dem iPad in Safari und wähle "Zum Home-Bildschirm". Dieser Link wird nur einmal angezeigt – am besten gleich einrichten.') }}
                    </p>
                    <div class="mt-3 flex items-center gap-2">
                        <input type="text" readonly value="{{ $magicLinkUrl }}" class="flex-1 border-gray-300 rounded-md shadow-sm text-sm" onclick="this.select()">
                    </div>
                    <a href="{{ $magicLinkUrl }}" target="_blank" class="inline-block mt-3 text-sm text-indigo-700 underline">
                        {{ __('Link jetzt öffnen') }}
                    </a>
                </div>
            @else
                <div class="bg-white border rounded-lg p-6">
                    <p class="text-sm text-gray-600">
                        {{ __('Das Homescreen-Icon wurde bereits eingerichtet. Falls das iPad verloren geht oder der Link erneuert werden soll:') }}
                    </p>
                    <form method="POST" action="{{ route('parent.children.token', $child) }}" class="mt-3" onsubmit="return confirm('{{ __('Der alte Link funktioniert danach nicht mehr. Fortfahren?') }}')">
                        @csrf
                        <x-secondary-button type="submit">{{ __('Neuen Link erzeugen') }}</x-secondary-button>
                    </form>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex items-center justify-between">
                <div>
                    <h3 class="font-medium text-gray-900">{{ __('Übungseinstellungen') }}</h3>
                    <p class="text-sm text-gray-500">{{ __('Schwierigkeit, Session-Dauer und Häufigkeit festlegen.') }}</p>
                </div>
                <a href="{{ route('parent.children.exercise-settings.edit', $child) }}" class="text-sm text-indigo-600 underline whitespace-nowrap">
                    {{ __('Bearbeiten') }}
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex items-center justify-between">
                <div>
                    <h3 class="font-medium text-gray-900">{{ __('Statistik') }}</h3>
                    <p class="text-sm text-gray-500">{{ __('Fortschritt und starke/schwache Aufgaben ansehen.') }}</p>
                </div>
                <a href="{{ route('parent.children.statistics', $child) }}" class="text-sm text-indigo-600 underline whitespace-nowrap">
                    {{ __('Ansehen') }}
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 flex items-center justify-between gap-4">
                <div>
                    <h3 class="font-medium text-gray-900">{{ __('Vorschau als :name', ['name' => $child->name]) }}</h3>
                    <p class="text-sm text-gray-500">{{ __('Sieh genau, was :name auf dem eigenen Gerät sieht — ohne echte Punkte, Level oder Abzeichen zu verändern.', ['name' => $child->name]) }}</p>
                </div>
                <form method="POST" action="{{ route('child-preview.start', $child) }}">
                    @csrf
                    <x-secondary-button type="submit" class="whitespace-nowrap">{{ __('Vorschau starten') }}</x-secondary-button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('parent.children.update', $child) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    @include('parent.children._form', ['child' => $child])

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Speichern') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-900">{{ __('Kind entfernen') }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ __('Löscht alle Übungsdaten dieses Kindes unwiderruflich.') }}</p>
                <form method="POST" action="{{ route('parent.children.destroy', $child) }}" class="mt-3" onsubmit="return confirm('{{ __('Wirklich unwiderruflich löschen?') }}')">
                    @csrf
                    @method('DELETE')
                    <x-danger-button type="submit">{{ __('Kind löschen') }}</x-danger-button>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
