FROM php:8.4-apache

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      $PHPIZE_DEPS \
      imagemagick \
      libmagickwand-dev; \
    pecl install imagick; \
    docker-php-ext-enable imagick; \
    a2enmod rewrite headers; \
    apt-get purge -y --auto-remove $PHPIZE_DEPS; \
    rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
