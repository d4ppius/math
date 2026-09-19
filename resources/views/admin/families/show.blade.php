<x-app-layout>
    <x-slot name="header">@include('admin._header', ['title' => $family->name])</x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin._flash')

            <a href="{{ route('admin.families.index') }}" class="text-sm text-indigo-600 underline">← {{ __('Alle Familien') }}</a>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Familie') }}</h3>
                <form method="POST" action="{{ route('admin.families.update', $family) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name', $family->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="timezone" :value="__('Zeitzone')" />
                        <x-text-input id="timezone" name="timezone" class="block mt-1 w-full" :value="old('timezone', $family->timezone)" required />
                        <x-input-error :messages="$errors->get('timezone')" class="mt-2" />
                    </div>
                    <x-primary-button>{{ __('Speichern') }}</x-primary-button>
                </form>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Eltern-Konten') }}</h3>
                <ul class="divide-y">
                    @forelse ($family->users as $user)
                        <li class="py-3 flex items-center justify-between gap-4 flex-wrap">
                            <div>
                                <div class="font-medium text-gray-900">
                                    {{ $user->name }}
                                    @if ($user->is_admin)
                                        <span class="ms-2 text-xs bg-indigo-100 text-indigo-700 rounded px-2 py-0.5">{{ __('Admin') }}</span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500">{{ $user->email }}</div>
                            </div>
                            @unless ($user->is_admin)
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('admin.users.impersonate', $user) }}">
                                        @csrf
                                        <x-secondary-button type="submit">{{ __('Als Elternteil ansehen') }}</x-secondary-button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('{{ __('Dieses Eltern-Konto endgültig löschen?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <x-danger-button>{{ __('Löschen') }}</x-danger-button>
                                    </form>
                                </div>
                            @endunless
                        </li>
                    @empty
                        <li class="py-3 text-sm text-gray-500">{{ __('Keine Eltern-Konten.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Kinder') }}</h3>
                <ul class="divide-y">
                    @forelse ($family->children as $child)
                        <li class="py-3 flex items-center justify-between gap-4 flex-wrap">
                            <div>
                                <div class="font-medium text-gray-900">{{ $child->name }}
                                    @unless ($child->active)
                                        <span class="ms-2 text-xs text-red-600">{{ __('Deaktiviert') }}</span>
                                    @endunless
                                </div>
                                <div class="text-sm text-gray-500">{{ $child->total_points }} {{ __('Punkte') }}</div>
                            </div>
                            <div class="flex items-center gap-4 text-sm">
                                <a href="{{ route('parent.children.edit', $child) }}" class="text-indigo-600 hover:underline">{{ __('Einstellungen') }}</a>
                                <a href="{{ route('parent.children.exercise-settings.edit', $child) }}" class="text-indigo-600 hover:underline">{{ __('Übung') }}</a>
                                <a href="{{ route('parent.children.statistics', $child) }}" class="text-indigo-600 hover:underline">{{ __('Statistik') }}</a>
                                <a href="{{ route('admin.sessions.index', ['child' => $child->id]) }}" class="text-indigo-600 hover:underline">{{ __('Sessions') }}</a>
                            </div>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-gray-500">{{ __('Keine Kinder.') }}</li>
                    @endforelse
                </ul>
            </div>

            @unless ($family->id === auth()->user()->family_id)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 border border-red-200">
                    <h3 class="text-lg font-medium text-red-700">{{ __('Familie löschen') }}</h3>
                    <p class="text-sm text-gray-600 mt-1">{{ __('Entfernt die Familie samt allen Eltern-Konten, Kindern, Sessions und Statistiken. Das kann nicht rückgängig gemacht werden.') }}</p>
                    <form method="POST" action="{{ route('admin.families.destroy', $family) }}" class="mt-4" onsubmit="return confirm('{{ __('Familie und alle zugehörigen Daten endgültig löschen?') }}')">
                        @csrf
                        @method('DELETE')
                        <x-danger-button>{{ __('Familie endgültig löschen') }}</x-danger-button>
                    </form>
                </div>
            @endunless
        </div>
    </div>
</x-app-layout>
