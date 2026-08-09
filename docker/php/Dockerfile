# syntax=docker/dockerfile:1

# ---- Stage 1: PHP dependencies (Composer) ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---- Stage 2: Frontend assets (Vite/Tailwind) ----
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
# Livewire's Vite plugin imports its JS bundle straight out of the Composer
# vendor directory (resources/js/app.js -> ../../vendor/livewire/livewire/dist/...),
# so this stage needs it too, not just the PHP runtime stage.
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ---- Stage 3: Application runtime ----
FROM php:8.3-fpm AS app

# https://github.com/mlocati/docker-php-extension-installer — resolves system
# packages per extension automatically, far less fragile than hand-rolled apt-get.
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    pdo_pgsql \
    pdo_mysql \
    pdo_sqlite \
    gd \
    zip \
    bcmath \
    mbstring \
    exif \
    pcntl \
    intl \
    opcache \
    redis

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build
COPY . .

# The nginx container needs these built static files too, but it's a separate
# container/filesystem — they're handed over via a shared volume at runtime
# (see docker-compose.yml + entrypoint.sh). That volume mount shadows whatever
# is baked into the image at /var/www/html/public, so keep an untouched copy
# here purely as the sync source for every container start, not just the first.
RUN cp -r /var/www/html/public /var/www/html/public-src

# Regenerate the package-discovery/config manifest fresh against THIS image's
# vendor state (no dev packages) — never trust bootstrap/cache/*.php copied
# in from a host machine that may have dev dependencies installed.
RUN php artisan package:discover --ansi

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/98-opcache.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Stay root here: php-fpm's master process needs it to manage worker processes,
# which it then runs as www-data per docker/php/php-fpm's default pool config.
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
