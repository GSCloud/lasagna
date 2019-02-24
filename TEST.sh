#!/bin/bash

dir="$(dirname "$0")"
. $dir"/_includes.sh"

php -f Bootstrap.php production
