FROM php:8.1-apache

# Install basic dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libzip-dev \
    nodejs \
    npm \
    ssl-cert

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif gd zip

# Enable Apache modules
RUN a2enmod rewrite ssl headers

# Set document root to public folder
RUN sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www\/html\/public/g' /etc/apache2/sites-available/000-default.conf

# Enable SSL in default Apache site
RUN sed -i 's/VirtualHost \*:80/VirtualHost *:80/' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's/<\/VirtualHost>/\n\tRedirectPermanent \/ https:\/\/${HTTP_HOST}\/\n<\/VirtualHost>/' /etc/apache2/sites-available/000-default.conf

# Create SSL config file
RUN cp /etc/apache2/sites-available/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf.orig \
    && sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www\/html\/public/g' /etc/apache2/sites-available/default-ssl.conf \
    && sed -i 's/#SSLCertificateFile/SSLCertificateFile/g' /etc/apache2/sites-available/default-ssl.conf \
    && sed -i 's/#SSLCertificateKeyFile/SSLCertificateKeyFile/g' /etc/apache2/sites-available/default-ssl.conf \
    && a2ensite default-ssl

# Get composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Set permissions
RUN chmod -R 777 storage bootstrap/cache

# Create a basic .env file if not exists
RUN if [ ! -f .env ]; then cp -n .env.example .env || touch .env; fi

# Install dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader --ignore-platform-reqs

# Generate application key
RUN php artisan key:generate

# Build frontend assets
RUN npm ci && npm run prod

# Expose ports
EXPOSE 80 443

# Start Apache
CMD ["apache2-foreground"]
