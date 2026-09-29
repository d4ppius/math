@php
    $levels = [
        ['🌱', 'Rechen-Anfänger'], ['🔍', 'Zahlen-Entdecker'], ['📘', 'Rechen-Lehrling'], ['🏃', 'Zahlen-Flitzer'], ['⭐', 'Rechen-Profi'],
        ['🧩', 'Knobel-Meister'], ['🪄', 'Zahlen-Zauberer'], ['🎯', 'Rechen-Ass'], ['🦸', 'Mathe-Held'], ['🦊', 'Rechenfuchs-Meister'],
    ];

    $badgeCategories = [
        ['🎉', 'Meilensteine', 'Für die erste geschaffte Übung und eine 7-Tage-Serie.'],
        ['⚡', 'Blitz-Abzeichen', 'Für viele richtige Antworten in einer Session, im Schnitt schnell beantwortet. Wird nie vergeben, wenn der Tempo-Bonus ausgeschaltet ist.'],
        ['👑', 'Reihen-Meister', 'Ein eigenes Abzeichen für jede Einmaleins-Reihe (1er bis 9er), sobald sie sicher sitzt.'],
        ['🥇', 'Rechen-Meister', 'Eigene Meilensteine für die Aufgabenarten von Plus, Minus sowie fürs Verdoppeln und Halbieren.'],
        ['🌟', 'Allrounder', 'Für Kinder, die am selben Tag mit mindestens zwei Übungen üben.'],
    ];

    $settings = [
        ['⏱', 'Timer ein- oder ausblenden', 'Manche Kinder stresst der Countdown. Ausgeblendet läuft die Zeit im Hintergrund weiter, kurz vor Schluss erscheint nur ein sanftes «Gleich geschafft!».'],
        ['🚀', 'Tempo-Bonus ein- oder ausschalten', 'Aus heisst: jede richtige Antwort gibt gleich viele Punkte, egal wie schnell.'],
        ['🔊', 'Töne ein- oder ausschalten', 'Ein heller Klang bei richtig, ein leiser Ton bei falsch — praktisch abschaltbar für unterwegs.'],
        ['💡', 'Rechentipp bei falschen Antworten', 'Zeigt einen kurzen Rechenweg statt nur die Lösung. Standardmässig aus, da Schulen oft eigene Rechenwege lehren — am besten erst ausprobieren.'],
    ];
@endphp

<x-public-layout
    title="Anleitung: Punkte, Level und Abzeichen"
    description="Wie das Punktesystem, die zehn Level, die Abzeichen und die Erinnerungen bei Rechenfuchs genau funktionieren."
>
    {{-- Hero --}}
    <section class="bg-gradient-to-b from-orange-50 via-amber-50/40 to-white">
        <div class="mx-auto max-w-3xl px-4 py-14 text-center sm:px-6 sm:py-20">
            <p class="inline-block rounded-full bg-orange-100 px-4 py-1 text-sm font-semibold text-orange-700">Anleitung</p>
            <h1 class="mt-4 font-display text-3xl font-bold text-gray-900 sm:text-4xl">Wie Punkte, Level und Abzeichen funktionieren</h1>
            <p class="mt-4 text-lg text-gray-600">Für Eltern, die genauer wissen möchten, was ihr Kind beim Üben sammelt — und wie lange es bis zum nächsten Level dauert.</p>
        </div>
    </section>

    {{-- Punkte --}}
    <section class="border-t border-gray-100 py-14 sm:py-16">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="font-display text-2xl font-bold text-gray-900 sm:text-3xl">⭐ Punkte</h2>
            <p class="mt-3 leading-relaxed text-gray-600">Punkte gibt es nur für richtige Antworten. Eine falsche Antwort gibt 0 Punkte und beendet eine laufende Serie.</p>

            <div class="mt-6 space-y-4">
                <div class="rounded-2xl bg-gray-50 p-5">
                    <h3 class="font-semibold text-gray-900">10 Grundpunkte</h3>
                    <p class="mt-1 text-gray-600">Für jede richtige Antwort, unabhängig vom Tempo.</p>
                </div>
                <div class="rounded-2xl bg-gray-50 p-5">
                    <h3 class="font-semibold text-gray-900">Bis zu 10 Zusatzpunkte fürs Tempo</h3>
                    <p class="mt-1 text-gray-600">Wer schneller antwortet als eine übungsübliche Zielzeit, bekommt zusätzliche Punkte. Die erste Sekunde ist dabei immer geschenkt — niemand braucht buchstäblich null Sekunden, um eine Aufgabe zu lesen und einzutippen. Eine langsame richtige Antwort gibt trotzdem immer mindestens die 10 Grundpunkte. Dieser Bonus lässt sich pro Kind und Übung ganz abschalten, für Kinder, die kein Zeitdruck-Gefühl brauchen.</p>
                </div>
                <div class="rounded-2xl bg-gray-50 p-5">
                    <h3 class="font-semibold text-gray-900">Mehr Punkte bei einer Serie</h3>
                    <p class="mt-1 text-gray-600">Ab der 5. richtigen Antwort in Folge gibt es 20 % mehr Punkte, ab der 10. sogar 50 % mehr. Eine falsche Antwort setzt die Serie wieder auf null.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Level --}}
    <section class="border-t border-gray-100 bg-gray-50 py-14 sm:py-16">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="font-display text-2xl font-bold text-gray-900 sm:text-3xl">🏅 Level</h2>
            <p class="mt-3 leading-relaxed text-gray-600">
                Jede Übung — Einmaleins und Plus bis 20 — hat ihr eigenes Level, unabhängig davon, wie weit das Kind in der anderen Übung schon ist. So bleibt jede Übung ein eigener, motivierender Fortschritt. Solange nur eine Übung freigeschaltet ist (der Normalfall), sieht man einfach ein Level statt zwei.
            </p>

            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach ($levels as $i => [$emoji, $name])
                    <div class="rounded-xl bg-white p-3 text-center shadow-sm ring-1 ring-gray-100">
                        <div class="text-2xl" aria-hidden="true">{{ $emoji }}</div>
                        <div class="mt-1 text-xs font-semibold text-gray-500">Level {{ $i + 1 }}</div>
                        <div class="text-sm font-medium text-gray-900">{{ $name }}</div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                <h3 class="font-semibold text-gray-900">Wie lange dauert es bis zum höchsten Level?</h3>
                <p class="mt-2 text-gray-600">Kommt aufs Tempo an, ganz grob bei regelmässigem Üben derselben Übung:</p>
                <ul class="mt-3 space-y-2 text-gray-600">
                    <li class="flex gap-2"><span aria-hidden="true">🐢</span><span><strong class="text-gray-900">Gemütliches Tempo:</strong> einige Monate</span></li>
                    <li class="flex gap-2"><span aria-hidden="true">🚶</span><span><strong class="text-gray-900">Typisches Tempo:</strong> etwa 10 Wochen</span></li>
                    <li class="flex gap-2"><span aria-hidden="true">🏃</span><span><strong class="text-gray-900">Zügiges Tempo auf leichten Aufgaben:</strong> etwa 1 Monat</span></li>
                </ul>
                <p class="mt-3 text-sm text-gray-500">Das erste Level-Up geht dafür schnell: meist schon nach der ersten oder zweiten Übungsrunde.</p>
            </div>
        </div>
    </section>

    {{-- Abzeichen --}}
    <section class="border-t border-gray-100 py-14 sm:py-16">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="font-display text-2xl font-bold text-gray-900 sm:text-3xl">🎖️ Abzeichen</h2>
            <p class="mt-3 leading-relaxed text-gray-600">Es gibt 27 Abzeichen zum Sammeln, in fünf Kategorien. Ein Abzeichen ist nur als Ziel sichtbar, wenn es für das Kind überhaupt erreichbar ist (z. B. kein Plus-Abzeichen, solange Plus nicht freigeschaltet ist), und geht nie wieder verloren.</p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($badgeCategories as [$icon, $title, $text])
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-100 text-xl" aria-hidden="true">{{ $icon }}</div>
                        <h3 class="mt-3 font-semibold text-gray-900">{{ $title }}</h3>
                        <p class="mt-1 text-sm text-gray-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>

            <p class="mt-6 text-sm text-gray-500">Eltern legen pro Kind fest, ob es auf seiner Erfolge-Seite auch noch nicht verdiente Abzeichen ausgegraut sieht (mit Erklärung, wie man sie bekommt) oder nur die bereits verdienten.</p>
        </div>
    </section>

    {{-- Erinnerungen --}}
    <section class="border-t border-gray-100 bg-gray-50 py-14 sm:py-16">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="font-display text-2xl font-bold text-gray-900 sm:text-3xl">🔔 Erinnerungen</h2>
            <p class="mt-3 leading-relaxed text-gray-600">Zwei getrennte Benachrichtigungen, beide freiwillig:</p>
            <div class="mt-4 space-y-4">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <h3 class="font-semibold text-gray-900">Für das Kind: «Zeit zum Üben»</h3>
                    <p class="mt-1 text-gray-600">Das Kind aktiviert das selbst auf seinem eigenen Gerät (ein Tipp genügt). Eltern legen zusätzlich fest, <strong>ob</strong> überhaupt und <strong>um wie viel Uhr</strong> erinnert wird — Standard ist an, um 17 Uhr, lässt sich auf der Seite «Kind bearbeiten» ändern.</p>
                </div>
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                    <h3 class="font-semibold text-gray-900">Für Eltern: «Tagesziel erreicht»</h3>
                    <p class="mt-1 text-gray-600">Sobald ein Kind an einem Tag mindestens 10 Aufgaben beantwortet hat, bekommen alle Eltern der Familie einmal eine Nachricht mit dem Ergebnis.</p>
                </div>
            </div>
            <p class="mt-4 text-sm text-gray-500">Auf iPhone und iPad funktionieren Benachrichtigungen nur, wenn die Seite zuvor über «Zum Home-Bildschirm hinzufügen» installiert wurde (iOS 16.4 oder neuer).</p>
        </div>
    </section>

    {{-- Weitere Einstellungen --}}
    <section class="border-t border-gray-100 py-14 sm:py-16">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="font-display text-2xl font-bold text-gray-900 sm:text-3xl">⚙️ Was Eltern sonst noch einstellen können</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach ($settings as [$icon, $title, $text])
                    <div class="rounded-2xl bg-gray-50 p-5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-100 text-xl" aria-hidden="true">{{ $icon }}</div>
                        <h3 class="mt-3 font-semibold text-gray-900">{{ $title }}</h3>
                        <p class="mt-1 text-sm text-gray-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
            <p class="mt-6 text-sm text-gray-500">Dazu wählbar: welche Reihen bzw. Aufgabenarten aktiv sind, und wie lange eine Übungs-Session dauert (3 bis 20 Minuten).</p>
        </div>
    </section>

    {{-- CTA --}}
    <section class="border-t border-gray-100 bg-orange-50 py-14 text-center sm:py-16">
        <div class="mx-auto max-w-2xl px-4 sm:px-6">
            <h2 class="font-display text-2xl font-bold text-gray-900 sm:text-3xl">Neugierig geworden?</h2>
            <p class="mt-3 text-gray-600">Leg los, oder schau dir zuerst an, was Rechenfuchs sonst noch kann.</p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('register') }}" class="rounded-full bg-orange-500 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-orange-600">Jetzt registrieren</a>
                <a href="{{ route('home') }}" class="rounded-full border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-700 hover:bg-white">Zurück zur Startseite</a>
            </div>
        </div>
    </section>
</x-public-layout>
