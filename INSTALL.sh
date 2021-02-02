#!/bin/bash
#@author Filip Oščádal <oscadal@gscloud.cz>

dir="$(dirname "$0")"
cd $dir
. $dir"/_includes.sh"

info "Setting up ..."

find . -name "*.sh" -exec chmod +x {} \;
mkdir -p ci data logs temp www/cdn-assets www/download www/upload
sudo chmod 0777 ci data logs temp www/download www/upload
sudo chown -R www-data:www-data data
sudo chgrp -R www-data ci data www www/cdn-assets www/download www/upload
sudo apt-get install -y libapache2-mod-php8.0 openssl php-imagick php-redis \
  php8.0 php8.0-cli php8.0-curl php8.0-gd php8.0-intl php8.0-mbstring php8.0-readline php8.0-xml php8.0-zip
sudo a2enmod rewrite

command -v composer >/dev/null 2>&1 || fail "PHP composer is not installed!"

if [ ! -d "vendor" ]; then
  . ./UPDATE.sh
fi

info "Done."
echo -en "\nRun \e[1m\e[4m./cli.sh doctor\e[0m to check your configuration.\n\n"
