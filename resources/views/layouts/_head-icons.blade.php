{{--
    Shared by every parent-facing layout (app, guest, public) and by the child
    layout's anonymous fallback. Unlike the child's per-child manifest (which
    must be dynamic — each child's start_url is their own magic link), every
    parent gets the same icon, name and start_url, so a single static
    public/manifest.webmanifest is enough here; no controller/route needed.
--}}
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="apple-touch-icon" href="{{ asset('images/icons/apple-touch-icon.png') }}?v=2">
<link rel="icon" type="image/png" href="{{ asset('images/icons/icon-192.png') }}?v=2">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
