FROM richarvey/nginx-php-fpm:3.1.6

COPY . /var/www/app

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/app/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr

RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/www.conf

CMD ["/start.sh"]