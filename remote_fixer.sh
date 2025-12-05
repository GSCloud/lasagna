#!/bin/bash
#@author Fred Brooker <git@gscloud.cz>

dir="$(dirname "$0")"
cd $dir

mkdir -p app ci data logs temp www/{download,upload}

[ ! -r ".env" ] && {
    echo -en "Missing .env file!\n"
    exit 1
}
source .env

[ -z "$ORIG" ] && {
    echo "Missing ORIG definition!"
    exit 1
}

[ -z "$USER" ] && {
    echo "Missing USER definition!"
    exit 1
}

if [ ! -z "$1" ]; then
    if [ -z "${ORIG}" ]; then
        echo -en "\nMissing ORIG site configuration!\n\n"
    else
        echo -en "\nBranch: $1 linked to ${ORIG}\n\n"
        rm -rf data www/download www/upload
        ln -s ${ORIG}/data data
        ln -s ${ORIG}/www/download www/download
        ln -s ${ORIG}/www/upload www/upload
    fi
fi

chmod -R 0777 ci data logs temp www www/{download,upload}
chown -R www-data:www-data . www ci data www/{download,upload}
find www/ -type f -exec chmod 0644 {} +
rm -f data/_random_cdn_hash
