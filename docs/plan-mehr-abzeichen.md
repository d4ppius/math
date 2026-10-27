# Plan: Mehr Abzeichen — Level und Wochentag-Tierfreunde

**Stand:** 30. September 2026 · **Status:** Level-Abzeichen und Wochentag-Tierfreunde umgesetzt (mit Emoji-Rückfall); Bilder: Igel (`weekday_1`), Hund (`weekday_2`), Pferd (`weekday_3`), Elefant (`weekday_6`) und Faultier (`weekday_7`) eingebaut, die anderen 2 Tiere (Katze, Hase) stehen noch aus.

## Ausgangslage

Ein Kind hat alle Abzeichen in kurzer Zeit gesammelt und möchte mehr sammeln. Zwei neue Abzeichen-Familien:

1. **Level-Abzeichen** — **✅ umgesetzt** (9 Stück, Level 2–10, verdient sobald das Kind das Level in irgendeiner Übung erreicht; trägt das Level-Emoji).
2. **Wochentag-Tierfreunde** — «Freunde vom Fuchs», je ein Tier pro Wochentag. Belohnt nebenbei das über die Woche verteilte Üben (spaced practice, siehe [`lernkonzept-plan.md`](lernkonzept-plan.md)) — anders als die bestehende 7‑Tage‑Serie, die aufeinanderfolgende Tage verlangt.

## Wochentag-Tierfreunde

- Verdient über den **Wochentag**: eine an diesem Wochentag abgeschlossene Session mit mindestens 5 Aufgaben schaltet den Freund dieses Tages frei. Alle 7 = an jedem Wochentag einmal geübt.
- Jedes Tier trägt zusätzlich ein **Mathe-Zeichen als Bild-Detail** (so wie der Fuchs sein «+» am Halstuch trägt). Das ist reine Gestaltung der Bilder, keine Spiel-Regel — man muss nicht mit dem Zeichen rechnen. Der Fuchs (+) bleibt das App-Maskottchen und ist kein Wochentag-Abzeichen.
- Schlüssel `weekday_1` … `weekday_7` (ISO: 1 = Montag … 7 = Sonntag), jedes ein eigenes Bild. Immer erreichbar (allgemein), unabhängig von Übung und Tempo-Bonus.

| Tag | Tier | Zeichen im Bild | Emoji-Rückfall | Bilddatei |
| --- | --- | --- | --- | --- |
| Montag | Igel | − | 🦔 | `public/images/badges/weekday_1.png` |
| Dienstag | Hund | × | 🐶 | `weekday_2.png` |
| Mittwoch | Pferd | ÷ | 🐴 | `weekday_3.png` |
| Donnerstag | Katze | = | 🐱 | `weekday_4.png` |
| Freitag | Hase | < | 🐰 | `weekday_5.png` |
| Samstag | Elefant | > | 🐘 | `weekday_6.png` |
| Sonntag | Faultier | ? | 🦥 | `weekday_7.png` |

- **Bildvorgabe** (wie die bestehenden Abzeichen): PNG, 512 × 512 Pixel, transparenter Hintergrund, Tier von Kopf bis Oberkörper. Genau nach obiger Spalte benennen und in `public/images/badges/` ablegen — dann erscheint das Bild ohne Code-Änderung. Marius liefert die Bilder; bis dahin zeigt jedes Abzeichen sein Tier-Emoji.

## Abzeichen-Seite übersichtlich halten

Der Sammel-Bereich «Allgemein» wächst (Meilensteine + 9 Level + 7 Tierfreunde). Er wird darum in Untergruppen geteilt (`BadgeSections`), Reihenfolge:

1. **Meilensteine** (Erste Übung, 7‑Tage‑Serie, Allrounder)
2. **Level** (die 9 Level-Abzeichen)
3. **Freunde vom Fuchs** (die 7 Wochentage)
4. … danach wie bisher ein Abschnitt je Übung (Einmaleins, Plus, Minus, Verdoppeln & Halbieren)

Überschriften erscheinen künftig, sobald es mehr als einen Abschnitt gibt (heute nur, wenn mehrere Übungen Abzeichen haben) — nötig, damit die neuen Untergruppen benannt sind.

## Schritte

### 1. Level-Abzeichen — ✅ umgesetzt

### 2. Wochentag-Tierfreunde (S–M) — ✅ umgesetzt
- `weekday` in `BadgeEvaluator`, 7 Einträge in `BadgeSeeder` (Namen/Emojis laut Tabelle).
- **Tests:** an einem Montag abgeschlossene Session (≥ 5 Aufgaben) vergibt nur den Montags-Freund; zu kurze Session vergibt nichts; für jedes Kind erreichbar; Vorschau-Sessions lösen nichts aus (laufen ohne das Session-Ende-Event, schon abgedeckt).

### 3. Abzeichen-Seite gruppieren (M) — ✅ umgesetzt
- `BadgeSections` teilt den allgemeinen Bereich in Meilensteine / Level / Freunde vom Fuchs; Überschriften-Logik in `badge-sections.blade.php` anpassen.
- **Tests:** die Untergruppen erscheinen mit Überschrift; bestehende Übungs-Abschnitte unverändert.

### 4. Bilder einbauen (S) — ⏳ wartet auf die Bilder
- Die von Marius gelieferten 7 Bilder nach `public/images/badges/weekday_1..7.png` legen (kein Code).

### 5. Dokumentation (S) — ✅ umgesetzt
- README und Anleitungsseite ergänzen (neue Abzeichen-Familie, Katalog-Zahl, Bildnamen); hartkodierte Abzeichen-Zahlen in den Tests aktualisieren.

## Risiken

- **Abzeichen-Zähler in Tests**: Wochentag-Abzeichen sind allgemein und für jedes Kind erreichbar, die «X von Y»-Zähler steigen also (wie schon bei den Level-Abzeichen). Betroffene Tests werden angepasst — Routinearbeit.
- Keine Migration, nur Seed-Daten; kein Datenrisiko für bestehende Kinder.
