#!/bin/bash
#@author Filip Oščádal <oscadal@gscloud.cz>

dir="$(dirname "$0")"
. $dir"/_includes.sh"

info "Setting up ..."

mkdir -p app cache ci data www/cdn-assets www/download www/upload

chmod +x *.sh
sudo chmod 0777 www/download www/upload
sudo chmod -R 0775 cache ci data
sudo chgrp -R www-data cache ci data www www/cdn-assets www/download www/upload
sudo apt install php7.3-cli php7.3-curl php7.3-mbstring php7.3-mysql php7.3-sqlite3 php7.3-zip

command -v composer >/dev/null 2>&1 || fail "PHP composer is not installed!"

if [ ! -d "vendor" ]; then
  . ./UPDATE.sh
fi

info "Done."
echo -en "\nRun \e[1m\e[4m./cli.sh doctor\e[0m to check your configuration.\n\n"
