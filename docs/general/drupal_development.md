# Drupal Development



From outside the VM, you can find the files for:

* The custom theme at `web/themes/custom/`
* Custom modules at `web/modules/custom/`
* Drupal contrib dependencies, managed by composer, at `web/modules/contrib/`
* Library dependencies, managed by composer, at `vendor/`
* Drupal config YAML files at:
    * `config/sites/default` for config which is shared across all environments
    * `config/config_split/<environment>` for environment-specific config (see "Configure Drupal" below)
* Template files for Drupal `settings.php` at `conf/drupal/settings.php` and `conf/drupal/settings.<hosting-platform>.php`
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

### Config Split

This project uses [config_split](https://www.drupal.org/project/config_split) to set different configuration for different environments. For example, by default, devel is enabled for the local development environment.
 
Existing splits are defined by a YML file at `config/sites/default/config_split.config_split.<environment>.yml`, and the environment-specific configuration files exist inside the environment split directory (`config/config_split/<environment>`). 

#### Exporting shared configuration for all environments

1. Make any changes in the Drupal UI or via drush as usual
1. Use drush to export the shared configuration to `config/sites/default`

#### Exporting environment-specific config

1. If you're exporting configuration for an environment other than development, you'll need to enable the split for that environment locally.  
    1. See your `settings.php` file around line #30, replace "development" with the environment your configuring (either "staging" or "production").
    1. Run `drush cr` then `drush cim -y` to get a clean environment.
    1. You should now notice that your local development modules are disabled, and any other environment-specific modules (i.e. purge) are now enabled.
    1. Visit `/admin/config/development/configuration/config-split` to see that your environment's split is active.
1. Make any changes you'd like in the Drupal UI, as usual.
1. _Preview_ your development only configuration changes by running `drush cex` (but don't hit 'y').
1. Visit the `/admin/config/development/configuration/config-split` for the environment you're configuring.
1. Add _either_ the whole module or specific config files that you'd like to export specifically for this environment in the ["Blacklist"](https://www.drupal.org/docs/8/modules/configuration-split/blacklist). (Be sure not to overwrite other options in the list.) Then save the form.
    * The term "Blacklist" is a little confusing here, but think of it as "blacklisting" config from the global directory (and "whitelisting" it into the specific environment).
    * The "Greylist" is used to target a configuration object having a different value than that for the shared configuration. (i.e. Error logging level set to show everything locally, but show nothing everywhere else.)
    * For more details on Config Split, [read the docs](https://www.drupal.org/docs/8/modules/configuration-split).
1. Run `drush cex -y` to export the environment-specific config.
1. Carefully review the changes in git before pushing. Be sure not to commit any changes you weren't expecting.
1. If your config should apply to multiple environments (i.e. "staging" and "production", you'll need to repeat this process for those environments
1. Change your `settings.php` back to "development" when finished

#### Importing development-only config

* Building the site from scratch
    1. Once you have build your site from your current branch's configuration, run `drush cim -y` to import the development-only configuration
* Building from production data
    1. Once you have built your site, run `drush cim -y` to import the shared configuration from your current branch
    1. Run `drush cim -y` a second time to import the development-only configuration 

### Setting specific config variables

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
