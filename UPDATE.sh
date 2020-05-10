#!/bin/bash
#@author Filip Oščádal <oscadal@gscloud.cz>

dir="$(dirname "$0")"
. $dir"/_includes.sh"

VERSION=`git rev-parse HEAD`
echo $VERSION > VERSION
REVISIONS=`git rev-list --all --count`
echo $REVISIONS > REVISIONS

rm -rf cache
rm -rf logs/* temp/*
mkdir -p ci logs temp
mkdir -p www/cdn-assets
ln -s ../. www/cdn-assets/$VERSION >/dev/null 2>&1

info "Version: $VERSION Revisions: $REVISIONS"

command -v composer >/dev/null 2>&1 || fail "PHP composer is not installed!"
composer update --no-plugins --no-scripts

info "Done."
