#!/usr/bin/env bash
# SparesPro — cPanel update script.
# Pulls the repo, installs PHP deps, migrates, rebuilds caches.
#
# Usage:
#   bash ~/spares/deploy/update.sh
# If composer is not on the PATH:
#   COMPOSER=/opt/cpanel/composer/bin/composer bash ~/spares/deploy/update.sh
#   COMPOSER="php $HOME/composer.phar" bash ~/spares/deploy/update.sh
# If the shell PHP is not 8.3+:
#   PHP=/opt/cpanel/ea-php83/root/usr/bin/php bash ~/spares/deploy/update.sh

set -euo pipefail

PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"
APP_DIR="${APP_DIR:-$HOME/spares}"

cd "$APP_DIR"

$PHP artisan down || true

git pull --ff-only
$COMPOSER install --no-dev --optimize-autoloader --no-interaction

$PHP artisan migrate --force
$PHP artisan optimize:clear
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan event:cache

chmod -R ug+rwX storage bootstrap/cache

$PHP artisan up

echo "Update complete."
