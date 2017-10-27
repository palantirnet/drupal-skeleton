# Drupal Development



From outside the VM, you can find the files for:

* The custom theme at `web/themes/custom/`
* Custom modules at `web/modules/custom/`
* Drupal contrib dependencies, managed by composer, at `web/modules/contrib/`
* Library dependencies, managed by composer, at `vendor/`
* Drupal config YAML files at `conf/drupal/config/`
* Template files for Drupal `settings.php` at `conf/drupal/settings.php` and `conf/drupal/settings.acquia.php`
* Behat tests at `features/`

From within the VM:

* Run `drush` commands from anywhere within the repository
* Add a module using `composer require drupal/new_module`
* Update a module using `composer update drupal/existing_module`
* Clear your Drupal caches with `drush cr`
* Rebuild the CSS with `phing build`
* Reinstall the site with `phing build install migrate`
* Get a fresh copy of the database from Acquia stage with `drush sql-sync @yoursite.test @self`

## The Drupal root

This project uses [Composer Installers](https://github.com/composer/installers), [drupal-scaffold](https://github.com/drupal-composer/drupal-scaffold), and [palantirnet/the-build](https://github.com/palantirnet/the-build) to assemble our Drupal root in `web`. Dig into `web` to find the both contrib Drupal code (installed by composer) and custom Drupal code (included in the git repository).

## Add modules

Drupal contrib dependencies are managed with composer, and are not checked directly into this repository. To add a module, ssh into your VM, then:

1. Download the module with composer: `composer require drupal/bad_judgement:~8.1`
2. Enable the module with drush: `drush en bad_judgement`
3. Visit your local site and configure the module as necessary
4. Export the config with the module enabled: `drush config-export`
5. Commit the changes to `composer.json`, `composer.lock`, and `conf/drupal/config/*`.

Note that the module code itself will be excluded by the project's `.gitignore`; Composer will manage downloading and installing the module code for other developers when they run `composer install`.

## Patch modules

Patches from the Drupal.org issue queues should be applied using composer using the [Composer Patches](https://github.com/cweagans/composer-patches) composer plugin.


## Configure Drupal

Drupal config YAML files live at `conf/drupal/config/`. Import the default config with `drush config-import`; export your changes from Drupal with `drush config-export`.

Some specific config variables are managed on a per-environment basis using `settings.php` templating, which is part of the `phing build` step. See:

* `conf/drupal/settings.php`: The template used for the VM and CircleCI environments
* `conf/drupal/settings.acquia.php`: The template used for the Acquia environment
* `conf/build.default.properties`: Properties used by `phing` targets
* `conf/build.circle.properties`: Property overrides used when running `phing` commands on CircleCI
* `conf/build.acquia.properties`: Property overrides used when running the `phing` command to deploy to Acquia

When you make changes to these files, you will generally need to run `phing build` in order to see your changes.

### Test Drupal

This project uses Behat to test Drupal; it also provides some PHP linting tools. You can run:

* All Behat tests: `behat`
* One behat test: `behat features/installation.feature`
* The PHP code review: `phing code-review`

----
Copyright 2017 Palantir.net, Inc.
