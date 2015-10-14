#!/bin/bash
set -e
path=$(dirname "$0")
source $path/common.sh

npm install -g sitespeed.io

echo "Installing Drupal minimal profile.";
$drush si minimal --site-name=skeleton --account-name=admin --account-pass=admin
source $path/update.sh
