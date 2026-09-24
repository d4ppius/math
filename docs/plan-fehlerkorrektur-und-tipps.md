# Plan: Fehlerkorrektur zum Nachtippen und Rechentipps

**Stand:** 25. September 2026 · **Status:** Genehmigt, wird umgesetzt.

Konkretisiert und ersetzt Paket B ("Fehlerkorrektur und Nachholen") und Paket C1 ("Tipps nach Fehlern") aus `docs/lernkonzept-plan.md`, jetzt mit zusätzlichen Quellen und für beide Übungen (nicht nur Einmaleins).

## Ausgangslage

Heute zeigt `resources/views/child/practice.blade.php` nach jeder Antwort 1,4 Sekunden Feedback und springt automatisch weiter — bei einer richtigen Antwort reicht das, bei einer falschen ist es zu knapp, um sich die richtige Lösung wirklich einzuprägen. Es gibt auch keinen Hinweis, wie man auf die Lösung kommt.

**Was die Forschung dazu sagt:**

| Erkenntnis | Beleg |
| --- | --- |
| Aktives Nachschreiben/-tippen der richtigen Lösung nach einem Fehler ("Cover-Copy-Compare") verbessert die Fakten-Fluency messbar, in mehreren Studien und einem systematischen Review, gerade beim Einmaleins. | [Systematic Review](https://perrjournal.com/index.php/perrjournal/article/view/204), [Cressey & Ezbicki 2008](https://charts.intensiveintervention.org/intervention/ToolView/?id=366b7f5b64db87d2), [mit Fehler-Drill kombiniert](https://eric.ed.gov/?id=EJ869200) |
| Sofortiges Feedback schlägt verzögertes deutlich (u. a. ~44 % mehr richtige Folgeantworten) — spricht für sofortiges, aber nicht für hastiges Feedback. | [Überblick](https://funexpectedapps.com/en/blog-posts/math-learning-strategies-proven-to-work-interleaving-immediate-feedback-spaced-repetition), [Wing Institute](https://www.winginstitute.org/instructional-delivery-feedback) |
| Der Worked-Example-Effect (Sweller): ein vorgerechneter Lösungsweg hilft besonders am Anfang des Lernens deutlich mehr als reines Ausprobieren. | [Wikipedia-Überblick mit Quellen](https://en.wikipedia.org/wiki/Worked-example_effect) |

**Einordnung:** Wie beim übrigen Lernkonzept betreffen die Studien primär Grundschulkinder in den USA und vor allem rechenschwache Kinder; die Grössenordnung des Effekts lässt sich nicht direkt auf Rechenfuchs übertragen, die Richtung aber schon.

## Entschieden (dieses Gespräch, 25. September 2026)

1. **Kein Auto-Weiterspringen mehr nach einer falschen Antwort.** Das Kind sieht die richtige Lösung und tippt sie selbst ein (Cover-Copy-Compare-Prinzip), bevor es zur nächsten Aufgabe geht.
2. **Tipps für beide Übungen**, nicht nur Einmaleins: Jeder Übungstyp bekommt eigene Rechenweg-Regeln.
3. **Der Session-Timer läuft während der Korrektur normal weiter**, wird nicht pausiert. Begründung: Der eigentliche Zeitdruck (Tempo-Bonus pro Frage) endet ohnehin schon mit dem Abschicken der falschen Antwort; eine echte Pause bräuchte neue Serverlogik und wäre ein Fehlanreiz ("falsch antworten, um eine Pause vom Timer zu bekommen"). Bei 10 Minuten Session fallen die paar Sekunden Korrektur pro Fehler kaum ins Gewicht, und wem der Timer generell zu stressig ist, kann ihn schon heute ausblenden.

## Was gleich bleibt

- Sofortiges Feedback direkt nach dem Absenden (richtig/falsch, in Punkten) — nur das automatische Weiterspringen danach ändert sich, und nur bei falschen Antworten.
- Die Punkte-Berechnung: eine falsche Antwort gibt weiterhin 0 Punkte, unabhängig davon, wie lange die Korrektur dauert.
- Bei einer **richtigen** Antwort ändert sich nichts: weiterhin 1,4 Sekunden Feedback, dann automatisch weiter.
- Der Session-Timer-Mechanismus selbst (serverseitig, aus `started_at` + `planned_duration_seconds` berechnet, siehe `PracticeSessionController::timeRemainingSeconds()`).
- Die adaptive Auswahl der nächsten Aufgabe (`WeightedFactSelector`) — kein Nachholen falsch beantworteter Aufgaben in dieser Iteration (das war Paket B's zweiter Teil, bleibt zurückgestellt).

## Design

### 1. Fehlerkorrektur zum Nachtippen

- Nach einer falschen Antwort bleibt der Feedback-Block wie heute sichtbar ("🤔 Fast! Antwort war: 56"), aber die Seite springt nicht mehr automatisch weiter.
- Die Zifferntastatur bleibt aktiv. Das Kind tippt die richtige Zahl selbst ein.
- Stimmt die eingetippte Zahl, geht es sofort automatisch weiter (wie der bestehende `fetchNext()`-Ablauf) — kein zusätzlicher Tap nötig.
- Das Nachtippen wird **nicht an den Server geschickt**: Der Server hat die Lösung mit der Fehler-Antwort schon geliefert, ein weiterer `SessionAttempt`-Datensatz wäre nicht sinnvoll. Reine Client-Logik in `practice.blade.php`.
- Bleibt das Kind zu lange hängen (Vorschlag: nach 2 falschen Nachtipp-Versuchen), erscheint ein „Weiter"-Knopf, damit niemand stecken bleibt.

### 2. Rechentipps

- Neue Methode `hint(Fact $fact): array` im `ExerciseTypeContract`, liefert die Lösung als Kette nachvollziehbarer Schritte (Zahlen, nicht nur Text), damit sich jeder Tipp automatisiert auf Korrektheit prüfen lässt, z. B. für 6 × 7: `['6 × 7 = 5 × 7 + 1 × 7', '5 × 7 = 35', '1 × 7 = 7', '35 + 7 = 42']`.
- Angezeigt wird der Tipp direkt neben der Lösung im Feedback-Block, z. B. „💡 Tipp: 5 × 7 + 1 × 7 = 35 + 7 = 42".

**Regeln Einmaleins** (aus Paket C, unverändert):

| Reihe | Strategie |
| --- | --- |
| ×1 | bleibt gleich |
| ×2 | verdoppeln |
| ×10 | Null anhängen |
| ×5 | Hälfte der 10er-Aufgabe |
| ×4 | zweimal verdoppeln |
| ×3 | 2er-Aufgabe + 1er-Aufgabe |
| ×6 | 5er-Aufgabe + 1er-Aufgabe |
| ×9 | 10er-Aufgabe − 1er-Aufgabe |
| ×7, ×8 | aus einer bekannten Nachbaraufgabe ableiten |

**Regeln Plus bis 20** (neu, an den bestehenden Aufgabenarten `AdditionExerciseType::groupFor()` orientiert, Standard-Rechenwege der Schweizer Unterstufe):

| Fall | Beispiel | Strategie |
| --- | --- | --- |
| Verdoppeln (a = b), unabhängig von der Aufgabenart | 6 + 6 | „Verdoppeln: 6 + 6 = 12" |
| Aufgabenart „Plus mit der 10" | 10 + 6 | „Mit der 10 einfach dranhängen: 10 + 6 = 16" |
| Aufgabenart „Zehnerübergang" | 8 + 5 | „Zuerst zur 10 ergänzen: 8 + 2 = 10, dann + 3 = 13" |
| Aufgabenart „Plus bis 10", sonst | 3 + 4 | „Vom grösseren Summanden aus weiterzählen: 4, dann 5, 6, 7" |

Die Zehnerübergang-Regel zerlegt den kleineren Summanden in „Rest bis 10" + „übrig": bei 8 + 5 ist 8 der grössere, 10 − 8 = 2, also 5 = 2 + 3, Ergebnis 10 + 3 = 13.

- Schalter `show_hints` (**Standard aus**) pro Kind und Übung, analog zu `sound_enabled`/`show_timer`/`speed_bonus_enabled`: Migration für eine neue Spalte in `child_exercise_settings`, Auswahlfeld im Eltern-Formular. Hinweistext im Formular: „Prüfe, ob dein Kind einen anderen Rechenweg aus der Schule kennt — passt der Tipp nicht dazu, kann er hier verwirren."

## Schritte

### 1. Fehlerkorrektur zum Nachtippen (M)

- Alpine-Logik in `practice.blade.php`: kein Auto-Advance bei `!feedback.is_correct`, Zifferntastatur bleibt nutzbar, Vergleich mit `feedback.correct_answer` rein im Client, „Weiter"-Knopf nach 2 Fehlversuchen.
- **Tests:** Die Logik ist reines Client-JS ohne Server-Rundtrip, lässt sich also nicht sinnvoll mit PHPUnit-Feature-Tests abdecken. Feature-Test nur dafür, dass die Antwort-Route sich nicht ändert (`points_awarded: 0`, `correct_answer` weiterhin in der Response). Der eigentliche Ablauf wird im Browser geprüft.

### 2. `hint()` für Einmaleins (S)

- Vertragsmethode + Implementierung in `MultiplicationExerciseType` nach der Tabelle oben.
- **Tests:** Für alle 90 Aufgaben rechnerisch geprüft (jeder Schritt der zurückgegebenen Kette muss stimmen, die Summe/das Produkt der letzten Schritte muss `correct_answer` ergeben) — kein Stichprobentest.

### 3. `hint()` für Plus bis 20 (M)

- Implementierung in `AdditionExerciseType` nach der Tabelle oben.
- **Tests:** Für alle 100 Aufgaben rechnerisch geprüft, wie bei Schritt 2.

### 4. Tipp-Anzeige und Schalter (S)

- `PracticeSessionController::attempt()` liefert bei einer falschen Antwort zusätzlich `hint`.
- `practice.blade.php` zeigt „💡 Tipp: …" im Feedback-Block, nur wenn `show_hints` an ist.
- Migration: neue Spalte `show_hints` (boolean, Standard `false`) in `child_exercise_settings`. Auswahlfeld im Eltern-Formular mit dem Hinweistext oben.
- **Tests:** Tipp erscheint bei falscher Antwort und aktivem Schalter, bleibt weg wenn ausgeschaltet oder die Antwort richtig war.

### 5. Dokumentation (S)

- README: neuer Abschnitt neben „Punkte"/„Level und Abzeichen", der die Fehlerkorrektur und die Tipp-Regeln (inkl. beider Tabellen oben) erklärt.
- `docs/lernkonzept-plan.md`: Paket B und C1 als „umgesetzt, siehe `plan-fehlerkorrektur-und-tipps.md`" markieren.

## Reihenfolge und Aufwand

1 → (2, 3 parallel möglich) → 4 → 5. Aufwand grob: M + S + M + S + S, insgesamt etwa **3 bis 4 Arbeitstage**.

## Risiken

- **Kein Nachholen falsch beantworteter Aufgaben** (das war Paket B's zweiter Teil): Eine falsch beantwortete Aufgabe kann in derselben Session direkt danach nochmal drankommen (Zufall), aber es gibt keine gezielte Wiederholung nach 3–4 Fragen. Separates, späteres Paket, falls gewünscht.
- **Tipp-Formulierungen** sind pädagogisch gängige Standard-Rechenwege, aber nicht mit einem konkreten Lehrmittel abgeglichen — falsche/fremde Begriffe könnten mehr verwirren als helfen. Deshalb Schalter standardmässig aus (siehe Entscheidung 3).
- **JS-lastige Änderung** in Schritt 1 lässt sich nur eingeschränkt automatisiert testen, Browser-Check bleibt nötig (wie schon bei den früheren Timer- und Sound-Änderungen in diesem Projekt).

## Entscheidungen (mit Marius abgestimmt, 25. September 2026)

1. **Rechenwege Plus:** Nicht mit der Schule abgeglichen, sondern vorerst nach pädagogisch sinnvollen Standard-Strategien umgesetzt (Tabelle oben) — genau deshalb Schalter 3 standardmässig aus.
2. **Fehlversuche beim Nachtippen:** 2, wie vorgeschlagen.
3. **`show_hints`-Schalter:** Standard **aus**. Hinweistext im Eltern-Formular bittet die Eltern zu prüfen, ob der Tipp ihr Kind eher verwirrt (siehe Design-Abschnitt oben).
