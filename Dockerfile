# syntax=docker/dockerfile:1

# UKRIDA LabReserve — application image.
# PHP 8.2 CLI + required extensions, Composer, and Node 20 (for building Vite/Tailwind assets).
# The container is orchestrated by docker/entrypoint.sh (install → key → build → migrate/seed → serve).
FROM php:8.2-cli

# --- System packages + PHP extensions ---
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip ca-certificates curl gnupg \
        libzip-dev libonig-dev libpng-dev libjpeg-dev libfreetype6-dev \
        default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring bcmath zip gd exif \
    && rm -rf /var/lib/apt/lists/*

# --- Node.js 20 (builds the frontend assets via `npm run build`) ---
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# --- Composer ---
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Entrypoint lives outside the bind-mounted app dir so it is never shadowed.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
