# Drupal Skeleton

This is a template for starting Drupal projects using the `composer create-project` command.

## Quick start

This "quick start" section will show you how to set up a local server accessible at `http://example.local` with Drupal ready to install.

### Preface

You should have the development dependencies installed on your Mac before you begin. These dependencies are not project-specific, and you may have some or all of them already installed. If you don't, find a location with good internet and set aside at least an hour to complete this step.

The development dependencies are:

* PHP 8.1+
  * Check your PHP version from the command line using `php --version`
* Mac only: [XCode command line tools](https://mac.install.guide/commandlinetools/3)
* [Composer](https://getcomposer.org/download/)
* [Docker](https://www.docker.com/)
* [DDev Local](https://ddev.com/ddev-local/)

On Macs, Docker and ddev can be installed with [homebrew](https://brew.sh/):
  * `brew install docker --cask`
  * `brew install ddev`

Once you have your dependencies installed, setting up this skeleton will take at least another hour, depending on your internet connection.

Some of the commands below will prompt you for a response, with the default answer in brackets. For this quick start, hit return to accept each default answer:

```
Enter a short name for your project [example] :
```

### Steps

1. Create a new Drupal 10 project called "example" based on this template:

    ```
    composer create-project palantirnet/drupal-skeleton example dev-develop --no-interaction
    ```
   
1. Go into your new project directory and update the ddev configuration in `.ddev/config.yml`:

    ```
    # Update to match your project name. Using "drupal-skeleton" would make the site
    # accessible at 'drupal-skeleton.ddev.site'.
    name: drupal-skeleton
  
    # Use 'docroot' for Acquia, or 'web' for Pantheon or Platform.sh.
    docroot: web
    ```
   
1. From inside the ddev environment, run the script from `palantirnet/the-build` to set up the default Drupal variables and install Drupal:

    ```
    ddev start
    ddev ssh
    vendor/bin/the-build-installer
    ```
   
1. In your web browser, visit [http://example.ddev.site](http://example.ddev.site)

1. _Optional:_ While you are logged into the ddev environment, you can run Drush commands like `drush status`.

### Extra Credit

* Replace this `README.md` with `README.dist.md`, then customize for your project
* Update the project name in the `composer.json` file, then run `composer update --lock`
* Initialize a git repository and commit your work
* Access your database via phpMyAdmin
  * URL: [https://example.ddev.site:8037](https://example.ddev.site:8037)
  * Username: `drupal`
  * Password: `drupal`
* View email sent by your development site at [https://example.ddev.site:8026](https://example.ddev.site:8026)
* Connect to your Solr server in Drupal with the `search_api_solr` module:
  * HTTP Protocol: `http`
  * Solr host: `solr`
  * Solr port: `8983`
  * Solr path: `/`
  * Solr core: `dev`
  * View the Solr admin interface: [https://example.ddev.site:8983](https://example.ddev.site:8983)

*Note: Make sure your project directory is in the right place / named correctly, because renaming or moving the project directory (e.g. `example/`) after starting can break your ddev setup.*

## Full Project Setup

### Create a project with a custom name

Use composer to create a new project based on this skeleton, replacing `PROJECTNAME` with the short name for your project:

```
composer create-project palantirnet/drupal-skeleton PROJECTNAME dev-develop --no-interaction
```

### Update your documentation

Update the `README`:

  * Remove the `README.md`
  * Rename the `README.dist.md` to `README.md`
  * Edit as you like

Project-specific documentation at [docs/technical_approach.md](docs/technical_approach.md)

  * Add `deployment.md` for deployment instructions  

Update the `LICENSE.txt`:

  * Remove or replace this file with the appropriate license for your project. (The existing license applies to the Drupal Skeleton template only.)

Update the `composer.json`:

  * Change the `name` from `palantirnet/drupal-skeleton` to `palantirnet/PROJECTNAME`
  * Update the `description` with a brief description of your project.
  * Update the `license` property based on how your work will be licensed
  * Update the lock file so composer doesn't complain:

  ```
    composer update --lock
  ```

Update the `package.json`:

* Change the `name` from `palantirnet--drupal-skeleton` to `palantirnet--PROJECTNAME` (see [package.json name requirements](https://docs.npmjs.com/cli/v8/configuring-npm/package-json#name))
* Update the `description` with a brief description of your project.

### Configure your ddev development environment

Go into your new project directory and update the ddev configuration in `.ddev/config.yml`.

### Run the installers

From inside ddev, run the script from `palantirnet/the-build` to set up a base Drupal installation:

  ```
  ddev start
  ddev ssh
  vendor/bin/the-build-installer
  ```

### Commit your work to git

Initialize a git repository and commit your work to the `develop` branch:

```
git init
git checkout -b develop
git commit --allow-empty -m "Initial commit."
git add --all
git commit -m "Add the skeleton."
```

Create an empty repository on [GitHub](https://github.com/) for your work. Then, push your work up to the repository:

```
git remote add origin git@github.com:palantirnet/PROJECTNAME.git
git push -u origin develop
```

### Manage your ddev environment

* Start ddev: `ddev start`
* Log in: `ddev ssh`
* Log out (just like you would from any other ssh session): `exit`
* Shut down ddev: `ddev stop`
* Find information about your ddev environment: `ddev describe`
* See also the [ddev documentation](https://ddev.readthedocs.io/en/stable/)

### Replace "the-build" with [something else]

If you're allergic to phing and Benadryl isn't helping, you can also remove the-build:

1. `composer remove --dev palantirnet/the-build`
1. `rm -r .the-build`
1. `rm build.xml`
1. This will remove drush, coder, and phpmd -- if you want those dependencies, you'll need to add them back to your project:

    ```
    composer require --dev drush/drush drupal/coder phpmd/phpmd
    ```

1. Install your build tooling of choice... or nothing at all...
1. _Update your project's README_

See also: [Documentation on using the-build](https://github.com/palantirnet/the-build#using-the-build)

### Install Drupal from the command line

When using [drush](https://www.drush.org/) or [phing](https://www.phing.info/) to manage your Drupal site, you will need to log into the ddev environment (`ddev ssh`).

If you've run `vendor/bin/the-build-installer` from within ddev, Drupal will be installed and the initial config exported to `config/sites/default/`.

You can use the phing scripts provided by `palantirnet/the-build` to reinstall the site from config at any time:

```
vendor/bin/phing install
```

Or, you can use drush directly:

```
drush site-install --existing-config
```

### Manage your configuration in code

In Drupal development, all (or most) Drupal configuration should be exported and treated as part of the codebase. We use Drupal's "install from config" installer option to allow us to use the exported configuration as the basis for a repeatable, automation-friendly build and install process. We also use [config_split](https://www.drupal.org/project/config_split) to manage environment-specific configuration.

1. Log into Drupal in your browser and do some basic config customizations:
    * Set the site timezone
    * Disable per-user timezones
    * Disable user account creation
    * Remove unnecessary content types
    * Set the admin email address (your development environment will trap all emails)
    * Turn the Cron interval down to "never"
    * Uninstall unnecessary modules (e.g. Search, History, Comment)
1. Export your config:

    ```
    drush cex -y
    ```

1. You should have a ton of new `*.yml` files in `config/sites/default/`. Add them, and this config change, to git:

      ```
      git add config/
      git ci -m "Initial Drupal configuration."
      git push
      ```
1. Reinstall your site and verify that your config is ready to go:

      ```
      vendor/bin/phing install
      ```

## More information

* Site build and install process: [palantirnet/the-build](https://github.com/palantirnet/the-build)

----
Copyright 2016 - 2021 Palantir.net, Inc.
