FROM php:8.4-apache

RUN a2enmod rewrite headers

RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev libpq-dev zip unzip curl gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_pgsql zip opcache

RUN echo "opcache.enable=1\nopcache.memory_consumption=128\nrealpath_cache_size=4096K\nrealpath_cache_ttl=600" \
    > /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN printf "APP_NAME=Laravel\nAPP_ENV=production\nAPP_KEY=placeholder\nAPP_URL=http://localhost\nVITE_APP_NAME=Laravel\n" > .env

RUN NODE_OPTIONS="--max-old-space-size=512" npm run build

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache

RUN composer dump-autoload --optimize --no-scripts

RUN rm -f .env

RUN chown -R www-data:www-data storage bootstrap/cache

RUN chmod -R 775 storage bootstrap/cache

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]
