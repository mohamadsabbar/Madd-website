FROM php:8.3-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring zip bcmath opcache \
    && rm -rf /var/lib/apt/lists/*

# sockets for MikroTik API (optional; enable if needed)
RUN docker-php-ext-install sockets || true

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/portal

# Default FPM listen is 9000 inside the container network only
