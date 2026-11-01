FROM php:8.3-apache

RUN docker-php-ext-install mysqli \
    && a2enmod rewrite headers access_compat

# Allow .htaccess overrides for the app directory
RUN echo '<Directory /var/www/html>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-enabled/app.conf

WORKDIR /var/www/html

COPY ["./", "/var/www/html/Vehicles Fault Diagnosis and Service Monitoring System/"]

RUN chown -R www-data:www-data /var/www/html
