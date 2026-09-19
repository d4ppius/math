<x-app-layout>
    <x-slot name="header">@include('admin._header', ['title' => __('Aufgabe bearbeiten')])</x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <a href="{{ route('admin.exercises.facts.index', $fact->exercise_type_id) }}" class="text-sm text-indigo-600 underline">← {{ __('Zurück zu :name', ['name' => $fact->exerciseType->name]) }}</a>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-500 mb-4">{{ __('Achtung: Änderungen wirken sofort bei allen Kindern.') }}</p>
                <form method="POST" action="{{ route('admin.facts.update', $fact) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    @foreach (['operand_a' => 'Wert A', 'operand_b' => 'Wert B', 'correct_answer' => 'Richtige Antwort', 'difficulty_group' => 'Gruppe'] as $field => $label)
                        <div>
                            <x-input-label :for="$field" :value="__($label)" />
                            <x-text-input :id="$field" :name="$field" type="number" class="block mt-1 w-full" :value="old($field, $fact->{$field})" required />
                            <x-input-error :messages="$errors->get($field)" class="mt-2" />
                        </div>
                    @endforeach
                    <x-primary-button>{{ __('Speichern') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
