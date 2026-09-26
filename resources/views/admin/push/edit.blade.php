<x-app-layout>
    <x-slot name="header">@include('admin._header', ['title' => __('Push-Benachrichtigungen')])</x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('admin._flash')

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-600">
                {{ __(':subscribedParents von :parents Eltern haben Push aktiviert, :subscribedChildren von :children Kindern.', [
                    'subscribedParents' => $counts['parentsSubscribed'], 'parents' => $counts['parents'],
                    'subscribedChildren' => $counts['childrenSubscribed'], 'children' => $counts['children'],
                ]) }}
            </div>

            <form
                method="POST"
                action="{{ route('admin.push.send') }}"
                class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4"
                x-data="{ audience: '{{ old('audience', 'all_parents') }}' }"
            >
                @csrf

                <div>
                    <x-input-label :value="__('Empfänger')" />
                    <div class="mt-2 space-y-2">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="audience" value="all_parents" x-model="audience" {{ old('audience', 'all_parents') === 'all_parents' ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">{{ __('Alle Eltern') }}</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="audience" value="all_children" x-model="audience" {{ old('audience') === 'all_children' ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">{{ __('Alle Kinder') }}</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="audience" value="specific" x-model="audience" {{ old('audience') === 'specific' ? 'checked' : '' }}>
                            <span class="text-sm text-gray-700">{{ __('Bestimmte Person') }}</span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('audience')" class="mt-2" />
                </div>

                <div x-show="audience === 'specific'" x-cloak>
                    <x-input-label for="recipient" :value="__('Person')" />
                    <select id="recipient" name="recipient" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">{{ __('— auswählen —') }}</option>
                        <optgroup label="{{ __('Eltern') }}">
                            @foreach ($parents as $parent)
                                <option value="user:{{ $parent->id }}" {{ old('recipient') === "user:{$parent->id}" ? 'selected' : '' }}>
                                    {{ $parent->name }} ({{ $parent->family->name }})
                                </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="{{ __('Kinder') }}">
                            @foreach ($children as $child)
                                <option value="child:{{ $child->id }}" {{ old('recipient') === "child:{$child->id}" ? 'selected' : '' }}>
                                    {{ $child->name }} ({{ $child->family->name }})
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                    <x-input-error :messages="$errors->get('recipient')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="title" :value="__('Titel')" />
                    <x-text-input id="title" name="title" class="block mt-1 w-full" :value="old('title')" maxlength="100" required />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="body" :value="__('Text')" />
                    <textarea id="body" name="body" rows="3" maxlength="500" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>{{ old('body') }}</textarea>
                    <x-input-error :messages="$errors->get('body')" class="mt-2" />
                </div>

                <x-primary-button>{{ __('Senden') }}</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
