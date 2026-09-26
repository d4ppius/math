<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">
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
            <link rel="apple-touch-icon" href="{{ $child->iconUrl(180) }}">
            <link rel="icon" href="{{ $child->iconUrl(32) }}">
        @else
            @include('layouts._head-icons')
        @endif


        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body { font-family: 'Baloo 2', ui-rounded, system-ui, sans-serif; }
        </style>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-orange-100 via-amber-50 to-sky-100 text-gray-800">
        @if (session()->has(\App\Http\Controllers\ChildPreviewController::SESSION_KEY))
            <div class="bg-amber-100 border-b border-amber-300 text-amber-900 text-sm">
                <div class="max-w-lg mx-auto px-4 py-2 flex items-center justify-between gap-4 flex-wrap">
                    <span>{{ __('Vorschau als :name — nichts hier verändert echte Punkte, Level oder Abzeichen.', ['name' => $child?->name]) }}</span>
                    <form method="POST" action="{{ route('child-preview.stop') }}">
                        @csrf
                        <button type="submit" class="underline font-semibold whitespace-nowrap">{{ __('Vorschau beenden') }}</button>
                    </form>
                </div>
            </div>
        @endif

        <div class="min-h-screen flex flex-col items-center justify-center px-4 py-8">
            {{ $slot }}
        </div>
    </body>
</html>
