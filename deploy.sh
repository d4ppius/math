#!/usr/bin/env bash
#
# Redeploy auf dem Server. Im Projektordner ausführen, nachdem neuer Code
# gepusht wurde:
#
#   ./deploy.sh
#
# Falls `php`/`composer` auf dem Server nicht im Standardpfad liegen (Plesk):
#
#   PHP=/opt/plesk/php/8.5/bin/php COMPOSER=/usr/local/bin/composer ./deploy.sh
#
# Erstinstallation siehe INSTALL.md – dieses Skript ist nur für Updates.

set -euo pipefail

cd "$(dirname "$0")"

PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"

step() { printf '\n\033[1m==> %s\033[0m\n' "$*"; }

step "Wartungsmodus einschalten"
"$PHP" artisan down

# Ab hier bleibt die Seite bei einem Fehler bewusst im Wartungsmodus: Neuer
# Code mit halb migrierter Datenbank soll nicht auf Familien losgelassen werden.
trap 'printf "\n\033[31mDeployment fehlgeschlagen.\033[0m Die Seite bleibt im Wartungsmodus.\nFehler beheben und ./deploy.sh erneut ausführen (oder Seite freigeben mit: php artisan up).\n" >&2' ERR

step "Code aktualisieren"
git pull --ff-only

step "PHP-Abhängigkeiten installieren"
"$COMPOSER" install --no-dev --optimize-autoloader --no-interaction

step "Frontend bauen"
npm ci
npm run build

step "Datenbank migrieren"
"$PHP" artisan migrate --force

step "Aufgaben seeden (idempotent)"
"$PHP" artisan db:seed --force

step "Caches neu aufbauen"
"$PHP" artisan optimize

step "Wartungsmodus ausschalten"
"$PHP" artisan up

printf '\n\033[32mDeployment abgeschlossen.\033[0m\n'
