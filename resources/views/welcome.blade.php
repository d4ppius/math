<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>

        @include('layouts._head-icons')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=baloo-2:400,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Baloo 2', ui-rounded, system-ui, sans-serif; }
        </style>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-orange-100 via-amber-50 to-sky-100 text-gray-800">
        <div class="min-h-screen flex flex-col items-center justify-center px-4 text-center">
            <x-mascot :width="150" :height="229" class="mascot-float mb-4" />
            <h1 class="text-2xl font-bold mb-2">{{ config('app.name') }}</h1>
            <p class="text-gray-500 max-w-sm">
                {{ __('Diese App ist für ein persönliches Homescreen-Icon gedacht. Frag deine Eltern nach dem Link!') }}
            </p>
        </div>

        <footer class="fixed bottom-3 inset-x-0 text-center">
            <a href="{{ route('login') }}" class="text-xs text-gray-300 hover:text-gray-400">{{ __('Für Eltern') }}</a>
        </footer>
    </body>
</html>
