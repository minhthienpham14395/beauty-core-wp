ARG WORDPRESS_TAG=php8.2-apache

FROM wordpress:${WORDPRESS_TAG}

RUN a2enmod rewrite \
    && printf '%s\n' '<Directory /var/www/html>' '    AllowOverride All' '    Require all granted' '</Directory>' > /etc/apache2/conf-available/beautycore-rewrite.conf \
    && a2enconf beautycore-rewrite

COPY docker/php/uploads.ini /usr/local/etc/php/conf.d/zz-beautycore.ini

WORKDIR /var/www/html
