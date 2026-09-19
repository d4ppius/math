<x-app-layout>
    <x-slot name="header">@include('admin._header', ['title' => __('Übungen')])</x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin._flash')

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">{{ __('Eine deaktivierte Übung kann von Kindern nicht mehr gestartet werden. Laufende Statistiken bleiben erhalten.') }}</p>
                <ul class="divide-y">
                    @foreach ($exerciseTypes as $exerciseType)
                        <li class="py-4 flex items-center justify-between gap-4 flex-wrap">
                            <div>
                                <div class="font-medium text-gray-900">
                                    {{ $exerciseType->name }}
                                    <span class="ms-2 text-xs rounded px-2 py-0.5 {{ $exerciseType->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $exerciseType->is_active ? __('Aktiv') : __('Deaktiviert') }}
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $exerciseType->facts_count }} {{ __('Aufgaben') }} · {{ $exerciseType->child_exercise_settings_count }} {{ __('Kinder eingerichtet') }}
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.exercises.facts.index', $exerciseType) }}" class="text-sm text-indigo-600 hover:underline">{{ __('Aufgaben ansehen') }}</a>
                                <form method="POST" action="{{ route('admin.exercises.update', $exerciseType) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $exerciseType->is_active ? 0 : 1 }}">
                                    <x-secondary-button type="submit">{{ $exerciseType->is_active ? __('Deaktivieren') : __('Aktivieren') }}</x-secondary-button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
