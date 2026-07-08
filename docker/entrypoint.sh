#!/bin/sh
set -e

[ -d vendor ] || composer install --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

exec symfony server:start --port=8000 --allow-all-ip --no-tls
