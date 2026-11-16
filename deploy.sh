#!/usr/bin/env bash
#
# Server-side update script. Run from the app directory on the host:
#   bash deploy.sh
# Normally started by scripts/deploy.ps1 from the developer PC, which first uploads the
# freshly built frontend as ~/iron-nest-build.tgz (shared hosting has no Node to build it).
#
# If the host's default `php` is the wrong version, create .deploy.env (gitignored) next to this
# script on the server, e.g.:
#   PHP=/opt/alt/php84/usr/bin/php
#   COMPOSER="/opt/alt/php84/usr/bin/php /usr/local/bin/composer"

set -euo pipefail

cd "$(dirname "$0")"

if [ -f .deploy.env ]; then
    # shellcheck disable=SC1091
    . ./.deploy.env
fi

PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"
BUILD_ARCHIVE="${BUILD_ARCHIVE:-$HOME/iron-nest-build.tgz}"

on_error() {
    echo
    echo "!! Deploy failed. The site may still be in maintenance mode."
    echo "!! Fix the error above, then re-run 'bash deploy.sh'."
    echo "!! To bring the site back without finishing the deploy: $PHP artisan up"
}
trap on_error ERR

echo "==> Pulling latest code"
git pull --ff-only

echo "==> Installing PHP dependencies"
# Unquoted on purpose so COMPOSER can be a command with arguments, e.g. "php84 /usr/local/bin/composer".
$COMPOSER install --no-dev --optimize-autoloader --no-interaction

# Checked before maintenance mode so a missing build never leaves the site down.
if [ ! -f "$BUILD_ARCHIVE" ] && [ ! -f public/build/manifest.json ]; then
    echo "!! No frontend build found (public/build/manifest.json). Run scripts/deploy.ps1 from the PC."
    exit 1
fi

echo "==> Entering maintenance mode"
"$PHP" artisan down --refresh=15 || true

if [ -f "$BUILD_ARCHIVE" ]; then
    echo "==> Installing frontend build"
    rm -rf public/build
    mkdir -p public/build
    tar -xzf "$BUILD_ARCHIVE" -C public/build
    rm -f "$BUILD_ARCHIVE"
fi

echo "==> Running database migrations"
"$PHP" artisan migrate --force

echo "==> Rebuilding caches"
"$PHP" artisan optimize:clear
"$PHP" artisan config:cache
"$PHP" artisan route:cache
"$PHP" artisan view:cache

echo "==> Leaving maintenance mode"
"$PHP" artisan up

echo "==> Deploy complete: $(git log -1 --format='%h %s')"
