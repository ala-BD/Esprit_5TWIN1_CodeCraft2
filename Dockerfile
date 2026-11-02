# syntax=docker/dockerfile:1.7
#
# Image de l'application RETISS (Laravel 12 / PHP 8.4), en trois étapes :
#   1. vendor : dépendances Composer de production
#   2. app    : PHP-FPM + code de l'application (cible par défaut)
#   3. web    : Nginx qui sert les fichiers publics et transmet le PHP à « app »
#
#   docker build --target app -t retiss-app .
#   docker build --target web -t retiss-web .

ARG PHP_VERSION=8.4

############################
# 1. Dépendances Composer
############################
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
# Les extensions PHP sont installées dans l'étape suivante, pas dans l'image composer
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

############################
# 2. Application (PHP-FPM)
############################
FROM php:${PHP_VERSION}-fpm-alpine AS app

LABEL org.opencontainers.image.title="RETISS — application Laravel" \
      org.opencontainers.image.description="Plateforme d'économie circulaire du textile (TextileCycle)" \
      org.opencontainers.image.source="https://github.com/ala-BD/Esprit_5TWIN1_CodeCraft2"

# Extensions requises : MySQL, images (DomPDF, QR codes), Excel (zip), dates (intl)
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql gd zip intl bcmath exif opcache pcntl \
    && apk add --no-cache fcgi tzdata

WORKDIR /var/www/html

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    TZ=Africa/Tunis

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-retiss.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-retiss.conf
COPY docker/php/entrypoint.sh /usr/local/bin/retiss-entrypoint
RUN chmod +x /usr/local/bin/retiss-entrypoint

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor

RUN mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

# PHP-FPM répond-il ? (page /ping activée dans www.conf)
HEALTHCHECK --interval=15s --timeout=5s --start-period=60s --retries=5 \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET cgi-fcgi -bind -connect 127.0.0.1:9000 | grep -q pong || exit 1

EXPOSE 9000
ENTRYPOINT ["retiss-entrypoint"]
CMD ["php-fpm"]

############################
# 3. Serveur web (Nginx)
############################
FROM nginx:1.27-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public

HEALTHCHECK --interval=15s --timeout=5s --start-period=30s --retries=5 \
    CMD wget -qO- http://127.0.0.1/nginx-health >/dev/null || exit 1

EXPOSE 80
