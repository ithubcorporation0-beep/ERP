# syntax=docker/dockerfile:1

##
## Stage 1: PHP dependencies (composer install --no-dev)
##
FROM composer:2 AS vendor
WORKDIR /app

# spatie/laravel-medialibrary declares ext-exif as a hard composer
# requirement (composer's platform check fails without it, even though
# this stage never runs the app itself) - the composer:2 image doesn't
# enable it by default.
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install exif \
    && apk del .build-deps

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction
COPY . .
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

##
## Stage 2: front-end build (npm ci && npm run build)
##
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

##
## Stage 3: runtime image - PHP-FPM + Nginx in one container, managed by
## supervisord. Both processes share the same filesystem, so there's no
## cross-container volume to keep in sync between deploys - the standard
## gotcha with running Nginx and PHP-FPM as two separate services (Nginx
## needs the exact same public/ contents PHP-FPM resolves paths against,
## and a Docker named volume only auto-populates from the image on its
## *first* mount, so it goes stale on every deploy after that unless you
## add extra sync machinery). One container per app instance is also all
## this app's traffic needs; scale by running more of this same image
## behind a load balancer if that ever changes.
##
FROM php:8.4-fpm-alpine AS app

RUN apk add --no-cache \
        nginx \
        supervisor \
        libpng \
        libjpeg-turbo \
        freetype \
        libzip \
        icu-libs \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && apk del .build-deps

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/php/production.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
