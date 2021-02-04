ARG CODE_VERSION=8.0-apache
ARG DEBIAN_FRONTEND=noninteractive
ARG LC_ALL=en_US.UTF-8
ARG TERM=linux
FROM php:${CODE_VERSION}
RUN mkdir -p /var/www/ci /var/www/data /var/www/logs /var/www/temp \
    && chmod 0777 /var/www/ci /var/www/data /var/www/logs /var/www/temp \
    && ln -s /var/www/html /var/www/www
RUN a2enmod rewrite expires headers
ADD vendor /var/www/vendor
COPY _includes.sh .env Bootstrap.php cli.sh docker_updater.sh README.md REVISIONS VERSION /var/www/
WORKDIR /var/www/
EXPOSE 80