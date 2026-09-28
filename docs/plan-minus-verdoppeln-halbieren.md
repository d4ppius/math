# Plan: Minus bis 20 und Verdoppeln & Halbieren

**Stand:** 29. September 2026 · **Status:** Entwurf, wartet auf Freigabe.

Setzt die beiden höchstpriorisierten Lücken aus [`docs/lehrplan21-abgleich.md`](lehrplan21-abgleich.md) um: **Subtraktion bis 20** (Einsminuseins, Kern-Grundanspruch Zyklus 1) und **Verdoppeln & Halbieren** (eigene Zyklus-1-Fertigkeit und Brücke zwischen Plus und Einmaleins).

## Was die bestehende Architektur schon trägt

Beide Übungen sind neue `ExerciseTypeContract`-Implementierungen plus ein Eintrag in `config/exercise_types.php`. Ohne Änderung am Session-Ablauf bekommen sie automatisch: Freischalten pro Kind (standardmässig aus), eigene Punkte/Level/Fortschrittsbalken pro Übung, adaptive Fragenauswahl, Cover-Copy-Compare (Nachtippen bei Fehlern), optionale Rechentipps und den `ExerciseTypeSeeder` (seedet die Aufgaben aus `generateFacts()`). Es sind also fast nur je eine Klasse, Abzeichen-Einträge und Tests nötig.

## Übung A: Minus bis 20

Direkte Spiegelung der bestehenden `AdditionExerciseType`.

- **Schlüssel / Name:** `subtraction` / «Minus bis 20». Operator «−», standardmässig aus.
- **Aufgaben (`generateFacts`):** Minuend `a`, Subtrahend `b`, Ergebnis `a − b`. Bereich so gewählt, dass Subtrahend **und** Ergebnis je ≤ 10 bleiben (genau die Spiegelung von «Plus bis 20» mit zwei Summanden ≤ 10): für jedes Ergebnis `r` = 0…10 und jeden Subtrahenden `b` = 1…10 ist der Minuend `r + b` (2…20). Ergibt 110 Aufgaben, vergleichbar mit den 100 von Plus. Keine negativen Ergebnisse (nicht Zyklus 1).
- **Aufgabenarten (Gruppen), gestuft wie bei Plus:**
  | Gruppe | Bedingung | Beispiel |
  | --- | --- | --- |
  | Minus bis 10 | Minuend ≤ 10 | 9 − 4 |
  | Minus mit der 10 | Minuend > 10, aber kein Übergang nötig (Zehner bleibt ganz), oder die 10 als Landmarke | 17 − 4, 10 − 6 |
  | Zehnerübergang | Minuend > 10, Ergebnis < 10, Übergang nötig (Einer des Minuenden < Subtrahend) | 13 − 8 |
- **Zielzeiten (`targetResponseMs`):** 2000 ms für die beiden leichten Gruppen, 3500 ms für den Zehnerübergang (analog Plus).
- **Rechentipps (`hint`), die im Lehrplan genannten halbschriftlichen Strategien, jede Kette endet exakt beim Ergebnis** (damit der mechanische `ExerciseTypeHintTest` wie bei Plus/Einmaleins jede Aufgabe prüfen kann):
  - Ohne Übergang: direkt, z. B. 17 − 4 → `["17 − 4 = 13"]`.
  - Mit Übergang, «Zehner zuerst / schrittweises Abziehen»: 13 − 8 → `["13 − 3 = 10", "10 − 5 = 5"]` (erst auf die 10 hinunter, dann den Rest). Endet beim Ergebnis 5.
  - Standardmässig aus (wie bei Plus), Hinweis für Eltern bleibt gleich.
- **Heatmap:** funktioniert unverändert (Minuend × Subtrahend als Raster, wie Plus).
- **Abzeichen** (Details unten): `subtraction_first_session`, `subtraction_mastery_1/2/3`, optional `subtraction_blitz`. `allrounder` zählt automatisch mit, sobald zwei Übungen aktiv sind.

## Übung B: Verdoppeln & Halbieren

Neuer Typ mit zwei Aufgabenarten. Pädagogisch die Brücke von der Addition zur Multiplikation (Verdoppeln = ×2, Halbieren als Umkehrung; ×5 ist die Hälfte von ×10).

- **Schlüssel / Name:** `doubling` / «Verdoppeln & Halbieren». Operator für die Heatmap-Beschriftung z. B. «·». Standardmässig aus. Emoji-Vorschlag 🔁 (änderbar).
- **Datenmodell-Kniff:** Das `Fact` hat zwei Operanden; diese Aufgaben sind einstellig. Lösung: `operand_b` kodiert die Operation, damit der Schlüssel `(operand_a, operand_b)` nie kollidiert:
  - Verdoppeln: `(a, 2)`, Ergebnis `a × 2`, Aufgabenart 1.
  - Halbieren: `(a, 0)`, Ergebnis `a ÷ 2`, Aufgabenart 2.
  - `formatPrompt` liest `operand_b`: `2` → «Doppelte von {a}», `0` → «Hälfte von {a}».
- **Aufgaben (`generateFacts`):**
  - Verdoppeln: `a` = 1…25 → Ergebnisse 2…50 («Verdoppeln bis 50»).
  - Halbieren: nur gerade `a` = 2, 4, … 50 → Ergebnisse 1…25.
  - (Genaue Obergrenzen siehe offene Entscheidung 3.)
- **Zielzeit:** 2500 ms (schneller Abruf).
- **Rechentipps:**
  - Verdoppeln(7) → `["7 + 7 = 14"]` (Verdoppeln *ist* fortgesetzte Addition — genau der Lehrplan-Gedanke).
  - Halbieren(16) → `["16 ÷ 2 = 8"]` (endet beim Ergebnis; `÷` wird vom Hint-Test bereits unterstützt und teilt hier immer glatt, weil nur gerade Zahlen halbiert werden).
- **Heatmap:** Das bestehende Raster ist für zwei Operanden gebaut. Für diese Übung braucht `gridDefinition` eine angepasste Form (Vorschlag: Zeilen = Zahlen, zwei Spalten «Verdoppeln»/«Halbieren», leere Zellen bei ungeraden Zahlen in der Halbieren-Spalte). Details bei der Umsetzung; kein Blocker, aber der einzige Punkt, der über «Plus abschreiben» hinausgeht.
- **Abzeichen:** `doubling_first_session`, `doubling_mastery_1` (Verdoppeln, Marke «·2»), `doubling_mastery_2` (Halbieren, Marke «½»), optional `doubling_blitz`.

## Abzeichen: Was nötig ist, was optional

Das Abzeichen-System zeigt ein Bild `public/images/badges/{key}.png`, sonst ein geteiltes `{criteria.type}.png`, sonst das Emoji — **ohne Code-Änderung** austauschbar. Für die neuen Abzeichen heisst das:

- **Zwingend nötig: nichts.** Alle neuen Abzeichen greifen auf bestehende geteilte Fuchs-Bilder zurück: `first_session.png` (Starter), `group_mastery.png` (Meister-Abzeichen, mit aufgesetzter Marke wie «10»/«½»), `blitz.png` (Blitz). Es funktioniert und sieht stimmig aus, sobald `BadgeSeeder` läuft.
- **Optional («nice to have»), für denselben Feinschliff wie bei Plus** — eigene Fuchs-Bilder, jederzeit später per Drag-and-drop nachlegbar, kein Code nötig. Falls gewünscht, wären das diese Dateien:
  - `subtraction_first_session.png` (Minus-Starter)
  - `subtraction_blitz.png` (Minus-Blitz, falls das Blitz-Abzeichen kommt)
  - `doubling_first_session.png` (Verdoppeln/Halbieren-Starter)
  - `doubling_blitz.png` (falls Blitz kommt)
  - Die Meister-Abzeichen brauchen **kein** eigenes Bild: `group_mastery.png` plus Marke reicht (so ist es bei Plus auch).

Kurz: **Du musst keine Bilder liefern, damit ich loslegen kann.** Wenn du sie später generierst (wie bei den anderen), lege ich sie ohne weiteren Aufwand ein.

## Schritte

Jeder Schritt ein eigener Commit, mit voller Testsuite + Pint davor.

### 1. Übung «Minus bis 20» (M)
- `SubtractionExerciseType` (nach Vorlage `AdditionExerciseType`), Registrierung in `config/exercise_types.php`, `db:seed` seedet die Aufgaben.
- **Tests:** `generateFacts` erzeugt nur nicht-negative Ergebnisse im richtigen Bereich; jede der 110 Aufgaben ist der richtigen Gruppe zugeordnet (Grenzfälle Zehnerübergang); `hint()` für alle Aufgaben mechanisch korrekt (Erweiterung von `ExerciseTypeHintTest`); eine Minus-Session vergibt Punkte und schreibt Statistik wie Plus.

### 2. Abzeichen für Minus (S)
- Einträge in `BadgeSeeder` (Starter, drei Meister-Abzeichen mit Marken 10/−10/20, optional Blitz).
- **Tests:** Meister-Abzeichen wird bei ≥ 90 % über genug Aufgaben vergeben; nur sichtbar/erreichbar wenn Minus freigeschaltet ist; `allrounder` bezieht Minus als zweite Übung ein.

### 3. Übung «Verdoppeln & Halbieren» (M)
- `DoublingExerciseType`, Registrierung, Seed. Angepasste `gridDefinition` für die Heatmap.
- **Tests:** Verdoppeln- und Halbieren-Aufgaben kollidieren nicht im Schlüssel; Halbieren nur für gerade Zahlen; `formatPrompt` zeigt «Doppelte von …» / «Hälfte von …»; `hint()` mechanisch korrekt (inkl. `÷`); Heatmap der Eltern-Statistik rendert ohne Fehler.

### 4. Abzeichen für Verdoppeln & Halbieren (S)
- Starter + zwei Meister-Abzeichen (Marken «·2» / «½»), optional Blitz.
- **Tests:** wie Schritt 2.

### 5. Dokumentation (S)
- README: die zwei neuen Übungen in der Übungs-Tabelle und im Punkte/Abzeichen-Abschnitt; die Anleitungsseite (`/anleitung`) um die neuen Übungen ergänzen.
- `docs/lehrplan21-abgleich.md`: die zwei Lücken als «umgesetzt» markieren.

## Reihenfolge und Aufwand

1 → 2 → 3 → 4 → 5. Aufwand grob: M + S + M + S + S. Übung A ist reine Spiegelung von Plus; Übung B hat einen echten Neuteil (einstellige Aufgaben im Zwei-Operanden-Modell + Heatmap).

## Risiken

- **Heatmap für Verdoppeln/Halbieren** ist der einzige Punkt, der nicht 1:1 von Plus abgeschrieben werden kann. Falls die angepasste `gridDefinition` unschön wird, ist eine schlichtere Darstellung (Liste statt Raster) eine Rückfalloption — nicht blockierend.
- **Aufgabenmenge Verdoppeln/Halbieren**: bei zu grossem Zahlenraum wird die adaptive Auswahl dünn. Die vorgeschlagenen Bereiche (Verdoppeln bis 50, Halbieren gerader Zahlen bis 50) sind bewusst begrenzt.
- Keine Migration nötig (nur neue Seed-Daten), also kein Datenrisiko für bestehende Kinder.

## Offene Entscheidungen

1. **Blitz-Abzeichen** für die neuen Übungen: ja (Parität mit Plus/Einmaleins) oder weglassen, um die Abzeichen-Zahl klein zu halten? Vorschlag: ja, für Parität.
2. **Eigene Fuchs-Bilder** für die neuen Starter/Blitz-Abzeichen jetzt gewünscht oder erst mal die geteilten Bilder? Vorschlag: mit geteilten starten, eigene später optional.
3. **Zahlenräume Verdoppeln/Halbieren:** Verdoppeln bis 50 (a = 1…25) und Halbieren gerader Zahlen bis 50 — passt das, oder lieber kleiner (bis 20/40) beginnen?
4. **Reihenfolge zur Freigabe:** beide Übungen zusammen ausliefern, oder Minus zuerst allein (klar grösste Lücke) und Verdoppeln/Halbieren als zweiter Schritt?
