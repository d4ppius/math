<p align="center">
  <img src="public/images/logo.png" alt="Rechenfuchs" width="240">
</p>

# Rechenfuchs

Eine kleine Webapp, mit der Kinder spielerisch und regelmässig Einmaleins und Plus bis 20 üben – als installierbare PWA auf dem iPad, mit persönlichem Homescreen-Icon pro Kind.

> Dies ist ein privates Familienprojekt. Das Repository ist öffentlich, weil der Code jemandem nützen könnte – es enthält aber **keine echten Namen, Zugangsdaten oder sonstigen persönlichen Daten** einer echten Familie. Alle `.env`-Dateien, Datenbank-Inhalte und Zugangsdaten bleiben ausschliesslich auf dem jeweiligen Server.

## Was die App macht

- **Mandantenfähig nach Familien:** Eine Familie kann mehrere Eltern-Accounts (z.B. Mama + Papa) und beliebig viele Kinder-Accounts haben.
- **Eltern-Bereich** (`/eltern`, bewusst unauffällig, nicht von der Kind-Oberfläche aus verlinkt): Kinder anlegen, Schwierigkeit und Häufigkeit der Übungen einstellen, Statistiken einsehen, Push-Benachrichtigung erhalten, wenn ein Kind sein Tagesziel erreicht hat. Den Timer während der Übung kann man pro Kind ausblenden (für Kinder, die er stresst): Er läuft dann unsichtbar weiter, und kurz vor Schluss erscheint ein sanftes «Gleich geschafft!». Auch der Tempo-Bonus bei den Punkten lässt sich pro Kind abschalten (siehe [Übungseinstellungen](#übungseinstellungen) und [Punkte](#punkte)).
- **Kind-Login ganz ohne Passwort:** Jedes Kind bekommt einen persönlichen Magic-Link, den die Eltern einmalig als Homescreen-Icon auf dem iPad einrichten. Ein Tap auf das Icon reicht zum Einloggen (optional zusätzlich geschützt durch einen 4-stelligen PIN-Code, falls mehrere Kinder sich ein Gerät teilen).
- **Adaptives Üben:** Jede einzelne 1×1-Aufgabe wird pro Kind einzeln getrackt. Aufgaben, die noch nicht sitzen oder langsam beantwortet werden, kommen häufiger wieder – kein reiner Zufall.
- **Zeitlich begrenzte Sessions:** z.B. 10 Minuten am Stück, in der eigenen Geschwindigkeit des Kindes, serverseitig zeitlich abgesichert.
- **Punkte, Level, Abzeichen, Konfetti und Töne** als Motivation, siehe [Punkte](#punkte) und [Level und Abzeichen](#level-und-abzeichen).
- **Öffentliche Startseite** (`/`) für Eltern: erklärt in vier Schritten, wie es funktioniert, mit Funktionen, «Sicher für Kinder» und FAQ, dazu Impressum, Datenschutzerklärung und Kontaktformular, siehe [Öffentliche Seiten](#öffentliche-seiten).
- **Admin-Bereich** (`/admin`): Für den Betreiber der Installation, siehe [unten](#admin-bereich).
- **Erweiterbar:** Die Architektur ist so gebaut, dass später weitere Matheaufgaben-Typen (Addition, Division, …) ergänzt werden können, ohne den Kern umzubauen.

## Übungen

Rechenfuchs hat zwei Übungen. Weitere Aufgabentypen lassen sich nach demselben Muster ergänzen (`ExerciseTypeContract`, Eintrag in `config/exercise_types.php`).

| Übung | Aufgaben | Gruppen (Eltern wählen) |
| --- | --- | --- |
| **Einmaleins** | 90 Aufgaben, 1×1 bis 9×10 | Reihen 1 bis 9 |
| **Plus bis 20** | 100 Aufgaben, beide Summanden von 1 bis 10 (Einspluseins) | «Plus bis 10» (45), «Plus mit der 10» (19), «Zehnerübergang» (36) |

Eltern schalten pro Kind frei, welche Übungen es gibt. Einmaleins ist von Anfang an aktiv, **Plus ist aus**, bis die Eltern es in den Übungseinstellungen freischalten. Ist genau eine Übung aktiv, sieht das Kind wie bisher einen grossen Knopf. Sind mehrere aktiv, wählt es auf der Startseite (eine Karte pro Übung, «Weiter üben», wenn dort noch eine Session läuft). Der Server prüft die Auswahl selbst, ein Kind kann keine Übung starten, die nicht freigeschaltet oder vom Admin deaktiviert ist. Punkte, Level und Tagesziel sind für beide Übungen gemeinsam. Die Eltern-Statistik zeigt pro Übung eine eigene Heatmap.

## Übungseinstellungen

Eltern stellen diese Optionen pro Kind und pro Übung ein (Eltern-Bereich → Kind → Übungseinstellungen):

| Einstellung | Wirkung |
| --- | --- |
| **Für [Kind] freischalten** | Schaltet die Übung für das Kind ein oder aus. Aus: Das Kind sieht sie nicht, die übrigen Einstellungen bleiben erhalten. Mindestens eine Übung muss aktiv bleiben. |
| **Aktive Reihen / Aufgabenarten** | Nur die ausgewählten Gruppen werden abgefragt (bei Einmaleins «Reihen», bei Plus «Aufgabenarten»). Am besten mit wenigen starten. Eine aktive Übung braucht mindestens eine Gruppe. |
| **Session-Dauer** | 3, 5, 10, 15 oder 20 Minuten. Die Zeit wird serverseitig überwacht. |
| **Timer anzeigen** | Aus: Der Countdown ist für das Kind unsichtbar, läuft aber im Hintergrund weiter. Kurz vor Schluss (letzte Minute) erscheint ein sanftes «Gleich geschafft!» ohne Zahlen. Gedacht für Kinder, die der Timer stresst. |
| **Tempo-Bonus** | Aus: Jede richtige Antwort gibt gleich viele Punkte, egal wie schnell (siehe unten). |
| **Töne** | Aus: Keine Töne bei richtigen und falschen Antworten. An: Ein heller Klang bei richtig, ein leiser tiefer Ton bei falsch (bewusst kein schrilles Signal). |
| **Ziel-Häufigkeit** | Täglich oder nur wochentags (Montag bis Freitag). Bestimmt, an welchen Tagen die Push-Erinnerung an das Kind verschickt wird. |

Timer, Tempo-Bonus und Töne sind standardmässig **an**, und alle Einstellungen gelten je Übung. Bestehende Kinder behalten ihr bisheriges Verhalten, bis Eltern etwas ändern.

## Punkte

Punkte gibt es nur für richtige Antworten. Die Berechnung steht in `app/Services/Gamification/PointsCalculator.php`.

```
Punkte = (10 + Tempo-Bonus) × Serien-Multiplikator     (gerundet)
```

- **Grundpunkte:** 10 pro richtiger Antwort. Eine falsche Antwort gibt 0 Punkte und beendet die Serie.
- **Tempo-Bonus:** 0 bis +10, je nachdem, wie deutlich die Zielzeit unterboten wird. Er wird nie negativ: Auch eine langsame, richtige Antwort gibt mindestens die 10 Grundpunkte. Ohne Bonus (Einstellung oben) ist er immer 0.

  | Übung und Gruppe | Zielzeit |
  | --- | --- |
  | Einmaleins: 1er, 2er | 2,5 s |
  | Einmaleins: 3er bis 5er | 3,5 s |
  | Einmaleins: 6er bis 9er | 4,5 s |
  | Plus: bis 10, mit der 10 | 2,0 s |
  | Plus: Zehnerübergang | 3,5 s |

  Sofort beantwortet: +10. In der Zielzeit oder langsamer: +0. Dazwischen linear. Die Zeit misst der Server, von der Ausgabe der Frage bis zum Eingang der Antwort.
- **Serien-Multiplikator:** Zählt die richtigen Antworten in Folge innerhalb der laufenden Session: ab der 5. gilt ×1,2, ab der 10. gilt ×1,5.

Ergebnis: 0 bis 30 Punkte pro Antwort. Beispiel für eine 3er-Aufgabe (Zielzeit 3,5 s): in 1,75 s richtig ergibt 15 Punkte, in 3,5 s oder langsamer 10 Punkte, in 3,5 s als 5. richtige Antwort in Folge 12 Punkte. Die Punkte zählen für die Session, für das Punktekonto der jeweiligen Übung (siehe Level unten) und zusätzlich für das Gesamtkonto des Kindes.

## Fehlerkorrektur und Rechentipps

Bei einer **richtigen** Antwort bleibt es wie gewohnt: 1,4 Sekunden Feedback, dann automatisch weiter. Bei einer **falschen** Antwort springt die App nicht mehr automatisch weiter: Das Kind sieht die richtige Lösung und tippt sie selbst auf derselben Zifferntastatur ein, bevor es zur nächsten Aufgabe geht ([Cover-Copy-Compare](https://perrjournal.com/index.php/perrjournal/article/view/204), eine gut belegte Methode für Rechenfakten). Nach 2 falschen Nachtipp-Versuchen erscheint ein „Weiter"-Knopf, damit niemand hängen bleibt. Das Nachtippen wird nicht an den Server geschickt oder gespeichert — der Server hat die Lösung mit der ersten (falschen) Antwort schon geliefert. Der Session-Timer läuft während der Korrektur normal weiter.

Zusätzlich kann bei einer falschen Antwort ein Rechentipp erscheinen, ein kurzer vorgerechneter Lösungsweg statt nur der Lösung ([Worked-Example-Effect](https://en.wikipedia.org/wiki/Worked-example_effect)). Jeder Übungstyp hat eigene Regeln (`ExerciseTypeContract::hint()`), für jede der 90 Einmaleins- und 100 Plus-Aufgaben einzeln geprüft, nicht nur stichprobenweise:

| Einmaleins | Strategie |
| --- | --- |
| ×1 | bleibt gleich |
| ×2 | verdoppeln |
| ×10 | Null anhängen (gilt in jeder Reihe) |
| ×5 | Hälfte der 10er-Aufgabe |
| ×4 | zweimal verdoppeln |
| ×3 | 2er-Aufgabe + 1er-Aufgabe |
| ×6 | 5er-Aufgabe + 1er-Aufgabe |
| ×9 | 10er-Aufgabe − 1er-Aufgabe |
| ×7, ×8 | aus der 6er- bzw. 4er-Reihe abgeleitet |

| Plus bis 20 | Strategie |
| --- | --- |
| Verdoppeln (z. B. 6 + 6) | „Verdoppeln", geprüft vor allen anderen Regeln |
| Mit der 10 (z. B. 10 + 6) | die andere Zahl einfach dranhängen |
| Zehnerübergang (z. B. 8 + 5) | zuerst zur 10 ergänzen, dann den Rest dazuzählen |
| Plus bis 10, sonst (z. B. 3 + 4) | vom grösseren Summanden aus weiterzählen |

Die Rechenwege sind pädagogisch gängige Standardstrategien, aber nicht mit einem bestimmten Lehrmittel abgeglichen. Deshalb ist der Tipp pro Kind und Übung **standardmässig aus** (Eltern-Einstellung „Rechentipp bei falschen Antworten"), mit dem Hinweis, zu prüfen, ob er zum Rechenweg der Schule passt oder eher verwirrt.

## Level und Abzeichen

**Level:** Jede Übung hat ihr eigenes Punktekonto und ihr eigenes Level 1 bis 10, unabhängig davon, wie weit das Kind in einer anderen Übung schon ist (Spalte `child_exercise_settings.points`, Ableitung in `app/Services/Gamification/LevelCalculator.php` über `ChildExerciseSetting::level()`). Damit bleibt jede Übung ein eigener, motivierender Fortschritt statt vom Level der anderen "erledigt" zu wirken. Solange ein Kind nur eine Übung hat (der Normalfall, Plus ist standardmässig aus), ist davon nichts zu sehen: Kinder-Startseite und Eltern-Statistik zeigen weiterhin nur einen Balken. Sobald zwei oder mehr Übungen aktiv sind, bekommt jede ihr eigenes Level: auf der Kinder-Startseite auf der jeweiligen Übungs-Karte, auf der Erfolgsseite (`/kind/erfolge`) als eigener Block mit dem Namen der Übung, in der Eltern-Statistik im jeweiligen Übungs-Reiter direkt über der Heatmap. `children.total_points` bleibt daneben als reine Anzeige-Summe über alle Übungen bestehen ("X Punkte insgesamt"), beeinflusst aber kein Level mehr.

Die Schwellen sind mit dem echten `PointsCalculator` simuliert (schnelles/typisches/langsames Tempo auf einer einzelnen Übung): Das erste Level-Up kommt nach einer bis wenigen 10-Minuten-Sessions, das letzte Level nach schätzungsweise einem Monat (schnelles Tempo) bis einigen Monaten (langsames Tempo) regelmässigem Üben derselben Übung. Bestehende Punkte gingen bei der Umstellung nicht verloren: Eine einmalige Migration hat sie aus der Session-Historie pro Übung zurückgerechnet.

| Level | ab Punkten | Titel |
| --- | --- | --- |
| 1 | 0 | Rechen-Anfänger 🌱 |
| 2 | 500 | Zahlen-Entdecker 🔍 |
| 3 | 1500 | Rechen-Lehrling 📘 |
| 4 | 3500 | Zahlen-Flitzer 🏃 |
| 5 | 7000 | Rechen-Profi ⭐ |
| 6 | 13000 | Knobel-Meister 🧩 |
| 7 | 21000 | Zahlen-Zauberer 🪄 |
| 8 | 31000 | Rechen-Ass 🎯 |
| 9 | 42000 | Mathe-Held 🦸 |
| 10 | 55000 | Rechenfuchs-Meister 🦊 |

**Abzeichen:** Nach jeder abgeschlossenen Session prüft `BadgeEvaluator` alle noch nicht verdienten Abzeichen. Ein Abzeichen wird nie doppelt vergeben. Es gibt 18 Abzeichen. Den festen Katalog legt `BadgeSeeder` an (mehrfach ausführbar, läuft bei jedem Deployment mit `db:seed`).

| Abzeichen | Bedingung |
| --- | --- |
| **Erste Übung** 🎉 | Erste Session mit mindestens 5 beantworteten Aufgaben |
| **7-Tage-Serie** 🔥 | Das Tagesziel an 7 Tagen hintereinander erreicht (die heutige Session zählt mit) |
| **Blitzrechner** ⚡ | In einer **Einmaleins**-Session mindestens 10 richtige Antworten, im Schnitt unter 2 Sekunden. Wird bei Kindern mit ausgeschaltetem Tempo-Bonus nie vergeben, damit niemand zum Hetzen verleitet wird. |
| **Meister der N-er-Reihe** 👑 (N = 1 bis 9) | Die Reihe zu mindestens 90 % richtig, mit mindestens 15 Versuchen über mindestens 8 der 10 Aufgaben der Reihe |
| **Plus-Starter** ➕ | Erste Plus-Session mit mindestens 5 beantworteten Aufgaben |
| **Meister von Plus bis 10** 🥇 | «Plus bis 10» zu mindestens 90 % richtig, mit mindestens 50 Versuchen über mindestens 34 der 45 Aufgaben |
| **Meister von Plus mit der 10** 🥇 | «Plus mit der 10» zu mindestens 90 % richtig, mit mindestens 22 Versuchen über mindestens 14 der 19 Aufgaben |
| **Meister des Zehnerübergangs** 🥇 | «Zehnerübergang» zu mindestens 90 % richtig, mit mindestens 40 Versuchen über mindestens 27 der 36 Aufgaben |
| **Plus-Blitz** ⚡ | In einer Plus-Session mindestens 10 richtige Antworten, im Schnitt unter 1,5 Sekunden (nur bei aktivem Tempo-Bonus für Plus) |
| **Allrounder** 🌟 | Am selben Tag beide Übungen mit je mindestens 5 beantworteten Aufgaben geübt |

Ein Abzeichen ist nur erreichbar (und wird dem Kind nur als Ziel gezeigt), wenn seine Übung für das Kind freigeschaltet ist. Der Allrounder braucht mindestens zwei aktive Übungen, ein Speed-Abzeichen zusätzlich den Tempo-Bonus der jeweiligen Übung. Schon verdiente Abzeichen bleiben immer sichtbar. Auf der Erfolgsseite und in der Eltern-Statistik sind die Abzeichen in «Allgemein» und einen Abschnitt je Übung gegliedert.

Neue Abzeichen erscheinen auf der Zusammenfassung nach der Übung. Damit die Startseite des Kindes schlank bleibt, zeigt sie nur eine kompakte Karte «Meine Abzeichen» (Anzahl und die drei neuesten). Ein Tipp darauf öffnet die Seite **Meine Erfolge** (`/kind/erfolge`), die Statistik-Seite des Kindes: Level mit Fortschritt, Punkte, die verdienten Abzeichen mit Datum und, falls die Eltern es erlauben, darunter «Das kannst du noch schaffen»: die noch offenen Abzeichen ausgegraut, mit einer kurzen Beschreibung, wie man sie bekommt. Die Eltern-Statistik zeigt den ganzen Katalog (nicht verdiente ausgegraut).

Eltern schalten das pro Kind ein und aus: Kind bearbeiten → **«Abzeichen zeigen, die noch nicht verdient sind»** (standardmässig an). Aus: Das Kind sieht nur die bereits verdienten. Was ein Kind nicht erreichen kann (z.B. Plus-Abzeichen ohne Plus, oder ein Speed-Abzeichen ohne Tempo-Bonus), wird ihm gar nicht erst als Ziel angezeigt. Die Zähler passen sich an, z.B. «3 von 11».

**Konfetti und Töne:** Am Ende einer Session mit mindestens einer richtigen Antwort regnet es Konfetti, bei einem neuen Abzeichen zusätzlich von beiden Seiten. Es läuft nur auf der Zusammenfassung, nie während des Übens, und entfällt bei aktivierter Systemeinstellung «Bewegung reduzieren». Die Töne werden im Browser erzeugt (keine Audio-Dateien); auf iOS starten sie nach dem ersten Tipp auf eine Zifferntaste.

### Bilder für Abzeichen und Maskottchen

Solange für ein Abzeichen kein Bild vorhanden ist, zeigt es sein Emoji. Bilder legt man als PNG in `public/images/badges/` ab, ganz ohne Code-Änderung. Die Anzeige sucht in dieser Reihenfolge:

1. `public/images/badges/{key}.png` für ein einzelnes Abzeichen: `first_session.png`, `streak_7.png`, `blitz.png`, `row_mastery_1.png` bis `row_mastery_9.png`, `addition_first_session.png`, `addition_mastery_1.png` bis `addition_mastery_3.png`, `addition_blitz.png`, `allrounder.png`
2. `public/images/badges/{typ}.png` als gemeinsames Bild einer ganzen Gruppe. Für die Reihen-Meister genügt **ein** Bild `row_mastery.png` (die Reihen-Nummer wird als kleines Schild darübergelegt), für die drei Plus-Meister **ein** Bild `group_mastery.png` (Schild mit der Marke der Gruppe: «10», «+10», «20»). Achtung: `addition_first_session` fällt ohne eigenes Bild auf `first_session.png` zurück, `addition_blitz` auf `blitz.png`.

Nicht verdiente Abzeichen zeigt die Eltern-Statistik automatisch ausgegraut, es braucht dafür kein zweites Bild.

**Kinder-Icons** (`ChildIconController` / `IconGenerator`, der Fuchs mit farbigem Kreis und Anfangsbuchstabe fürs Home-Bildschirm-Icon) zeichnet den Buchstaben mit `resources/fonts/Figtree-Bold.ttf` (OFL-1.1, aus dem Quell-Repo der ohnehin schon verwendeten Schrift Figtree). Die Datei liegt bewusst im Projekt statt sich auf Systemschriften zu verlassen: Auf einem Server mit `open_basedir`-Beschränkung (bei den meisten Hosting-Anbietern der Fall) liegen Systemschriften ausserhalb des erlaubten Pfads, und selbst nur zu *prüfen*, ob so eine Datei existiert, bricht die Bildgenerierung dort sonst mit einem 500er ab.

**Wo die Bilder liegen**

| Ordner | Inhalt |
| --- | --- |
| `resources/mascot/originals/` | Die **unbearbeiteten Originale** der Fuchs-Posen (transparente PNGs, rund 1,9 MB): `winkt` (der stehende, winkende Fuchs), `jubelt`, `flamme`, `blitz`, `krone`, `plus`, `medaille`, `sprung`, `jongliert`. Sie werden nicht ausgeliefert und dienen als Quelle für alles Weitere. |
| `public/images/mascot/` | Web-taugliche Fassungen der ganzen Pose (lange Seite 720 px), bereit, um den Fuchs an weiteren Stellen zu zeigen. |
| `public/images/badges/` | Die Abzeichen-Bilder: 512 × 512 Pixel, ein Ausschnitt bis zum Oberkörper, damit der Fuchs auch in der kleinen Medaille erkennbar ist. |
| `public/images/mascot.png` | Der stehende, winkende Fuchs auf den Kinder-Seiten und der Startseite (400 px breit, aus `winkt` abgeleitet). |

**Neue Pose einbinden:** Original als PNG nach `resources/mascot/originals/{name}.png` legen und ausführen:

```bash
php scripts/process-mascot-image.php {name} --badge={key} --crop=x,y,breite,höhe --fade=110
```

`--badge` schreibt zusätzlich das Abzeichen-Bild, `--crop` wählt den Ausschnitt des Originals (empfohlen: Kopf bis Oberkörper), `--fade` blendet einen harten Schnitt durch den Körper unten weich aus, `--fadeleft` dasselbe am linken Rand (z.B. durch einen Schwanz). Ein quer laufender Ausschnitt wird in der Medaille mittig ausgerichtet. Beispiel Blitz: `--badge=blitz --crop=430,10,1070,900 --fade=110 --fadeleft=150`. Das Skript entfernt auch die unsichtbaren Pixel mit Fremdfarben, die sonst beim Verkleinern dunkle Ränder erzeugen.

**Bilder mit «falscher Transparenz»:** Manche Bilder (`plus`, `medaille`, `sprung`) kamen mit einem ins Bild eingebrannten weiss-grauen Schachbrett statt echter Transparenz. Das Skript erkennt das an den deckenden Ecken und stellt den Fuchs frei: Vom Rand aus wird alles Neutral-Helle entfernt (Fell und Creme sind wärmer, Augen und das Zeichen auf dem Halstuch sind umschlossen), dazu eingeschlossene Schachbrett-Flecken, ein heller Saum und hellblaue Tempolinien. Mit `--minarea=N` werden zusätzlich lose Splitter unter N Pixeln verworfen.

Die bisher verwendeten Aufrufe (Originale in `resources/mascot/originals/`):

| Original | Aufruf |
| --- | --- |
| `jubelt` | `--badge=first_session --crop=0,40,1024,1100 --fade=160` |
| `flamme` | `--badge=streak_7 --crop=0,40,1024,1160 --fade=110` |
| `krone` | `--badge=row_mastery --crop=0,90,1024,1110 --fade=110` |
| `blitz` | `--badge=blitz --crop=430,10,1070,900 --fade=110 --fadeleft=150` |
| `plus` | `--badge=addition_first_session --crop=0,10,1024,1200 --fade=100` |
| `medaille` | `--badge=group_mastery --crop=0,10,1024,1170 --fade=120` |
| `sprung` | `--badge=addition_blitz --crop=40,10,1470,970 --minarea=8000` |
| `jongliert` | `--badge=allrounder --crop=0,40,1024,910 --fade=100` |
| `winkt` | nur die Web-Fassung, kein Abzeichen |

Damit haben alle 18 Abzeichen ein eigenes oder ein geteiltes Bild.

Die Tests arbeiten in einem eigenen, wieder gelöschten Ordner (`config/badges.php`) und rühren die echten Bilder nie an.

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

## Continuous Integration

Bei jedem Push und jedem Pull Request gegen `main` läuft eine GitHub-Actions-Pipeline (`.github/workflows/ci.yml`):

- **Tests & Codestil:** `composer install`, `npm ci` und `npm run build` (der Frontend-Build ist Pflicht, ohne ihn schlagen alle Seiten mit `@vite(...)` fehl, siehe unten), `composer validate`, ein Syntax-Check aller PHP-Dateien, `vendor/bin/pint --test` und die ganze Testsuite (`php artisan test`). Danach probeweise `php artisan optimize`/`optimize:clear`, wie im Deployment. `composer audit`/`npm audit` laufen informativ mit (blockieren nichts).
- **Migrationen gegen MySQL:** Die Tests laufen gegen sqlite im Speicher, das Deployment aber gegen MySQL. Dieser Job führt alle Migrationen einmal gegen eine echte MySQL-Datenbank aus (inkl. Rollback und erneutem Migrieren), weil sqlite MySQL-spezifisches Verhalten nicht immer nachbildet — genau das war schon einmal die Ursache eines echten Bugs in diesem Projekt (`2026_09_17_225010_fix_practice_sessions_started_at_column.php`).

`public/build` ist bewusst nicht eingecheckt (`.gitignore`); die Pipeline baut das Frontend deshalb selbst, bevor die Tests laufen.

## Öffentliche Seiten

| Adresse | Inhalt |
| --- | --- |
| `/` | Startseite für Eltern: So funktioniert's, Funktionen, «Sicher für Kinder», FAQ, Weiter zu Registrierung und Anmeldung (angemeldete Eltern sehen «Zum Dashboard»). Responsiv von Handy bis Desktop. |
| `/impressum`, `/datenschutz` | Impressum und Datenschutzerklärung. Die Angaben stehen **nicht im Code**, sondern in der `.env` (`LEGAL_*`, siehe [INSTALL.md](INSTALL.md#rechtliches-und-kontaktformular)). Fehlt ein Pflichtwert, steht auf der Seite sichtbar «[LEGAL_NAME fehlt in der .env]». Die E-Mail-Adresse steht nicht als ein Text im Quelltext (Schutz vor Adress-Sammlern). |
| `/kontakt` | Kontaktformular für Fragen und Support. |

**Spam-Schutz** (Kontaktformular und Registrierung), bewusst ohne externen Dienst und ohne Rechenaufgabe für Menschen:

- ein für Menschen unsichtbares Falle-Feld (Honeypot), das Bots ausfüllen: Sie bekommen einen vorgetäuschten Erfolg, es wird nichts gespeichert oder gesendet,
- ein verschlüsselter Zeitstempel, der mit dem Formular ausgegeben wird: Zu schnelle oder gefälschte Absendungen werden abgelehnt (`app/Services/SpamGuard.php`),
- Begrenzung pro Stunde (Kontaktformular: 3 Nachrichten pro E-Mail-Adresse und 10 pro IP-Adresse; Registrierung: 10 pro IP-Adresse) und höchstens zwei Links pro Nachricht.

Das Kontaktformular sendet **keine** automatische Bestätigung an den Absender, so lässt es sich nicht als Spam-Schleuder gegen Dritte missbrauchen. Nachrichten werden in der Datenbank gespeichert und, falls `CONTACT_MAIL_TO` gesetzt ist, per E-Mail weitergeleitet (der Absender als Antwort-Adresse). Der Admin-Bereich hat dafür einen Reiter **Nachrichten** (offen/erledigt, Antworten, löschen). Erledigte Nachrichten werden nach 12 Monaten automatisch gelöscht.

**Registrierung:** Sie ist offen, aber die E-Mail-Adresse muss bestätigt werden, bevor der Eltern-Bereich nutzbar ist. Konten, die nicht innerhalb von 7 Tagen bestätigt werden, löscht ein täglicher Job samt der dadurch leeren Familie. Wer als letzte Elternperson das eigene Konto löscht, löscht auch die Familie, alle Kinder und ihre Übungsdaten.

**Suchmaschinen:** Startseite, Impressum, Datenschutz und Kontakt sind indexierbar. Persönliche Login-Links der Kinder, Kinder-, Eltern- und Admin-Bereich sind per `robots.txt` und `noindex` ausgeschlossen. Alle Schriften werden lokal ausgeliefert, die Seiten laden nichts von Dritten.

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

## Vorschau als Kind

Eltern (für ihre eigenen Kinder, Knopf auf der Seite «Kind bearbeiten») und Admins (für jedes Kind, Knopf in der Familienansicht) können in die Sicht eines Kindes wechseln, ohne dessen Magic-Link zu benutzen — praktisch für Support oder um eine neue Einstellung selbst auszuprobieren. Ein Banner oben («Vorschau als …») erinnert daran, «Vorschau beenden» führt zurück.

Eine Übung lässt sich dabei ganz normal starten, mit echten, an den tatsächlichen Lernstand angepassten Fragen und normaler Rückmeldung (inklusive Tipp, falls eingeschaltet) — **verändert aber nichts**: keine Punkte, kein Level, kein Eintrag im Lernstand pro Aufgabe (`ChildFactStat`), keine Abzeichen, kein Tagesziel. Technisch markiert `practice_sessions.is_preview` eine solche Session; alles, was sie schreibt, bleibt auf ihrer eigenen Zeile. Eine Ausnahme: Der Serien-Bonus bei den Punkten (ab der 5. richtigen Antwort in Folge) greift während einer Vorschau nie, weil er die vorherigen Antworten derselben Session braucht, die hier absichtlich nicht gespeichert werden — eine bewusst in Kauf genommene, kleine Einschränkung.

Eine Vorschau übernimmt niemals eine echte, laufende Session des Kindes (dafür gäbe es sonst ein «Weiter üben», das eigentlich dem Kind gehört) — es entsteht immer eine eigene, neue Session. In der Admin-Sessionliste bleiben Vorschau-Sessions sichtbar, aber mit «Vorschau» gekennzeichnet.

Es lässt sich immer nur eine Vorschau oder Admin-Impersonation gleichzeitig fahren; ein zweiter Versuch wird abgewiesen, statt zwei verschiedene Wege zurück verwalten zu müssen.

## Deployment (Plesk)

Eine ausführliche Schritt-für-Schritt-Anleitung für das Deployment auf einem Plesk-Server (Datenbank anlegen, `.env` einrichten, Admin-Zugang, Cronjob für den Laravel-Scheduler) befindet sich in [INSTALL.md](INSTALL.md). Updates spielt man mit `./deploy.sh` ein: Das Skript zieht den Code, installiert Abhängigkeiten, baut das Frontend, migriert die Datenbank und baut die Caches neu auf.

## Lizenz

Der Code ist unter der [MIT-Lizenz](LICENSE.md) freigegeben. Das schliesst keine personenbezogenen Daten ein – siehe Hinweis oben.
