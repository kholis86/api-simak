# Stage 1: Build PHP dependencies
FROM composer:2.7 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist

# Stage 2: Build Node dependencies (for Vite/Swagger if needed)
FROM node:20-alpine AS node-builder
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 3: Final Production Image
FROM php:8.2-cli-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    bash \
    icu-dev

RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    bcmath \
    gd \
    pcntl \
    intl

# Copy RoadRunner binary from official image
COPY --from=spiralsc/roadrunner:2023.3.11 /usr/bin/rr /usr/bin/rr

WORKDIR /var/www/html

# Copy application code
COPY . .

# Copy vendor from stage 1
COPY --from=vendor /app/vendor ./vendor

# Copy built assets from stage 2 (if any)
# COPY --from=node-builder /app/public/build ./public/build

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Use production RoadRunner config
RUN cp .rr.prod.yaml .rr.yaml

# Expose the port RoadRunner is listening on (from .rr.prod.yaml)
EXPOSE 8080

# Command to run RoadRunner
CMD ["/usr/bin/rr", "serve", "-c", ".rr.yaml"]
