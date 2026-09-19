@php $avatarEmoji = ['fox' => '🦊', 'owl' => '🦉', 'cat' => '🐱', 'bear' => '🐻', 'rabbit' => '🐰', 'panda' => '🐼']; @endphp

<div>
    <x-input-label for="name" :value="__('Name')" />
    <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name', $child?->name)" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label :value="__('Avatar')" />
    <div class="mt-2 flex gap-2 flex-wrap">
        @foreach ($avatars as $avatar)
            <label class="cursor-pointer">
                <input type="radio" name="avatar" value="{{ $avatar }}" class="peer sr-only"
                       {{ old('avatar', $child?->avatar ?? 'fox') === $avatar ? 'checked' : '' }}>
                <span class="flex items-center justify-center w-12 h-12 text-2xl rounded-full border-2 border-gray-200 peer-checked:border-indigo-500">
                    {{ $avatarEmoji[$avatar] }}
                </span>
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get('avatar')" class="mt-2" />
</div>

<div>
    <x-input-label :value="__('Farbe')" />
    <div class="mt-2 flex gap-2 flex-wrap">
        @php
            $swatchClasses = [
                'orange' => 'bg-orange-400',
                'blue' => 'bg-blue-400',
                'green' => 'bg-green-400',
                'pink' => 'bg-pink-400',
                'purple' => 'bg-purple-400',
            ];
        @endphp
        @foreach ($colorThemes as $theme)
            <label class="cursor-pointer">
                <input type="radio" name="color_theme" value="{{ $theme }}" class="peer sr-only"
                       {{ old('color_theme', $child?->color_theme ?? 'orange') === $theme ? 'checked' : '' }}>
                <span class="block w-8 h-8 rounded-full border-2 border-gray-200 peer-checked:border-black {{ $swatchClasses[$theme] }}"></span>
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get('color_theme')" class="mt-2" />
</div>

<div>
    <x-input-label for="pin" :value="__('PIN-Code (optional, 4 Ziffern)')" />
    <x-text-input id="pin" name="pin" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="4" class="block mt-1 w-full" placeholder="z.B. 1234" />
    <p class="text-xs text-gray-500 mt-1">{{ __('Zusätzlicher Schutz, falls mehrere Kinder dasselbe Gerät nutzen. Leer lassen für Login ohne Code.') }}</p>
    <x-input-error :messages="$errors->get('pin')" class="mt-2" />
</div>

<div>
    <label class="inline-flex items-center">
        <input type="hidden" name="show_locked_badges" value="0">
        <input type="checkbox" name="show_locked_badges" value="1" class="rounded border-gray-300" {{ old('show_locked_badges', $child?->show_locked_badges ?? true) ? 'checked' : '' }}>
        <span class="ms-2 text-sm text-gray-600">{{ __('Abzeichen zeigen, die noch nicht verdient sind') }}</span>
    </label>
    <p class="text-xs text-gray-500 mt-1">{{ __('An: Das Kind sieht auf seiner Abzeichen-Seite auch ausgegraut, welche Abzeichen es noch gibt und wie es sie bekommt. Aus: Es sieht nur die bereits verdienten.') }}</p>
</div>

@if ($child)
    <div>
        <label class="inline-flex items-center">
            <input type="hidden" name="active" value="0">
            <input type="checkbox" name="active" value="1" class="rounded border-gray-300" {{ old('active', $child->active) ? 'checked' : '' }}>
            <span class="ms-2 text-sm text-gray-600">{{ __('Aktiv') }}</span>
        </label>
    </div>

    @if ($child->requiresPin())
        <div>
            <label class="inline-flex items-center">
                <input type="checkbox" name="remove_pin" value="1" class="rounded border-gray-300">
                <span class="ms-2 text-sm text-gray-600">{{ __('PIN entfernen') }}</span>
            </label>
        </div>
    @endif
@endif
