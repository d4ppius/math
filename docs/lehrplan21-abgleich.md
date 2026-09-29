# Abgleich mit dem Lehrplan 21 (Zyklus 1)

**Stand:** 28. September 2026 · **Status:** Recherche und Analyse, keine Umsetzung. Grundlage für spätere Plan-Dokumente.

Dieses Dokument gleicht ab, was der Schweizer Lehrplan 21 im **Zyklus 1** (zwei Jahre Kindergarten + 1./2. Klasse, ca. 4–8 Jahre) im Fach Mathematik verlangt, was Rechenfuchs heute abdeckt, welche Aufgaben fehlen und wie sie pädagogisch sinnvoll ergänzt werden könnten.

## Was der Lehrplan 21 verlangt

Mathematik hat im Lehrplan 21 drei **Kompetenzbereiche** und drei **Handlungsaspekte** ([Aufbau](https://zh.lehrplan.ch/index.php?code=e%7C101%7C3), [Kompetenzmodell](https://www.zahlenbu.ch/cms/media/archive3/kursunterlagen_zahlenbuch/3b_Kompetenzmodell_MATH_LP21.pdf)):

| Kompetenzbereiche | Handlungsaspekte |
| --- | --- |
| MA.1 Zahl und Variable | A Operieren und Benennen |
| MA.2 Form und Raum | B Erforschen und Argumentieren |
| MA.3 Grössen, Funktionen, Daten und Zufall | C Mathematisieren und Darstellen |

**Wichtige Einordnung vorweg:** Rechenfuchs ist ein reiner **Geläufigkeits-Trainer** — Aufgaben mit Zahlen-Antwort über eine Zifferntastatur. Das deckt genau einen Ausschnitt ab: **MA.1 «Zahl und Variable», Handlungsaspekt A «Operieren und Benennen»**. Geometrie (MA.2), Grössen/Sachrechnen/Daten (MA.3) sowie die Handlungsaspekte B und C (Erforschen, Argumentieren, Modellieren) brauchen andere Aufgabenformate und sind **bewusst nicht** Ziel dieser App. Das ist keine Lücke der App, sondern ihre gewollte Fokussierung; nur innerhalb von MA.1.A lohnt sich der Abgleich.

### MA.1 «Zahl und Variable», Zyklus 1, das für ein Keypad relevant ist

Aus dem Lehrplan (Kompetenz MA.1.A.3 «Die Schülerinnen und Schüler können addieren, subtrahieren, multiplizieren, dividieren …», Zyklus-1-Stufen) und den kantonalen Umsetzungshilfen ([Bern](https://www.faechernet.bkd.be.ch/content/dam/faechernet_bkd/dokumente-bilder/de/startseite/unterrichtsfaecher/mathematik/umsetzungshilfe/umsetzungshilfen-zyklus-1-bis-3.pdf), [Luzern zu Rechenverfahren](https://volksschulbildung.lu.ch/-/media/Volksschulbildung/Dokumente/unterricht_organisation/faecher_wost_lehrmittel/faecher/Zyklus1_2/rechenverfahren_lp21.pdf)):

- **Zahlenraum:** Aufbau bis 20 (sicher, ohne Zählen), zum Übergang in Zyklus 2 hin Erweiterung Richtung 100.
- **Addition und Subtraktion** sind die tragenden Operationen des Zyklus 1. Grundanspruch bis Ende 2. Klasse: die meisten Ergebnisse des **Einspluseins** *und* des **Einsminuseins** bis 20 geläufig ermitteln.
- **Verdoppeln und Halbieren** sind eine eigene, ausdrücklich genannte Zyklus-1-Fertigkeit (und die Brücke zur Multiplikation).
- **Ergänzen / Zerlegen** (Zahlzerlegung, Ergänzen auf 10 und 20) als Grundlage des Zehnerübergangs.
- **Multiplikation:** Das **kleine Einmaleins ist im Lehrplan 21 in den Zyklus 2 verschoben** (Grundanspruch bis zum Orientierungspunkt = Ende 4. Klasse). Am **Ende von Zyklus 1** werden nur die **Kernaufgaben** (1×, 2×, 5×, 10×) erarbeitet und das Ableiten weiterer Malaufgaben angebahnt ([Klett/Zahlenbuch-Handreichung Einmaleins](https://cms.klett.ch/uploads/images/textbooks/Dateien/Schweizer-Zahlenbuch-1-4_Einmaleins_Handreichung.pdf)).
- **Halbschriftliche Strategien** statt stures Auswendig: Der Lehrplan betont das Notieren eigener Rechenwege und das Ableiten aus bekannten Aufgaben — genau das, was unser Rechentipp-System (Kernaufgabe + Ableiten) schon unterstützt.

## Was Rechenfuchs heute abdeckt

| Übung | Lehrplan-Einordnung |
| --- | --- |
| **Plus bis 20** (Einspluseins, Gruppen: bis 10, mit der 10, Zehnerübergang) | Kern-Grundanspruch Zyklus 1 ✓ genau richtig platziert |
| **Einmaleins** (1×1 bis 9×10) | Eigentlich **Zyklus 2** (Ende 4. Klasse), nicht Zyklus 1. Nicht falsch — nur weiter fortgeschritten als der Rest. |

Dazu passend schon vorhanden: pro Übung freischaltbar, eigene Level/Punkte/Abzeichen pro Übung, Rechentipps mit Kernaufgaben-Strategien, adaptive Auswahl. Diese Architektur (`ExerciseTypeContract` + `config/exercise_types.php`) trägt neue Übungen ohne Änderung am Session-Ablauf.

## Lückenanalyse (nur MA.1.A, Keypad-tauglich)

| Kompetenz Zyklus 1 | Status | Passt ins Zifferntastatur-Format? |
| --- | --- | --- |
| Einspluseins bis 20 (Addition) | ✅ vorhanden («Plus bis 20») | ja |
| **Einsminuseins bis 20 (Subtraktion)** | ✅ umgesetzt («Minus bis 20», siehe [`plan-minus-verdoppeln-halbieren.md`](plan-minus-verdoppeln-halbieren.md)) | ja, direkt parallel zu Plus |
| **Verdoppeln / Halbieren** | ✅ umgesetzt («Verdoppeln & Halbieren») | ja («Doppelte von 7?», «Hälfte von 16?») |
| **Plus/Minus bis 100** (Zehner/Einer, mit/ohne Übergang) | ❌ fehlt | ja, als weitere Aufgabenarten |
| **Ergänzen auf 10/20/100** («Was fehlt auf 20?») | ❌ fehlt | ja |
| Multiplikation Kernaufgaben (1/2/5/10×) als Zyklus-1-Einstieg | teilweise (im vollen Einmaleins enthalten, aber nicht als eigener sanfter Einstieg) | ja |
| Division | ❌ fehlt (Zyklus 2, Gegenstück zum Einmaleins) | ja |
| Form und Raum, Grössen, Sachrechnen, Argumentieren | ❌ fehlt | **nein — bewusst ausserhalb des App-Zwecks** |

## Empfehlung: Reihenfolge und pädagogische Einbettung

Priorisiert nach «grösste Lücke × passt am besten × geringster Aufwand». Nichts davon ist hier schon geplant im Detail — jede Zeile wäre ein eigenes Plan-Dokument mit Freigabe.

### 1. Minus bis 20 (Einsminuseins) — höchste Priorität

Die grösste inhaltliche Lücke und zugleich die einfachste: Subtraktion bis 20 ist ein **Kern-Grundanspruch** des Zyklus 1, exakt gleichwertig zu unserem bestehenden «Plus bis 20», und die bestehende `AdditionExerciseType` ist die direkte Vorlage.

Pädagogisch sinnvoll:
- **Erst wenn Plus sitzt**: Subtraktion baut auf sicherer Addition auf; die Übung bleibt (wie Plus) standardmässig aus und wird von den Eltern freigeschaltet.
- **Aufgabenarten wie bei Plus**, nach Schwierigkeit gestuft: Minus bis 10 → Minus mit der 10 → Zehnerübergang. Das entspricht dem Aufbau des Lehrplans (erst ohne, dann mit Übergang).
- **Rechentipps nutzen das bestehende `hint()`-System**: z. B. «Ergänzen» (13 − 8: von 8 auf 13 hochzählen) oder «Zehner zuerst» (13 − 8: erst 13 − 3 = 10, dann − 5). Beides sind die im Lehrplan genannten halbschriftlichen Strategien. Wie bei Plus standardmässig aus, weil die Schule ihren eigenen Weg lehrt.
- Nutzt Cover-Copy-Compare (Nachtippen bei Fehlern), Level und Abzeichen automatisch mit.

### 2. Verdoppeln und Halbieren — Brücke zur Multiplikation

Eigene kleine Übung («Verdoppeln bis 50», «Halbieren gerader Zahlen bis 100»). Pädagogisch wertvoll, weil Verdoppeln/Halbieren im Lehrplan die **Brücke von der Addition zur Multiplikation** ist (Kernaufgaben ×2, und ×5 als Hälfte von ×10). Das verbindet inhaltlich unsere beiden bestehenden Welten Plus und Einmaleins.

### 3. Plus/Minus bis 100 — Übergang in Zyklus 2

Kein neuer Übungstyp nötig: liesse sich als zusätzliche **Aufgabenarten** in Plus und Minus ergänzen (Zehner ± Einer, Zehner ± Zehner, mit/ohne Übergang). Deckt den Übergang Ende Zyklus 1 → Zyklus 2 ab. Eltern schalten die schwierigeren Arten frei, wenn der Zahlenraum bis 20 sitzt.

### 4. Ergänzen («Was fehlt auf …?»)

Fragt die Zahlzerlegung von der anderen Seite ab («8 + ? = 20»). Fördert die Zahl-Beziehungen, die der Lehrplan als Grundlage nennt. Könnte eine eigene Übung oder eine Fragevariante innerhalb von Plus sein — Entscheidung offen.

### 5. Division — Gegenstück zum Einmaleins (Zyklus 2)

Niedrigere Priorität (Zyklus 2), aber natürliches Gegenstück zum bereits vorhandenen Einmaleins und mit derselben Kernaufgaben-Logik. Sinnvoll erst, wenn das Einmaleins sicher ist.

## Ehrliche Grenze

Selbst voll ausgebaut deckt Rechenfuchs nur **MA.1.A (Operieren und Benennen)** ab — die reine Rechen-Geläufigkeit. Der Lehrplan 21 will im Zyklus 1 zusätzlich Erforschen/Argumentieren, Mathematisieren/Darstellen sowie Geometrie und Grössen. Das ist gut so: Rechenfuchs ist als fokussiertes Übungswerkzeug für die Rechen-Automatisierung gedacht, nicht als Ersatz für den Mathematikunterricht. Bei einer Aussendarstellung (z. B. Anleitungsseite) sollte das ehrlich so benannt werden, statt «Lehrplan-21-konform» zu suggerieren.

## Offene Entscheidungen (vor einer Umsetzung zu klären)

1. **Umfang der ersten Ausbaustufe:** Nur «Minus bis 20» (klar grösste Lücke), oder gleich zusammen mit «Verdoppeln/Halbieren»?
2. **Zahlenraum bis 100:** als neue Aufgabenarten in Plus/Minus, oder bewusst noch weglassen, um Zyklus 1 sauber abzuschliessen?
3. **Ergänzen:** eigene Übung oder Fragevariante?
4. Reihenfolge und ob überhaupt: Division (Zyklus 2) jetzt schon oder später.
