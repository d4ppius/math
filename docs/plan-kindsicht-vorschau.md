# Plan: Vorschau als Kind (aus Eltern- und Admin-Bereich)

**Stand:** 26. September 2026 · **Status:** Entwurf, wartet auf Freigabe.

## Ausgangslage

Eltern und Admins können heute nicht sehen, was ein Kind tatsächlich auf seinem Gerät erlebt, ausser sie benutzen dessen echten Magic-Link. Das erschwert Support ("was sieht mein Kind eigentlich gerade") und das Ausprobieren neuer Einstellungen. Admins können sich bereits als Eltern-Konto anmelden (`ImpersonationController`, `Auth::guard('web')`) — dasselbe Prinzip liesse sich für Kinder bauen (`Auth::guard('child')`).

Der Knackpunkt ist der Wunsch, dabei **eine Übung ausprobieren zu können, ohne echte Fortschrittsdaten zu verändern**. Eine beantwortete Frage schreibt heute an mehreren, unabhängigen Stellen gleichzeitig:

| Was passiert bei jeder Antwort | Wo |
| --- | --- |
| Lernstand pro Aufgabe (steuert die adaptive Auswahl künftiger Fragen) | `ChildFactStat` |
| Protokoll des einzelnen Versuchs | `SessionAttempt` |
| Punkte am Kind (Anzeige-Summe) und an der Übung (Level) | `children.total_points`, `child_exercise_settings.points` |
| Beim Session-Ende: Abzeichen und Tagesziel-Serie | Event `PracticeSessionCompleted` → `EvaluateBadges`, `EvaluateDailyGoal` |

Eine „Vorschau"-Session muss sich für das Kind (bzw. hier: für die vorschauende Person) normal anfühlen — realistische Fragen, richtige/falsche Rückmeldung, angezeigte Punkte — darf aber **nichts davon dauerhaft schreiben**.

## Entschieden (schon in der Anfrage festgelegt)

- Zugänglich sowohl aus dem **Eltern-Bereich** (für die eigenen Kinder) als auch aus dem **Admin-Bereich** (für alle Kinder) — beides ist bereits durch die bestehende `ChildPolicy::view()` abgedeckt, keine neue Berechtigungslogik nötig.
- Reines Ansehen (Kind-Startseite, Erfolge) zeigt die **echten, aktuellen** Daten des Kindes — nur das **Starten einer Übung** braucht den Vorschau-Schutz.

## Design

### Umschalten in die Kindsicht

Neuer, schlanker `ChildPreviewController` (analog zu `ImpersonationController`, aber für den `child`-Guard statt `web`):

- `POST /kind-vorschau/{child}` (`child-preview.start`): `authorize('view', $child)`; verweigert, wenn schon eine Vorschau oder Admin-Impersonation läuft (keine Verschachtelung); merkt sich serverseitig in der Session den Rückweg (`return_url`, die aufrufende Seite) und die aufrufende Person (`previewer_id`); loggt per `Auth::guard('child')->login($child)` ein; `session()->regenerate()`; redirect zu `child.home`.
- `POST /kind-vorschau/beenden` (`child-preview.stop`): liest `previewer_id` zurück, loggt diese Person wieder im `web`-Guard ein, redirect zu `return_url`.

**Banner:** Während einer laufenden Vorschau zeigt `x-child-layout` (die Kind-Seiten nutzen ein eigenes Layout, nicht `layouts/app.blade.php`) oben denselben Hinweis-Balken wie die bestehende Admin-Impersonation, kindgerecht neutral formuliert: „Vorschau als :name — Vorschau beenden".

**Zugriffspunkte:**
- Eltern: Knopf „Vorschau starten" auf der Seite „Kind bearbeiten" (`parent.children.edit`).
- Admin: Knopf pro Kind auf der Familienansicht (`admin.families.show`), neben dem bestehenden „Als Elternteil anmelden".

### Übung ohne Nebenwirkungen starten

- Neue Spalte `practice_sessions.is_preview` (boolean, Standard `false`).
- `PracticeSessionController::start()`: Läuft gerade eine Vorschau (erkennbar an der Session-Variable aus `ChildPreviewController`), wird **niemals** eine bestehende Session fortgesetzt (`resumableSessionFor()` wird übersprungen) — sonst würde die "harmlose" Vorschau eine echte, laufende Session des Kindes übernehmen und darin weiterschreiben. Es wird immer eine frische Session mit `is_preview = true` angelegt.
- `AttemptRecorder::record()`: Bei `is_preview` werden `ChildFactStat` nicht aktualisiert, kein `SessionAttempt` angelegt, `children.total_points` und `child_exercise_settings.points` nicht erhöht. Punkte, Richtig/Falsch und der Tipp werden trotzdem ganz normal berechnet und in der Antwort zurückgegeben, damit sich die Vorschau echt anfühlt — nur eben nirgends dauerhaft vermerkt. Die Zähler auf der Session-Zeile selbst (`questions_answered`, `questions_correct`, `total_points`) werden weiterhin gesetzt, sie gehören nur dieser einen Vorschau-Session und werden von nichts anderem mitgezählt (siehe unten).
- `PracticeSessionController::completeSession()`: Das Event `PracticeSessionCompleted` wird bei `is_preview` **nicht** ausgelöst. Das allein genügt, damit weder `EvaluateBadges` noch `EvaluateDailyGoal` laufen — keine Abzeichen, kein Tagesziel-Eintrag, keine Benachrichtigung an die Eltern. Die Zusammenfassungsseite (`child.sessions.summary`) funktioniert unverändert, sie liest ohnehin nur die (leere) Abzeichen-Liste dieser Session zurück.
- Die adaptive Fragenauswahl (`WeightedFactSelector`) liest weiterhin die echten `ChildFactStat`-Daten — die Vorschau zeigt also realistische, an den tatsächlichen Lernstand angepasste Fragen, schreibt selbst aber nichts hinein.

### Bestehende Abfragen, die Vorschau-Sessions sonst mitzählen würden

Eine Session mit `is_preview = true` bekommt ganz normal `status = completed`, `started_at`, `questions_answered` usw. — jede Stelle, die Sessions **ausserhalb** des einzelnen Event-Handlers direkt abfragt, muss sie deshalb explizit ausschliessen:

| Stelle | Ohne Korrektur |
| --- | --- |
| `Child::resumableSessionFor()` | Das Kind bekäme auf seiner echten Startseite „Weiter üben" für eine fremde Vorschau-Session angezeigt |
| `StatisticsController::weeklyPoints()` | Die Punkte-Grafik in der Eltern-Statistik würde Vorschau-Punkte mitzählen |
| `BadgeEvaluator::practisedAllExercisesToday()` | Eine Vorschau am selben Tag könnte dem Kind fälschlich den „Allrounder"-Abzeichen einbringen, sobald es selbst danach eine echte Session abschliesst |
| `Admin\DashboardController` (`sessions`-Zähler, `practicedToday`) | Eine Admin-Vorschau würde die globale „heute geübt"-Statistik verfälschen |

Alle vier bekommen ein zusätzliches `where('is_preview', false)`. In der Admin-Sessionliste (`Admin\SessionController`) bleiben Vorschau-Sessions dagegen sichtbar, aber mit einem Label „Vorschau" gekennzeichnet — dort ist Nachvollziehbarkeit nützlicher als Ausblenden.

## Schritte

### 1. Umschalten in die Kindsicht (M–L)

- `ChildPreviewController` (start/stop), Route-Definitionen, Banner in `x-child-layout`, Knöpfe bei Eltern und Admin.
- **Tests:** Eltern können ihr eigenes Kind vorschauen, nicht das eines fremden Familie (403, analog zu bestehenden `ChildPolicy`-Tests). Admin kann jedes Kind vorschauen. „Vorschau beenden" landet wieder beim ursprünglichen Konto und auf der Ausgangsseite. Keine Verschachtelung (Vorschau während einer Vorschau, oder während einer Admin-Impersonation, wird abgewiesen).

### 2. Übung ohne Nebenwirkungen (M)

- Migration `is_preview`, Anpassungen in `PracticeSessionController::start()`/`completeSession()` und `AttemptRecorder::record()` wie oben beschrieben.
- **Tests:**
  - Eine Vorschau überschreibt niemals eine echte laufende Session (beide Sessions bleiben als getrennte Zeilen bestehen).
  - Eine beantwortete Frage in der Vorschau verändert `ChildFactStat`-Anzahl, `SessionAttempt`-Anzahl, `children.total_points` und `child_exercise_settings.points` **nicht**, liefert aber ganz normal `points_awarded`/`is_correct`/`correct_answer`/`hint` in der Antwort.
  - Eine vollständige Vorschau-Session, die eigentlich ein Abzeichen oder das Tagesziel auslösen würde, erzeugt weder einen `child_badges`- noch einen `daily_goal_logs`-Eintrag, und keine Benachrichtigung wird verschickt.

### 3. Bestehende Abfragen korrigieren (S–M)

- `where('is_preview', false)` an den vier oben genannten Stellen, Label in der Admin-Sessionliste.
- **Tests:** Für jede der vier Stellen ein gezielter Test mit einer Vorschau-Session, die ohne die Korrektur das falsche Ergebnis liefern würde (Regressionstest gegen genau das beschriebene Risiko).

### 4. Dokumentation (S)

- README: neuer Abschnitt bei „Admin-Bereich" bzw. „Übungseinstellungen", der die Vorschau erklärt und ausdrücklich festhält, dass sie keine echten Daten verändert.
- `INSTALL.md`: Hinweis auf die neue Migration (reine Schemaänderung, nichts Zusätzliches beim Deployment nötig).

## Reihenfolge und Aufwand

1 → 2 → 3 → 4. Aufwand grob: M–L + M + S–M + S, insgesamt etwa **6 bis 8 Arbeitstage** — spürbar mehr als die letzten beiden Pläne, weil Schritt 3 mehrere unabhängige, bestehende Stellen im Code betrifft, die alle einzeln nachvollzogen und getestet werden müssen.

## Risiken

- **Vergessenes „Vorschau beenden":** Wie bei der bestehenden Admin-Impersonation bleibt man sonst unbegrenzt im `child`-Guard angemeldet. Gleiches Risiko wie heute schon akzeptiert, kein neuer Mechanismus vorgesehen (siehe offene Entscheidung 2).
- **Geteiltes Gerät:** Meldet sich jemand auf demselben Gerät/Browser als Vorschau an, auf dem gerade das echte Kind eingeloggt ist, überschreibt das dessen Sitzung (`child`-Guard ist pro Browser, nicht pro Person) — unvermeidbar bei einem session-basierten Login, aber es sollte in der Doku erwähnt werden.
- **Vergessene Abfrage-Stelle:** Der Katalog in Schritt 3 stammt aus einer Code-Suche nach `practiceSessions()`/`PracticeSession::` zum Zeitpunkt dieses Plans; eine künftige neue Auswertung über Sessions müsste denselben Ausschluss übernehmen. Kein Blocker, aber im README als Hinweis für spätere Änderungen wert.

## Offene Entscheidungen

1. **Zeitliches Limit für eine Vorschau:** Automatisch beenden (z. B. nach Verlassen der Kind-Seiten oder nach X Minuten) oder wie die bestehende Admin-Impersonation zeitlich unbegrenzt, bis explizit „Vorschau beenden" geklickt wird? Vorschlag: unbegrenzt, wie heute schon bei der Admin-Impersonation.
2. **Admin-Sessionliste:** Reicht ein Label „Vorschau" pro Zeile, oder soll es einen Filter „nur echte Sessions" geben? Vorschlag: nur Label, ein Filter lässt sich bei Bedarf leicht nachrüsten.
3. **Aufräumen alter Vorschau-Sessions:** Nichts löschen (harmlos, evtl. nützlich zur Fehlersuche) oder nach einer Weile automatisch entfernen? Vorschlag: nichts löschen.
