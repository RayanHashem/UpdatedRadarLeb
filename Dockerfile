# syntax=docker/dockerfile:1
#
# Production image for RadarLeb. Used by Render (and any other container host:
# Fly.io, Lightsail Containers, ECS, Coolify, etc.). Multi-stage so the final
# image doesn't carry composer/npm/node toolchains.
#
# Build:   docker build -t radarleb .
# Run:     docker run -p 8080:8080 --env-file .env radarleb
#
# Image base: serversideup/php is the de-facto Laravel-optimized image. It
# packages php-fpm + nginx + s6 supervisor in one container, which is what
# Render and Fly want. Pinned to PHP 8.4 because the project's deps cap at
# 8.4 (nette/schema, nette/utils, openspout/openspout).
# -----------------------------------------------------------------------------

# Stage 1: build front-end assets with Node.
FROM node:20-alpine AS frontend
WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY resources ./resources
COPY public ./public
COPY vite.config.ts tsconfig.json components.json ./
RUN npm run build

# Stage 2: install composer dependencies. Separate stage so we don't carry
# composer's cache and platform tools into the runtime image.
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader --ignore-platform-req=php --ignore-platform-req=ext-intl

# Stage 3: runtime. PHP-FPM + nginx + s6 in one image.
FROM serversideup/php:8.4-fpm-nginx

WORKDIR /var/www/html

# Bring in the application code.
COPY --chown=www-data:www-data . /var/www/html

# Drop in composer's vendor/ from stage 2.
COPY --from=vendor --chown=www-data:www-data /app/vendor /var/www/html/vendor

# Drop in the built JS/CSS bundle from stage 1.
COPY --from=frontend --chown=www-data:www-data /app/public/build /var/www/html/public/build

# Run the deferred composer scripts (package:discover, filament:upgrade) now
# that the full Laravel app + vendor/ + built assets are in place.
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && php artisan filament:upgrade --ansi \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# serversideup/php exposes 8080.
EXPOSE 8080

# Migrations + cached config/routes/views run on container start so a fresh
# deploy auto-applies schema changes. Then handoff to s6 (php-fpm + nginx).
ENTRYPOINT ["docker-php-entrypoint"]
CMD ["sh", "-lc", "php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && exec /init"]
