<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <x-spam-guard />

        @error('form')
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">{{ $message }}</div>
        @enderror

        @if ($inviteToken)
            <input type="hidden" name="invite_token" value="{{ $inviteToken }}">
        @endif

        @if ($invitingFamily)
            <div class="mb-4 text-sm text-gray-600">
                {{ __('Du trittst der Familie ":name" bei.', ['name' => $invitingFamily->name]) }}
            </div>
        @else
            <!-- Familienname -->
            <div>
                <x-input-label for="family_name" :value="__('Familienname')" />
                <x-text-input id="family_name" class="block mt-1 w-full" type="text" name="family_name" :value="old('family_name')" required autofocus autocomplete="off" placeholder="z.B. Familie Muster" />
                <x-input-error :messages="$errors->get('family_name')" class="mt-2" />
            </div>
        @endif

        <!-- Name -->
        <div class="mt-4">
            <x-input-label for="name" :value="__('Dein Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('E-Mail')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Passwort')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Passwort bestätigen')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-5">
            <label class="flex items-start gap-3 text-sm text-gray-600">
                <input type="checkbox" name="privacy" value="1" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked(old('privacy')) required>
                <span>{{ __('Ich habe die') }} <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="underline text-indigo-600">{{ __('Datenschutzerklärung') }}</a> {{ __('gelesen und bin damit einverstanden.') }}</span>
            </label>
            <x-input-error :messages="$errors->get('privacy')" class="mt-2" />
            <p class="mt-3 text-xs text-gray-500">{{ __('Wir schicken dir eine E-Mail, um deine Adresse zu bestätigen.') }}</p>
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Schon registriert?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Registrieren') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
