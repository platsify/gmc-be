FROM bitnami/php-fpm:latest

RUN apt-get update && apt-get install -y autoconf build-essential
RUN pecl install mongodb
RUN echo "extension=mongodb.so" >> /opt/bitnami/php/etc/php.ini

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
