FROM php:8.2-apache

# Install PHP extensions required by the app (PDO + MySQL driver)
RUN docker-php-ext-install pdo pdo_mysql \
    && docker-php-ext-enable opcache

# Enable Apache rewrite module (useful for clean URLs / future routing)
RUN a2enmod rewrite

# Copy application source into Apache document root
COPY . /var/www/html/

# Wait-for-db entrypoint helper
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Ensure web server owns the files (needed for uploads under assets/)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
