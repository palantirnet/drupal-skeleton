<?php

// @codingStandardsIgnoreFile

/**
 * @file
 * Drupal site-specific configuration file.
 *
 * @see default.settings.php for documentation.
 */

/**
 * These settings should be set in hosting-specific include files:
 * - Databases
 * - Trusted host patterns
 * - Private files path
 */

// Database credentials.
$databases = [];

// An array of regular expressions.
$settings['trusted_host_patterns'] = [];

// An absolute path on the filesystem to a directory outside of the web root.
$settings['file_private_path'] = '';

/**
 * Salt for one-time login links, cancel links, form tokens, etc.
 */
$settings['hash_salt'] = file_get_contents(DRUPAL_ROOT . '/../config/salt.txt');

/**
 * Access control for update.php script.
 */
$settings['update_free_access'] = FALSE;

/**
 * Public file path, relative to the Drupal installation directory.
 */
$settings['file_public_path'] = 'sites/default/files';

/**
 * Load services definition file.
 */
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

/**
 * The default list of directories that will be ignored by Drupal's file API.
 */
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

/**
 * The default number of entities to update in a batch process.
 */
$settings['entity_update_batch_size'] = 50;

/**
 * Entity update backup.
 */
$settings['entity_update_backup'] = TRUE;

/**
 * Node migration type.
 */
$settings['migrate_node_migrate_type_classic'] = FALSE;


// Automatically generated include for settings managed by ddev.
if (getenv('IS_DDEV_PROJECT') == 'true' && file_exists(__DIR__ . '/settings.ddev.php')) {
  include __DIR__ . '/settings.ddev.php';
  include __DIR__ . '/settings.ddev-overrides.php';
}

// Circle CI
if (getenv('CIRCLECI') == 'true' && file_exists(__DIR__ . '/settings.circleci.php')) {
  include __DIR__ . '/settings.circleci.php';
}

// Acquia
if (getenv('AH_SITE_GROUP') && file_exists(__DIR__ . '/settings.acquia.php')) {
  include __DIR__ . '/settings.acquia-custom.php';
  include __DIR__ . '/settings.acquia.php';
}

// Pantheon
if (getenv('PANTHEON_ENVIRONMENT') && file_exists(__DIR__ . '/settings.pantheon.php')) {
  include __DIR__ . '/settings.pantheon-custom.php';
  include __DIR__ . '/settings.pantheon.php';
}

// Platform.sh
if (getenv('PLATFORM_APPLICATION') && file_exists(__DIR__ . '/settings.platform.php')) {
  include __DIR__ . '/settings.platform-custom.php';
  include __DIR__ . '/settings.platform.php';
}

/**
 * Location of the site configuration files.
 *
 * This is set last because some hosts (Acquia) override this in their host-specific
 * configuration code.
 */
$settings['config_sync_directory'] = DRUPAL_ROOT . '/../config/sites/default/';

/**
 * Load local development override configuration, if available.
 *
 * Create a settings.local.php file to override variables on secondary (staging,
 * development, etc.) installations of this site.
 *
 * Typical uses of settings.local.php include:
 * - Disabling caching.
 * - Disabling JavaScript/CSS compression.
 * - Rerouting outgoing emails.
 *
 * Keep this code block at the end of this file to take full effect.
 */

if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
