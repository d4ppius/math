# 1×1 Trainer

Eine kleine Webapp, mit der Kinder spielerisch und regelmässig das kleine Einmaleins üben – als installierbare PWA auf dem iPad, mit persönlichem Homescreen-Icon pro Kind.

> Dies ist ein privates Familienprojekt. Das Repository ist öffentlich, weil der Code jemandem nützen könnte – es enthält aber **keine echten Namen, Zugangsdaten oder sonstigen persönlichen Daten** einer echten Familie. Alle `.env`-Dateien, Datenbank-Inhalte und Zugangsdaten bleiben ausschliesslich auf dem jeweiligen Server.

## Was die App macht

- **Mandantenfähig nach Familien:** Eine Familie kann mehrere Eltern-Accounts (z.B. Mama + Papa) und beliebig viele Kinder-Accounts haben.
- **Eltern-Bereich** (`/eltern`, bewusst unauffällig, nicht von der Kind-Oberfläche aus verlinkt): Kinder anlegen, Schwierigkeit und Häufigkeit der Übungen einstellen, Statistiken einsehen, Push-Benachrichtigung erhalten, wenn ein Kind sein Tagesziel erreicht hat.
- **Kind-Login ganz ohne Passwort:** Jedes Kind bekommt einen persönlichen Magic-Link, den die Eltern einmalig als Homescreen-Icon auf dem iPad einrichten. Ein Tap auf das Icon reicht zum Einloggen (optional zusätzlich geschützt durch einen 4-stelligen PIN-Code, falls mehrere Kinder sich ein Gerät teilen).
- **Adaptives Üben:** Jede einzelne 1×1-Aufgabe wird pro Kind einzeln getrackt. Aufgaben, die noch nicht sitzen oder langsam beantwortet werden, kommen häufiger wieder – kein reiner Zufall.
- **Zeitlich begrenzte Sessions:** z.B. 10 Minuten am Stück, in der eigenen Geschwindigkeit des Kindes, serverseitig zeitlich abgesichert.
- **Punkte, Level, Badges** als Motivation.
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

## Deployment (Plesk)

Eine ausführliche Schritt-für-Schritt-Anleitung für das Deployment auf einem Plesk-Server (Datenbank anlegen, `.env` einrichten, Cronjob für den Laravel-Scheduler, Redeploy-Ablauf) befindet sich in [INSTALL.md](INSTALL.md).

## Lizenz

Der Code ist unter der [MIT-Lizenz](LICENSE.md) freigegeben. Das schliesst keine personenbezogenen Daten ein – siehe Hinweis oben.
