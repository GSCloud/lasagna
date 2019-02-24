#!/bin/bash
#@author Filip Oščádal <oscadal@gscloud.cz>

dir="$(dirname "$0")"
. $dir"/_includes.sh"

info "Setting folders ..."

mkdir -p app cache ci data www/cdn-assets www/download www/upload
chmod 0777 www/download www/upload
chmod -R 0775 cache ci data
sudo chgrp www-data cache ci data www/cdn-assets www/download www/upload

info "Done."
