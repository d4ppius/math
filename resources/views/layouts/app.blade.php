<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">
        <meta name="theme-color" content="#4f46e5">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="manifest" href="/manifest.webmanifest">
        @include('layouts._head-icons')


        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @if (session()->has(\App\Http\Controllers\Admin\ImpersonationController::SESSION_KEY))
                <div class="bg-amber-100 border-b border-amber-300 text-amber-900 text-sm">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex items-center justify-between gap-4 flex-wrap">
                        <span>{{ __('Admin-Ansicht: angemeldet als :name (:family)', ['name' => Auth::user()->name, 'family' => Auth::user()->family->name]) }}</span>
                        <form method="POST" action="{{ route('impersonation.stop') }}">
                            @csrf
                            <button type="submit" class="underline font-semibold">{{ __('Zurück zum Admin') }}</button>
                        </form>
                    </div>
                </div>
            @endif

            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            <footer class="py-8">
                @include('layouts._legal-links')
            </footer>
        </div>
    </body>
</html>
