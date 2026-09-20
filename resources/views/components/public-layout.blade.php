@props(['title' => null, 'description' => 'Rechenfuchs trainiert Einmaleins und Plus bis 20: adaptiv, in kurzen Sessions, mit Punkten, Abzeichen und einem Fuchs, der mitfiebert.', 'noindex' => false])

@php
    $pageTitle = ($title ? $title.' · ' : '').config('app.name');
    $home = route('home');
@endphp

<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#f97316">

        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $description }}">
        @if ($noindex)
            <meta name="robots" content="noindex">
        @endif

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:image" content="{{ asset('images/logo.png') }}">
        <meta property="og:locale" content="de_CH">

        @include('layouts._head-icons')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-white font-sans text-gray-800 antialiased">
        <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-gray-100 bg-white/90 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                <a href="{{ $home }}" aria-label="{{ config('app.name') }}"><x-brand /></a>

                <nav class="hidden items-center gap-6 text-sm font-medium text-gray-600 md:flex" aria-label="Hauptnavigation">
                    <a href="{{ $home }}#so-gehts" class="hover:text-gray-900">So funktioniert's</a>
                    <a href="{{ $home }}#funktionen" class="hover:text-gray-900">Funktionen</a>
                    <a href="{{ $home }}#faq" class="hover:text-gray-900">Fragen</a>
                    <a href="{{ route('contact.show') }}" class="hover:text-gray-900">Kontakt</a>
                </nav>

                <div class="hidden items-center gap-3 md:flex">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-full bg-orange-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600">Zum Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">Anmelden</a>
                        <a href="{{ route('register') }}" class="rounded-full bg-orange-500 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-600">Registrieren</a>
                    @endauth
                </div>

                <button type="button" class="-me-2 inline-flex h-11 w-11 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 md:hidden" @click="open = ! open" :aria-expanded="open" aria-label="Menü">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path x-show="! open" stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="open" x-cloak stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>

            <div x-show="open" x-cloak class="border-t border-gray-100 bg-white px-4 pb-4 pt-2 md:hidden">
                <nav class="flex flex-col text-base font-medium text-gray-700" aria-label="Mobile Navigation">
                    <a href="{{ $home }}#so-gehts" class="rounded-lg px-3 py-3 hover:bg-gray-50" @click="open = false">So funktioniert's</a>
                    <a href="{{ $home }}#funktionen" class="rounded-lg px-3 py-3 hover:bg-gray-50" @click="open = false">Funktionen</a>
                    <a href="{{ $home }}#faq" class="rounded-lg px-3 py-3 hover:bg-gray-50" @click="open = false">Fragen</a>
                    <a href="{{ route('contact.show') }}" class="rounded-lg px-3 py-3 hover:bg-gray-50">Kontakt</a>
                </nav>
                <div class="mt-3 flex gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="flex-1 rounded-full bg-orange-500 px-5 py-3 text-center text-sm font-semibold text-white">Zum Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="flex-1 rounded-full border border-gray-300 px-5 py-3 text-center text-sm font-semibold text-gray-700">Anmelden</a>
                        <a href="{{ route('register') }}" class="flex-1 rounded-full bg-orange-500 px-5 py-3 text-center text-sm font-semibold text-white">Registrieren</a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="flex-1">
            {{ $slot }}
        </main>

        <footer class="border-t border-gray-100 bg-gray-50">
            <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-3">
                <div>
                    <x-brand :size="32" />
                    <p class="mt-3 max-w-xs text-sm text-gray-500">Einmaleins und Plus üben, mit einem Fuchs, der mitfiebert.</p>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Rechenfuchs</h2>
                    <ul class="mt-3 space-y-2 text-sm text-gray-600">
                        <li><a href="{{ $home }}#so-gehts" class="hover:text-gray-900">So funktioniert's</a></li>
                        @auth
                            <li><a href="{{ route('dashboard') }}" class="hover:text-gray-900">Zum Dashboard</a></li>
                        @else
                            <li><a href="{{ route('login') }}" class="hover:text-gray-900">Anmelden</a></li>
                            <li><a href="{{ route('register') }}" class="hover:text-gray-900">Registrieren</a></li>
                        @endauth
                        <li><a href="{{ route('contact.show') }}" class="hover:text-gray-900">Kontakt &amp; Support</a></li>
                    </ul>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Rechtliches</h2>
                    <ul class="mt-3 space-y-2 text-sm text-gray-600">
                        <li><a href="{{ route('legal.imprint') }}" class="hover:text-gray-900">Impressum</a></li>
                        <li><a href="{{ route('legal.privacy') }}" class="hover:text-gray-900">Datenschutz</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-100 px-4 py-4 text-center text-xs text-gray-400">
                © {{ date('Y') }} {{ config('legal.name') ?: config('app.name') }}
            </div>
        </footer>
    </body>
</html>
