FROM composer:2 AS php-dependencies

WORKDIR /app

COPY composer.json composer.lock ./
COPY app ./app
COPY database ./database
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

FROM node:22-alpine AS frontend-assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY vite.config.js ./
COPY --from=php-dependencies /app/vendor/tightenco/ziggy ./vendor/tightenco/ziggy

RUN mkdir -p public && npm run build

FROM richarvey/nginx-php-fpm:3.1.6

COPY . .
COPY --from=php-dependencies /app/vendor ./vendor
COPY --from=frontend-assets /app/public/build ./public/build

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr

RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/www.conf
RUN chmod +x /var/www/html/scripts/00-bootstrap.sh

CMD ["/start.sh"]
