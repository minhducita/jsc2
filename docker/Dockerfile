FROM php:5.6-apache
 # 1. Add php.ini, 000-default.conf to ubuntu folder

 # 2. Install Development Packages
 RUN apt-get update \
 && apt-get install -y \
 git \
 zip \
 curl \
 unzip \
 vim \
 libpng-dev \
 libpq-dev 
 #&& docker-php-ext-install pdo_mysql
 # 3. Install php extension
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable pdo_mysql
 # 3. Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
 # 4. mod_rewrite for URL rewrite and mod_headers for .htaccess extra headers like Access-Control-Allow-Origin-
 # 5. Disable modRewrite for Laravel
 #RUN mv /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled
 # RUN a2enmod rewrite headers
 # RUN /bin/sh -c a2enmod rewrite

ARG HOME_DIR=/var/www/html
 RUN a2enmod rewrite \
    && a2enmod headers \
    && sed -ri -e 's/^([ \t]*)(<\/VirtualHost>)/\1\tHeader set Access-Control-Allow-Origin "*"\n\1\2/g' /etc/apache2/sites-available/*.conf