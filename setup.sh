#!/bin/bash

echo "Setting up CodeIgniter 4 Boilerplate with Shadui..."

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "Composer not found. Installing..."
    curl -sS https://getcomposer.org/installer | php
    chmod +x composer
fi

# Install dependencies
echo "Installing dependencies..."
composer install --no-interaction

# Generate encryption key
echo "Generating encryption key..."
php spark key:generate

# Run migrations
echo "Running migrations..."
php spark migrate

# Seed database with default users
echo "Seeding database..."
php spark db:seed UserSeeder

# Set permissions
echo "Setting permissions..."
chmod -R 775 writable/

echo ""
echo "Setup complete!"
echo "Default users:"
echo "  - Admin: admin@example.com / admin123"
echo "  - User:  user@example.com / user123"
echo ""
echo "Start the server with: php spark serve"