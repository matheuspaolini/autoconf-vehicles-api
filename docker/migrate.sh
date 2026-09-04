#!/usr/bin/env sh

set -eu

/usr/local/bin/ensure-dependencies

php artisan storage:link --force
php artisan migrate --force
