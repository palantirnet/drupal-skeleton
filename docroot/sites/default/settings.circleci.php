<?php

/**
 * @file
 * Settings for running the project on Circle CI.
 */

$databases['default']['default'] = array(
  'database' => 'circle_test',
  'username' => 'root',
  'password' => '',
  'host' => '127.0.0.1',
  'driver' => 'mysql',
  'port' => '3306',
  'prefix' => '',
);

// Allow any host name.
$settings['trusted_host_patterns'] = ['.*'];

// Enable assertions.
// @see http://php.net/assert
// @see https://www.drupal.org/node/2492225
use Drupal\Component\Assertion\Handle;

assert_options(ASSERT_ACTIVE, TRUE);
Handle::register();

// Show all error messages, with backtrace information.
$config['system.logging']['error_level'] = 'verbose';

// Allow test modules and themes to be installed.
$settings['extension_discovery_scan_tests'] = FALSE;

// Don't chmod the sites subdirectory.
$settings['skip_permissions_hardening'] = TRUE;

// Use the 'development' environment for Config Split.
$config['config_split.config_split.development']['status'] = TRUE;

// Hide Acquia Connector messages.
$config['acquia_connector.settings']['hide_signup_messages'] = TRUE;
