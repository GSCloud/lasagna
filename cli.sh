#!/bin/bash
#@author Filip Oščádal <git@gscloud.cz>

ABSPATH=$(readlink -f $0)
ABSDIR=$(dirname $ABSPATH)
cd $ABSDIR

php -f ./Bootstrap.php "$@"
