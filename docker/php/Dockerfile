FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends unzip \
    && docker-php-ext-install pdo_mysql \
    && pecl install redis pcov \
    && docker-php-ext-enable redis pcov \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

ENV TZ=Asia/Jakarta
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/apache.conf /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-autoloader

COPY . .
RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data public/uploads

HEALTHCHECK --interval=10s --timeout=3s CMD curl -f http://localhost/login || exit 1
