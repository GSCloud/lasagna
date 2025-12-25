# Tesseract Lasagna CMS v2.5.0
# @author Fred Brooker <git@gscloud.cz>

ARG CODE_VERSION=8.2-apache
FROM php:${CODE_VERSION}

LABEL maintainer="Fred Brooker <git@gscloud.cz>"
LABEL description="Tesseract LASAGNA by GS Cloud Ltd."
LABEL version="2.5.0 beta"

ENV TERM=xterm-256color \
    LANG=C.UTF-8 \
    LC_ALL=C.UTF-8 \
    DEBIAN_FRONTEND=noninteractive

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update -qq && apt-get upgrade -yqq && \
    apt-get install -yqq --no-install-recommends \
        curl haveged htop mc openssl unzip && \
    install-php-extensions \
        bcmath exif gd imagick intl opcache redis sodium zip && \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false && \
    rm -rf /var/lib/apt/lists/* && \
    a2enmod \
        rewrite expires headers setenvif

RUN apt-get purge -y libopenexr-3-1-30 && apt-get autoremove -y

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
        data logs temp www && \
    chmod -R 775 \
        data logs temp www

HEALTHCHECK --interval=1m --timeout=7s CMD curl -f http://localhost/en || exit 1

EXPOSE 80
CMD ["apache2-foreground"]