<?php

/**
 * @file
 * Drupal settings file template for use on Pantheon environments.
 */

// Enable/disable config_split configurations.
if (isset($_ENV['PANTHEON_ENVIRONMENT'])) {
  if ($_ENV['PANTHEON_ENVIRONMENT'] == 'live') {
    $config['config_split.config_split.production']['status'] = TRUE;
  }
  else {
    $config['config_split.config_split.staging']['status'] = TRUE;
  }
}
