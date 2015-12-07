#!/bin/bash
#
# Compile the Drupal settings.php file from cnf/config.yml. Uses config.yml.dist if
# config.yml does not exist.

set -e
path=$(dirname "$0")
source $path/common.sh

SETTINGS_DIST="$base/cnf/config.yml.dist"
SETTINGS_YML="$base/cnf/config.yml"
SETTINGS_PHP="$base/cnf/settings.php"

if [ ! -e "$SETTINGS_YML" ]; then SETTINGS_YML="$SETTINGS_DIST"; fi
if [ -e "$SETTINGS_PHP" ]; then rm -rf "$SETTINGS_PHP"; fi

$base/vendor/bin/settings_compile $SETTINGS_YML $SETTINGS_PHP
