#!/bin/sh
# Nuxt Setup Script for Docker Container

set -e

# Create Nuxt project if it doesn't exist
if [ ! -f /app/package.json ]; then
    echo "Creating Nuxt 3.x project..."
    npx nuxi@latest init --packageManager pnpm --gitInit false .

    echo "Nuxt project created successfully!"
else
    echo "Nuxt project already exists, skipping..."
fi

echo "Nuxt setup complete!"
