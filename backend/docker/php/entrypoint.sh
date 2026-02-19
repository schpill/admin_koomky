#!/bin/sh
set -e

# This script runs as root

# Wait for database to be ready (optional but good practice)
# while !</dev/tcp/postgres/5432; do sleep 1; done

# Set correct permissions for storage and cache
# This ensures that the application can write to these directories
# even if the volume is mounted from the host with different ownership.
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Execute the command passed to this script (e.g., "php-fpm" or "php artisan ...")
# We don't use gosu here, let the process run as root. This is simpler for dev,
# but for production you'd want to drop privileges.
exec "$@"
