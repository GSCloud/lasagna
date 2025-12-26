#!/bin/bash
#@author Fred Brooker <git@gscloud.cz>

dir="$(dirname "$0")"

info() {
  echo -e " \e[1;32m*\e[0;1m ${*}\e[0m" 1>&2
}

warn() {
  echo -e " \e[1;33m***\e[0;1m ${*}\e[0m" 1>&2
}

fail() {
  echo -e " \e[1;31m***\e[0;1m ${*}\e[0m" 1>&2
  sleep 5
  exit 1
}

command -v docker >/dev/null 2>&1 || fail "Docker is NOT installed!"

if [ ! -n $(id -Gn "$(whoami)" | grep -c "docker") ]
    then if [ "$(id -u)" != "0" ]; then fail "Add yourself to the 'docker' group or run this script as root!"; fi
fi

[ ! -r ".env" ] && fail "Missing .env file!"
source ".env"

[ -z "${TAG}" ] && fail "Missing TAG definition!"
docker build --pull -t ${TAG} .

[ -z "${TAG2}" ] && fail "Missing TAG2 definition!"
docker build --pull -t ${TAG2} .
