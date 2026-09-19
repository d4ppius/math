<p align="center">
  <img src="public/images/logo.png" alt="Rechenfuchs" width="240">
</p>

# Rechenfuchs

Eine kleine Webapp, mit der Kinder spielerisch und regelmässig das kleine Einmaleins üben – als installierbare PWA auf dem iPad, mit persönlichem Homescreen-Icon pro Kind.

> Dies ist ein privates Familienprojekt. Das Repository ist öffentlich, weil der Code jemandem nützen könnte – es enthält aber **keine echten Namen, Zugangsdaten oder sonstigen persönlichen Daten** einer echten Familie. Alle `.env`-Dateien, Datenbank-Inhalte und Zugangsdaten bleiben ausschliesslich auf dem jeweiligen Server.

## Was die App macht

- **Mandantenfähig nach Familien:** Eine Familie kann mehrere Eltern-Accounts (z.B. Mama + Papa) und beliebig viele Kinder-Accounts haben.
- **Eltern-Bereich** (`/eltern`, bewusst unauffällig, nicht von der Kind-Oberfläche aus verlinkt): Kinder anlegen, Schwierigkeit und Häufigkeit der Übungen einstellen, Statistiken einsehen, Push-Benachrichtigung erhalten, wenn ein Kind sein Tagesziel erreicht hat. Den Timer während der Übung kann man pro Kind ausblenden (für Kinder, die er stresst): Er läuft dann unsichtbar weiter, und kurz vor Schluss erscheint ein sanftes «Gleich geschafft!». Auch der Tempo-Bonus bei den Punkten lässt sich pro Kind abschalten (siehe [Übungseinstellungen](#übungseinstellungen) und [Punkte](#punkte)).
- **Kind-Login ganz ohne Passwort:** Jedes Kind bekommt einen persönlichen Magic-Link, den die Eltern einmalig als Homescreen-Icon auf dem iPad einrichten. Ein Tap auf das Icon reicht zum Einloggen (optional zusätzlich geschützt durch einen 4-stelligen PIN-Code, falls mehrere Kinder sich ein Gerät teilen).
- **Adaptives Üben:** Jede einzelne 1×1-Aufgabe wird pro Kind einzeln getrackt. Aufgaben, die noch nicht sitzen oder langsam beantwortet werden, kommen häufiger wieder – kein reiner Zufall.
- **Zeitlich begrenzte Sessions:** z.B. 10 Minuten am Stück, in der eigenen Geschwindigkeit des Kindes, serverseitig zeitlich abgesichert.
- **Punkte, Level, Abzeichen, Konfetti und Töne** als Motivation, siehe [Punkte](#punkte) und [Level und Abzeichen](#level-und-abzeichen).
- **Admin-Bereich** (`/admin`): Für den Betreiber der Installation, siehe [unten](#admin-bereich).
- **Erweiterbar:** Die Architektur ist so gebaut, dass später weitere Matheaufgaben-Typen (Addition, Division, …) ergänzt werden können, ohne den Kern umzubauen.

## Übungseinstellungen

Eltern stellen diese Optionen pro Kind und pro Übung ein (Eltern-Bereich → Kind → Übungseinstellungen):

| Einstellung | Wirkung |
| --- | --- |
| **Aktive Reihen** | Nur die ausgewählten Reihen werden abgefragt. Am besten mit 1–2 Reihen starten. |
| **Session-Dauer** | 3, 5, 10, 15 oder 20 Minuten. Die Zeit wird serverseitig überwacht. |
| **Timer anzeigen** | Aus: Der Countdown ist für das Kind unsichtbar, läuft aber im Hintergrund weiter. Kurz vor Schluss (letzte Minute) erscheint ein sanftes «Gleich geschafft!» ohne Zahlen. Gedacht für Kinder, die der Timer stresst. |
| **Tempo-Bonus** | Aus: Jede richtige Antwort gibt gleich viele Punkte, egal wie schnell (siehe unten). |
| **Töne** | Aus: Keine Töne bei richtigen und falschen Antworten. An: Ein heller Klang bei richtig, ein leiser tiefer Ton bei falsch (bewusst kein schrilles Signal). |
| **Ziel-Häufigkeit** | Täglich oder nur wochentags (Montag bis Freitag). Bestimmt, an welchen Tagen die Push-Erinnerung an das Kind verschickt wird. |

Alle Schalter (Timer, Tempo-Bonus, Töne) sind standardmässig **an**. Bestehende Kinder behalten also ihr bisheriges Verhalten, bis Eltern etwas ändern.

## Punkte

Punkte gibt es nur für richtige Antworten. Die Berechnung steht in `app/Services/Gamification/PointsCalculator.php`.

```
Punkte = (10 + Tempo-Bonus) × Serien-Multiplikator     (gerundet)
```

- **Grundpunkte:** 10 pro richtiger Antwort. Eine falsche Antwort gibt 0 Punkte und beendet die Serie.
- **Tempo-Bonus:** 0 bis +10, je nachdem, wie deutlich die Zielzeit unterboten wird. Er wird nie negativ: Auch eine langsame, richtige Antwort gibt mindestens die 10 Grundpunkte. Ohne Bonus (Einstellung oben) ist er immer 0.

  | Reihe der Aufgabe | Zielzeit |
  | --- | --- |
  | 1er, 2er | 2,5 s |
  | 3er bis 5er | 3,5 s |
  | 6er bis 10er | 4,5 s |

  Sofort beantwortet: +10. In der Zielzeit oder langsamer: +0. Dazwischen linear. Die Zeit misst der Server, von der Ausgabe der Frage bis zum Eingang der Antwort.
- **Serien-Multiplikator:** Zählt die richtigen Antworten in Folge innerhalb der laufenden Session: ab der 5. gilt ×1,2, ab der 10. gilt ×1,5.

Ergebnis: 0 bis 30 Punkte pro Antwort. Beispiel für eine 3er-Aufgabe (Zielzeit 3,5 s): in 1,75 s richtig ergibt 15 Punkte, in 3,5 s oder langsamer 10 Punkte, in 3,5 s als 5. richtige Antwort in Folge 12 Punkte. Die Punkte zählen für die Session und zusätzlich für das Gesamtkonto des Kindes.

## Level und Abzeichen

**Level:** Aus den Gesamtpunkten eines Kindes werden zehn Level abgeleitet (`app/Services/Gamification/LevelCalculator.php`, nichts davon wird gespeichert). Auf der Startseite des Kindes steht der Titel mit einem Fortschrittsbalken zum nächsten Level, Eltern sehen es in der Statistik. Eine Session bringt grob 300 bis 650 Punkte, die Schwellen wachsen deshalb: Das erste Level-Up kommt nach etwa einer Session, das letzte nach einigen Monaten regelmässigem Üben.

| Level | ab Punkten | Titel |
| --- | --- | --- |
| 1 | 0 | Rechen-Anfänger 🌱 |
| 2 | 400 | Zahlen-Entdecker 🔍 |
| 3 | 1200 | Rechen-Lehrling 📘 |
| 4 | 2500 | Zahlen-Flitzer 🏃 |
| 5 | 4500 | Rechen-Profi ⭐ |
| 6 | 7500 | Knobel-Meister 🧩 |
| 7 | 11500 | Zahlen-Zauberer 🪄 |
| 8 | 16500 | Rechen-Ass 🎯 |
| 9 | 22500 | Mathe-Held 🦸 |
| 10 | 30000 | Rechenfuchs-Meister 🦊 |

**Abzeichen:** Nach jeder abgeschlossenen Session prüft `BadgeEvaluator` alle noch nicht verdienten Abzeichen. Ein Abzeichen wird nie doppelt vergeben. Den festen Katalog legt `BadgeSeeder` an (mehrfach ausführbar, läuft bei jedem Deployment mit `db:seed`).

| Abzeichen | Bedingung |
| --- | --- |
| **Erste Übung** 🎉 | Erste Session mit mindestens 5 beantworteten Aufgaben |
| **7-Tage-Serie** 🔥 | Das Tagesziel an 7 Tagen hintereinander erreicht (die heutige Session zählt mit) |
| **Blitzrechner** ⚡ | In einer Session mindestens 10 richtige Antworten, im Schnitt unter 2 Sekunden. Wird bei Kindern mit ausgeschaltetem Tempo-Bonus nie vergeben, damit niemand zum Hetzen verleitet wird. |
| **Meister der N-er-Reihe** 👑 (N = 1 bis 9) | Die Reihe zu mindestens 90 % richtig, mit mindestens 15 Versuchen über mindestens 8 der 10 Aufgaben der Reihe |

Neue Abzeichen erscheinen auf der Zusammenfassung nach der Übung. Damit die Startseite des Kindes schlank bleibt, zeigt sie nur eine kompakte Karte «Meine Abzeichen» (Anzahl und die drei neuesten). Ein Tipp darauf öffnet die Seite **Meine Erfolge** (`/kind/erfolge`), die Statistik-Seite des Kindes: Level mit Fortschritt, Punkte, die verdienten Abzeichen mit Datum und, falls die Eltern es erlauben, darunter «Das kannst du noch schaffen»: die noch offenen Abzeichen ausgegraut, mit einer kurzen Beschreibung, wie man sie bekommt. Die Eltern-Statistik zeigt den ganzen Katalog (nicht verdiente ausgegraut).

Eltern schalten das pro Kind ein und aus: Kind bearbeiten → **«Abzeichen zeigen, die noch nicht verdient sind»** (standardmässig an). Aus: Das Kind sieht nur die bereits verdienten. Bei Kindern mit ausgeschaltetem Tempo-Bonus wird der Blitzrechner gar nicht erst als Ziel angezeigt, weil sie ihn nie bekommen können (die Zähler heissen dann z.B. «3 von 11»).

**Konfetti und Töne:** Am Ende einer Session mit mindestens einer richtigen Antwort regnet es Konfetti, bei einem neuen Abzeichen zusätzlich von beiden Seiten. Es läuft nur auf der Zusammenfassung, nie während des Übens, und entfällt bei aktivierter Systemeinstellung «Bewegung reduzieren». Die Töne werden im Browser erzeugt (keine Audio-Dateien); auf iOS starten sie nach dem ersten Tipp auf eine Zifferntaste.

### Eigene Bilder für Abzeichen

Solange kein Bild vorhanden ist, zeigt ein Abzeichen sein Emoji. Bilder legt man als PNG in `public/images/badges/` ab, ganz ohne Code-Änderung (quadratisch, transparenter Hintergrund, 512 × 512 Pixel empfohlen). Die Anzeige sucht in dieser Reihenfolge:

1. `public/images/badges/{key}.png` für ein einzelnes Abzeichen: `first_session.png`, `streak_7.png`, `blitz.png`, `row_mastery_1.png` bis `row_mastery_9.png`
2. `public/images/badges/{typ}.png` als gemeinsames Bild einer ganzen Gruppe. Für die Reihen-Meister genügt **ein** Bild `row_mastery.png`: Die Reihen-Nummer wird als kleines Schild darübergelegt.

Nicht verdiente Abzeichen zeigt die Eltern-Statistik automatisch ausgegraut, es braucht dafür kein zweites Bild.

## Tech-Stack

- [Laravel](https://laravel.com) (PHP 8.5) im klassischen MVC-Aufbau
- MySQL
- Blade + [Alpine.js](https://alpinejs.dev) + [Tailwind CSS](https://tailwindcss.com) (via Vite, kein SPA-Framework nötig)
- [Laravel Breeze](https://laravel.com/docs/starter-kits) als Basis für den Eltern-Login
- Web-Push (VAPID) über [`laravel-notification-channels/webpush`](https://github.com/laravel-notification-channels/webpush) für Browser-Benachrichtigungen an die Eltern, mit E-Mail als Fallback
- PWA (Manifest + Service Worker), damit die App auf dem iPad als eigenständiges Icon installiert werden kann

## Lokale Entwicklung

Voraussetzungen: PHP 8.5, Composer, Node.js/npm, MySQL.

```bash
composer install
cp .env.example .env
php artisan key:generate
# .env anpassen: DB_* auf eine lokale MySQL-Datenbank zeigen lassen
php artisan migrate
npm install
npm run dev   # oder: npm run build
php artisan serve
```

## Admin-Bereich

Ein Admin sieht und verwaltet alle Familien auf der Installation. Admin wird man ausschliesslich per Befehl auf dem Server (siehe [INSTALL.md](INSTALL.md#7-admin-zugang-einrichten)):

```bash
php artisan app:make-admin deine@mail.example            # Admin-Rechte vergeben
php artisan app:make-admin deine@mail.example --revoke   # Admin-Rechte entziehen
```

Unter `/admin` (für alle anderen Benutzer ein 404):

| Bereich | Was möglich ist |
| --- | --- |
| **Übersicht** | Zähler für Familien, Eltern, Kinder, Sessions; die letzten Sessions |
| **Familien** | Suchen, umbenennen (Name, Zeitzone), löschen (samt allen Eltern, Kindern und Daten); Eltern-Konten der Familie löschen |
| **Kinder** | Alle Kinder über alle Familien; Bearbeiten, Übungseinstellungen und Statistik öffnen die normalen Eltern-Seiten des jeweiligen Kindes |
| **Sessions** | Alle Übungs-Sessions (filterbar pro Kind) mit jeder einzelnen Antwort, nur lesend |
| **Übungen** | Übungstypen aktivieren/deaktivieren (deaktivierte Übungen können Kinder nicht mehr starten); einzelne Aufgaben ansehen und bearbeiten |

**Als Elternteil ansehen:** In der Familienansicht kann der Admin ein Eltern-Konto «übernehmen» und die App so sehen, wie die Eltern sie sehen (z.B. zur Fehlersuche). Ein gelbes Banner oben erinnert daran, und «Zurück zum Admin» beendet die Ansicht.

Schutzregeln: Der Admin kann weder sich selbst noch seine eigene Familie löschen. Andere Admins lassen sich weder löschen noch übernehmen; dafür zuerst die Admin-Rechte entziehen.

## Deployment (Plesk)

Eine ausführliche Schritt-für-Schritt-Anleitung für das Deployment auf einem Plesk-Server (Datenbank anlegen, `.env` einrichten, Admin-Zugang, Cronjob für den Laravel-Scheduler) befindet sich in [INSTALL.md](INSTALL.md). Updates spielt man mit `./deploy.sh` ein: Das Skript zieht den Code, installiert Abhängigkeiten, baut das Frontend, migriert die Datenbank und baut die Caches neu auf.

## Lizenz

Der Code ist unter der [MIT-Lizenz](LICENSE.md) freigegeben. Das schliesst keine personenbezogenen Daten ein – siehe Hinweis oben.
