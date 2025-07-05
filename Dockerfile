# Use the official PHP 7.4 image with Apache
FROM php:7.4-apache

# Enable Apache mod_rewrite (useful if needed for clean URLs)
RUN a2enmod rewrite

# Copy all files from your repo into the Apache web root
COPY . /var/www/html/

# Set proper permissions (optional but recommended)
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
