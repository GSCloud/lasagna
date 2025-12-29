#!/bin/bash
#@author Fred Brooker <git@gscloud.cz>

info() {
  echo -e "${*}\e[0m" 1>&2
}

warn() {
  echo -e " \e[1;33m❗️ \e[0;1m ${*}\e[0m" 1>&2
}

fail() {
  echo -e " \e[1;31m❌ \e[0;1m ${*}\e[0m" 1>&2
  sleep 5
  exit 1
}

VERSION=$(git rev-parse HEAD)
echo $VERSION > VERSION

REVISIONS=$(git rev-list --all --count)
echo $REVISIONS > REVISIONS

info "Version: $VERSION Revisions: $REVISIONS"

rm -rf logs/* temp/*
touch logs/.gitkeep temp/.gitkeep

command -v composer >/dev/null 2>&1 || fail "PHP composer is not installed!"

composer update --no-plugins --no-scripts || exit 2
composer dump-autoload --optimize || exit 2

# patching
if [ -d "patches" ]; then
  info "✅ applying patches to vendor/ ..."
  cp -R patches/* vendor/
fi

if [ -d ".git" ]; then
  if [[ $(git status --porcelain) ]]; then
    info "✅ committing updates ..."
    git commit -am "automatic update [rev: $REVISIONS]"
    git push origin master
  else
    info "✅ nothing to commit, working tree clean"
  fi
fi
