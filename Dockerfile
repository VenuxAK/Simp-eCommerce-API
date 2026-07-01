FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    nginx \
    supervisor \
    curl \
    zip \
    unzip \
    git \
    oniguruma-dev \
    icu-dev \
&& apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    linux-headers \
&& docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    mbstring \
    intl \
    pcntl \
    bcmath \
&& pecl install redis \
&& docker-php-ext-enable redis \
&& apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
&& chown -R www-data:www-data storage bootstrap/cache \
&& chmod -R 775 storage bootstrap/cache

COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/*.ini /etc/supervisor.d/

EXPOSE 80

CMD ["supervisord", "-c", "/etc/supervisord.conf", "-n"]
