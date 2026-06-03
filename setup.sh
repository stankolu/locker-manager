#!/bin/bash
# Locker Manager - Setup Script
# Run this to set up the application from scratch.
# Usage: bash setup.sh

set -e

echo "=== Locker Manager Setup ==="
echo ""

# Check PHP
if ! command -v php &> /dev/null; then
    echo "[!] PHP not found. Installing..."
    sudo apt-get update
    sudo apt-get install -y php php-cli php-mysql php-mbstring php-xml php-gd php-zip
fi

# Check MySQL
if ! command -v mysql &> /dev/null; then
    echo "[!] MySQL not found. Installing..."
    sudo apt-get update
    sudo apt-get install -y mysql-server mysql-client
fi

# Start MySQL if not running
if ! pgrep -x mysqld > /dev/null; then
    echo "[*] Starting MySQL..."
    sudo mysqld --user=mysql --port=3306 --bind-address=127.0.0.1 --socket=/var/run/mysqld/mysqld.sock &
    sleep 5
fi

# Create database
echo "[*] Creating database..."
sudo mysql < schema.sql 2>/dev/null || mysql -u root < schema.sql

# Install PHP dependencies
if [ ! -d "vendor" ]; then
    echo "[*] Installing PHP dependencies..."
    if ! command -v composer &> /dev/null; then
        echo "[*] Installing Composer..."
        curl -sS https://getcomposer.org/installer | php
        sudo mv composer.phar /usr/local/bin/composer
    fi
    composer install --no-dev --optimize-autoloader
fi

# Create uploads directory
mkdir -p uploads

echo ""
echo "=== Setup Complete ==="
echo ""
echo "Start the development server with:"
echo "  php -S 0.0.0.0:8080"
echo ""
echo "Then open http://localhost:8080 in your browser."
