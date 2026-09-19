{{-- Small always-reachable legal links for the login/registration pages and the parent area. --}}
<nav class="flex flex-wrap items-center justify-center gap-x-5 gap-y-1 text-xs text-gray-500" aria-label="Rechtliches">
    <a href="{{ route('home') }}" class="hover:text-gray-800">Startseite</a>
    <a href="{{ route('legal.imprint') }}" class="hover:text-gray-800">Impressum</a>
    <a href="{{ route('legal.privacy') }}" class="hover:text-gray-800">Datenschutz</a>
    <a href="{{ route('contact.show') }}" class="hover:text-gray-800">Kontakt</a>
</nav>
