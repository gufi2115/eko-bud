FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libjpeg-dev libpng-dev libwebp-dev libzip-dev libsqlite3-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd pdo_sqlite zip exif \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Apache działa jako www-data (procesy obsługujące żądania)
ENV APACHE_RUN_USER=www-data APACHE_RUN_GROUP=www-data

WORKDIR /var/www/html
COPY --chown=www-data:www-data . /var/www/html

VOLUME ["/var/www/html/data", "/var/www/html/uploads"]
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
  CMD php -r 'exit(@file_get_contents("http://127.0.0.1/healthz.php")===false?1:0);'

ENTRYPOINT ["entrypoint.sh"]
