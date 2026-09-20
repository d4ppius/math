<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Übungseinstellungen für :name', ['name' => $child->name]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg p-4">
                    {{ session('status') }}
                </div>
            @endif

            <div class="mb-2">
                <a href="{{ route('parent.children.edit', $child) }}" class="text-sm text-indigo-600 underline">
                    ← {{ __('Zurück zu :name', ['name' => $child->name]) }}
                </a>
            </div>

            <form method="POST" action="{{ route('parent.children.exercise-settings.update', $child) }}" class="space-y-6">
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg p-4" role="alert">
                        <ul class="list-disc ps-5 space-y-1">
                            @foreach ($errors->all() as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @foreach ($settings as $index => $setting)
                    @php $groups = $setting->implementation->groups(); @endphp
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" x-data="{ enabled: {{ $setting->enabled ? 'true' : 'false' }} }">
                        <input type="hidden" name="settings[{{ $index }}][id]" value="{{ $setting->id }}">

                        <h3 class="text-lg font-medium text-gray-900">{{ $setting->implementation->label() }}</h3>

                        <div class="mt-3">
                            <label class="inline-flex items-center">
                                <input type="hidden" name="settings[{{ $index }}][enabled]" value="0">
                                <input type="checkbox" name="settings[{{ $index }}][enabled]" value="1" class="rounded border-gray-300" x-model="enabled"
                                       {{ $setting->enabled ? 'checked' : '' }}>
                                <span class="ms-2 text-sm font-medium text-gray-700">{{ __('Für :name freischalten', ['name' => $child->name]) }}</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">{{ __('Ist die Übung aus, sieht :name sie nicht auf der Startseite. Deine Einstellungen darunter bleiben erhalten.', ['name' => $child->name]) }}</p>
                        </div>

                        <div x-show="enabled" x-cloak>
                        <div class="mt-4">
                            <x-input-label :value="__('Aktive :label', ['label' => $setting->implementation->groupsLabel()])" />
                            <p class="text-xs text-gray-500 mb-2">{{ __('Nur die ausgewählten :label werden abgefragt. Am besten mit wenigen starten.', ['label' => $setting->implementation->groupsLabel()]) }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($groups as $group => $groupLabel)
                                    <label class="cursor-pointer">
                                        <input type="checkbox" name="settings[{{ $index }}][active_groups][]" value="{{ $group }}" class="peer sr-only"
                                               {{ in_array($group, $setting->active_groups) ? 'checked' : '' }}>
                                        <span class="flex items-center justify-center min-w-10 h-10 px-3 rounded-full border-2 border-gray-200 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 font-semibold">
                                            {{ $groupLabel }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-4">
                            <x-input-label for="duration-{{ $index }}" :value="__('Session-Dauer (Minuten)')" />
                            <select id="duration-{{ $index }}" name="settings[{{ $index }}][session_duration_minutes]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                @foreach ([3, 5, 10, 15, 20] as $minutes)
                                    <option value="{{ $minutes }}" {{ $setting->session_duration_minutes === $minutes ? 'selected' : '' }}>{{ $minutes }} {{ __('Minuten') }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mt-4">
                            <label class="inline-flex items-center">
                                <input type="hidden" name="settings[{{ $index }}][show_timer]" value="0">
                                <input type="checkbox" name="settings[{{ $index }}][show_timer]" value="1" class="rounded border-gray-300"
                                       {{ $setting->show_timer ? 'checked' : '' }}>
                                <span class="ms-2 text-sm text-gray-700">{{ __('Timer während der Übung anzeigen') }}</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">{{ __('Ohne Timer läuft die Zeit im Hintergrund weiter. Kurz vor Schluss erscheint stattdessen ein sanftes „Gleich geschafft!“ ganz ohne Zahlen.') }}</p>
                        </div>

                        <div class="mt-4">
                            <label class="inline-flex items-center">
                                <input type="hidden" name="settings[{{ $index }}][speed_bonus_enabled]" value="0">
                                <input type="checkbox" name="settings[{{ $index }}][speed_bonus_enabled]" value="1" class="rounded border-gray-300"
                                       {{ $setting->speed_bonus_enabled ? 'checked' : '' }}>
                                <span class="ms-2 text-sm text-gray-700">{{ __('Tempo-Bonus bei den Punkten') }}</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">{{ __('Mit Tempo-Bonus gibt es für schnelle richtige Antworten bis zu 10 Extrapunkte. Ohne bekommt jede richtige Antwort gleich viele Punkte, egal wie schnell. Langsame richtige Antworten bekommen nie weniger als die Grundpunkte.') }}</p>
                        </div>

                        <div class="mt-4">
                            <label class="inline-flex items-center">
                                <input type="hidden" name="settings[{{ $index }}][sound_enabled]" value="0">
                                <input type="checkbox" name="settings[{{ $index }}][sound_enabled]" value="1" class="rounded border-gray-300"
                                       {{ $setting->sound_enabled ? 'checked' : '' }}>
                                <span class="ms-2 text-sm text-gray-700">{{ __('Töne bei richtigen und falschen Antworten') }}</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">{{ __('Ein heller Klang bei richtigen, ein leiser tiefer Ton bei falschen Antworten. Praktisch ausschalten, wenn das Kind in Ruhe oder unterwegs übt.') }}</p>
                        </div>

                        <div class="mt-4">
                            <x-input-label for="frequency-{{ $index }}" :value="__('Ziel-Häufigkeit')" />
                            <select id="frequency-{{ $index }}" name="settings[{{ $index }}][target_frequency]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="daily" {{ $setting->target_frequency === 'daily' ? 'selected' : '' }}>{{ __('Täglich') }}</option>
                                <option value="weekdays" {{ $setting->target_frequency === 'weekdays' ? 'selected' : '' }}>{{ __('Wochentags') }}</option>
                            </select>
                        </div>
                        </div>
                    </div>
                @endforeach

                <div class="flex justify-end">
                    <x-primary-button>{{ __('Speichern') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
