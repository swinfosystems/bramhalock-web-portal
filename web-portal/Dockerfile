FROM php:8.2-apache

# Enable Apache modules
RUN a2enmod rewrite headers expires

# Install PHP extensions for MySQL and PostgreSQL
RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pgsql pdo_pgsql

# Copy app files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Use Render's dynamic PORT at runtime
EXPOSE 10000

# Start Apache listening on $PORT provided by Render
CMD ["/bin/sh", "-c", "sed -ri 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf && \
    sed -ri 's/:80>/:${PORT}>/' /etc/apache2/sites-available/000-default.conf && \
    apache2-foreground"]
