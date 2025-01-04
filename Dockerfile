#@author Fred Brooker <git@gscloud.cz>

ARG CODE_VERSION=8.2-apache
ARG DEBIAN_FRONTEND=noninteractive
ARG LC_ALL=en_US.UTF-8
ARG TERM=linux

FROM php:${CODE_VERSION}
ENV TERM=xterm LANG=C.UTF-8 LC_ALL=C.UTF-8

RUN apt-get update -qq && apt-get upgrade -yqq && apt-get install -yqq --no-install-recommends curl openssl redis

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions gd redis imagick

RUN a2enmod rewrite expires headers && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false
COPY php.ini /usr/local/etc/php/

RUN mkdir -p /var/www/ci /var/www/data /var/www/logs /var/www/temp \
    && chmod 0777 /var/www/ci /var/www/data /var/www/logs /var/www/temp \
    && ln -s /var/www/html /var/www/www

COPY app/*.txt app/*.redist  app/*.php app/router* app/csp.neon app/base.csv /var/www/app/
COPY app/partials/* /var/www/app/partials/
COPY app/templates/* /var/www/app/templates/
COPY Bootstrap.php composer.json composer.lock LICENSE *.md REVISIONS VERSION docker/ /var/www/
COPY vendor /var/www/vendor
COPY www /var/www/html
COPY bashrc /root/.bashrc

WORKDIR /var/www/
EXPOSE 80
