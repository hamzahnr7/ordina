FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        unzip \
        git \
    && docker-php-ext-install pdo pdo_mysql \
    && pecl install redis pcov \
    && docker-php-ext-enable redis pcov \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# PCOV = code-coverage driver for PHPUnit. Off by default so normal web
# requests and `composer test` pay nothing; `composer test:coverage` turns it
# on for that one run with `-d pcov.enabled=1`.
RUN printf 'pcov.enabled=0\npcov.directory=/var/www/html/app\n' > /usr/local/etc/php/conf.d/zz-pcov.ini
# Jam lokal Indonesia (WIB) untuk date()/DateTime di web dan CLI (JOB-01, laporan, dsb).
ENV TZ=Asia/Jakarta
RUN printf 'date.timezone=Asia/Jakarta\n' > /usr/local/etc/php/conf.d/zz-timezone.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN sed -i 's#/var/www/html#/var/www/html/public#' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n\tAllowOverride All\n\tRequire all granted\n</Directory>\n' >> /etc/apache2/apache2.conf

COPY composer.json ./
RUN composer install --no-interaction --no-progress --no-scripts --optimize-autoloader || true

COPY . .
