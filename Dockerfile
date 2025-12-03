FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mysqli

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Expose port
EXPOSE 10000

# Start command will be overridden by render-start.sh
CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]
