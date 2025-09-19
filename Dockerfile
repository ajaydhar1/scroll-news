# Use the official PHP 7.4 image with Apache
FROM php:7.4-apache

# Install Postgres client libs and build the PHP pgsql extensions
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev \
 && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql \
 && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite (useful for clean URLs)
RUN a2enmod rewrite

# Copy app into the Apache web root
COPY . /var/www/html/

# Set permissions (optional but recommended)
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
