#!/bin/bash
# @author Fred Brooker <git@gscloud.cz>

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

function yes_or_no () {
  while true
  do
    read -p "$* [y/N]: " yn
    case $yn in
      [Yy]*) return 0 ;;
      [Nn]*) return 1 ;;
      *) return 1 ;;
    esac
  done
}

PACKAGES=(
  libapache2-mod-php8.2 
  openssl 
  php8.2 
  php8.2-cli 
  php8.2-curl 
  php8.2-gd 
  php8.2-intl 
  php8.2-mbstring 
  php8.2-readline 
  php8.2-xml 
  php8.2-redis 
  php8.2-imagick
)

info "👶 installing"

# create directory structure for volumes
mkdir -p ci custom data logs temp www/download/export www/upload www/img

echo "😎 root permission needed to run the following tasks"

# set permissions and ownership for all mapped volumes
sudo chmod -R 0777 ci custom data logs temp www
sudo find . -name "*.sh" -exec chmod +x {} +
sudo find www -type d -exec chmod 555 {} +
sudo find www -type f -exec chmod 444 {} +
sudo chmod 0775 www www/img/create_favicons.sh
sudo chmod -R 0666 www/img/logo.* www/img/favicon*
sudo chmod -R 0755 www/download
sudo chown -R www-data:www-data custom data logs www
sudo chgrp -R www-data ci custom data logs www www/download www/upload www/img

# check for missing packages
MISSING_PKGS=()
for pkg in "${PACKAGES[@]}"; do
    dpkg-query -W -f='${Status}' "$pkg" 2>/dev/null | grep -q "ok installed" || MISSING_PKGS+=("$pkg")
done

if [ ${#MISSING_PKGS[@]} -eq 0 ]; then
    info "✅ all PHP 8.2 components are already installed"
else
    warn "Missing packages: ${MISSING_PKGS[*]}"
    if yes_or_no "Run PHP 8.2 and Apache APT installation?"; then
        sudo apt-get update -yq
        sudo apt-get install -yq "${PACKAGES[@]}"
        sudo a2enmod php8.2 expires headers rewrite
    else
        warn "❌ installation skipped by user"
    fi
fi

# check for composer and run update
command -v composer >/dev/null 2>&1 || fail PHP composer is not installed!
[ ! -d "vendor" ] && make update

info "👾 run \e[1m\e[4mmake doctor\e[0m to check your configuration\n"
