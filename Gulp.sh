#!/bin/bash

dir="$(dirname "$0")"
. $dir"/_includes.sh"

command -v nodejs >/dev/null 2>&1 || {
  info "Installing Nodejs ..."
  curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
  sudo apt-get install -y nodejs
}

command -v yarn >/dev/null 2>&1 || {
  curl -sL https://dl.yarnpkg.com/debian/pubkey.gpg | sudo apt-key add -
  echo "deb https://dl.yarnpkg.com/debian/ stable main" | sudo tee /etc/apt/sources.list.d/yarn.list
  sudo apt-get update
  sudo apt-get install yarn
}

info 'Checking gulp ...'
command -v gulp >/dev/null 2>&1 || {
  info "Installing gulp ..."
  sudo npm install --global gulp
  npm link gulp

  info 'Installing gulp plugins ...'
  npm install exec
  npm install gulp
  npm install gulp-autoprefixer
  npm install gulp-concat
  npm install gulp-cssmin
  npm install gulp-jshint
  npm install gulp-minify-css
  npm install gulp-rename
  npm install gulp-replace
  npm install gulp-uglify
  npm install gulp-util
}

info "Updating npm ..."
X=`npm outdated -g --depth=0`
if [ ! -z "$X" ]; then sudo npm update -g; fi
npm update
