#!/bin/sh
set -e

# Only the primary "app" container should run one-time setup — queue/scheduler
# containers just want the code + config, not a race to migrate/cache in parallel.
if [ "$CONTAINER_ROLE" = "app" ]; then
  # Refresh the shared public/ volume from this image's build on every start,
  # not just the first — otherwise nginx keeps serving last deploy's assets.
  echo "Syncing public/ assets to the shared volume..."
  cp -rf /var/www/html/public-src/. /var/www/html/public/

  echo "Waiting for database..."
  until php artisan db:show > /dev/null 2>&1; do
    sleep 2
  done

  echo "Running migrations..."
  php artisan migrate --force

  echo "Caching config/routes/views..."
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
fi

exec "$@"
