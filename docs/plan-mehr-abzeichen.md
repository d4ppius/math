# Plan: Mehr Abzeichen — Level und Wochentag-Freunde

**Stand:** 29. September 2026 · **Status:** Entwurf, wartet auf Freigabe (und auf die 7 Tierfreund-Bilder).

## Ausgangslage

Ein Kind hat alle 27 Abzeichen in kurzer Zeit gesammelt und möchte mehr sammeln. Zwei neue Abzeichen-Familien kommen dazu:

1. **Level-Abzeichen** — ein Abzeichen pro Level, als steter Nachschub beim Fortschritt.
2. **Wochentag-Freunde** — «Freunde vom Fuchs», je ein Tier pro Wochentag. Belohnt nebenbei das über die Woche verteilte Üben (spaced practice, siehe [`lernkonzept-plan.md`](lernkonzept-plan.md)) — anders als die bestehende 7‑Tage‑Serie, die aufeinanderfolgende Tage verlangt.

Das Abzeichen-System trägt beide ohne Umbau: neue Einträge in `BadgeSeeder`, neue Regel-Typen in `BadgeEvaluator`. Bilder sind optional (Emoji-Rückfall).

## Entschieden (mit Marius abgestimmt, 29. September 2026)

1. **Level-Abzeichen:** übungsübergreifend, ein Abzeichen je Level 2 bis 10 (9 Stück). Verdient, sobald das Kind dieses Level in **irgendeiner** Übung erreicht.
2. **Wochentag-Freunde:** 7 Abzeichen, verschiedene Tiere. Marius liefert die 7 Bilder **zuerst**, dann baue ich sie fix ein.

## Design

### A. Level-Abzeichen (9 Stück, allgemein)

- Schlüssel `level_2` … `level_10`. Kein Übungsbezug (allgemein, immer erreichbar).
- Name = Level-Titel, Emoji = Level-Emoji (aus `LevelCalculator`): 🔍 Zahlen-Entdecker (2), 📘 Rechen-Lehrling (3), 🏃 Zahlen-Flitzer (4), ⭐ Rechen-Profi (5), 🧩 Knobel-Meister (6), 🪄 Zahlen-Zauberer (7), 🎯 Rechen-Ass (8), 🦸 Mathe-Held (9), 🦊 Rechenfuchs-Meister (10).
- Beschreibung: «Erreiche Level N in einer Übung.»
- Neuer Regel-Typ `level_reached` in `BadgeEvaluator`: beim Session-Ende erfüllt, wenn das höchste Level über alle Übungen des Kindes ≥ N ist. (Bestehende Kinder holen die passenden Level-Abzeichen so bei der nächsten Session rückwirkend nach.)
- **Kein Bild nötig** — das Level-Emoji genügt (später optional durch Bilder ersetzbar).

### B. Wochentag-Freunde (7 Stück, allgemein)

- Schlüssel `weekday_1` … `weekday_7` (ISO: 1 = Montag … 7 = Sonntag). Jedes ein eigenes Tier, eigenes Bild (kein geteiltes Bild).
- Neuer Regel-Typ `weekday` in `BadgeEvaluator`: erfüllt, wenn eine abgeschlossene Session an diesem Wochentag mindestens 5 Aufgaben hatte. Alle 7 = an jedem Wochentag einmal geübt.
- Immer erreichbar (allgemein), unabhängig von Übung und Tempo-Bonus.
- **Vorschlag Tier ↔ Wochentag** (alle «Waldfreunde» des Fuchses — bitte anpassen, damit deine Bilder dazu passen):

  | Tag | Abzeichen-Name | Emoji-Rückfall | Bilddatei |
  | --- | --- | --- | --- |
  | Montag | Igel | 🦔 | `public/images/badges/weekday_1.png` |
  | Dienstag | Hase | 🐰 | `weekday_2.png` |
  | Mittwoch | Eule | 🦉 | `weekday_3.png` |
  | Donnerstag | Bär | 🐻 | `weekday_4.png` |
  | Freitag | Eichhörnchen | 🐿️ | `weekday_5.png` |
  | Samstag | Waschbär | 🦝 | `weekday_6.png` |
  | Sonntag | Reh | 🦌 | `weekday_7.png` |

- **Bildvorgabe** (wie die bestehenden Abzeichen): PNG, 512 × 512 Pixel, transparenter Hintergrund, Tier von Kopf bis Oberkörper (damit es auch als kleine Medaille erkennbar ist). Die Datei genau nach obiger Spalte benennen und in `public/images/badges/` ablegen — dann erscheint sie ohne Code-Änderung.

### C. Abzeichen-Seite übersichtlich halten

Mit 43 Abzeichen (27 + 9 + 7) wird der bisherige Sammel-Bereich «Allgemein» gross. Darum wird er in Untergruppen geteilt (`BadgeSections`), Reihenfolge:

1. **Meilensteine** (Erste Übung, 7‑Tage‑Serie, Allrounder)
2. **Level** (die 9 Level-Abzeichen)
3. **Freunde vom Fuchs** (die 7 Wochentage)
4. … danach wie bisher ein Abschnitt je Übung (Einmaleins, Plus, Minus, Verdoppeln & Halbieren)

Die Überschriften werden künftig gezeigt, sobald es mehr als einen Abschnitt gibt (heute nur, wenn mehrere Übungen Abzeichen haben) — nötig, damit die neuen Untergruppen benannt sind.

## Schritte

### 1. Level-Abzeichen (S–M)
- `level_reached` in `BadgeEvaluator`, 9 Einträge in `BadgeSeeder`.
- **Tests:** Level-Abzeichen wird beim Erreichen des Levels in einer Übung vergeben, auch rückwirkend mehrere auf einmal; nicht vergeben, solange das Level nicht erreicht ist; ist für jedes Kind erreichbar (kein Übungsbezug).

### 2. Wochentag-Freunde (S–M)
- `weekday` in `BadgeEvaluator`, 7 Einträge in `BadgeSeeder` (Namen/Emojis laut Tabelle).
- **Tests:** an einem Montag abgeschlossene Session (≥ 5 Aufgaben) vergibt nur den Montags-Freund; zu kurze Session vergibt nichts; Vorschau-Sessions lösen nichts aus (laufen ohne das Session-Ende-Event, schon abgedeckt).

### 3. Abzeichen-Seite gruppieren (M)
- `BadgeSections` teilt den allgemeinen Bereich in Meilensteine / Level / Freunde vom Fuchs; Überschriften-Logik in `badge-sections.blade.php` anpassen.
- **Tests:** die drei Untergruppen erscheinen mit Überschrift; bestehende Übungs-Abschnitte unverändert; Zähler «X von Y» stimmt mit der grösseren Gesamtzahl.

### 4. Bilder einbauen (S)
- Die von Marius gelieferten 7 Bilder nach `public/images/badges/weekday_1..7.png` legen (kein Code).

### 5. Dokumentation (S)
- README: Abzeichen-Abschnitt (neue Familien, Katalog 27 → 43, neue Bildnamen) und die Anleitungsseite (`/anleitung`) ergänzen; die hartkodierten Abzeichen-Zahlen in den Tests aktualisieren.

## Reihenfolge und Aufwand

1 → 2 → 3 → 4 → 5. Aufwand grob: S–M + S–M + M + S + S. Reine Erweiterung, keine Migration (nur Seed-Daten), kein Datenrisiko für bestehende Kinder.

## Risiken

- **Abzeichen-Zähler in Tests**: Level- und Wochentag-Abzeichen sind allgemein und damit für jedes Kind erreichbar, d. h. die «X von Y»-Zähler steigen deutlich (z. B. von «0 von 12» auf «0 von 28» für ein Kind mit nur Einmaleins). Mehrere Tests mit fixen Zahlen sind anzupassen — erwartete Routinearbeit, kein inhaltliches Risiko.
- **Menge**: 43 Abzeichen sind viel; die Untergruppen (Schritt 3) halten die Seite lesbar.

## Offene Entscheidungen

1. **Tier ↔ Wochentag und Namen** (Tabelle oben): passt die Zuordnung, oder möchtest du andere Tiere/Namen? Davon hängen deine Bilder und die Emoji-Rückfälle ab.
2. **Reihenfolge der Umsetzung:** Soll ich die Level-Abzeichen (brauchen keine Bilder) schon jetzt bauen und die Wochentag-Freunde nachziehen, sobald deine 7 Bilder da sind — oder alles zusammen, wenn die Bilder vorliegen?
