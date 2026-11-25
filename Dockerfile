
FROM php:8.3-apache

WORKDIR /var/www/html

# Install required PHP extensions
RUN docker-php-ext-install pdo_mysql

# Enable Apache URL rewriting
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy Composer dependency files
COPY composer.json composer.lock ./

# Install PHPMailer and other PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy the rest of the application
COPY . .

# Configure Apache to use Render's assigned port
CMD sed -i "s/Listen 80/Listen ${PORT:-10000}/" /etc/apache2/ports.conf \
    && sed -i "s/:80>/:${PORT:-10000}>/" /etc/apache2/sites-available/000-default.conf \
    && apache2-foreground
