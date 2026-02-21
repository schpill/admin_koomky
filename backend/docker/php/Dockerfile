FROM php:8.3-fpm

ARG PUID=1000
ARG PGID=1000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    gosu \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    libicu-dev \
    libsqlite3-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure user and group IDs
RUN groupmod -o -g ${PGID} www-data \
    && usermod -o -u ${PUID} www-data \
    && chown -R www-data:www-data /var/www/html

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip intl pdo_sqlite

# Install PCOV for code coverage
RUN pecl install pcov && docker-php-ext-enable pcov

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Add custom php configuration
COPY backend/docker/php/conf.d/memory.ini /usr/local/etc/php/conf.d/memory.ini

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html
