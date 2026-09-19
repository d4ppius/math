<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="{{ $child?->name ?? config('app.name') }}">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="theme-color" content="#fb923c">

        <title>{{ $child?->name ?? config('app.name') }}</title>

        {{--
            The manifest link only appears when $token is set — i.e. only
            on the actual /k/{token} magic-link page. That per-child
            manifest's start_url points right back at /k/{token}, so "Add
            to Home Screen" (which iOS resolves via the manifest's
            start_url once one is present, not the current page) still
            re-opens the magic link. Pages reached without the token
            (e.g. plain /kind visits) intentionally omit the manifest —
            they're never the page a parent bookmarks from.
        --}}
        @if ($token)
            <link rel="manifest" href="{{ route('child.manifest', ['token' => $token]) }}">
        @endif

        @if ($child)
            <link rel="apple-touch-icon" href="{{ route('child.icon', ['child' => $child, 'size' => 180]) }}">
            <link rel="icon" href="{{ route('child.icon', ['child' => $child, 'size' => 32]) }}">
        @else
            @include('layouts._head-icons')
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=baloo-2:400,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Baloo 2', ui-rounded, system-ui, sans-serif; }
        </style>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-orange-100 via-amber-50 to-sky-100 text-gray-800">
        <div class="min-h-screen flex flex-col items-center justify-center px-4 py-8">
            {{ $slot }}
        </div>
    </body>
</html>
