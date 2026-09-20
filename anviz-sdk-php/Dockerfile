# Stage 1: Build dependencies
FROM composer:latest AS composer
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --no-dev --no-scripts --no-progress --ignore-platform-reqs --optimize-autoloader

# Stage 2: Final image
FROM php:8.1-apache-bullseye AS final

# Installiere Apache und PHP-Module
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    unzip \
    && docker-php-ext-install sockets zip \
    && a2enmod rewrite \
    && sed -i 's!/var/www/html!/var/www/html/web!g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's!/var/www/!/var/www/html/web/!g' /etc/apache2/apache2.conf \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Arbeitsverzeichnis setzen
WORKDIR /var/www/html

# Kopiere nur notwendige Dateien
COPY . .
# Überschreibe vendor mit optimierten Abhängigkeiten aus Stage 1
COPY --from=composer /app/vendor ./vendor

# Logs-Verzeichnis sicherstellen und unnötige Dateien entfernen
RUN mkdir -p logs && chown -R www-data:www-data logs \
    && rm -rf tests docs .git .github

# Entrypoint vorbereiten
RUN chmod +x /var/www/html/docker-entrypoint.sh

ENTRYPOINT ["/var/www/html/docker-entrypoint.sh"]
CMD ["apache2ctl", "-D", "FOREGROUND"]
