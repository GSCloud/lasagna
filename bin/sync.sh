#!/bin/bash
#@author Filip Oščádal <git@gscloud.cz>

dir="$(dirname "$0")"
. "$dir/_includes.sh"

if [ -z "$GLOBALSYNC" ]; then
  if [ -n "$1" ]; then export BETA="$1"; else export BETA="b"; fi
  info Branch: ${BETA:-MAIN}
  if [ "$BETA" == "x" ]; then
    export BETA=""
    info Branch: ${BETA:-MAIN}
  fi
  sleep 3
fi

if [ ! -r ".env" ]; then fail "Missing .env file!"; fi
export $(grep -v '^#' .env | xargs -d '\n')

[ -z "$DEST" ] && fail "Missing DEST definition!"
[ -z "$HOST" ] && fail "Missing HOST definition!"
[ -z "$USER" ] && fail "Missing USER definition!"

mkdir -p app ci data temp www/cdn-assets www/download www/upload
chmod 0777 www/download www/upload >/dev/null 2>&1

find www/ -type f -exec chmod 0644 {} \; >/dev/null 2>&1
find . -type f -iname "*.sh" -exec chmod +x {} \;

VERSION=$(git rev-parse HEAD)
echo $VERSION > VERSION

REVISIONS=$(git rev-list --all --count)
echo $REVISIONS > REVISIONS

ln -s ../. www/cdn-assets/$VERSION >/dev/null 2>&1
info "Version: $VERSION Revisions: $REVISIONS"

rsync -ahz --progress --delete-after --delay-updates --exclude "www/upload" \
  .env \
  *.json \
  *.php \
  _includes.sh \
  LICENSE \
  REVISIONS \
  VERSION \
  app \
  cli.sh \
  remote_fixer.sh \
  vendor \
  www \
  ${USER}@${HOST}:${DEST}'/' | grep -E -v '/$'

ssh root@$HOST $DEST/remote_fixer.sh ${BETA}

exit 0
