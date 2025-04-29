FROM php:8.1-apache

# Set environment variables
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libzip-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory contents
COPY . /var/www/html

# Create a basic .env file
RUN touch .env && \
    echo 'APP_NAME=Pharmacy' >> .env && \
    echo 'APP_ENV=production' >> .env && \
    echo 'APP_KEY=' >> .env && \
    echo 'APP_DEBUG=false' >> .env && \
    echo 'APP_URL=http://localhost' >> .env && \
    echo '' >> .env && \
    echo 'LOG_CHANNEL=stack' >> .env && \
    echo '' >> .env && \
    echo 'DB_CONNECTION=mysql' >> .env && \
    echo 'DB_HOST=127.0.0.1' >> .env && \
    echo 'DB_PORT=3306' >> .env && \
    echo 'DB_DATABASE=laravel' >> .env && \
    echo 'DB_USERNAME=root' >> .env && \
    echo 'DB_PASSWORD=' >> .env && \
    echo '' >> .env && \
    echo 'CACHE_DRIVER=file' >> .env && \
    echo 'QUEUE_CONNECTION=sync' >> .env && \
    echo 'SESSION_DRIVER=file' >> .env && \
    echo 'SESSION_LIFETIME=120' >> .env

# Set correct ownership
RUN chown -R www-data:www-data /var/www/html

# Install application dependencies
RUN composer install --optimize-autoloader --no-dev --no-interaction --ignore-platform-reqs

# Generate key if not already set
RUN php artisan key:generate --force

# Install NPM dependencies
RUN npm ci && npm run prod

# Cache configuration for better performance
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Configure Apache
RUN a2enmod rewrite
RUN sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www\/html\/public/g' /etc/apache2/sites-available/000-default.conf

# Expose port 80 (Apache default)
EXPOSE 80

# Create entrypoint script
RUN echo '#!/bin/bash' > /usr/local/bin/entrypoint.sh && \
    echo 'apache2-foreground' >> /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Start server
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
