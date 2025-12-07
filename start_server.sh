#!/usr/bin/env bash

# Verify that php is installed before starting the server
if ! command -v php >/dev/null 2>&1; then
    echo "Error: PHP is not installed or not in PATH."
    exit 1
fi

# Optional: show PHP version for debugging
php -v | head -n 1

# Start a simple PHP development server on port 8000, serving the current directory
php -S localhost:8000