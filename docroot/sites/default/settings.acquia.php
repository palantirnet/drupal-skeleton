<?php

/**
 * @file
 * Drupal settings file for use with Acquia hosted sites.
 */

// Include the Acquia database connection and other config.
if (file_exists('/var/www/site-php')) {
  require "/var/www/site-php/{$_ENV['AH_SITE_GROUP']}/{$_ENV['AH_SITE_GROUP']}-settings.inc";
}
