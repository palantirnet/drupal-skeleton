<?php
/**
 * @file
 * Provide Behat step-definitions involving users.
 *
 * @copyright (C) Copyright 2014 Palantir.net, Inc.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Behat\Behat\Context\BehatContext;
use Behat\Behat\Context\ContextInterface;
use Behat\Behat\Context\Step\Given;
use Behat\Behat\Context\Step\Then;
use Behat\Behat\Context\Step\When;
use Drupal\DrupalExtension\Context\DrupalContext;

class MigrationSubContext extends BehatContext implements DrupalSubContextInterface {
  /**
   * An array of all migrations that have been imported.
   *
   * @var array
   */
  protected $imports = array();

  /**
   * Initializes context.
   *
   * @param array $parameters
   *   context parameters (set them up through behat.yml)
   */
  public function __construct(array $parameters = array()) {

  }

  /**
   * Return a unique alias for this sub-context.
   *
   * @return string
   */
  public static function getAlias() {
    return 'migration';
  }

  /**
   * Get Mink session from MinkContext
   */
  public function getSession($name = NULL) {
    return $this->getMainContext()->getSession($name);
  }

  /**
   * Rollback migrations after completing all steps of the migration scenario.
   *
   */
  public function rollbackMigration($event)
  {
    $names = $this->imports;
    foreach ($names as $name) {
      $migration = Migration::getInstance($name);
      $migration->processRollback();
      $migration::deregisterMigration($name);
    }
  }
  /**
   * Run migrations.
   *
   * @Given /^I run "([^"]*)" migration$/
   *
   * @param string $name
   *   Name of the migration to be instantiated and processed.
   *
   * @throws \Exception if the migration does not exist.
   */
  public function iRunMigration($name)
  {
    // Ensure modules that are using the migrate api are using the version
    // that is used in the project.
    migrate_get_module_apis(TRUE);
    $migration = Migration::getInstance($name);
    if ($migration == NULL) {
      $message = sprintf('The "%s" migration does not exist.', $name);
      throw new \Exception($message);
    }
    $migration->processImport();
    $this->imports[] = $name;
  }

  /**
   * Asserts the given migration is complete.
   *
   * @Then /^the "([^"]*)" migration is complete$/
   *
   * @throws /Exception if the migration did not complete.
   */
  public function assertMigrationIsComplete($name)
  {
    $migration = Migration::getInstance($name);
    if ($migration->isComplete()) {
      return TRUE;
    }
    $message = sprintf('The "%s" migration is not complete.', $name);
    throw new \Exception($message);
  }
}
