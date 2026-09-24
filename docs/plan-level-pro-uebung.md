# Plan: Level pro Übung statt ein gemeinsames Level

**Stand:** 24. September 2026 · **Status:** Genehmigt, wird umgesetzt.

## Ausgangslage

Aktuell haben alle Übungen eines Kindes zusammen **ein** gemeinsames Punktekonto (`children.total_points`) und **ein** gemeinsames Level. Zwei Probleme sind dabei aufgefallen:

1. **Die Level-Schwellen (400 bis 30 000) sind zu niedrig für das echte Tempo.** Durchgerechnet mit dem tatsächlichen Punkte-Code (`PointsCalculator`):

   | Tempo | Fragen in 10 Min. | Punkte in dieser Session | Bei 5 Sessions/Woche über 10 Wochen |
   | --- | --- | --- | --- |
   | Schnell, leichte Reihe (1,5 s Antwortzeit, 90 % richtig) | 206 | 2893 | 144 650 |
   | Typisch (3,5 s auf einer mittleren Reihe, 85 % richtig) | 122 | 1090 | 54 500 |
   | Langsam, schwierige Reihe (5,5 s, 70 % richtig) | 86 | 600 | 30 000 |

   Ein schnelles Kind auf leichten Aufgaben erreicht das heutige Top-Level (30 000) in weniger als einer Woche, statt wie geplant nach mehreren Monaten.

2. **Ein gemeinsames Level nimmt der zweiten Übung den Anreiz.** Ein Kind, das mit Plus bis 20 schon hoch levelt, hat beim Einmaleins keinen sichtbar frischen Fortschritt mehr zu holen — das gemeinsame Level wirkt bereits „erledigt“.

**Entschieden (dieses Gespräch):** Jede Übung bekommt ihr eigenes Punktekonto und ihr eigenes Level 1 bis 10. Damit bleibt jede Übung ein eigener, motivierender Fortschritt, unabhängig davon, wie weit das Kind in der anderen schon ist. Als Nebeneffekt werden auch die einzelnen Zahlen kleiner, weil sie sich auf mehrere Übungen verteilen.

**Randnotiz, nicht Teil dieses Plans:** Auffällig ist auch, dass Tempo das Level sehr viel stärker treibt als Regelmässigkeit (bei gleicher Übungszeit liegen schnelle und langsame Kinder um den Faktor 5 auseinander). Das könnte man später gesondert angehen (siehe `docs/lernkonzept-plan.md`), hier lassen wir es unangetastet.

## Was gleich bleibt

- Die Punkte-Formel (`PointsCalculator`) ändert sich nicht.
- Abzeichen ändern sich nicht (sie sind grösstenteils schon pro Übung).
- `LevelCalculator::forPoints()` bleibt als reine Funktion bestehen. Sie wird nur künftig **pro Übung einmal** statt einmal insgesamt aufgerufen.
- `children.total_points` bleibt erhalten und wird weiterhin bei jeder Antwort erhöht — als einfache „Punkte insgesamt“-Zahl (z. B. auf der Kinder-Startseite und im Eltern-Dashboard), aber sie bestimmt das Level nicht mehr.

## Datenmodell

- Neue Spalte `child_exercise_settings.points` (ganzzahlig, Standard 0). Diese Tabelle hat schon heute genau eine Zeile pro Kind und Übung — der natürliche Ort für übungsspezifische Punkte.
- **Bestehende Punkte gehen nicht verloren:** Eine einmalige Migration berechnet sie aus der Vergangenheit: `SUM(practice_sessions.total_points)` gruppiert nach Kind und Übungstyp. Das funktioniert exakt, weil jede Session schon heute weiss, zu welcher Übung sie gehört, und `practice_sessions.total_points` und `children.total_points` bei jeder Antwort gemeinsam erhöht werden. Die Migration vergleicht anschliessend die Summe alle Übungen eines Kindes mit `children.total_points` und schreibt eine Warnung ins Log, falls es (z. B. durch einen früheren manuellen Eingriff) nicht exakt übereinstimmt — bricht deswegen aber nicht ab.

## Schritte

### 1. Punkte schreiben (S)

- Migration: `child_exercise_settings.points` hinzufügen.
- Einmalige Datenmigration: bestehende Punkte wie oben beschrieben aus der Historie befüllen.
- `AttemptRecorder::record()`: erhöht zusätzlich zum bisherigen `children.total_points` auch `points` der passenden `ChildExerciseSetting`-Zeile.
- **Tests:** Eine richtige Antwort erhöht beides (Kind-Gesamtsumme und die Punkte der jeweiligen Übung). Eine falsche Antwort erhöht keines von beiden. Zwei Übungen sammeln unabhängig voneinander.

### 2. Level pro Übung berechnen (S)

- Neue, dünne Methode (Vorschlag: `ChildExerciseSetting::level()`), die nur `LevelCalculator::forPoints($this->points)` aufruft. Der `LevelCalculator` selbst bleibt Code-seitig unverändert.
- **In diesem Schritt gleich die Schwellen neu kalibrieren** (siehe Vorschlag unten), weil sich sonst dasselbe Problem einfach nur auf zwei Übungen verteilt statt behoben zu werden.
- **Tests:** Grenzwerte der neuen Schwellen, wie bei den bestehenden `LevelCalculatorTest`-Fällen.

**Vorschlag für neue Schwellen** (Rechenweg: „typisches“ Tempo soll das Top-Level nach ungefähr 10 Wochen regelmässigem Üben erreichen; „schnell“ nach etwa einem Monat, „langsam“ nach rund 4 Monaten):

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

Das ist eine Schätzung auf Basis der Simulation oben, keine gemessene Grösse aus echter Nutzung — bei der Freigabe gerne anpassen, bevor ich es umsetze. Einmal in Betrieb, liesse sich das später mit echten Zahlen aus der Admin-Übersicht nachschärfen (nicht Teil dieses Plans).

### 3. Anzeige: Kinder-Startseite (M)

- **Ist nur eine Übung freigeschaltet** (heute der Normalfall, Plus ist ja standardmässig aus): keine sichtbare Änderung. Ein Fortschrittsbalken wie heute, nur gespeist aus der übungseigenen statt der gemeinsamen Punktzahl.
- **Sind mehrere Übungen freigeschaltet:** Der bisher einzelne, globale Balken oberhalb der Auswahl entfällt. Stattdessen zeigt jede Übungs-Karte in der Auswahl („Was möchtest du üben?“) ihr eigenes Level, z. B. „➕ Plus bis 20 · Level 4“. Das macht den Unterschied zwischen den Übungen genau dort sichtbar, wo das Kind seine Wahl trifft. Die Zeile „⭐ X Punkte“ ganz oben bleibt bestehen und zeigt weiterhin die Gesamtsumme.
- **Tests:** Mit einer Übung wie bisher ein Balken. Mit zweien: zwei Karten mit je eigenem Level, kein globaler Balken mehr.

### 4. Anzeige: Erfolge-Seite `/kind/erfolge` (M)

- Statt einem `$level` von der Steuerung: eine Liste, ein Eintrag pro Übung (dieselben Übungen, die schon auf der Abzeichen-Seite als eigene Abschnitte auftauchen).
- Ein `<x-level-progress>`-Block pro Übung, mit dem Namen der Übung als Überschrift — passt zum bestehenden Muster der Abzeichen-Abschnitte auf derselben Seite.
- **Offene Frage (siehe unten):** Bei nur einer aktiven Übung trotzdem den Übungsnamen als Titel zeigen, oder wie heute ohne Titel?
- **Tests:** Ein Balken pro Übung mit korrekter Beschriftung; der Abzeichen-Teil bleibt unverändert.

### 5. Anzeige: Eltern-Statistik (M)

- Heute steht eine einzelne „Level“-Karte oberhalb der Reihen-Tabs, unabhängig davon, welche Übung gerade ausgewählt ist — das wirkt schon jetzt etwas unverbunden.
- **Vorschlag:** Das Level in die bestehenden Übungs-Reiter verschieben, direkt über der jeweiligen Heatmap dieser Übung. Das verbindet Level und Statistik derselben Übung, statt einer separaten Karte oben.
- Die Zeile „X Punkte insgesamt“ bleibt als eigene, immer sichtbare Zeile (weiterhin die Summe über alle Übungen).
- **Tests:** Jeder Übungs-Reiter zeigt das Level dieser Übung.

### 6. Aufräumen und Dokumentation (S)

- **README**, Abschnitt „Level und Abzeichen“: erklärt künftig Level pro Übung, mit der neuen Schwellen-Tabelle, und wo die Punkte liegen (`child_exercise_settings.points`) — im selben Stil, wie schon dokumentiert ist, wo die Abzeichen-Bilder liegen.
- **INSTALL.md**: Hinweis auf die neue Migration und die einmalige Datenmigration im Deployment-Ablauf (`deploy.sh` führt Migrationen ohnehin automatisch aus, hier reicht ein Satz zur Einordnung).
- Vollständiger Testlauf und Pint vor Abschluss.

## Reihenfolge und Aufwand

1 → 2 → (3, 4, 5 parallel möglich, da unabhängig voneinander) → 6. Aufwand grob: S + S + M + M + M + S, insgesamt etwa **4 bis 6 Arbeitstage**.

## Risiken

- **Die neuen Schwellen sind eine Schätzung**, keine Messung aus echter Nutzung. Das ist kein technisches Risiko (falsch kalibriert bedeutet höchstens „Level fühlt sich zu schnell oder zu langsam an“, nichts bricht), aber es lohnt sich, nach ein paar Wochen echten Betriebs nachzuschauen, ob es passt.
- **Migration hängt an der Historie:** Sie setzt voraus, dass `practice_sessions.exercise_type_id` immer gesetzt war. Das ist hier der Fall (die Spalte gibt es seit der allerersten Migration dieses Projekts), also kein echtes Risiko.
- **UI-Aufwand ist real, aber begrenzt:** Drei Seiten sind betroffen (Kinder-Startseite, Erfolge-Seite, Eltern-Statistik). Das entspricht in etwa dem Aufwand, den wir schon für die Abzeichen-Abschnitte pro Übung betrieben haben — also ein bekanntes, kein neues Muster.

## Entscheidungen (mit Marius abgestimmt, 24. September 2026)

1. **Schwellen-Vorschlag:** übernommen wie oben in der Tabelle.
2. **Erfolge-Seite:** Der Übungsname wird **weggelassen, solange nur eine Übung existiert** (keine sichtbare Änderung für den heutigen Normalfall). Sobald ein Kind zwei oder mehr Übungen hat, bekommt jeder Level-Block den Namen seiner Übung als Überschrift.
3. **Eltern-Statistik:** Das Level wandert wie vorgeschlagen in die bestehenden Übungs-Reiter, direkt über der jeweiligen Heatmap. Keine separate Level-Karte mehr oberhalb der Reiter.
4. **`children.total_points`:** Bleibt als reine Anzeige-Summe ohne Einfluss auf das Level.
