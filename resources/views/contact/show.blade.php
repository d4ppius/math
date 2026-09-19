<x-public-layout title="Kontakt & Support" description="Fragen, Hilfe oder eine Idee zu Rechenfuchs? Schreib uns über das Kontaktformular.">
    <div class="bg-gradient-to-b from-orange-50 to-white">
        <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 sm:py-16">
            <h1 class="font-display text-3xl font-bold text-gray-900 sm:text-4xl">Kontakt &amp; Support</h1>
            <p class="mt-3 text-gray-600">
                Eine Frage, ein Problem oder eine Idee? Schreib uns, wir melden uns so bald wie möglich per E-Mail bei dir.
            </p>

            @if (session('sent'))
                <div class="mt-8 rounded-2xl border border-green-200 bg-green-50 p-6 text-center" role="status">
                    <x-mascot :width="90" :height="138" class="mx-auto" />
                    <h2 class="mt-3 font-display text-2xl font-bold text-green-800">Danke für deine Nachricht!</h2>
                    <p class="mt-2 text-green-900">Sie ist bei uns angekommen. Wir antworten dir an die angegebene E-Mail-Adresse.</p>
                    <a href="{{ route('home') }}" class="mt-5 inline-block rounded-full bg-orange-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-orange-600">Zur Startseite</a>
                </div>
            @else
                <form method="POST" action="{{ route('contact.store') }}" class="mt-8 space-y-5 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8" novalidate>
                    @csrf
                    <x-spam-guard />

                    @error('form')
                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">{{ $message }}</div>
                    @enderror

                    <div>
                        <x-input-label for="name" value="Dein Name" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user?->name)" required maxlength="100" autocomplete="name" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Deine E-Mail-Adresse" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user?->email)" required maxlength="190" autocomplete="email" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="topic" value="Worum geht es?" />
                        <select id="topic" name="topic" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500" required>
                            @foreach ($topics as $key => $label)
                                <option value="{{ $key }}" @selected(old('topic', 'question') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('topic')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="message" value="Deine Nachricht" />
                        <textarea id="message" name="message" rows="6" maxlength="3000" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('message') }}</textarea>
                        <x-input-error :messages="$errors->get('message')" class="mt-2" />
                    </div>

                    <div>
                        <label class="flex items-start gap-3 text-sm text-gray-600">
                            <input type="checkbox" name="privacy" value="1" class="mt-0.5 rounded border-gray-300 text-orange-500 focus:ring-orange-500" @checked(old('privacy')) required>
                            <span>Ich habe die <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener" class="text-indigo-600 underline">Datenschutzerklärung</a> gelesen und bin damit einverstanden, dass meine Angaben zur Beantwortung gespeichert werden.</span>
                        </label>
                        <x-input-error :messages="$errors->get('privacy')" class="mt-2" />
                    </div>

                    <button type="submit" class="w-full rounded-full bg-orange-500 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-orange-600 sm:w-auto">Nachricht senden</button>
                </form>
            @endif
        </div>
    </div>
</x-public-layout>
