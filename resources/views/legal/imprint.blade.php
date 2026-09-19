@php
    // Required details show a visible hint when the .env is incomplete, so a missing value is noticed.
    $legal = fn (string $key) => config("legal.{$key}") ?: '['.strtoupper("LEGAL_{$key}").' fehlt in der .env]';
@endphp

<x-public-layout title="Impressum" description="Impressum und Kontaktangaben von Rechenfuchs.">
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 sm:py-16">
        <div class="legal-doc">
            <h1>Impressum</h1>

            <h2>Betreiber</h2>
            <p>
                {{ $legal('name') }}<br>
                @if (config('legal.responsible'))
                    Vertretungsberechtigt: {{ config('legal.responsible') }}<br>
                @endif
                {{ $legal('street') }}<br>
                {{ $legal('zip_city') }}<br>
                {{ config('legal.country') }}
            </p>

            <h2>Kontakt</h2>
            <p>
                E-Mail:
                @if (config('legal.email'))
                    <x-obfuscated-email :email="config('legal.email')" />
                @else
                    [LEGAL_EMAIL fehlt in der .env]
                @endif
                <br>
                @if (config('legal.phone'))
                    Telefon: {{ config('legal.phone') }}<br>
                @endif
                Für Fragen zur App und Support gibt es ausserdem das <a href="{{ route('contact.show') }}">Kontaktformular</a>.
            </p>

            @if (config('legal.uid'))
                <h2>Unternehmens-Identifikationsnummer</h2>
                <p>{{ config('legal.uid') }}</p>
            @endif

            <h2>Haftungsausschluss</h2>
            <p>
                Die Inhalte dieser Website wurden mit grösster Sorgfalt erstellt. Für die Richtigkeit, Vollständigkeit
                und Aktualität der Inhalte kann jedoch keine Gewähr übernommen werden. Rechenfuchs ist ein
                Übungsangebot und ersetzt keinen Unterricht und keine pädagogische Beratung.
            </p>

            <h2>Urheberrecht</h2>
            <p>
                Texte, Grafiken und das Maskottchen «Rechenfuchs» dieser Website sind urheberrechtlich geschützt,
                soweit nicht anders gekennzeichnet. Eine Verwendung bedarf der vorherigen Zustimmung.
            </p>

            <h2>Datenschutz</h2>
            <p>Informationen zum Umgang mit Personendaten finden Sie in der <a href="{{ route('legal.privacy') }}">Datenschutzerklärung</a>.</p>
        </div>
    </div>
</x-public-layout>
