ARG PHP_DOCKER_VERSION
FROM php:${PHP_DOCKER_VERSION}-cli

RUN apt update

RUN apt install unzip

RUN curl -o /usr/bin/composer https://getcomposer.org/download/latest-stable/composer.phar && chmod +x /usr/bin/composer
