# Plan: Zweite Übung «Plus bis 20» (Phase 6.1)

**Stand:** 21. September 2026 · **Status:** umgesetzt (Schritte 1 bis 8). Abweichungen zum Entwurf: Die Registrierung des Typs und die Erinnerungs-Anpassung (Schritt 7) sind schon in Schritt 2 erfolgt, damit Eltern nie eine halb fertige Karte sehen und ausgeschaltete Übungen keinen Erinnerungstag auslösen.

Rechenfuchs bekommt einen zweiten Aufgabentyp. Eltern schalten pro Kind frei, welche Übungen es gibt. Sind mehrere freigeschaltet, wählt das Kind auf seiner Startseite. Auch für Plus gibt es Abzeichen zum Sammeln.

Aufwand grob: **S** = bis 1 Tag, **M** = 2 bis 3 Tage. Jeder Schritt wird einzeln umgesetzt, getestet und committet. Insgesamt sind es etwa **8 bis 10 Arbeitstage**.

## Entscheidungen (geklärt)

| Frage | Entscheid |
| --- | --- |
| Umfang | **Einspluseins:** Summanden 1 bis 10, also 100 Aufgaben mit Summen bis 20. Zehner plus Einer (12 + 5) sind nicht dabei. |
| Standard | Plus ist **aus**. Bei neuen und bestehenden Kindern bleibt nur Einmaleins aktiv, bis die Eltern Plus freischalten. |
| Punkte und Level | **Gemeinsam:** ein Konto und ein Level pro Kind. Die Zielzeiten bei Plus sind knapper. |
| Abzeichen | Etwa **5 bis 6** neue Abzeichen (siehe unten). |

## Ausgangslage

Die Architektur ist schon für weitere Übungen gebaut: `ExerciseTypeContract`, `ExerciseTypeRegistry`, ein generischer `ExerciseTypeSeeder`, eine generische Auswahl (`WeightedFactSelector`), generische Punkte, Einstellungen pro Übung (Dauer, Timer, Tempo-Bonus, Töne) und der Admin-Umschalter `is_active`. Fest auf Einmaleins verdrahtet sind nur:

- `PracticeSessionController::start()`: `ExerciseType::where('key', 'multiplication')`
- `StatisticsController::show()`: dieselbe feste Abfrage, die Heatmap gilt nur für Einmaleins
- `BadgeSeeder`: die Reihen-Meister-Abzeichen tragen `exercise_type: multiplication`
- `SendPracticeReminders`: prüft alle Einstellungen, auch nicht freigeschaltete
- Texte mit «Einmaleins»: Push-Erinnerung, Tagesziel-Mail, Startseite, Manifest, README
- Die Einstellungsseite nimmt die Gruppen aus `gridDefinition()['rows']` und nennt sie «Reihen». Das passt nur zu Einmaleins.

## Die Aufgaben

100 Aufgaben `a + b` mit a, b von 1 bis 10. `difficulty_group` unterscheidet nach Schwierigkeit statt nach Reihe:

| Gruppe | Bezeichnung | Regel | Anzahl |
| --- | --- | --- | --- |
| 1 | Plus bis 10 | a + b ≤ 10 | 45 |
| 2 | Plus mit der 10 | a = 10 oder b = 10 (Summe ab 11) | 19 |
| 3 | Zehnerübergang | a, b ≤ 9 und Summe ab 11 | 36 |

Zielzeiten: Gruppe 1 und 2 je 2,0 s, Gruppe 3 je 3,5 s. Die Heatmap ist ein 10 × 10-Gitter über die Summanden (Zeilen a, Spalten b), wie sie die Statistik heute schon über `operand_a` und `operand_b` aufbaut. Startgruppe für neue Freischaltungen: 1.

## Datenmodell

- **`child_exercise_settings.enabled`** (boolean, Standard `true`). Bestehende Zeilen sind alle Einmaleins und bleiben aktiv. Für Plus legt der Provisioner die Zeile mit `enabled = false` an. Eine fehlende Zeile bedeutet «nicht freigeschaltet», deshalb braucht es keinen Nachtrag für bestehende Kinder.
- **Erweiterung von `ExerciseTypeContract`:**
  - `groups(): array` mit den Gruppen und ihren Bezeichnungen (statt der Zeilen aus `gridDefinition()`),
  - `groupsLabel(): string` («Reihen» bei Einmaleins, «Aufgabenarten» bei Plus),
  - `enabledByDefault(): bool`,
  - optional `badges(): array`: jeder Übungstyp liefert seinen Abzeichen-Katalog, der `BadgeSeeder` durchläuft die Registry.
- Keine neuen Tabellen. Punkte, Level, Tagesziel und Statistik bleiben gemeinsam.

## Schritte

### 1. Aufgabentyp «Plus» (S bis M)
- Neue Klasse `AdditionExerciseType` mit Schlüssel `addition`, Bezeichnung «Plus bis 20», `formatPrompt()` = `7 + 8`, Zielzeiten, Gitter und Gruppen. Registrierung in `config/exercise_types.php`.
- Der Seeder legt Übungstyp und 100 Aufgaben an (mehrfach ausführbar).
- **Tests:** genau 100 Aufgaben, jede Summe stimmt und liegt bei höchstens 20, jede Aufgabe gehört zu genau einer Gruppe, die Gruppengrössen sind 45 / 19 / 36.

### 2. Freischalten durch die Eltern (M)
- Migration `enabled`. Vertrags-Methoden `groups()`, `groupsLabel()`, `enabledByDefault()`.
- Die Einstellungsseite bekommt pro Übung einen Schalter «Für Mia freischalten». Gruppen werden mit ihrer Bezeichnung gezeigt. Ist die Übung aus, sind die übrigen Felder der Karte zugeklappt.
- Validierung: Mindestens eine Übung muss aktiv bleiben, und jede aktive Übung braucht mindestens eine aktive Gruppe.
- Der `ExerciseSettingsProvisioner` legt fehlende Zeilen mit dem Standard an (`enabledByDefault()`).
- **Tests:** Standard aus für Plus, Speichern und Validierung, nur die eigene Familie und der Admin dürfen ändern.

### 3. Auswahl durch das Kind (M)
- `POST /kind/sessions` nimmt den Übungsschlüssel entgegen. Der Server prüft, dass die Übung aktiv ist (`is_active`), dass sie für das Kind freigeschaltet ist und dass eine Einstellung existiert. Ohne Schlüssel und bei genau einer Übung gilt das heutige Verhalten. Das schützt auch bereits geöffnete Seiten.
- Startseite: Bei genau einer Übung bleibt es der eine grosse Knopf. Bei mehreren gibt es eine Karte pro Übung mit Name und Emoji, und mit «Weiter», wenn dort noch eine Session läuft.
- Eine laufende Session pro Übung bleibt erlaubt. Fortgesetzt wird jeweils in der gewählten Übung.
- Die Zusammenfassung nennt die Übung.
- **Tests:** Start nur für freigeschaltete und aktive Übungen, Fortsetzen pro Übung, die Rückfallregel ohne Schlüssel. Viele bestehende Tests rufen die Start-Route auf (12 Testdateien erwähnen «multiplication») und müssen angepasst werden.

### 4. Eltern-Statistik pro Übung (M)
- `StatisticsController` läuft über die freigeschalteten Übungen. Die Heatmap kommt aus `gridDefinition()` und `formatPrompt()`. Jede Übung bekommt einen eigenen Abschnitt (bei mehreren mit Reitern).
- Level, Punkte, Wochenverlauf und Tagesziel bleiben gemeinsam.
- **Tests:** eine und zwei Übungen, Plus ohne Freischaltung erscheint nicht.

### 5. Abzeichen für Plus (M)

| Schlüssel | Name | Regel |
| --- | --- | --- |
| `addition_first_session` | Plus-Starter | erste Plus-Übung mit mindestens 5 Aufgaben |
| `addition_mastery_1` | Meister von Plus bis 10 | Gruppe zu über 90 % richtig |
| `addition_mastery_2` | Meister von Plus mit der 10 | wie oben |
| `addition_mastery_3` | Meister des Zehnerübergangs | wie oben |
| `addition_blitz` | Plus-Blitz | 10 richtige Antworten, im Schnitt unter 1,5 s |
| `allrounder` | Allrounder | am selben Tag beide Übungen geübt |

Damit alles sauber zusammenspielt:
- **`first_session`** und **`blitz`** bekommen einen `exercise_type`-Filter. Der bestehende «Blitzrechner» wird auf Einmaleins beschränkt, sonst würde Plus ihn viel zu leicht auslösen. Bereits verdiente Abzeichen bleiben.
- **Gruppen-Meister:** `row_mastery` (Einmaleins) bleibt. Für Plus kommt ein allgemeiner Typ `group_mastery`. Das Schild auf dem Bild zeigt bei Einmaleins die Reihen-Nummer, bei Plus eine kurze Marke aus den Kriterien.
- **Erreichbarkeit:** `Badge::isAttainableBy()` verlangt, dass die Übung des Abzeichens für das Kind freigeschaltet ist, beim Allrounder mindestens zwei Übungen. So sehen Kinder ohne Plus keine gesperrten Plus-Abzeichen, und die Zähler («x von N») stimmen.
- **Anzeige:** Die Erfolgsseite und die Eltern-Statistik gruppieren die Abzeichen nach Übung («Einmaleins», «Plus», «Allgemein»).
- **Bilder:** Ohne Bilder zeigt die Medaille das Emoji. Für Fuchs-Posen liefere ich Prompts, sobald der Katalog steht.
- **Tests:** jede Regel, Filter, Erreichbarkeit, Zähler, Gruppierung.

### 6. Texte (S)
- Startseite, Meta-Beschreibung, README, Manifest und Fusszeile: «Einmaleins und Plus bis 20».
- Push-Erinnerung: «Lust auf ein paar Runden?», ohne Übungsnamen. Tagesziel-Mail: «die heutige Übung».
- Die Startseiten-Bilder zeigen weiter nur Einmaleins. Das ist in Ordnung, kann später aufgefrischt werden.

### 7. Erinnerungen (S)
- `SendPracticeReminders::isPracticeDayFor()` beachtet nur freigeschaltete Einstellungen.
- Das Tagesziel bleibt: Jede Session mit mindestens 10 beantworteten Aufgaben zählt, egal in welcher Übung.

### 8. Abschluss (S)
- Doku (README-Abschnitt zu den Übungen und den Abzeichen, INSTALL), Deployment-Hinweise, alle Tests, Browser-Check auf Handy und Tablet.

**Reihenfolge:** 1 → 2 → 3 → 4 → 5 → 6 und 7 → 8. Schritt 5 setzt 2 voraus (Erreichbarkeit).

## Deployment

- Eine Migration: die Spalte `enabled`.
- `php artisan db:seed` legt die Plus-Aufgaben und die Plus-Abzeichen an. Das Deployment-Skript macht das ohnehin.
- Bestehende Kinder sehen nach dem Update keine Änderung, bis Eltern Plus freischalten.

## Risiken

- **Punkte-Balance:** Plus-Aufgaben gehen schneller, also gibt es mehr Punkte pro Minute. Die Grundpunkte hängen nicht von der Zielzeit ab. Nach den ersten echten Sessions prüfen und bei Bedarf einen kleinen Faktor pro Übung (`pointsMultiplier()`, Standard 1,0) im Vertrag ergänzen.
- **Bestehende Tests:** Viele gehen von genau einer Übung aus und brauchen Anpassungen.
- **Zähler und Texte:** Abzeichen-Zähler hängen künftig von den freigeschalteten Übungen ab. Es lohnt sich, sie gründlich zu testen.
- **Schule abgleichen:** Im Zahlenraum 20 lehren Schulen oft feste Wege (zuerst zur 10 ergänzen, Verdoppeln, Nachbaraufgaben). Die Gruppen und späteren Tipps (siehe Lernkonzept-Plan, Paket C) sollten dazu passen.

## Nicht Teil dieses Plans

- Minus. Die Architektur bereitet es vor: Nach demselben Muster entsteht ein weiterer Aufgabentyp.
- Zehner plus Einer (12 + 5) und Zahlen über 20.
- Eigene Level oder Punkte pro Übung.
