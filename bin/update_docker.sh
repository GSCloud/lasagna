#!/bin/bash
#@author Filip Oščádal <oscadal@gscloud.cz>

ABSPATH=$(readlink -f $0)
ABSDIR=$(dirname $ABSPATH)
dir="$(dirname "$0")"
. "$dir/_includes.sh"

# connect to container and run CSV updater
info Updating CSV data from Google ...

docker exec tesseract ./docker_updater.sh

# connect to container and run bash
docker exec tesseract make

# connect to container and run bash
docker exec -ti tesseract bash
