FROM php:8.2-fpm

RUN apt-get update && apt-get install -y git zip nano zsh iputils-ping graphviz libpq-dev
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
RUN docker-php-ext-install pdo_mysql pdo_pgsql pgsql oci8 mysqli
RUN docker-php-ext-enable pdo_mysql pdo_pgsql pgsql oci8 mysqli

WORKDIR /application
COPY ./src ./src
COPY ./tests ./tests
COPY composer.json .
#COPY .env .
COPY phpstan.neon .
COPY phpunit.xml .
COPY ./vendor ./vendor

RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer
#RUN composer update

RUN git config --global user.email \'JuanchoSL@hotmail.com\'
RUN git config --global user.name \'Juan Sánchez\'

#COPY var/docker/php/conf.d/php.ini /usr/local/etc/php/php.ini

RUN pecl install xdebug
#COPY var/docker/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini


# install the xhprof extension to profile requests
RUN pecl install xhprof
#RUN docker-php-ext-enable xhprof