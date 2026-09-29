# Plan: Minus bis 20 und Verdoppeln & Halbieren

**Stand:** 29. September 2026 · **Status:** Genehmigt, wird umgesetzt.

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
- **Abzeichen** (Details unten): `subtraction_first_session`, `subtraction_mastery_1/2/3`, `subtraction_blitz`. Ob das Blitz-Abzeichen verdient werden kann, steuert wie gehabt der **Tempo-Bonus-Schalter**, den Eltern pro Übung ein-/ausschalten (ist er aus, kein Blitz — keine neue Einstellung nötig). `allrounder` zählt automatisch mit, sobald zwei Übungen aktiv sind.

## Übung B: Verdoppeln & Halbieren

Neuer Typ mit zwei Aufgabenarten. Pädagogisch die Brücke von der Addition zur Multiplikation (Verdoppeln = ×2, Halbieren als Umkehrung; ×5 ist die Hälfte von ×10).

- **Schlüssel / Name:** `doubling` / «Verdoppeln & Halbieren». Operator für die Heatmap-Beschriftung z. B. «·». Standardmässig aus. Emoji-Vorschlag 🔁 (änderbar).
- **Datenmodell-Kniff:** Das `Fact` hat zwei Operanden; diese Aufgaben sind einstellig. Lösung: `operand_b` kodiert die Operation, damit der Schlüssel `(operand_a, operand_b)` nie kollidiert:
  - Verdoppeln: `(a, 2)`, Ergebnis `a × 2`, Aufgabenart 1.
  - Halbieren: `(a, 0)`, Ergebnis `a ÷ 2`, Aufgabenart 2.
  - `formatPrompt` liest `operand_b`: `2` → «Doppelte von {a}», `0` → «Hälfte von {a}».
- **Aufgabenarten (Gruppen) = Operation × Zahlenraum, von den Eltern wählbar** (wie bei Plus haken Eltern im Formular an, was aktiv ist; der Zahlenraum ist damit von den Eltern gesteuert, ohne neue Einstellung — Entscheidung 2):
  | Gruppe | Aufgaben |
  | --- | --- |
  | Verdoppeln bis 10 | `a` = 1…10 → Ergebnisse 2…20 |
  | Verdoppeln bis 50 | `a` = 11…25 → Ergebnisse 22…50 |
  | Halbieren bis 20 | gerade `a` = 2…20 → Ergebnisse 1…10 |
  | Halbieren bis 50 | gerade `a` = 22…50 → Ergebnisse 11…25 |
  - Standardmässig aktiv: nur «Verdoppeln bis 10». Die übrigen schalten Eltern frei, wenn das Kind bereit ist.
  - `generateFacts` erzeugt die Aufgaben direkt pro Gruppe (kein `groupFor` nötig).
- **Zielzeit:** 2500 ms (schneller Abruf).
- **Rechentipps:**
  - Verdoppeln(7) → `["7 + 7 = 14"]` (Verdoppeln *ist* fortgesetzte Addition — genau der Lehrplan-Gedanke).
  - Halbieren(16) → `["16 ÷ 2 = 8"]` (endet beim Ergebnis; `÷` wird vom Hint-Test bereits unterstützt und teilt hier immer glatt, weil nur gerade Zahlen halbiert werden).
- **Heatmap:** Das bestehende Raster ist für zwei Operanden gebaut. Für diese Übung braucht `gridDefinition` eine angepasste Form (Vorschlag: Zeilen = Zahlen, zwei Spalten «Verdoppeln»/«Halbieren», leere Zellen bei ungeraden Zahlen in der Halbieren-Spalte). Details bei der Umsetzung; kein Blocker, aber der einzige Punkt, der über «Plus abschreiben» hinausgeht.
- **Abzeichen:** `doubling_first_session`, ein Meister-Abzeichen **pro Operation** (`doubling_mastery` mit Marke «·2», `halving_mastery` mit Marke «½») und `doubling_blitz` (parent-gesteuert über den Tempo-Bonus, wie oben). Weil es je Operation zwei Zahlenraum-Gruppen gibt, prüft das Meister-Abzeichen die Beherrschung über **beide** Gruppen einer Operation — dafür eine kleine Erweiterung des `group_mastery`-Kriteriums, das statt einer einzelnen Gruppe eine Liste von Gruppen akzeptiert. So bleiben es zwei Meister-Abzeichen statt vier.

## Abzeichen: Was nötig ist, was optional

Das Abzeichen-System zeigt ein Bild `public/images/badges/{key}.png`, sonst ein geteiltes `{criteria.type}.png`, sonst das Emoji — **ohne Code-Änderung** austauschbar. Für die neuen Abzeichen heisst das:

**Entschieden (3): geteilte Bilder verwenden, keine eigenen jetzt.** Alle neuen Abzeichen greifen auf bestehende geteilte Fuchs-Bilder zurück: `first_session.png` (Starter), `group_mastery.png` (Meister-Abzeichen, mit aufgesetzter Marke wie «10»/«½»), `blitz.png` (Blitz). Das funktioniert und sieht stimmig aus, sobald `BadgeSeeder` läuft — es wird also im Rahmen dieser Umsetzung **kein** Bild erstellt.

Eigene Fuchs-Bilder pro Abzeichen (wie bei Plus) bleiben jederzeit später per Drag-and-drop nachlegbar, ohne Code-Änderung (`public/images/badges/{key}.png`) — falls das später gewünscht ist.

## Schritte

Jeder Schritt ein eigener Commit, mit voller Testsuite + Pint davor.

### 1. Übung «Minus bis 20» (M)
- `SubtractionExerciseType` (nach Vorlage `AdditionExerciseType`), Registrierung in `config/exercise_types.php`, `db:seed` seedet die Aufgaben.
- **Tests:** `generateFacts` erzeugt nur nicht-negative Ergebnisse im richtigen Bereich; jede der 110 Aufgaben ist der richtigen Gruppe zugeordnet (Grenzfälle Zehnerübergang); `hint()` für alle Aufgaben mechanisch korrekt (Erweiterung von `ExerciseTypeHintTest`); eine Minus-Session vergibt Punkte und schreibt Statistik wie Plus.

### 2. Abzeichen für Minus (S)
- Einträge in `BadgeSeeder` (Starter, drei Meister-Abzeichen mit Marken 10/−10/20, Blitz).
- **Tests:** Meister-Abzeichen wird bei ≥ 90 % über genug Aufgaben vergeben; Blitz nur bei aktivem Tempo-Bonus (bestehende Regel); nur sichtbar/erreichbar wenn Minus freigeschaltet ist; `allrounder` bezieht Minus als zweite Übung ein.

### 3. Übung «Verdoppeln & Halbieren» (M)
- `DoublingExerciseType` (vier Gruppen: Verdoppeln/Halbieren × zwei Zahlenräume), Registrierung, Seed. Angepasste `gridDefinition` für die Heatmap.
- **Tests:** Verdoppeln- und Halbieren-Aufgaben kollidieren nicht im Schlüssel; Halbieren nur für gerade Zahlen; jede Aufgabe der richtigen Gruppe/Zahlenraum zugeordnet; `formatPrompt` zeigt «Doppelte von …» / «Hälfte von …»; `hint()` mechanisch korrekt (inkl. `÷`); Heatmap der Eltern-Statistik rendert ohne Fehler.

### 4. Abzeichen für Verdoppeln & Halbieren, plus Mastery-Erweiterung (S–M)
- `group_mastery`-Kriterium um eine Liste von Gruppen erweitern (rückwärtskompatibel: eine einzelne Gruppe bleibt gültig). Starter + zwei Meister-Abzeichen je Operation (Marken «·2» / «½», je über beide Zahlenraum-Gruppen) + Blitz.
- **Tests:** die bestehenden `group_mastery`-Abzeichen (Plus) funktionieren unverändert; das Operations-Meister-Abzeichen wird erst bei Beherrschung über beide Zahlenraum-Gruppen vergeben; Blitz an den Tempo-Bonus gekoppelt.

### 5. Dokumentation (S)
- README: die zwei neuen Übungen in der Übungs-Tabelle und im Punkte/Abzeichen-Abschnitt; die Anleitungsseite (`/anleitung`) um die neuen Übungen ergänzen.
- `docs/lehrplan21-abgleich.md`: die zwei Lücken als «umgesetzt» markieren.

## Reihenfolge und Aufwand

1 → 2 → 3 → 4 → 5. Aufwand grob: M + S + M + S + S. Übung A ist reine Spiegelung von Plus; Übung B hat einen echten Neuteil (einstellige Aufgaben im Zwei-Operanden-Modell + Heatmap).

## Risiken

- **Heatmap für Verdoppeln/Halbieren** ist der einzige Punkt, der nicht 1:1 von Plus abgeschrieben werden kann. Falls die angepasste `gridDefinition` unschön wird, ist eine schlichtere Darstellung (Liste statt Raster) eine Rückfalloption — nicht blockierend.
- **Aufgabenmenge Verdoppeln/Halbieren**: bei zu grossem Zahlenraum wird die adaptive Auswahl dünn. Die vorgeschlagenen Bereiche (Verdoppeln bis 50, Halbieren gerader Zahlen bis 50) sind bewusst begrenzt.
- Keine Migration nötig (nur neue Seed-Daten), also kein Datenrisiko für bestehende Kinder.

## Entscheidungen (mit Marius abgestimmt, 29. September 2026)

1. **Blitz-Abzeichen:** für beide neuen Übungen dabei. Ob es verdient werden kann, entscheiden die Eltern über den bestehenden Tempo-Bonus-Schalter pro Übung (aus = kein Blitz) — keine neue Einstellung.
2. **Zahlenräume Verdoppeln/Halbieren:** von den Eltern wählbar, umgesetzt als Aufgabenarten (Verdoppeln bis 10 / bis 50, Halbieren bis 20 / bis 50), Standard nur «Verdoppeln bis 10».
3. **Bilder:** geteilte Fuchs-Bilder verwenden, keine eigenen im Rahmen dieser Umsetzung. Eigene später jederzeit nachlegbar.
4. **Reihenfolge:** beide Übungen zusammen (Schritte 1–5 in einem Zug).
