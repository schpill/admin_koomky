#!/bin/bash
# Laravel Setup Script for Docker Container

set -e

# Create Laravel project if it doesn't exist
if [ ! -f /var/www/html/artisan ]; then
    echo "Creating Laravel 12.x project..."
    composer create-project laravel/laravel:^12.0.0 /var/www/html --no-interaction

    # Remove unnecessary Laravel files
    rm -rf /var/www/html/.github
    rm -f /var/www/html/docker-compose.yml
    rm -f /var/www/html/Dockerfile
    rm -f /var/www/html/.env.example

    echo "Laravel project created successfully!"
else
    echo "Laravel project already exists, skipping..."
fi

# Ensure storage is writable
chmod -R 775 /var/www/html/storage
chown -R www-data:www-data /var/www/html/storage

echo "Laravel setup complete!"
