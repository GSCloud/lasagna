#!/bin/bash
#@author Fred Brooker <git@gscloud.cz>

info() {
  echo -e "\e[1;32m*\e[0;1m ${*}\e[0m" 1>&2
}

fail() {
  echo -e "\n\n\e[1;31m***\e[0;1m ${*}\e[0m" 1>&2
  exit 1
}

if [ -n "$1" ]; then export BETA="$1"; else export BETA="b"; fi
if [ "$BETA" == "x" ]; then
  export BETA=""
fi

if [ ! -r ".env" ]; then fail "Missing .env file!"; fi
source .env

ENABLE_PROD=${ENABLE_PROD:-0}
ENABLE_BETA=${ENABLE_BETA:-0}
ENABLE_ALPHA=${ENABLE_ALPHA:-0}

if [ -z "$BETA" ]; then
  if [ "$ENABLE_PROD" == "0" ]; then echo "PRODUCTION is disabled"; exit 0; fi
fi

if [ "$BETA" == "a" ]; then
  if [ "$ENABLE_ALPHA" == "0" ]; then echo "ALPHA is disabled"; exit 0; fi
  export DEST=$DESTA
fi

if [ "$BETA" == "b" ]; then
  if [ "$ENABLE_BETA" == "0" ]; then echo "BETA is disabled"; exit 0; fi
  export DEST=$DESTB
fi

[ -z "$DEST" ] && fail "Missing DEST definition!"
[ -z "$HOST" ] && fail "Missing HOST definition!"
[ -z "$USER" ] && fail "Missing USER definition!"

info "HOST: $HOST USER: $USER DEST: $DEST"

mkdir -p app ci data temp www/download www/upload
chmod 0777 www/download www/upload >/dev/null 2>&1
find www/ -type f -exec chmod 0644 {} \; >/dev/null 2>&1
find . -type f -iname "*.sh" -exec chmod +x {} \;
rm -rf www/cdn-assets

VERSION=$(git rev-parse HEAD)
echo $VERSION > VERSION

REVISIONS=$(git rev-list --all --count)
echo $REVISIONS > REVISIONS

info "Version: $VERSION Revisions: $REVISIONS"

rsync -ahz --progress --delete-after --delay-updates \
  --exclude "www/download" \
  --exclude "www/upload" \
  .env \
  *.json \
  *.md \
  *.php \
  app \
  bin \
  cli.sh \
  composer.lock \
  remote_fixer.sh \
  vendor \
  www \
  Makefile \
  LICENSE \
  REVISIONS \
  VERSION \
  ${USER}@${HOST}:${DEST}'/' | grep -E -v '/$'

ssh ${USER}@${HOST} ${DEST}/remote_fixer.sh ${BETA}
