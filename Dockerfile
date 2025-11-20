# Stage 1 - Build assets
FROM node:16 as build
WORKDIR /app

# Copy only package files first
COPY package*.json vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm install
RUN npm run build

# Stage 2 - PHP + Apache
FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git \
    && docker-php-ext-install pdo pdo_mysql zip

RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy Laravel backend
COPY . .

# Copy Vite build output
COPY --from=build /app/public/build ./public/build

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

RUN php artisan key:generate

EXPOSE 80
CMD ["apache2-foreground"]
