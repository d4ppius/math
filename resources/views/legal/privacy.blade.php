@php
    $legal = fn (string $key) => config("legal.{$key}") ?: '['.strtoupper("LEGAL_{$key}").' fehlt in der .env]';
    $retention = config('contact.retention_months');
@endphp

<x-public-layout title="Datenschutz" description="Datenschutzerklärung von Rechenfuchs: welche Daten wir bearbeiten, wozu, und welche Rechte Sie haben.">
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 sm:py-16">
        <div class="legal-doc">
            <h1>Datenschutzerklärung</h1>
            <p>Stand: 20. September 2026</p>
            <p>
                Diese Erklärung beschreibt, welche Personendaten Rechenfuchs bearbeitet, wozu das geschieht und welche
                Rechte Sie haben. Sie richtet sich nach dem Schweizer Datenschutzgesetz (DSG) und, soweit anwendbar,
                der EU-Datenschutz-Grundverordnung (DSGVO).
            </p>

            <h2>1. Verantwortliche Stelle</h2>
            <p>
                {{ $legal('name') }}, {{ $legal('street') }}, {{ $legal('zip_city') }}, {{ config('legal.country') }}<br>
                E-Mail:
                @if (config('legal.email'))
                    <x-obfuscated-email :email="config('legal.email')" />
                @else
                    [LEGAL_EMAIL fehlt in der .env]
                @endif
            </p>

            <h2>2. Für wen ist Rechenfuchs?</h2>
            <p>
                Rechenfuchs richtet sich an <strong>Eltern</strong>. Sie legen ein Konto für ihre Familie an und
                erstellen die Zugänge für ihre Kinder. Die Kinder selbst registrieren sich nicht, haben weder E-Mail-Adresse
                noch Passwort und geben keine Daten in Formulare ein. Wir empfehlen, für Kinder nur den Vornamen oder einen
                Spitznamen zu verwenden.
            </p>

            <h2>3. Welche Daten wir bearbeiten</h2>
            <p><strong>Eltern-Konto:</strong></p>
            <ul>
                <li>Name, E-Mail-Adresse und Passwort (das Passwort wird nur verschlüsselt gespeichert)</li>
                <li>Familienname und Zeitzone der Familie</li>
                <li>auf Wunsch ein Push-Abonnement Ihres Browsers für Benachrichtigungen</li>
            </ul>
            <p><strong>Kinder-Zugang (von den Eltern angelegt):</strong></p>
            <ul>
                <li>Vorname oder Spitzname, gewählter Avatar und gewählte Farbe</li>
                <li>ein persönlicher Login-Link (gespeichert wird nur eine nicht umkehrbare Prüfsumme) und optional ein vierstelliger Code (verschlüsselt)</li>
                <li>Übungsdaten: beantwortete Aufgaben, Antworten, Antwortzeiten, Sitzungen, Punkte, Level und Abzeichen, Zeitpunkt des letzten Besuchs</li>
                <li>auf Wunsch ein Push-Abonnement des Geräts für Erinnerungen</li>
            </ul>
            <p><strong>Kontaktformular:</strong> Name, E-Mail-Adresse, Thema, Nachricht und Zeitpunkt der Nachricht.</p>
            <p>
                <strong>Technische Daten:</strong> Beim Besuch der Website und bei angemeldeten Sitzungen fallen technisch
                bedingt die IP-Adresse, Datum und Uhrzeit, der aufgerufene Inhalt und Angaben zum Browser an. Sie werden für
                den Betrieb, die Sicherheit und den Schutz vor Missbrauch (z.B. Begrenzung zu häufiger Anfragen) verwendet.
            </p>

            <h2>4. Wozu wir die Daten bearbeiten</h2>
            <ul>
                <li>um das Übungsangebot bereitzustellen (Anmeldung, Aufgaben, Punkte, Statistik für die Eltern),</li>
                <li>um Aufgaben an den Lernstand jedes Kindes anzupassen,</li>
                <li>um Benachrichtigungen zu senden, wenn Sie diese aktiviert haben,</li>
                <li>um Anfragen und Support-Nachrichten zu beantworten,</li>
                <li>um die Sicherheit und Stabilität des Angebots zu gewährleisten.</li>
            </ul>
            <p>
                Grundlage sind die Nutzung des Angebots und Ihre Einwilligung (z.B. Push-Benachrichtigungen, die Sie
                jederzeit im Browser widerrufen können) sowie unser berechtigtes Interesse an einem sicheren Betrieb.
                Daten der Kinder werden ausschliesslich im Auftrag und unter der Kontrolle der Eltern bearbeitet.
            </p>

            <h2>5. Kein Tracking, keine Werbung</h2>
            <p>
                Wir setzen <strong>keine</strong> Analyse- oder Werbedienste ein, verkaufen keine Daten und laden keine
                Schriften oder Programme von fremden Servern nach. Es gibt keine Werbung.
            </p>

            <h2>6. Cookies</h2>
            <p>
                Wir verwenden nur technisch notwendige Cookies: ein Sitzungs-Cookie, ein Sicherheits-Cookie gegen
                gefälschte Anfragen und, wenn Sie oder Ihr Kind angemeldet bleiben, ein Anmelde-Cookie. Ohne sie
                funktioniert die Anmeldung nicht. Deshalb ist keine Zustimmung nötig und es gibt kein Cookie-Banner.
            </p>

            <h2>7. Kontaktformular und E-Mails</h2>
            <p>
                Wenn Sie das Kontaktformular nutzen, speichern wir Ihre Angaben, um Ihre Anfrage zu beantworten, und
                leiten sie per E-Mail an uns weiter. Als Schutz vor automatisierten Nachrichten prüfen wir unsichtbar, ob
                das Formular von einem Menschen ausgefüllt wurde. Dazu wird kein externer Dienst verwendet. Erledigte
                Nachrichten löschen wir nach {{ $retention }} Monaten. Wir senden keine automatische Bestätigung an die
                angegebene Adresse.
            </p>
            <p>
                Für die Registrierung und das Zurücksetzen des Passworts senden wir Ihnen E-Mails (z.B. zur Bestätigung
                Ihrer Adresse). Diese enthalten nur, was für den jeweiligen Vorgang nötig ist.
            </p>

            <h2>8. Push-Benachrichtigungen</h2>
            <p>
                Push-Benachrichtigungen sind freiwillig. Für die Zustellung wird die Adresse Ihres Browsers oder Geräts
                gespeichert und über den Push-Dienst des jeweiligen Browser- bzw. Geräteherstellers (z.B. Apple, Google,
                Mozilla) ausgeliefert. Sie können die Benachrichtigungen jederzeit in den Einstellungen Ihres Browsers
                oder Geräts wieder ausschalten.
            </p>

            <h2>9. Weitergabe und Hosting</h2>
            <p>
                Die Daten werden auf Servern
                @if (config('legal.hoster'))
                    unseres Hosting- und E-Mail-Anbieters ({{ config('legal.hoster') }})
                @else
                    unseres Hosting- und E-Mail-Anbieters
                @endif
                gespeichert und über diesen versendet. Der Anbieter bearbeitet die Daten nur in unserem Auftrag. Eine
                darüber hinausgehende Weitergabe an Dritte findet nicht statt, ausser wir sind dazu gesetzlich verpflichtet.
            </p>

            <h2>10. Aufbewahrung und Löschung</h2>
            <p>
                Wir bewahren Ihre Daten so lange auf, wie Ihr Konto besteht. <strong>Eltern können jederzeit</strong>
                einzelne Kinder samt deren Übungsdaten löschen (im Eltern-Bereich beim jeweiligen Kind) oder ihr eigenes Konto
                löschen (Profil → Account löschen). Löscht die letzte Elternperson einer Familie ihr Konto, werden auch die
                Familie, alle Kinder und deren Übungsdaten unwiderruflich gelöscht. Nachrichten aus dem Kontaktformular
                werden wie oben beschrieben gelöscht.
            </p>

            <h2>11. Sicherheit</h2>
            <p>
                Die Übertragung erfolgt verschlüsselt (HTTPS). Passwörter, Kinder-Codes und Login-Links werden nur in
                geschützter Form gespeichert. Trotzdem kann bei der Datenübertragung im Internet keine absolute Sicherheit
                garantiert werden.
            </p>

            <h2>12. Ihre Rechte</h2>
            <p>
                Sie können Auskunft über Ihre bearbeiteten Daten verlangen sowie deren Berichtigung, Löschung oder
                Einschränkung der Bearbeitung, die Herausgabe Ihrer Daten und den Widerruf erteilter Einwilligungen.
                Schreiben Sie uns dazu über das <a href="{{ route('contact.show') }}">Kontaktformular</a> oder an die
                oben genannte Adresse. Sie haben zudem das Recht, sich bei der zuständigen Datenschutzbehörde zu
                beschweren, in der Schweiz beim Eidgenössischen Datenschutz- und Öffentlichkeitsbeauftragten (EDÖB).
            </p>

            <h2>13. Änderungen</h2>
            <p>Wir passen diese Erklärung an, wenn sich das Angebot oder die Rechtslage ändert. Es gilt die jeweils hier veröffentlichte Fassung.</p>
        </div>
    </div>
</x-public-layout>
