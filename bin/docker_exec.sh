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

if [ ! -r ".env" ]; then fail "Missing .env file!"; fi
source .env

[ -z "$NAME" ] && fail "Missing NAME definition!"
[ -z "$PORT" ] && fail "Missing PORT definition!"
[ -z "$TAG" ] && fail "Missing TAG definition!"

docker exec -it $NAME /bin/bash
