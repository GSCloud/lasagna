#!/bin/bash
#@author Fred Brooker <git@gscloud.cz>

ABSPATH=$(readlink -f $0)
ABSDIR=$(dirname $ABSPATH)

cd $ABSDIR
php -f ./www/index.php "$@"
