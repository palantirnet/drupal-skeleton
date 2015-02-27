#!/bin/bash
set -e
path=$(dirname "$0")
source $path/common.sh

chmod -R +w $base/cnf
chmod -R o+w $base/cnf/files
chmod -R +w $base/www/sites/default

echo "Enabling modules";
$drush en $(echo $DROPSHIP_SEEDS | tr ':' ' ')
echo "Rebuilding registry and clearing caches.";
$drush rr
$drush cc drush
echo "Clearing caches one last time.";
$drush cc all
