# 1×1 Trainer

Eine kleine Webapp, mit der Kinder spielerisch und regelmässig das kleine Einmaleins üben – als installierbare PWA auf dem iPad, mit persönlichem Homescreen-Icon pro Kind.

> Dies ist ein privates Familienprojekt. Das Repository ist öffentlich, weil der Code jemandem nützen könnte – es enthält aber **keine echten Namen, Zugangsdaten oder sonstigen persönlichen Daten** einer echten Familie. Alle `.env`-Dateien, Datenbank-Inhalte und Zugangsdaten bleiben ausschliesslich auf dem jeweiligen Server.

## Was die App macht

- **Mandantenfähig nach Familien:** Eine Familie kann mehrere Eltern-Accounts (z.B. Mama + Papa) und beliebig viele Kinder-Accounts haben.
- **Eltern-Bereich** (`/eltern`, bewusst unauffällig, nicht von der Kind-Oberfläche aus verlinkt): Kinder anlegen, Schwierigkeit und Häufigkeit der Übungen einstellen, Statistiken einsehen, Push-Benachrichtigung erhalten, wenn ein Kind sein Tagesziel erreicht hat. Den Timer während der Übung kann man pro Kind ausblenden (für Kinder, die er stresst): Er läuft dann unsichtbar weiter, und kurz vor Schluss erscheint ein sanftes «Gleich geschafft!».
- **Kind-Login ganz ohne Passwort:** Jedes Kind bekommt einen persönlichen Magic-Link, den die Eltern einmalig als Homescreen-Icon auf dem iPad einrichten. Ein Tap auf das Icon reicht zum Einloggen (optional zusätzlich geschützt durch einen 4-stelligen PIN-Code, falls mehrere Kinder sich ein Gerät teilen).
- **Adaptives Üben:** Jede einzelne 1×1-Aufgabe wird pro Kind einzeln getrackt. Aufgaben, die noch nicht sitzen oder langsam beantwortet werden, kommen häufiger wieder – kein reiner Zufall.
- **Zeitlich begrenzte Sessions:** z.B. 10 Minuten am Stück, in der eigenen Geschwindigkeit des Kindes, serverseitig zeitlich abgesichert.
- **Punkte, Level, Badges** als Motivation.
- **Admin-Bereich** (`/admin`): Für den Betreiber der Installation, siehe [unten](#admin-bereich).
- **Erweiterbar:** Die Architektur ist so gebaut, dass später weitere Matheaufgaben-Typen (Addition, Division, …) ergänzt werden können, ohne den Kern umzubauen.

## Tech-Stack

- [Laravel](https://laravel.com) (PHP 8.5) im klassischen MVC-Aufbau
- MySQL
- Blade + [Alpine.js](https://alpinejs.dev) + [Tailwind CSS](https://tailwindcss.com) (via Vite, kein SPA-Framework nötig)
- [Laravel Breeze](https://laravel.com/docs/starter-kits) als Basis für den Eltern-Login
- Web-Push (VAPID) über [`laravel-notification-channels/webpush`](https://github.com/laravel-notification-channels/webpush) für Browser-Benachrichtigungen an die Eltern, mit E-Mail als Fallback
- PWA (Manifest + Service Worker), damit die App auf dem iPad als eigenständiges Icon installiert werden kann

## Lokale Entwicklung

Voraussetzungen: PHP 8.5, Composer, Node.js/npm, MySQL.

```bash
composer install
cp .env.example .env
php artisan key:generate
# .env anpassen: DB_* auf eine lokale MySQL-Datenbank zeigen lassen
php artisan migrate
npm install
npm run dev   # oder: npm run build
php artisan serve
```

## Admin-Bereich

Ein Admin sieht und verwaltet alle Familien auf der Installation. Admin wird man ausschliesslich per Befehl auf dem Server (siehe [INSTALL.md](INSTALL.md#7-admin-zugang-einrichten)):

```bash
php artisan app:make-admin deine@mail.example            # Admin-Rechte vergeben
php artisan app:make-admin deine@mail.example --revoke   # Admin-Rechte entziehen
```

Unter `/admin` (für alle anderen Benutzer ein 404):

| Bereich | Was möglich ist |
| --- | --- |
| **Übersicht** | Zähler für Familien, Eltern, Kinder, Sessions; die letzten Sessions |
| **Familien** | Suchen, umbenennen (Name, Zeitzone), löschen (samt allen Eltern, Kindern und Daten); Eltern-Konten der Familie löschen |
| **Kinder** | Alle Kinder über alle Familien; Bearbeiten, Übungseinstellungen und Statistik öffnen die normalen Eltern-Seiten des jeweiligen Kindes |
| **Sessions** | Alle Übungs-Sessions (filterbar pro Kind) mit jeder einzelnen Antwort, nur lesend |
| **Übungen** | Übungstypen aktivieren/deaktivieren (deaktivierte Übungen können Kinder nicht mehr starten); einzelne Aufgaben ansehen und bearbeiten |

**Als Elternteil ansehen:** In der Familienansicht kann der Admin ein Eltern-Konto «übernehmen» und die App so sehen, wie die Eltern sie sehen (z.B. zur Fehlersuche). Ein gelbes Banner oben erinnert daran, und «Zurück zum Admin» beendet die Ansicht.

Schutzregeln: Der Admin kann weder sich selbst noch seine eigene Familie löschen. Andere Admins lassen sich weder löschen noch übernehmen; dafür zuerst die Admin-Rechte entziehen.

## Deployment (Plesk)

Eine ausführliche Schritt-für-Schritt-Anleitung für das Deployment auf einem Plesk-Server (Datenbank anlegen, `.env` einrichten, Admin-Zugang, Cronjob für den Laravel-Scheduler) befindet sich in [INSTALL.md](INSTALL.md). Updates spielt man mit `./deploy.sh` ein: Das Skript zieht den Code, installiert Abhängigkeiten, baut das Frontend, migriert die Datenbank und baut die Caches neu auf.

## Lizenz

Der Code ist unter der [MIT-Lizenz](LICENSE.md) freigegeben. Das schliesst keine personenbezogenen Daten ein – siehe Hinweis oben.
