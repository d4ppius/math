<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">

        <title>{{ config('app.name', 'Laravel') }}</title>

        @include('layouts._head-icons')


        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                <a href="/" class="block mb-4">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="mx-auto w-48 h-auto">
                </a>

                {{ $slot }}
            </div>

            <div class="mt-6 pb-6">
                @include('layouts._legal-links')
            </div>
        </div>
    </body>
</html>
