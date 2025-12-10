#!/bin/bash
#@author Fred Brooker <git@gscloud.cz>

info() {
  echo -e "\e[1;32m*\e[0;1m ${*}\e[0m" 1>&2
}

VERSION=$(git rev-parse HEAD)
echo $VERSION > VERSION

REVISIONS=$(git rev-list --all --count)
echo $REVISIONS > REVISIONS

info "Version: $VERSION Revisions: $REVISIONS"

rm -rf logs/* temp/*

command -v composer >/dev/null 2>&1 || fail "PHP composer is not installed!"

composer update --no-plugins --no-scripts
if [[ "$?" -eq "2" ]]; then exit 2; fi

composer dump-autoload --optimize
if [[ "$?" -eq "2" ]]; then exit 2; fi

# patches
cp -R patches/* vendor/

git commit -am "automatic update"
git push origin master
