#!/usr/bin/env sh

set -eu

lock_hash="$(sha256sum composer.lock | awk '{print $1}')"
marker_path="vendor/.composer-lock-sha256"

if [ ! -f "$marker_path" ] || [ "$(cat "$marker_path")" != "$lock_hash" ]; then
    composer install --no-interaction --prefer-dist --no-progress --optimize-autoloader
    printf '%s\n' "$lock_hash" > "$marker_path"
fi
