# Use the official PHP 7.4 image with Apache
# OLD (example)
# FROM php:7.4-apache

# NEW
FROM php:8.2-apache

# Common deps (adjust to your needs)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite (useful for clean URLs)
RUN a2enmod rewrite headers

# Copy app into the Apache web root
COPY . /var/www/html/

# Set permissions (optional but recommended)
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
