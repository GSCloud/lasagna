#!/bin/bash
#@author Fred Brooker <git@gscloud.cz>

dir="$(dirname "$0")"
. "$dir/_includes.sh"

info "Installing 👶"

find . -name "*.sh" -exec chmod +x {} \;
mkdir -p ci data logs temp www/cdn-assets www/download www/upload

echo "We need a root permission to run some tasks 😎"
sudo chmod 0777 ci data logs temp www/download www/upload
sudo chown -R www-data:www-data data
sudo chgrp -R www-data ci data www www/cdn-assets www/download www/upload

yes_or_no echo "Run PHP 8.2 and Apache APT installation?" || exit 0

sudo apt-get install -yq libapache2-mod-php8.2 openssl php8.2 php8.2-cli \
  php8.2-curl php8.2-gd php8.2-intl php8.2-mbstring php8.2-readline php8.2-xml php8.2-zip php8.2-redis php8.2-imagick
sudo a2enmod php8.2 expires headers rewrite

command -v composer >/dev/null 2>&1 || fail PHP composer is not installed!
[ ! -d "vendor" ] &&  make update

echo -en "\nRun \e[1m\e[4mmake doctor\e[0m to check your configuration 👨‍⚕️\n\n"

exit 0
