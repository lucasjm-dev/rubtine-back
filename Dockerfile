FROM php:7.4-fpm

# ── Dependencias del sistema + extensiones PHP que usa la app ──────────────
#  libpq-dev   → para pdo_pgsql / pgsql (PostgreSQL)
#  libonig-dev → para mbstring
#  libzip-dev  → para zip (lo usa composer)
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libonig-dev \
        libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring bcmath zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Remapeamos www-data al uid/gid del host para que pueda escribir en los
# archivos bind-mounteados (storage/, bootstrap/cache). Por defecto 1000:1000.
ARG UID=1000
ARG GID=1000
RUN groupmod -g ${GID} www-data && usermod -u ${UID} -g ${GID} www-data

# Composer, traído de su imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Overrides de PHP (tamaños de subida, memoria, etc.)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html

# Copiamos el código y resolvemos dependencias.
# --no-scripts evita que package:discover corra durante el build (Laravel lo
# resuelve en runtime igual). En dev este vendor queda tapado por el bind mount.
COPY . .
RUN composer install --no-interaction --prefer-dist --no-scripts --no-dev --optimize-autoloader

# Laravel necesita escribir en storage/ y bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
