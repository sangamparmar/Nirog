# Use an official PHP image with Composer and Node.js preinstalled
FROM laravelsail/php82-composer:latest

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    nodejs \
    npm \
    libpq-dev \
    default-mysql-client

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev --no-interaction --prefer-dist

# Set application key if not already set
RUN php artisan key:generate --force

# Install NPM dependencies and build assets
RUN npm ci && npm run prod

# Cache configuration for better performance
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port (using PORT env variable that Railway provides)
EXPOSE ${PORT:-8000}

# Start the Laravel server
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8000}

