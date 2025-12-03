#!/usr/bin/env bash
# Render Start Script for SwapIt

set -o errexit

echo "Starting SwapIt application..."

# Start PHP built-in server
# Render will provide PORT environment variable
PORT=${PORT:-3000}

echo "Server starting on port $PORT..."
php -S 0.0.0.0:$PORT -t public
