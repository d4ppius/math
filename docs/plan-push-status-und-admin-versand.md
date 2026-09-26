# Plan: Push-Status reparieren, Admin-Versand und Übersicht

**Stand:** 26. September 2026 · **Status:** Entwurf, wartet auf Freigabe.

## Ausgangslage

**Bug:** Der Button „Benachrichtigungen aktivieren" (Eltern-Dashboard) und „Erinnere mich ans Üben!" (Kind-Startseite) zeigen ihren Zustand rein über lokalen Alpine-State (`status: 'idle'`), der bei jedem Seiten-Reload neu startet — unabhängig davon, ob schon ein Push-Abo existiert. Das Abo selbst funktioniert (es landet korrekt in der `push_subscriptions`-Tabelle), nur die Anzeige „vergisst" es sofort wieder. Serverseitig gibt es für beide (`Parent\PushSubscriptionController`, `Child\ChildPushSubscriptionController`) bereits einen fertigen `destroy()`-Endpunkt zum Abmelden — nur keine Oberfläche, die ihn nutzt, und keine JS-Funktion zum Kündigen der Browser-Subscription.

**Fehlend:** Der Admin kann aktuell keine Push-Nachricht verschicken (weder gezielt noch an alle) und hat keine Übersicht, wer überhaupt ein Push-Abo hat. Es gibt bereits zwei automatische Push-Benachrichtigungen (`PracticeReminder` an Kinder, `DailyGoalAchieved` an Eltern), aber nichts manuell Auslösbares.

## Entschieden (aus der Anfrage, 26. September 2026)

1. Der Button-Zustand bleibt nach der Aktivierung über einen Reload hinweg korrekt — für Eltern und Kinder.
2. Bei Kindern verschwindet der Aktivieren-Hinweis, sobald ein Abo besteht.
3. Bei Eltern wird daraus eine echte Ab-/Anmeldung: Beschriftung wechselt zu „Deaktivieren", Klick kündigt das Abo wieder.
4. Admin kann eine Push-Nachricht schreiben und wahlweise an **alle Eltern**, **alle Kinder** oder **eine bestimmte Person** (aus beiden Gruppen) schicken.
5. Admin bekommt eine Übersicht, wer Push aktiviert hat.
6. Umsetzung direkt auf `main`, wie bei den meisten bisherigen Features (nicht auf einem eigenen Branch).

## Design

### 1. Bug-Fix und echtes Ein/Aus

- `Parent\DashboardController` und `Child\ChildHomeController` übergeben zusätzlich `pushSubscribed` (`$user->pushSubscriptions()->exists()` bzw. `$child->pushSubscriptions()->exists()`) an die jeweilige View.
- Alpine-`status` startet serverseitig vorbelegt: `'{{ $pushSubscribed ? "subscribed" : "idle" }}'` statt immer `'idle'`.
- Neue JS-Funktion `window.unsubscribeFromPush(unsubscribeUrl, csrfToken)` in `resources/js/app.js`, analog zu `subscribeToPush`: liest die aktuelle `PushManager`-Subscription aus, ruft `subscription.unsubscribe()` im Browser **und** den Server-Endpunkt auf. Der Server-Eintrag wird auch dann gelöscht, wenn der Browser die Subscription selbst schon verworfen hatte (z. B. abgelaufen) — Server und Realität sollen nie dauerhaft auseinanderlaufen.
- **Eltern-Dashboard:** Button zeigt bei `subscribed` „Benachrichtigungen deaktivieren" (neutraler/grauer statt des aktiven Indigo-Stils), Klick ruft `unsubscribe()` auf, danach `status = 'idle'`.
- **Kind-Startseite:** Bleibt wie gedacht — bei `subscribed` verschwindet der Hinweis-Button komplett, stattdessen weiterhin „Erledigt! Du bekommst eine Erinnerung.". Kein Deaktivieren-Weg für Kinder (siehe Entscheidung 1 unten falls das noch dazukommen soll).
- **Genauigkeit der Anzeige:** Der Status kommt aus der Datenbank (existiert überhaupt ein Abo), nicht aus dem aktuellen Browser. Jemand mit einem Abo auf einem anderen Gerät sieht auf einem neuen Gerät „aktiviert", obwohl *dieses* Gerät kein eigenes Abo hat — so ist das gewollt (mehrere Geräte möglich), die Formulierung sollte das aber nicht falsch versprechen.

### 2. Admin: Push senden

- Neuer `Admin\PushController` mit einer Seite unter `/admin/push`: Formular mit Empfängertyp (Alle Eltern / Alle Kinder / Bestimmte Person, mit Suche über Namen), Titel und Text.
- Neue Notification-Klasse `App\Notifications\AdminBroadcast` (nur `WebPushChannel`, kein Mail), Titel/Text kommen aus dem Formular statt fest im Code zu stehen.
- Es wird nur an Empfänger mit mindestens einem Abo geschickt (`whereHas('pushSubscriptions')`, wie schon bei `SendPracticeReminders`), damit der Versand nicht ins Leere läuft.
- Rückmeldung an den Admin nach dem Absenden: „An X von Y Eltern gesendet" (bzw. Kinder, bzw. 0/1 bei einer einzelnen Person).

### 3. Admin: Übersicht

- Kein komplett neuer Screen: Eine 🔔-Spalte/Badge in den bereits bestehenden Listen (Eltern-Konten und Kinder in der Familienansicht, Kinder-Übersicht), die zeigt, ob mindestens ein Abo existiert.
- Auf der neuen Versand-Seite zusätzlich eine kompakte Zahl „X von Y Eltern haben Push aktiviert, X von Y Kindern" direkt über dem Formular, damit vor dem Versand klar ist, wen man überhaupt erreicht.

## Schritte

### 1. Bug-Fix und Toggle-UI (M)

- `pushSubscribed` in beide Controller, Alpine-Vorbelegung, `unsubscribeFromPush()` in `app.js`, Deaktivieren-Button im Eltern-Dashboard.
- **Tests:** Nach einem simulierten Abo zeigt ein GET auf das Dashboard/die Kind-Startseite den `subscribed`-Zustand vorbelegt (nicht `idle`). Der Deaktivieren-Button ruft den bestehenden `destroy`-Endpunkt korrekt auf (Feature-Test für die Route bleibt wie gehabt, das JS selbst wird im Browser geprüft — wie bei der Fehlerkorrektur-Retype-Oberfläche).

### 2. Admin: Push-Versand-Seite (M)

- Controller, Route, View, `AdminBroadcast`-Notification.
- **Tests:** Versand an „alle Eltern"/„alle Kinder" erreicht nur Empfänger mit Abo, nicht die ohne. Versand an eine bestimmte Person erreicht nur diese. Validierung (Titel/Text Pflichtfelder). Nur Admins erreichen die Seite.

### 3. Admin: Übersicht/Badges (S)

- 🔔-Badge in den bestehenden Tabellen, Zähler auf der Versand-Seite.
- **Tests:** Badge erscheint nur bei bestehendem Abo, Zähler stimmt.

### 4. Dokumentation (S)

- README: kurzer Abschnitt bei „Admin-Bereich", der den Versand und die Übersicht erklärt.

## Reihenfolge und Aufwand

1 → 2 → 3 → 4. Aufwand grob: M + M + S + S, insgesamt etwa **4 bis 5 Arbeitstage**.

## Risiken

- Push funktioniert auf iOS nur, wenn die Seite als installierte PWA läuft (bereits bekannte Einschränkung der bestehenden automatischen Erinnerungen) — ein „0 von X erreicht" beim Admin-Versand kann daher überraschen, ist aber kein neuer Fehler.
- `subscription.unsubscribe()` im Browser kann fehlschlagen (z. B. Berechtigung entzogen); der Server-Eintrag wird trotzdem gelöscht, damit die Anzeige nicht dauerhaft „aktiviert" behauptet.

## Offene Entscheidungen

1. **Eltern-Kontrolle über das Kind-Abo:** Sollen Eltern das Push-Abo ihres Kindes zusätzlich selbst ein-/ausschalten können, oder bleibt das ausschliesslich Sache des Kindes auf dessen eigenem Gerät? Vorschlag: bleibt beim Kind.
2. **Protokoll der Admin-Versendungen:** Reicht eine einmalige Erfolgsmeldung nach dem Senden, oder soll es eine Historie geben (wer, wann, was geschickt wurde)? Vorschlag: keine Historie, nur die Erfolgsmeldung — lässt sich bei Bedarf später ergänzen.
