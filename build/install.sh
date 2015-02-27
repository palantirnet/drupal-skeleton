#!/bin/bash
set -e
path=$(dirname "$0")
source $path/common.sh

echo "Installing Drupal minimal profile.";
$drush si minimal --site-name=default --account-name=admin --account-pass=admin
source $path/install.sh
