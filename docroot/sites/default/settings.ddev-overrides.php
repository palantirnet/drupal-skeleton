<?php

/**
 * @file
 * Shared developer settings.
 */

// Allow any host name.
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

// Enable local development services, including the null cache backend.
$settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';

// Show all error messages, with backtrace information.
$config['system.logging']['error_level'] = 'verbose';

// Allow test modules and themes to be installed.
$settings['extension_discovery_scan_tests'] = FALSE;

// Enable access to rebuild.php.
$settings['rebuild_access'] = TRUE;

// Don't chmod the sites subdirectory.
$settings['skip_permissions_hardening'] = TRUE;

// Use the 'development' environment for Config Split.
$config['config_split.config_split.development']['status'] = TRUE;

// Hide Acquia Connector messages.
$config['acquia_connector.settings']['hide_signup_messages'] = TRUE;

// TODO: Use this section if the project uses Solr.
// 1. Configure a search server called 'local_search_server'
// 2. Update this line to assign your index to the local server.
# $config['search_api.index.YOUR_INDEX']['server'] = 'local_search_server';
