#!/bin/bash
set -e
path=$(dirname "$0")
source $path/common.sh

chmod -R +w $base/www/sites/default
chmod -R +w $base/conf

echo "Enabling modules";
$drush en $(echo $DROPSHIP_SEEDS | tr ':' ' ')
echo "Rebuilding registry and clearing caches.";
$drush cc drush
echo "Set basic site variables";
$drush scr $base/build/scripts/set_site_variables.php
echo "Clearing caches one last time.";
$drush cc all
