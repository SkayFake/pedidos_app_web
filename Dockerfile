FROM dunglas/frankenphp:php8.2-alpine

RUN install-php-extensions \
    pdo_mysql \
    gd \
    intl \
    zip \
    opcache \
    bcmath \
    mbstring

# Install Node.js for Vite build
RUN apk add --no-cache nodejs npm

COPY . /app/public
WORKDIR /app/public

ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install --optimize-autoloader

# Build frontend assets
RUN npm install && npm run build

RUN chown -R www-data:www-data /app/public/storage /app/public/bootstrap/cache
RUN php artisan key:generate

ENV SERVER_NAME=":8000"

EXPOSE 8000

CMD ["frankenphp", "php-server", "-r", "/app/public/public"]
