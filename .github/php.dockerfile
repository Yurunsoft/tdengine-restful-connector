ARG PHP_DOCKER_VERSION
FROM php:${PHP_DOCKER_VERSION}-cli

RUN apt update

RUN apt install unzip

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
php composer-setup.php && \
php -r "unlink('composer-setup.php');" && \
mv composer.phar /usr/local/bin/composer
