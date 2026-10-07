# Usa una imagen de PHP con Apache versión 8
FROM php:8.1-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Habilita el módulo de Apache para reescribir URLs
RUN a2enmod rewrite

# Sirve Laravel desde el unico directorio que debe ser publico.
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Instala las dependencias de Laravel
RUN apt-get update
RUN apt-get install -y \
    libzip-dev \
    zip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev

# Instala el cliente MySQL
RUN apt-get install -y default-mysql-client

# Instala la extensión ext-intl
RUN apt-get install -y libicu-dev
RUN docker-php-ext-install intl

# Instala GD, requerida por simplesoftwareio/simple-qrcode
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instala el controlador PDO de MySQL
RUN docker-php-ext-install zip pdo_mysql

# Configura el directorio de trabajo
WORKDIR /var/www/html

# Copia los archivos de Laravel al contenedor
COPY . .

# Configura permisos adecuados
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Instala exactamente las versiones registradas en composer.lock.
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Exponer el puerto 80
EXPOSE 80
EXPOSE 82

# Comando por defecto para iniciar Apache
CMD ["apache2-foreground"]
