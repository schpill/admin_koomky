FROM php:8.3-fpm

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
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip intl

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
