# Tesseract Lasagna CMS
# @author Fred Brooker <git@gscloud.cz>

ARG CODE_VERSION=8.2-apache
FROM php:${CODE_VERSION}
LABEL maintainer="Fred Brooker <git@gscloud.cz>"
LABEL description="GS Cloud Ltd. - Tesseract LASAGNA Production Image"

ENV TERM=xterm-256color \
    LANG=C.UTF-8 \
    LC_ALL=C.UTF-8 \
    DEBIAN_FRONTEND=noninteractive

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update -qq && apt-get upgrade -yqq && \
    apt-get install -yqq --no-install-recommends \
    curl openssl unzip mc && \
    install-php-extensions gd imagick opcache bcmath zip intl sodium && \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false && \
    rm -rf /var/lib/apt/lists/* && \
    a2enmod rewrite expires headers

RUN rm -rf /var/www/html && \
    mkdir -p /var/www/data /var/www/logs /var/www/temp /var/www/www && \
    ln -sf /var/www/www /var/www/html && \
    ln -sf /dev/stdout /var/log/apache2/access.log && \
    ln -sf /dev/stderr /var/log/apache2/error.log

WORKDIR /var/www/

COPY . ./
COPY docker/* ./
COPY php.ini /usr/local/etc/php/conf.d/tesseract.ini
COPY bashrc /root/.bashrc
RUN chown -R www-data:www-data \
        data logs temp  && \
    chmod -R 775 \
        data logs temp 

HEALTHCHECK --interval=1m --timeout=10s CMD curl -f http://localhost/en || exit 1

EXPOSE 80
CMD ["apache2-foreground"]