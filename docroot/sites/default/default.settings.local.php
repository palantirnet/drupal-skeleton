<?php

/**
 * @file
 * Local developer settings.
 *
 * Copy this file to 'settings.local.php' and customize for your development needs.
 */

// To simulate other config split environments, uncomment and edit these lines, then run:
//   drush cr && drush cim -y
# $config['config_split.config_split.development']['status'] = FALSE;
# $config['config_split.config_split.test']['status'] = FALSE;
# $config['config_split.config_split.production']['status'] = TRUE;

// Customizable development services file.
$settings['container_yamls'][] = DRUPAL_ROOT . '/' . $site_path . '/services.local.yml';

// Disable caching.
$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['cache']['bins']['page'] = 'cache.backend.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

// Disable CSS and JS aggregation.
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;
