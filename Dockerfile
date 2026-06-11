FROM dunglas/frankenphp:php8.2-alpine

RUN install-php-extensions \
    pdo_pgsql \
    gd \
    intl \
    zip \
    opcache \
    bcmath \
    mbstring

# Install Node.js for Vite build
RUN apk add --no-cache nodejs npm

# Increase PHP upload limits for image uploads
RUN echo "upload_max_filesize = 20M" > /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

COPY . /app/public
WORKDIR /app/public

ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install --optimize-autoloader

# Build frontend assets
RUN npm install && npm run build

RUN php artisan storage:link && \
    mkdir -p /app/public/storage/app/livewire-tmp && \
    chown -R www-data:www-data /app/public/storage /app/public/bootstrap/cache /app/public/public/storage

ENV SERVER_NAME=":8000"

EXPOSE 8000

CMD ["frankenphp", "php-server", "-r", "/app/public/public"]
