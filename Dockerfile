# Usa una imagen de PHP con Apache versión 8
FROM php:8.1-apache

# Habilita el módulo de Apache para reescribir URLs
RUN a2enmod rewrite

# Instala las dependencias de Laravel
RUN apt-get update
RUN apt-get install -y libzip-dev zip

# Instala el cliente MySQL
RUN apt-get install -y default-mysql-client

# Instala la extensión ext-intl
RUN apt-get install -y libicu-dev
RUN docker-php-ext-install intl

# Habilita la extensión intl en el archivo php.ini
RUN echo "extension=intl" >> /usr/local/etc/php/conf.d/docker-php-ext-intl.ini

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instala el controlador PDO de MySQL
RUN docker-php-ext-install zip pdo_mysql

RUN docker-php-ext-install pdo_mysql

# Configura el directorio de trabajo
WORKDIR /var/www/html

# Copia los archivos de Laravel al contenedor
COPY . .

# Configura permisos adecuados
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Instala el paquete filament/filament
# RUN composer require filament/filament:"^3.2" -W
RUN composer update

# Exponer el puerto 80
EXPOSE 80
EXPOSE 82

# Comando por defecto para iniciar Apache
CMD ["apache2-foreground"]
