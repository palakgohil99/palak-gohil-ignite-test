# Stage 1 - Build Vue + Vite assets
FROM node:22 AS build
WORKDIR /app

# Copy only package files first
COPY package*.json vite.config.js ./
RUN npm install

# Copy source files
COPY resources ./resources
COPY public ./public

# Build production assets
RUN npm run build

# Stage 2 - PHP + Apache
FROM php:8.2-apache

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git \
    && docker-php-ext-install pdo pdo_mysql zip opcache

# Enable Apache rewrite module
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy Laravel backend
COPY . .

# Copy Vite build output
COPY --from=build /app/public/build ./public/build

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Fix permissions for Laravel
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80
CMD ["apache2-foreground"]
