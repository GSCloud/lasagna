#!/bin/bash
#@author Filip Oščádal <git@gscloud.cz>

dir="$(dirname "$0")"
. "$dir/_includes.sh"

# CRLF normalization
git add --renormalize .

# add new files
git add -A

# commit automatic changes
git commit -am "automatic web sync"
git push origin master

# create VERSION file
VERSION=$(git rev-parse HEAD)
echo $VERSION > VERSION

# create REVISIONS file
REVISIONS=$(git rev-list --all --count)
echo $REVISIONS > REVISIONS

# clear space
rm -rf logs/* temp/*
ln -s ../. www/cdn-assets/$VERSION >/dev/null 2>&1

info "Version: $VERSION Revisions: $REVISIONS"

command -v composer >/dev/null 2>&1 || fail "PHP composer is not installed!"
composer update --no-plugins --no-scripts

# recalculate favicons
cd www/img && . ./create_favicons.sh

exit 0
