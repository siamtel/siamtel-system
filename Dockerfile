FROM php:8.2-apache

# Configurar php.ini para desarrollo
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" && \
    sed -i 's/session.cookie_httponly =/session.cookie_httponly = on/g' "$PHP_INI_DIR/php.ini"

# Instalar dependencias del sistema y compilar extensiones PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    libfreetype-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libicu-dev \
    libbz2-dev \
    libzip-dev \
    libldap2-dev \
    git \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql mysqli gd intl bz2 zip ldap exif \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Habilitar módulo rewrite de Apache
RUN a2enmod rewrite

# Redirigir DocumentRoot público a /var/www/html/public
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Incorporar y activar configuración para el directorio public
COPY config-available/public-directory.conf /etc/apache2/conf-available/public-directory.conf
RUN a2enconf public-directory

WORKDIR /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]

