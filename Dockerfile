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

# Create .env file if it doesn't exist
RUN if [ ! -f .env ]; then cp .env.example .env || echo "APP_NAME=Pharmacy\nAPP_ENV=production\nAPP_KEY=\nAPP_DEBUG=false\nAPP_URL=http://localhost\n\nLOG_CHANNEL=stack\n\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=laravel\nDB_USERNAME=root\nDB_PASSWORD=\n\nCACHE_DRIVER=file\nQUEUE_CONNECTION=sync\nSESSION_DRIVER=file\nSESSION_LIFETIME=120" > .env; fi

# Set correct ownership
RUN chown -R www-data:www-data /var/www/html

# Install application dependencies
RUN composer install --optimize-autoloader --no-dev --no-interaction --ignore-platform-reqs

# Generate key if not already set
RUN php artisan key:generate --force

# Install NPM dependencies
RUN npm ci && npm run prod

# Cache configuration
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Configure Apache
RUN a2enmod rewrite
RUN sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www\/html\/public/g' /etc/apache2/sites-available/000-default.conf

# Expose port 80 (Apache default)
EXPOSE 80

# Create entrypoint script
RUN echo '#!/bin/bash\n\
apache2-foreground' > /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Start server
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
