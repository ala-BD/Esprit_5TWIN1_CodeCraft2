# syntax=docker/dockerfile:1.7
#
# Image tout-en-un pour Railway : PHP-FPM + Nginx + file de tâches dans un seul conteneur.
# Railway ne partage pas de volume entre deux services : Nginx et PHP-FPM, séparés dans
# docker-compose (services web et app), tournent donc ici ensemble sous supervisord.
#
# Mêmes extensions, même étape vendor et mêmes fichiers de configuration (docker/php, docker/nginx)
# que le Dockerfile principal.
#   docker build -f docker/railway/Dockerfile -t retiss-railway .
#   docker run -e PORT=8080 -p 8080:8080 --env-file … retiss-railway

ARG PHP_VERSION=8.4

############################
# 1. Dépendances Composer
############################
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

############################
# 2. Application + Nginx + supervisord
############################
FROM php:${PHP_VERSION}-fpm-alpine

LABEL org.opencontainers.image.title="RETISS — image Railway (PHP-FPM + Nginx)" \
      org.opencontainers.image.source="https://github.com/ala-BD/Esprit_5TWIN1_CodeCraft2"

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql gd zip intl bcmath exif opcache pcntl \
    && apk add --no-cache fcgi tzdata nginx supervisor

WORKDIR /var/www/html

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    TZ=Africa/Tunis \
    PORT=8080

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-retiss.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-retiss.conf
COPY docker/php/entrypoint.sh /usr/local/bin/retiss-entrypoint
COPY docker/nginx/default.conf /etc/retiss/nginx-default.conf
COPY docker/railway/supervisord.conf /etc/supervisord.conf
COPY docker/railway/start.sh /usr/local/bin/retiss-railway
RUN chmod +x /usr/local/bin/retiss-entrypoint /usr/local/bin/retiss-railway \
    && rm -f /etc/nginx/http.d/default.conf

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor

RUN mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

HEALTHCHECK --interval=15s --timeout=5s --start-period=120s --retries=5 \
    CMD wget -qO- "http://127.0.0.1:${PORT}/nginx-health" >/dev/null || exit 1

EXPOSE 8080
CMD ["retiss-railway"]
