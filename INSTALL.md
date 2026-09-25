# Installation & Deployment (Plesk)

Diese Anleitung geht davon aus, dass das Deployment manuell per Git erfolgt (kein Plesk-Git-Autodeploy) und dass die MySQL-Datenbank sowie der Cronjob von Hand im Plesk-Panel angelegt werden.

## 1. Datenbank anlegen

Im Plesk-Panel unter **Datenbanken** eine neue MySQL-Datenbank samt eigenem Benutzer anlegen. Notiere dir:

- Datenbankname
- Benutzername
- Passwort
- Host/Port (bei Plesk meist `localhost`/`3306`)

## 2. Repository auf den Server holen

Per SSH auf den Server verbinden und das Repository klonen (oder bei einem Update: `git pull`):

```bash
git clone <repo-url> math
cd math
```

**Wichtig:** Die Plesk-Domain muss mit ihrem Dokumentstamm auf den `public/`-Ordner dieses Projekts zeigen (Domain-Einstellungen → "Dokumentstamm" → `math/public`).

## 3. Abhängigkeiten installieren

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

## 4. `.env` einrichten

```bash
cp .env.example .env
php artisan key:generate
```

Danach `.env` von Hand bearbeiten:

- `APP_URL` auf die echte Domain setzen, `APP_ENV=production`, `APP_DEBUG=false`
- `DB_*` mit den Zugangsdaten aus Schritt 1 ausfüllen
- `MAIL_*` für den echten Mailversand konfigurieren (`MAIL_MAILER=smtp` mit den Zugangsdaten des Providers). **Das ist nötig:** Ohne Mailversand kommen Bestätigungs-Mails der Registrierung, Passwort-Zurücksetzen und Kontaktformular nicht an, und neue Eltern können sich nicht anmelden. Für gute Zustellung sollte die Absender-Domain SPF und DKIM haben.
- VAPID-Schlüsselpaar für Web-Push erzeugen und eintragen:
  ```bash
  php artisan webpush:vapid
  ```
  Das trägt `VAPID_PUBLIC_KEY` und `VAPID_PRIVATE_KEY` direkt in die `.env` ein.

Die `.env`-Datei bleibt ausschliesslich auf dem Server und wird nie eingecheckt.

### Rechtliches und Kontaktformular

Impressum und Datenschutzerklärung (`/impressum`, `/datenschutz`) lesen ihre Angaben aus der `.env`. Ohne diese Werte zeigt die Seite sichtbar «[LEGAL_NAME fehlt in der .env]». **Bitte vor dem Livegang ausfüllen:**

```
LEGAL_NAME="Firma oder Name"
LEGAL_STREET="Strasse Nr."
LEGAL_ZIP_CITY="PLZ Ort"
LEGAL_COUNTRY=Schweiz
LEGAL_EMAIL=kontakt@beispiel.ch
LEGAL_PHONE=            # optional
LEGAL_UID=              # optional, z.B. CHE-123.456.789
LEGAL_RESPONSIBLE=      # optional, vertretungsberechtigte Person
LEGAL_HOSTER="Hosting-Anbieter, Land"   # wird in der Datenschutzerklärung genannt
CONTACT_MAIL_TO=support@beispiel.ch     # Empfänger der Kontaktformular-Nachrichten
```

Die Adresse in `LEGAL_NAME`/`LEGAL_STREET`/`LEGAL_ZIP_CITY` steht öffentlich im Impressum. `CONTACT_MAIL_TO` ist optional: Ohne sie werden Nachrichten trotzdem gespeichert und im Admin-Bereich unter **Nachrichten** angezeigt, aber nicht per E-Mail weitergeleitet. Die Datenschutzerklärung ist ein Entwurf nach bestem Wissen und ersetzt keine Rechtsberatung. Bitte einmal prüfen (lassen), besonders wenn die Seite öffentlich beworben wird.

Nach Änderungen an der `.env` `php artisan optimize` ausführen, sonst bleibt der alte Wert im Cache (das Deployment-Skript macht das ohnehin).

## 5. Schreibrechte setzen

```bash
chmod -R 775 storage bootstrap/cache
```

Je nach Plesk-Setup muss der Besitzer/die Gruppe auf den PHP-Ausführungsuser des Plesk-Webspace angepasst werden.

## 6. Datenbank migrieren und Aufgaben seeden

```bash
php artisan migrate --force
php artisan db:seed --force
```

Der Seed-Schritt ist **nicht optional**: er legt die Aufgabentypen ("Einmaleins" mit 1×1 bis 9×10, "Plus bis 20" mit 100 Aufgaben) samt allen Aufgaben und den Abzeichen an. Ohne ihn bleiben Übungseinstellungen und Statistik für jedes Kind leer, ohne dass ein Fehler auftritt. Der Befehl ist gefahrlos mehrfach ausführbar (`updateOrCreate`, keine Duplikate).

## 7. Admin-Zugang einrichten

Der Admin-Bereich (`/admin`) ist für dich als Betreiber gedacht: Er zeigt alle Familien, Eltern, Kinder, Sessions und Aufgaben. Es gibt bewusst **keinen** Weg, sich über die Oberfläche zum Admin zu machen. Das geht nur auf dem Server:

1. Ganz normal über `/eltern/register` ein Konto registrieren (falls noch nicht geschehen).
2. Danach per SSH:
   ```bash
   php artisan app:make-admin deine@mail.example
   ```
3. Nach dem nächsten Laden erscheint in der Navigation der Punkt **Admin**.

Admin-Rechte wieder entziehen:

```bash
php artisan app:make-admin deine@mail.example --revoke
```

Was der Admin kann, steht im Abschnitt [Admin-Bereich](README.md#admin-bereich) der README. Wer nicht Admin ist, bekommt auf `/admin` einen 404.

## 8. Cronjob für den Laravel-Scheduler anlegen

Im Plesk-Panel unter **Geplante Aufgaben** (Scheduled Tasks) einen neuen Cronjob anlegen, der **jede Minute** läuft:

```
* * * * * php /pfad/zum/projekt/artisan schedule:run >> /dev/null 2>&1
```

Darüber laufen alle zeitgesteuerten Aufgaben der App: die Erinnerungen an die Kinder (täglich 17:00), das Löschen erledigter Kontakt-Nachrichten nach 12 Monaten (`contact:prune`) und das Löschen von Konten, die ihre E-Mail-Adresse nicht innerhalb von 7 Tagen bestätigt haben (`accounts:prune-unverified`).

## 9. Redeploy bei Updates

Für Updates liegt im Projektordner das Skript `deploy.sh`. Es führt alle nötigen Schritte in der richtigen Reihenfolge aus, damit nach einem Update nichts vergessen geht (z.B. eine neue Migration):

```bash
./deploy.sh
```

Das Skript macht der Reihe nach:

1. Wartungsmodus einschalten (`artisan down`)
2. `git pull --ff-only` (bricht ab, falls auf dem Server lokale Änderungen liegen)
3. `composer install --no-dev --optimize-autoloader`
4. `npm ci` und `npm run build`
5. `php artisan migrate --force`
6. `php artisan db:seed --force`
7. `php artisan optimize`
8. Wartungsmodus ausschalten (`artisan up`)

Schlägt ein Schritt fehl, bleibt die Seite **bewusst im Wartungsmodus**, damit neuer Code nicht mit einer halb migrierten Datenbank läuft. Fehler beheben und `./deploy.sh` erneut ausführen; oder mit `php artisan up` die Seite wieder freigeben.

Liegen `php` oder `composer` auf dem Server nicht im Standardpfad, können sie als Variablen mitgegeben werden:

```bash
PHP=/opt/plesk/php/8.5/bin/php COMPOSER=/usr/local/bin/composer ./deploy.sh
```

Für die Erstinstallation ist das Skript nicht gedacht, dort gelten die Schritte 1 bis 8 dieser Anleitung.

**Migration vom 24. September 2026 (Level pro Übung):** `migrate --force` (Schritt 5) führt dabei einmalig auch eine Datenmigration aus, die bestehende Punkte pro Kind und Übung aus der Session-Historie zurückrechnet (siehe [Level und Abzeichen](README.md#level-und-abzeichen) in der README). Das passiert automatisch beim nächsten `./deploy.sh`, es ist kein manueller Schritt nötig.

**Migration vom 26. September 2026 (Vorschau als Kind):** Reine Schemaänderung (`practice_sessions.is_preview`, Standard `false`), nichts an bestehenden Daten wird verändert. Kein manueller Schritt nötig, läuft mit dem nächsten `./deploy.sh` mit.

## Hinweise

- `QUEUE_CONNECTION=sync` ist für dieses Projekt bewusst gewählt (kein Warteschlangen-Daemon nötig auf Shared Hosting). Sollte das Versenden von Benachrichtigungen später asynchron laufen müssen, kann auf `database` umgestellt werden – dann braucht es zusätzlich einen per Cron angestossenen `php artisan queue:work --stop-when-empty`.
- Web-Push funktioniert auf iPad/iPhone (Safari) nur, wenn die Seite zuvor über "Zum Home-Bildschirm hinzufügen" installiert wurde (iOS 16.4+).
