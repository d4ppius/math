# Plan: Statistik pro Runde und Verlauf (Eltern)

**Stand:** 12. Oktober 2026 · **Status:** umgesetzt (Schritte 1 bis 5).

## Ausgangslage und Ziel

Die Eltern-Statistik zeigt heute die Punkte der letzten 7 Tage, das Tagesziel, die Abzeichen und die Heatmap pro Übung. Punkte allein sagen aber wenig darüber, **ob das Kind besser wird**: Tempo-Bonus, Serien-Multiplikator und Rundenlänge verzerren sie. Gewünscht ist:

1. Pro Runde sehen, wie viele Aufgaben **richtig und falsch** waren.
2. Das Ganze als **Wochen- und Monatsgrafik**, um zu erkennen, ob das Kind in der gleichen Zeit mehr Aufgaben schafft, also schneller wird.

Nur für Eltern (Kindersicht bleibt unverändert, «Meine Erfolge» wird nicht erweitert).

## Welche Daten es schon gibt

Keine Migration nötig:

- `practice_sessions`: `questions_answered`, `questions_correct`, `started_at`, `status`, `is_preview`, `exercise_type_id`
- `session_attempts`: pro Antwort `is_correct`, `response_time_ms`, `answered_at`

**Falsch** = `questions_answered − questions_correct`.

## Das Mass für «schneller»: richtige Aufgaben pro Minute

- **Tempo = richtige Antworten ÷ Minuten zwischen Start und letzter Antwort** (`started_at` bis `MAX(answered_at)` der Session).
- Nicht `ended_at`: Bei automatisch geschlossenen Sessions (`sessions:close-expired`) ist das der Zeitpunkt des Cron-Laufs, nicht das Ende des Übens.
- Nicht die geplante Dauer: Bricht das Kind früher ab, stimmt sie nicht.
- Sessions mit **weniger als 5 beantworteten Aufgaben** fliessen nicht ins Tempo ein (zu wenig Aussagekraft), zählen aber bei richtig/falsch mit.
- Weil Einmaleins, Plus & Co. verschieden schnell sind, wird das Tempo **pro Übung** gezeigt («Alle» mischt nur die Anzahl, nicht das Tempo, siehe unten).
- Berücksichtigt werden nur abgeschlossene Sessions, keine Vorschau-Sessions (`is_preview`).

## Was die Eltern sehen

Neue Karten auf der Statistik-Seite des Kindes, direkt unter «Punkte diese Woche»:

### 1. Aufgaben pro Tag (richtig / falsch)
- Gestapelte Balken je Tag: unten grün (richtig), oben rot (falsch). Über dem Balken die Gesamtzahl.
- Umschalter **Woche** (7 Tage) / **Monat** (30 Tage) und Auswahl **Alle / eine Übung**.
- An Tagen mit mehreren Runden wird summiert.

### 2. Tempo (Aufgaben pro Minute)
- Liniendiagramm über denselben Zeitraum, ein Punkt pro Tag mit Tempo-Wert (Tage ohne gültige Session bleiben Lücken).
- Dazu ein Satz zum Trend: «Ø 7,4 richtige pro Minute, +1,2 gegenüber der Vorwoche» (Woche: letzte 7 gegen die 7 davor, Monat: letzte 30 gegen die 30 davor; ohne Vergleichsdaten entfällt der Vergleich).
- Bei **«Alle»** wird das Tempo nicht gemischt, sondern der Hinweis «Wähle eine Übung» gezeigt, sonst würde Plus (schnell) das Einmaleins (langsamer) verfälschen. Hat das Kind nur eine aktive Übung, ist sie vorgewählt.

### 3. Letzte Runden
- Tabelle der **letzten 10 Runden** (gefiltert wie oben): Datum und Uhrzeit, Übung, Aufgaben, richtig, falsch, Trefferquote in %, Tempo.
- Auf dem Handy als gestapelte Zeilen, nicht als breite Tabelle.

## Technik

- **`StatisticsController`**: neue Methode liefert die Tagesreihen der letzten 60 Tage (für den Vorwochen-/Vormonatsvergleich), je Übung und gesamt. Eine Abfrage für die Sessions mit `withMax('attempts as last_answered_at', 'answered_at')`, Gruppieren in PHP. Kein N+1.
- **Neuer Service** `app/Services/Statistics/SessionTrends.php`: reine Rechenlogik (Tagesreihen, Tempo, Trend), unabhängig vom Controller und gut testbar.
- **Darstellung ohne Bibliothek:** Balken in HTML/CSS (wie «Punkte diese Woche»), Linie als Inline-SVG. Die Reihen werden als JSON an Alpine übergeben, das Umschalten Woche/Monat und Übung geschieht im Browser, ohne Neuladen.
- Neue Blade-Komponente `components/session-trends` hält die drei Karten, damit `statistics.blade.php` nicht weiter wächst.
- Tage werden in der App-Zeitzone gezählt, wie bei den Wochenpunkten.

## Schritte

1. **`SessionTrends`-Service** mit Tests: richtig/falsch pro Tag, Tempo, Mindestgrenze 5 Aufgaben, Vorschau/aktive Sessions ausgeschlossen, Trendvergleich, Filter pro Übung.
2. **Controller und Karte «Aufgaben pro Tag»** (Balken, Woche/Monat, Übungsfilter).
3. **Karte «Tempo»** (Linie, Trend-Satz, Hinweis bei «Alle»).
4. **Karte «Letzte Runden»**.
5. **Dokumentation:** README (Abschnitt Statistik), Anleitungsseite `/anleitung` (ein Satz für Eltern, was Tempo bedeutet) und `GuideTest`; Plan-Status.

Jeder Schritt wird einzeln committet; vor jedem Commit laufen alle Tests und Pint. Geschätzt: ein halber bis ein Tag.

## Risiken und Grenzen

- **Aussagekraft:** Tempo schwankt von Tag zu Tag (Tagesform, Schwierigkeit der Aufgaben). Der Trend-Satz vergleicht darum ganze Wochen bzw. Monate, nicht einzelne Tage. Wer Aufgaben aus einer höheren Schwierigkeitsgruppe freischaltet, sieht eventuell vorübergehend ein tieferes Tempo, auch wenn das Kind besser wird. Das erklärt ein kurzer Hinweis unter der Grafik.
- **Wenig Daten:** Neue Kinder oder seltenes Üben ergeben Lücken. Ohne Sessions zeigt die Karte «Noch keine Runden in diesem Zeitraum».
- Keine Migration, keine Änderung an Punkten, Abzeichen oder Kindersicht; kein Datenrisiko.
