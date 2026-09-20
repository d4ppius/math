# Plan: Lernkonzept verbessern (Einmaleins)

**Stand:** 20. September 2026 · **Status:** Entwurf, noch nichts umgesetzt

Dieses Dokument hält fest, was die Lernforschung zum Einmaleins sagt, wie Rechenfuchs heute damit umgeht und welche Verbesserungen sich lohnen würden. Es ist bewusst in unabhängige Pakete gegliedert, damit man später gezielt einzelne Punkte beauftragen kann.

Aufwand grob: **S** = bis 1 Tag, **M** = 2 bis 3 Tage, **L** = mehr, jeweils inklusive Tests und Browser-Check. Jedes Paket soll einzeln umgesetzt und committet werden.

---

## 1. Was die Forschung sagt

| Erkenntnis | Belege | Belastbarkeit |
| --- | --- | --- |
| Übungsprogramme für Grundaufgaben wirken deutlich (g = 0,76). Mit 30 und mehr Einheiten mehr als mit weniger als 10. | [Meta-Analyse, 35 Studien](https://pmc.ncbi.nlm.nih.gov/articles/PMC13069136/) | Gut, aber nur für Kinder mit Rechenschwierigkeiten |
| Strategien plus Tempo-Übung schlagen reines Tempo-Üben. | [Woodward 2006](https://eric.ed.gov/?id=EJ786218), 58 Viertklässler | Mittel, eine Studie |
| Über Tage verteiltes Üben hilft (g = 0,28). Der beste Abstand hängt davon ab, wie lange man sich etwas merken will. | [Murray et al. 2025](https://eric.ed.gov/?id=EJ1478558), [Cepeda et al. 2006](https://www.yorku.ca/ncepeda/publications/CPVWR2006.html) | Gut, aber kleiner Effekt |
| Abfragen statt nochmal Anschauen verbessert die Flüssigkeit beim Einmaleins. Für Mathe allgemein ist der Effekt nicht sicher (g = 0,18, Unsicherheitsbereich reicht bis unter 0). | [Ophuis-Cox 2023](https://onlinelibrary.wiley.com/doi/10.1002/acp.4141), Murray et al. 2025 | Gemischt |
| Neue Aufgaben in kleinen Portionen zwischen bekannte mischen (etwa 90 % bekannt, 10 % neu). | [Inkrementelles Wiederholen](https://pmc.ncbi.nlm.nih.gov/articles/PMC9333052/) | Mittel, kleine Studien |
| Erst zählen, dann Ableiten, dann Automatisieren. Kernaufgaben zuerst, davon die übrigen ableiten, Reihenlernen später. | [Baroody, zusammengefasst](https://www.mathfactlab.com/number-facts), [Gaidoschik](https://www.friedrich-verlag.de/shop/einmaleins-verstehen-vernetzen-merken-14802) | Didaktisch breit akzeptiert, hier nur aus Zusammenfassungen und Verlagstexten |
| Erwartete Belohnungen können die Eigenmotivation senken (d ≈ −0,3 bis −0,4, bei Kindern eher stärker). | [Deci et al. 1999](https://www.selfdeterminationtheory.org/SDT/documents/2001_DeciKoestnerRyan.pdf) | Gut, aber für Sachbelohnungen, nicht direkt für Spielpunkte |
| Prüfungsangst trifft vor allem Kinder mit gutem Arbeitsgedächtnis. Dass Zeitdruck sie auslöst, ist nicht bewiesen. | [Ramirez et al. 2013](https://sites.temple.edu/cognitionlearning/files/2013/09/Ramirez-et-al-2013.pdf) | Nur Zusammenhänge |
| Adaptive Programme wie Rekentuin zielen auf etwa 75 % richtige Antworten. Effekt: etwas mehr Selbstvertrauen, mehr Leistung nur bei viel Üben. | [Studie mit 376 Fünftklässlern](https://pmc.ncbi.nlm.nih.gov/articles/PMC10299571/), [Klinkenberg et al.](https://www.sciencedirect.com/science/article/abs/pii/S0360131511000418) | Mittel |

**Einordnung:** Viele Studien betreffen Kinder mit Rechenschwierigkeiten oder kleine Gruppen, oft aus den USA. Die Übertragbarkeit auf normale Kinder in der Schweiz ist begrenzt. Übungsprogramme verbessern vor allem die Flüssigkeit bei Grundaufgaben, weniger das Lösen von Textaufgaben. Einige Volltexte waren nicht zugänglich (Ophuis-Cox, Murray, Gaidoschik), dort stützt sich die Übersicht auf Abstracts und Zusammenfassungen.

## 2. Wie die App heute funktioniert

- **Aufgaben:** 90 Aufgaben (Reihen 1 bis 9 × Spalten 1 bis 10). 7×8 und 8×7 sind getrennte Aufgaben. Standard-Reihen für neue Kinder: 1 und 2.
- **Auswahl** (`WeightedFactSelector`, `FactPriorityCalculator`): gewichtet zufällig innerhalb der aktiven Reihen. Gewicht = 0,5 × Fehlerquote + 0,3 × Tempo-Strafe + 0,2 × Aktualität (wächst bis 7 Tage) + 0,05. Ungeübte Aufgaben bekommen das höchste Gewicht (1,05). Die letzten 3 Aufgaben werden nicht wiederholt.
- **Zielzeiten** (`targetResponseMs`): 2,5 s für Reihen 1 und 2, 3,5 s für 3 bis 5, 4,5 s ab 6.
- **Fehler:** Die App zeigt 1,4 Sekunden lang „Fast! Antwort war …“ und geht weiter. Es gibt keinen Tipp, kein Nachtippen und keine gezielte Wiederholung.
- **Statistik:** Die Heatmap wertet nur die Trefferquote (ab 85 % „sicher“, ab 60 % „okay“, ab 35 % „schwierig“, sonst „sehr schwierig“). `child_fact_stats` speichert auch die durchschnittliche Antwortzeit, nutzt sie für die Farben aber nicht.
- **Motivation:** Punkte mit Tempo-Bonus, zehn Level, Abzeichen, 7-Tage-Serie, Konfetti und Töne. Timer, Tempo-Bonus, Töne und die Anzeige nicht verdienter Abzeichen sind pro Kind abschaltbar.

## 3. Überblick über die Pakete

| Paket | Nutzen | Aufwand | Braucht vorher |
| --- | --- | --- | --- |
| 0 Lernstand einer Aufgabe | Grundlage für fast alles | S | – |
| A Neue Aufgaben portionsweise | grosser Effekt bei wenig Aufwand | M | 0 |
| B Fehlerkorrektur und Nachholen | mittel bis gross | M | – |
| C Strategie-Tipps | grösste inhaltliche Lücke | M, dann S | – |
| D Empfehlungen für Eltern | viel Nutzen im Alltag | M | 0 |
| E Abstände über Tage | kleiner bis mittlerer Effekt | M bis L | A, 0 |
| F Flüssigkeit und Tauschaufgaben | Statistik wird ehrlicher | S bis M | 0 |
| G Motivation und Voreinstellungen | weniger Druck, mehr Fortschritt | S bis M | 0, teils E |
| H Blitz-Check, neue Aufgabenformen | optional | M bis L | – |

**Empfohlene Reihenfolge:** 0 → A → D → B → C → F → G → E → H. Mit 0, A und D hat man in etwa fünf bis sechs Arbeitstagen die Änderungen mit dem besten Verhältnis aus Wirkung und Aufwand.

---

## Paket 0: Lernstand einer Aufgabe (Fundament)

**Ziel:** Jede Aufgabe eines Kindes gehört zu einem von vier Zuständen.

| Zustand | Bedingung |
| --- | --- |
| neu | noch nie gefragt |
| in Arbeit | weniger als 3 Versuche oder unter 85 % richtig |
| richtig, aber langsam | mindestens 85 % richtig, mindestens 3 Versuche, durchschnittlich über 1,25 × Zielzeit |
| sitzt | alles andere |

**Umsetzung:**
- Neuer Dienst `FactMastery` in `app/Services/AdaptiveSelection/`. Er nutzt `targetResponseMs()` des Übungstyps.
- `ChildFactStat::masteryStatus()` (bisher nur Trefferquote) delegiert an den Dienst.
- Keine Migration.

**Tests:** Grenzfälle der Schwellen.

## Paket A: Neue Aufgaben portionsweise

**Problem:** Ungeübte Aufgaben haben das höchste Gewicht. Bei den Standard-Reihen 1 und 2 kommen alle 20 neuen Aufgaben sofort dran.

**Plan:**
- Ein Arbeitsstapel aus höchstens N Aufgaben in Arbeit oder langsam, dazu bekannte Aufgaben.
- Eine neue Aufgabe wird erst freigeschaltet, wenn im Stapel ein Platz frei ist. Innerhalb einer Reihe geht es von leicht nach schwer (×1, ×10, ×2, ×5, dann ×3, ×4 und so weiter).
- Gibt es genug bekannte Aufgaben, kommt etwa jede zweite bis dritte Frage aus dem Arbeitsstapel, der Rest aus Bekanntem. Die Werte sind einstellbar.

**Technik:**
- Änderungen in `WeightedFactSelector` und `FactPriorityCalculator`.
- Neue Methode `introductionOrder()` im `ExerciseTypeContract`.
- Neue Spalte `new_facts_at_a_time` (Standard 4) in `child_exercise_settings` und ein Auswahlfeld „Einführungstempo“ im Eltern-Formular.

**Tests:** Zufallsgenerator mit festem Startwert. Es dürfen nie mehr als N unbekannte Aufgaben im Stapel liegen. Rückfallregel bei sehr kleinem Vorrat.

**Messgrösse:** Trefferquote in den ersten drei Sessions über 75 %.

## Paket B: Fehlerkorrektur und Nachholen

**Oberfläche** (`resources/views/child/practice.blade.php`):
- Nach einer falschen Antwort geht die Seite nicht mehr nach 1,4 Sekunden weiter.
- Das Kind sieht „Richtig wäre 56“ und tippt die Lösung selbst ein. Nach zwei falschen Versuchen erscheint „Weiter“.
- Das Nachtippen wird nicht gespeichert, der Server hat die Lösung schon geliefert.

**Nachholen im Selektor:**
- Eine falsch beantwortete Aufgabe kommt nach 3 bis 4 anderen Fragen wieder.
- Das lässt sich aus `session_attempts` berechnen, ohne Migration.
- Höchstens zwei Wiederholungen pro Aufgabe und Session, damit keine Endlosschleifen entstehen.

**Optional:** Schalter `retype_wrong_answers` in den Übungseinstellungen.

**Tests:** Selektor-Test für das Nachholen. Die Oberfläche wird im Browser geprüft.

## Paket C: Strategie-Tipps

**C1, Tipps nach Fehlern**
- Neue Methode `hint(Fact)` im `ExerciseTypeContract`. Sie liefert strukturierte Schritte, zum Beispiel für 6 × 7: „5 × 7 + 1 × 7 = 35 + 7“.
- Regeln: ×1 bleibt gleich, ×2 verdoppeln, ×10 Null anhängen, ×5 Hälfte der 10er, ×4 zweimal verdoppeln, ×3 2er plus 1er, ×6 5er plus 1er, ×9 10er minus 1er, ×7 und ×8 aus bekannten Nachbaraufgaben.
- `PracticeSessionController::attempt()` liefert bei einem Fehler zusätzlich `hint`, die Ansicht zeigt „💡 Tipp“.
- Neuer Schalter `show_hints` (Standard an). Hinweis für Eltern: Wenn die Schule andere Wege lehrt, ausschalten.
- **Test:** Für alle 90 Aufgaben muss jeder Tipp rechnerisch stimmen. Darum liefert er Zahlen und nicht nur Text.

**C2, Punktefeld**
- Komponente `dot-array` zeigt a × b als Feld, ab 6 in einem 5er-Block plus Rest. Reines CSS, kein JavaScript. Erscheint neben dem Tipp.

**C3, „Ableiten üben“ (später)**
- Aufgaben wie „6 × 7 = 5 × 7 + ?“. Braucht eine neue Fragevariante, eine Spalte `variant` in `session_attempts` und Änderungen im Vertrag. Erst nach Rückmeldung zu C1 und C2.

**Vorher klären:** Welche Rechenwege lehrt die Schule, und wie heissen sie im Lehrmittel? Widersprüchliche Wege verwirren die Kinder.

## Paket D: Empfehlungen für Eltern

**Dienst `LearningAdvisor`** mit reinen Regeln auf Basis von Paket 0:

| Situation | Empfehlung |
| --- | --- |
| Eine Reihe ist zu mindestens 80 % „sitzt“ | Nächste Reihe dazunehmen |
| 40 oder mehr Versuche in vier Sessions und unter 30 % „sitzt“ | Sachlicher Hinweis, mit der Lehrperson oder einer Fachperson zu sprechen (keine Diagnose) |
| Unter drei Sessions in der Woche | Lieber kurz und öfter üben |
| Mindestens 90 % richtig ohne Tempo-Bonus | „Bereit für Tempo?“ |

Vorgeschlagene Reihenfolge der Reihen (mit der Schule abgleichen): 1, 2, 5, 4, 3, 6, 9, 8, 7.

**Oberfläche:**
- Karte „Empfehlungen“ in der Eltern-Statistik, ein kleines Zeichen im Dashboard.
- Knopf „Reihe 5 dazunehmen“ mit neuer Route. Die Policy-Prüfung schliesst andere Familien aus und erlaubt den Admin.
- Optional: automatische Freischaltung nach jeder Session (Standard aus) und eine Benachrichtigung an die Eltern.

**Tests:** Jede Regel einzeln mit fertigen Testdaten.

## Paket E: Abstände über Tage (Leitner-Boxen)

- **Migration:** `child_fact_stats` bekommt `box` (0 bis 5) und `due_at`. Bestehende Zeilen werden aus `current_streak` und `last_practiced_at` gefüllt.
- **Regeln:** Richtig und flüssig steigt eine Box, mit Abständen von 0, 1, 2, 4, 8 und 16 Tagen. Falsch oder langsam fällt zurück, in der Regel auf Box 0.
- **Selektor:** Statt des Aktualitäts-Anteils (bisher 20 %, gedeckelt bei 7 Tagen) zählt „fällig oder überfällig“. Nicht fällige Aufgaben bekommen ein kleines Gewicht (etwa ×0,15), nie null.
- **Risiko:** Feineinstellung. Erst nach A und B einführen und messen.
- **Tests:** Zeitreise-Tests für die Übergänge.

## Paket F: Flüssigkeit sichtbar, Tauschaufgaben

**F1**
- Die Heatmap bekommt vier Farben und die Legende „richtig, aber noch langsam“.
- Das Reihen-Meister-Abzeichen verlangt zusätzlich, dass ein Mindestanteil „sitzt“. Schon verdiente Abzeichen bleiben.

**F2**
- 7×8 und 8×7 bleiben getrennte Aufgaben (keine Datenmigration).
- Die Einführung aus Paket A nimmt die Tauschaufgabe gleich danach dran, dazu der Hinweis „Das kennst du schon: 3 × 7“.

## Paket G: Motivation und Voreinstellungen

- **G1:** Voreinstellung „Ruhig / Standard / Flott“ im Eltern-Formular. Sie stellt Timer, Tempo-Bonus und Töne per Klick ein und braucht keine Datenbankänderung.
- **G2:** Auf der Erfolgsseite „Meine Reihen“ mit Sternen: ein Stern für „alle Aufgaben bekannt“, ein zweiter für „flüssig“, ein dritter für „nach zwei Wochen noch sicher“. Dazu ein Zähler „Diese Woche neu gemeistert“. Braucht eine Spalte `mastered_at` in `child_fact_stats`.
- **G3:** Abzeichen „Wochenziel: 4 Tage“ als weichere Alternative zur 7-Tage-Serie.

## Paket H: optional und später

- **H1, Blitz-Check:** Ein einminütiger Check, den nur Eltern starten, ohne Punkte. Ergebnisse in einer neuen Tabelle `fluency_checks`, dazu ein Fortschrittsdiagramm. Stressrisiko, daher ausdrücklich freiwillig.
- **H2, Lücken- und Umkehraufgaben:** Aufgaben wie □ × 7 = 56 und Division. Gross, hängt an derselben Variantenspalte wie C3.

## Gemeinsame Messung

Ein Dienst `LearningReport` rechnet aus den vorhandenen Daten die Trefferquote pro Session, neu gemeisterte Aufgaben pro Woche und Sessions pro Woche. Er wird in der Eltern-Statistik und im Admin-Bereich sichtbar (Aufwand S). So zeigt sich, ob die Änderungen wirken.

---

## Offene Entscheidungen

1. **Bestandskinder:** Soll die portionsweise Einführung (Paket A) sofort für alle gelten? Empfehlung: ja, mit Hinweis im Eltern-Bereich.
2. **Strategien:** Welche Rechenwege lehrt die Schule, und wie heissen sie im Lehrmittel? Davon hängt die Wortwahl der Tipps in Paket C ab.
3. **„Ruhig“ als Standard** für neue Kinder (G1): ja oder nein?
4. **7-Tage-Serie:** Neben dem Wochenziel behalten oder ersetzen?
5. **Blitz-Check** (H1): Ist so eine Messung gewünscht?
