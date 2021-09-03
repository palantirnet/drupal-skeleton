<?php

/**
 * @file
 * Local developer settings.
 *
 * Copy this file to 'settings.local.php' and customize for your development needs.
 */

// Allow any host name locally.
$settings['trusted_host_patterns'] = ['.*'];

// Don't use Symfony's APCLoader. ddev includes APCu, and Composer's APCu loader has
// better performance.
$settings['class_loader_auto_detect'] = FALSE;

// Enable assertions.
// @see http://php.net/assert
// @see https://www.drupal.org/node/2492225
use Drupal\Component\Assertion\Handle;

assert_options(ASSERT_ACTIVE, TRUE);
Handle::register();

// Enable local development services.
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

// Show all error messages, with backtrace information.
$config['system.logging']['error_level'] = 'verbose';

// Disable caching.
$settings['cache']['bins']['render'] = 'cache.backend.null';
$settings['cache']['bins']['page'] = 'cache.backend.null';
$settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

// Disable CSS and JS aggregation.
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

// Allow test modules and themes to be installed.
$settings['extension_discovery_scan_tests'] = FALSE;

// Enable access to rebuild.php.
$settings['rebuild_access'] = TRUE;

// Don't chmod the sites subdirectory.
$settings['skip_permissions_hardening'] = TRUE;

// Enable/disable config_split configurations. To simulate other config split
// environments, change "development" to either "staging" or "production", then run:
//   drush cr && drush cim -y
$config['config_split.config_split.development']['status'] = TRUE;

// Hide Acquia Connector messages.
$config['acquia_connector.settings']['hide_signup_messages'] = TRUE;
