FROM composer AS strage_composer
ARG COMPOSER_AUTH
ENV COMPOSER_AUTH ${COMPOSER_AUTH}

FROM php:8.2-cli as stage_php7
COPY --from=strage_composer /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libicu-dev \
        cron \
        git wget libzip-dev \
        git librabbitmq-dev


RUN pecl install xdebug-3.3.0
RUN docker-php-ext-enable xdebug

ENV APP_ENV: dev
ENV PHP_IDE_CONFIG: serverName=_
ENV XDEBUG_CONFIG: mode=debug client_host=host.docker.internal client_port=9003 start_with_request=trigger

COPY conf.d/php.ini "$PHP_INI_DIR/php.ini"
COPY conf.d/xdebug.ini "/usr/local/etc/php/conf.d/xdebug.ini"

WORKDIR /opt/project
