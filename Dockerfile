FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql pgsql mysqli pdo_mysql

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Expose port (Render uses PORT env variable)
EXPOSE 10000

# Start PHP server with router (using PORT from environment or default to 10000)
CMD php -S 0.0.0.0:${PORT:-10000} -t public public/router.php
