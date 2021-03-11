#!/bin/bash

dir="$(dirname "$0")"
cd $dir

mkdir -p app ci data logs temp www/cdn-assets www/download www/upload

if [ ! -z "$1" ]; then
    if [ -z "${ORIG}" ]; then
        echo -en "Missing ORIG site configuration!"
    else
        echo -en "Branch: $1 linked to ${ORIG}"
        rm -rf data
        ln -s ${ORIG}/data data
    fi
fi

chown $USER:$USER .
chmod 0777 ci data logs temp www/download www/upload
chown www-data:www-data ci data www/download www/upload

find www/ -type f -exec chmod 0644 {} \;
find ./ -type f -iname "*.sh" -exec chmod +x {} \;

info "Remote fixer: $dir DONE"
