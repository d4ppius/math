<x-public-layout
    title="Einmaleins üben für Kinder"
    description="Rechenfuchs trainiert das kleine Einmaleins: adaptiv, in kurzen Sessions, mit Punkten, Abzeichen und einem Fuchs, der mitfiebert. Ohne Passwort für Kinder, ohne Werbung, ohne Tracking."
>
    @php
        $steps = [
            [
                'title' => 'Familie anlegen',
                'text' => 'Registriere dich mit deinem Namen und deiner E-Mail-Adresse, bestätige die Adresse und gib deiner Familie einen Namen. Ein zweites Elternteil lädst du später ganz einfach mit einem Einladungslink ein.',
            ],
            [
                'title' => 'Kind hinzufügen und einstellen',
                'text' => 'Name, Avatar und Farbe wählen, auf Wunsch einen vierstelligen Code festlegen. Dann bestimmst du, mit welchen Reihen dein Kind startet, wie lange eine Übung dauert und ob Timer, Tempo-Bonus und Töne dabei sind.',
            ],
            [
                'title' => 'Das Icon aufs Tablet holen',
                'text' => 'Für jedes Kind gibt es einen persönlichen Link. Öffne ihn auf dem iPad oder Handy im Browser und wähle «Zum Home-Bildschirm». Ab dann genügt ein Tipp auf das Icon mit dem Fuchs, ganz ohne Passwort.',
            ],
            [
                'title' => 'Üben und begleiten',
                'text' => 'Dein Kind übt in kurzen Sessions und sammelt Punkte, Level und Abzeichen. Du siehst in der Statistik auf einen Blick, welche Aufgaben schon sitzen, und kannst dich benachrichtigen lassen, wenn das Tagesziel erreicht ist.',
            ],
        ];

        $features = [
            ['🎯', 'Übt, was noch nicht sitzt', 'Jede der 90 Aufgaben wird für jedes Kind einzeln erfasst. Was noch wackelt oder länger nicht dran war, kommt öfter. Was sitzt, seltener.'],
            ['⏱️', 'Kurze Sessions', 'Eine Übung dauert 3 bis 20 Minuten, ganz wie du es einstellst. Die Zeit wird auf dem Server überwacht, ein Mogeln durch Neuladen gibt es nicht.'],
            ['🏆', 'Motivation mit Fuchs', 'Punkte, zehn Level, Abzeichen für Serien und Reihen-Meister, Konfetti am Ende und kleine Töne. Der Rechenfuchs feiert mit.'],
            ['🌿', 'Stressfrei einstellbar', 'Manche Kinder mag der Countdown nicht. Du kannst den Timer ausblenden, den Tempo-Bonus abschalten und die Töne ausstellen, pro Kind.'],
            ['📊', 'Klare Statistik', 'Eine Farbtabelle zeigt alle Aufgaben von 1×1 bis 9×10: sicher, wackelig oder noch nicht geübt. Dazu Wochenpunkte und Tagesziel.'],
            ['👨‍👩‍👧‍👦', 'Für die ganze Familie', 'Beliebig viele Kinder, mehrere Elternteile. Jedes Kind hat sein eigenes Icon mit Anfangsbuchstaben, damit auf einem Tablet nichts durcheinanderkommt.'],
        ];

        $faq = [
            ['Für welche Kinder ist Rechenfuchs gedacht?', 'Für Kinder, die das kleine Einmaleins (1×1 bis 9×10) lernen und festigen. Du wählst, mit welchen Reihen dein Kind beginnt, am besten zuerst mit ein bis zwei, und schaltest später weitere dazu.'],
            ['Braucht mein Kind ein Konto oder eine E-Mail-Adresse?', 'Nein. Nur Eltern haben ein Konto. Ein Kind meldet sich über seinen persönlichen Link an, den du auf dem Gerät als Icon speicherst. Wenn mehrere Kinder dasselbe Gerät nutzen, kannst du zusätzlich einen vierstelligen Code festlegen.'],
            ['Auf welchen Geräten funktioniert es?', 'Auf iPad, iPhone, Android-Geräten und am Computer, direkt im Browser. Am schönsten ist es als Icon auf dem Home-Bildschirm: Dann startet Rechenfuchs wie eine richtige App, ohne Adressleiste.'],
            ['Wie bekomme ich das Icon auf das iPad?', 'Öffne den persönlichen Link des Kindes in Safari, tippe auf das Teilen-Symbol und wähle «Zum Home-Bildschirm». Den Link bekommst du direkt nach dem Anlegen des Kindes im Eltern-Bereich.'],
            ['Was, wenn mein Kind der Timer stresst?', 'Dann blende ihn einfach aus: Er läuft im Hintergrund weiter, ist aber nicht mehr zu sehen. Kurz vor Schluss erscheint ein sanftes «Gleich geschafft!» ohne Zahlen. Auch den Tempo-Bonus bei den Punkten kannst du abschalten.'],
            ['Bekomme ich Benachrichtigungen?', 'Auf Wunsch ja: Du kannst dich informieren lassen, wenn ein Kind sein Tagesziel erreicht hat, und Kinder können sich ans Üben erinnern lassen. Beides ist freiwillig. Auf iPhone und iPad funktioniert das nur, wenn das Icon zuvor auf den Home-Bildschirm gelegt wurde (iOS 16.4 oder neuer).'],
            ['Wie viele Kinder und Eltern kann ich hinzufügen?', 'So viele du möchtest. Weitere Elternteile lädst du über einen Einladungslink in deine Familie ein, dann sehen beide dieselben Kinder und dieselbe Statistik.'],
            ['Kann ich meine Daten löschen?', 'Jederzeit. Du kannst einzelne Kinder samt Übungsdaten entfernen oder dein ganzes Konto löschen. Ist niemand mehr in der Familie, werden auch alle Kinder und Übungsdaten gelöscht.'],
        ];
    @endphp

    {{-- Hero --}}
    <section class="overflow-hidden bg-gradient-to-b from-orange-50 via-amber-50/40 to-white">
        <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 pb-14 pt-10 sm:px-6 md:grid-cols-2 md:gap-6 md:pb-20 md:pt-16">
            <div class="text-center md:text-start">
                <p class="inline-block rounded-full bg-orange-100 px-4 py-1 text-sm font-semibold text-orange-700">Das kleine Einmaleins üben</p>
                <h1 class="mt-4 font-display text-4xl font-bold leading-tight text-gray-900 sm:text-5xl">
                    Einmaleins üben, das Kindern <span class="text-orange-500">Spass</span> macht
                </h1>
                <p class="mx-auto mt-5 max-w-xl text-lg text-gray-600 md:mx-0">
                    Rechenfuchs übt genau die Aufgaben, die noch nicht sitzen, in kurzen Sessions, mit Punkten, Abzeichen und einem Fuchs, der mitfiebert.
                    Für Kinder ganz ohne Passwort, für Eltern mit klarer Statistik.
                </p>

                <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center md:justify-start">
                    @auth
                        <a href="{{ route('dashboard') }}" class="w-full rounded-full bg-orange-500 px-8 py-3.5 text-center text-base font-semibold text-white shadow-lg shadow-orange-500/30 hover:bg-orange-600 sm:w-auto">Zum Dashboard</a>
                    @else
                        <a href="{{ route('register') }}" class="w-full rounded-full bg-orange-500 px-8 py-3.5 text-center text-base font-semibold text-white shadow-lg shadow-orange-500/30 hover:bg-orange-600 sm:w-auto">Jetzt registrieren</a>
                        <a href="{{ route('login') }}" class="w-full rounded-full border border-gray-300 bg-white px-8 py-3.5 text-center text-base font-semibold text-gray-700 hover:bg-gray-50 sm:w-auto">Anmelden</a>
                    @endauth
                </div>

                <ul class="mt-8 flex flex-col items-center gap-2 text-sm text-gray-600 sm:flex-row sm:flex-wrap sm:justify-center sm:gap-x-6 md:justify-start">
                    <li class="flex items-center gap-2"><span class="text-green-600" aria-hidden="true">✓</span> Kinder brauchen weder E-Mail noch Passwort</li>
                    <li class="flex items-center gap-2"><span class="text-green-600" aria-hidden="true">✓</span> Ohne Werbung und Tracking</li>
                </ul>
            </div>

            <div class="relative mx-auto w-full max-w-sm">
                <div class="absolute inset-x-6 top-10 bottom-0 rounded-full bg-orange-200/50 blur-3xl" aria-hidden="true"></div>
                <x-phone-frame class="relative" src="child-home.webp" alt="Die Startseite von Mia mit dem Rechenfuchs, Level 3, einem grossen Los-geht's-Knopf und ihren Abzeichen" :width="270" :eager="true" />
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section id="so-gehts" class="scroll-mt-20 bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="font-display text-3xl font-bold text-gray-900 sm:text-4xl">So funktioniert's</h2>
                <p class="mt-3 text-lg text-gray-600">In vier Schritten von der Anmeldung zum ersten Übungstag, meist in wenigen Minuten.</p>
            </div>

            <ol class="mt-14 space-y-16 sm:space-y-20">
                @foreach ($steps as $i => $step)
                    <li class="grid items-center gap-8 md:grid-cols-2 md:gap-14">
                        <div class="{{ $i % 2 === 1 ? 'md:order-2' : '' }}">
                            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-orange-500 font-display text-xl font-bold text-white">{{ $i + 1 }}</span>
                            <h3 class="mt-4 font-display text-2xl font-bold text-gray-900">{{ $step['title'] }}</h3>
                            <p class="mt-3 text-lg leading-relaxed text-gray-600">{{ $step['text'] }}</p>
                            @if ($i === 0)
                                <a href="{{ route('register') }}" class="mt-5 inline-block font-semibold text-orange-600 hover:text-orange-700">Familie anlegen →</a>
                            @endif
                        </div>

                        <div class="{{ $i % 2 === 1 ? 'md:order-1' : '' }}">
                            @if ($i === 0)
                                <div class="mx-auto max-w-md rounded-3xl bg-gradient-to-br from-orange-50 to-sky-50 p-8 text-center ring-1 ring-orange-100">
                                    <x-mascot :width="170" :height="260" class="mascot-float mx-auto" />
                                    <p class="mt-4 font-display text-xl font-bold text-gray-800">Hallo! Ich bin der Rechenfuchs.</p>
                                    <p class="mt-1 text-gray-600">Ich freue mich auf euch.</p>
                                </div>
                            @elseif ($i === 1)
                                <x-browser-frame class="mx-auto max-w-lg" src="parent-settings.webp" alt="Die Übungseinstellungen für Mia: aktive Reihen, Session-Dauer und Schalter für Timer, Tempo-Bonus und Töne" :imageWidth="960" :imageHeight="1000" />
                            @elseif ($i === 2)
                                <x-phone-frame src="child-achievements.webp" alt="Die Erfolge-Seite von Mia mit Level, Fortschrittsbalken und verdienten Abzeichen" :width="260" />
                            @else
                                <div class="flex flex-col items-center gap-8 lg:flex-row lg:items-start lg:justify-center lg:gap-4">
                                    <x-phone-frame src="child-practice.webp" alt="Die Übungsansicht mit der Aufgabe 7 mal 8 und einem Zahlenfeld" :width="200" :imageHeight="933" class="mx-0 shrink-0 lg:mt-8" />
                                    <x-browser-frame class="w-full max-w-md lg:w-72 lg:max-w-none lg:shrink-0" src="parent-statistics.webp" alt="Die Statistik für Eltern mit Wochenpunkten, Tagesziel und einer Farbtabelle aller Aufgaben" :imageWidth="1100" :imageHeight="1313" />
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Features --}}
    <section id="funktionen" class="scroll-mt-20 bg-gray-50 py-16 sm:py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="font-display text-3xl font-bold text-gray-900 sm:text-4xl">Was Rechenfuchs kann</h2>
                <p class="mt-3 text-lg text-gray-600">Gebaut für Familien: einfach für Kinder, übersichtlich für Eltern.</p>
            </div>

            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($features as [$icon, $title, $text])
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-100 text-2xl" aria-hidden="true">{{ $icon }}</div>
                        <h3 class="mt-4 font-display text-xl font-bold text-gray-900">{{ $title }}</h3>
                        <p class="mt-2 leading-relaxed text-gray-600">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Safe for children --}}
    <section id="sicher" class="scroll-mt-20 bg-blue-900 py-16 text-white sm:py-20">
        <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 sm:px-6 md:grid-cols-[1fr_auto]">
            <div>
                <h2 class="font-display text-3xl font-bold sm:text-4xl">Sicher für Kinder, entspannt für Eltern</h2>
                <ul class="mt-6 space-y-4 text-lg text-blue-50">
                    <li class="flex gap-3"><span class="mt-1 text-orange-300" aria-hidden="true">✓</span><span><strong class="text-white">Keine Konten für Kinder.</strong> Kein Passwort, keine E-Mail-Adresse, keine Chats, keine Fotos. Nur ein Vorname genügt.</span></li>
                    <li class="flex gap-3"><span class="mt-1 text-orange-300" aria-hidden="true">✓</span><span><strong class="text-white">Keine Werbung, kein Tracking.</strong> Wir laden nichts von fremden Servern nach und verkaufen keine Daten.</span></li>
                    <li class="flex gap-3"><span class="mt-1 text-orange-300" aria-hidden="true">✓</span><span><strong class="text-white">Du behältst die Kontrolle.</strong> Du legst die Kinder an, stellst alles ein und kannst Daten jederzeit löschen.</span></li>
                </ul>
                <a href="{{ route('legal.privacy') }}" class="mt-6 inline-block font-semibold text-orange-300 hover:text-orange-200">Zur Datenschutzerklärung →</a>
            </div>
            <div class="hidden md:block">
                <x-mascot :width="190" :height="290" class="mascot-float" />
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="scroll-mt-20 bg-white py-16 sm:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <h2 class="text-center font-display text-3xl font-bold text-gray-900 sm:text-4xl">Häufige Fragen</h2>

            <div class="mt-10 divide-y divide-gray-200 rounded-2xl border border-gray-200">
                @foreach ($faq as [$question, $answer])
                    <details class="group p-5 [&_summary::-webkit-details-marker]:hidden">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-semibold text-gray-900">
                            {{ $question }}
                            <span class="shrink-0 text-2xl leading-none text-orange-500 transition group-open:rotate-45" aria-hidden="true">+</span>
                        </summary>
                        <p class="mt-3 leading-relaxed text-gray-600">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>

            <p class="mt-8 text-center text-gray-600">
                Deine Frage ist nicht dabei? <a href="{{ route('contact.show') }}" class="font-semibold text-orange-600 hover:text-orange-700">Schreib uns über das Kontaktformular.</a>
            </p>
        </div>
    </section>

    {{-- Final call to action --}}
    <section class="bg-gradient-to-b from-white to-orange-50 pb-16 pt-4 sm:pb-24">
        <div class="mx-auto max-w-3xl rounded-3xl bg-orange-500 px-6 py-12 text-center text-white shadow-xl sm:px-12">
            <h2 class="font-display text-3xl font-bold sm:text-4xl">Bereit für den ersten Übungstag?</h2>
            <p class="mx-auto mt-3 max-w-xl text-lg text-orange-50">Lege deine Familie an, füge dein Kind hinzu und starte in wenigen Minuten.</p>
            <div class="mt-7 flex flex-col items-center justify-center gap-3 sm:flex-row">
                @auth
                    <a href="{{ route('dashboard') }}" class="w-full rounded-full bg-white px-8 py-3.5 text-base font-semibold text-orange-600 hover:bg-orange-50 sm:w-auto">Zum Dashboard</a>
                @else
                    <a href="{{ route('register') }}" class="w-full rounded-full bg-white px-8 py-3.5 text-base font-semibold text-orange-600 hover:bg-orange-50 sm:w-auto">Jetzt registrieren</a>
                    <a href="{{ route('login') }}" class="w-full rounded-full border-2 border-white/70 px-8 py-3.5 text-base font-semibold text-white hover:bg-white/10 sm:w-auto">Anmelden</a>
                @endauth
            </div>
        </div>
    </section>
</x-public-layout>
