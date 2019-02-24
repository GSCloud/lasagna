#!/bin/bash
#@author Filip Oščádal <oscadal@gscloud.cz>

dir="$(dirname "$0")"
. $dir"/_includes.sh"

info "Updating ..."

VERSION=`git rev-parse HEAD`
echo ${VERSION} > VERSION
ln -s ../. www/cdn-assets/${VERSION} >/dev/null 2>&1
info "Version: ${VERSION}"

composer update --no-plugins --no-scripts
