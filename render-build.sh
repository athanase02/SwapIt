#!/usr/bin/env bash
# Render Build Script for SwapIt

set -o errexit

# Install PHP and required extensions if needed
echo "Installing dependencies..."

# Install Composer if not present
if ! command -v composer &> /dev/null; then
    echo "Installing Composer..."
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --quiet
    rm composer-setup.php
    mv composer.phar /usr/local/bin/composer
fi

# Set up database (if needed)
echo "Setting up database..."

# Import database schema if SQL file exists
if [ -f "db/SI2025.sql" ]; then
    echo "Database schema found. It will be imported on first run."
fi

echo "Build completed successfully!"
