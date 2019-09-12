# Drupal Skeleton

This is a template for starting Drupal 8 projects using the `composer create-project` command.

## Quick start

This "quick start" section will show you how to set up a local server accessible at `http://example.ddev.site` with Drupal ready to install.

### Preface

You should have the development dependencies installed on your Mac before you begin. These dependencies are not project-specific, and you may have some or all of them already installed. If you don't, find a location with good internet and set aside at least an hour to complete this step.

The development dependencies are:

* PHP 7
  * Check your PHP version from the command line using `php --version`
* [XCode](https://itunes.apple.com/us/app/xcode/id497799835?mt=12)
* [Composer](https://getcomposer.org/download/)

### **DDEV**

* [Docker](https://ddev.readthedocs.io/en/stable/users/docker_installation/)
  * We recommend installing with [homebrew](https://brew.sh/): `brew cask install docker`
* [DDEV](https://ddev.readthedocs.io/en/stable/#installation)
  * We recommend installing with [homebrew](https://brew.sh/): `brew tap drud/ddev && brew install ddev`
* [NFS](https://ddev.readthedocs.io/en/stable/users/performance/#macos-nfs-setup)
  * [Download & run this script](https://raw.githubusercontent.com/drud/ddev/master/scripts/macos_ddev_nfs_setup.sh)

Once you have your dependencies installed, setting up this skeleton will take at least another hour, depending on your internet connection.

Some of the commands below will prompt you for a response, with the default answer in brackets. For this quick start, hit return to accept each default answer:

```
Enter a short name for your project [example] :
```

### Steps

1. Create a new project called "example" based on this template:

  ```
  composer create-project palantirnet/drupal-skeleton example dev-drupal8 --no-interaction
  ```

3. From your host machine, run the script from `palantirnet/the-build` to set up the default Drupal variables:

  ```
  vendor/bin/the-build-installer
  ```

4. Use the phing script installed by `palantirnet/the-build` to create the `settings.php` file for Drupal from within the VM.

  ```
  ddev config --nfs-mount-enabled=true
  ddev start
  vendor/bin/phing build
  ```
5. In your web browser, visit [https://example.ddev.site](https://example.ddev.site) -- if you type in this URL, you will need to include the `http://` portion for your browser find the site.
6. You should see the Drupal installer screen here. Follow the instructions to complete the installation.
7. _Optional:_ You can run Drush commands, like `ddev . drush status`.

### Extra Credit

* Running drush commands (`ddev . drush status`)
* Importing a new database (`ddev import-db --src=<database_file.tar.gz>`)
* Log into docker image (`ddev ssh`) and export the Drupal configuration (`drush config-export`)
* Update the `README.md` based on the contents of `README.dist.md`
* Update the project name in the `composer.json` file, then run `composer update --lock`
* Initialize a git repository and commit your work
* Access your database via phpMyAdmin at [http://example.local/phpmyadmin](http://example.local/phpmyadmin) using the username `drupal` and the password `drupal`
* View email sent by your development site at [http://example.local:8025](http://example.local:8025)
* View your Solr 4.5 server at [http://example.local:8983/solr](http://example.local:8983/solr)
* Note that renaming or moving this `example/` directory can break your Vagrant machine

## Full Project Setup

### Create a project with a custom name

Use composer to create a new project based on this skeleton, replacing `PROJECTNAME` with the short name for your project:

```
composer create-project palantirnet/drupal-skeleton PROJECTNAME dev-drupal8 --no-interaction
```

### Update your documentation

Update the `README`:

  * Remove the `README.md`
  * Rename the `README.dist.md` to `README.md`
  * Edit as you like

Update the `LICENSE.txt`:

  * Keep the current license if you're planning to use the MIT license
  * Remove or replace this file for any other license

Update the `composer.json`:

  * Change the `name` from `palantirnet/drupal-skeleton` to `palantirnet/PROJECTNAME`
  * Update the `description` with a brief description of your project.
  * Update the `license` property based on how your work will be licensed
  * Update the lock file so composer doesn't complain:

  ```
    composer update --lock
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

### Manage your Vagrant VM

* Start or wake up your Vagrant VM: `vagrant up`
* Log in: `vagrant ssh`
* Log out (just like you would from any other ssh session): `exit`
* Shut down the VM: `vagrant halt`
* Check whether your VM is up or not: `vagrant status`
* More information about this Vagrant setup is available at [palantirnet/the-vagrant](https://github.com/palantirnet/the-vagrant)
* See also the [official Vagrant documentation](https://www.vagrantup.com/docs/index.html)

### Install Drupal from the command line

When using [drush](https://www.drush.org/) or [phing](https://www.phing.info/) to manage your Drupal site, you will need to log into the vagrant box (`vagrant ssh`).

You can use the phing scripts provided by `palantirnet/the-build`:

```
vendor/bin/phing build install
```

Or, you can use drush directly:

```
drush site-install
```

### Manage your configuration in code

Drupal 8 supports exporting all of your configuration. On top of this core process, we use the [config_installer profile](https://www.drupal.org/project/config_installer) to allow us to use the exported configuration as the basis for a repeatable, automation-friendly build and install process.

1. Log into Drupal in your browser and do some basic config customizations:

  * Set the site timezone
  * Disable per-user timezones
  * Disable user account creation
  * Remove unnecessary content types
  * Set the admin email address (your VM will trap all emails)
  * Turn the Cron interval down to "never"
  * Uninstall unnecessary modules (e.g. Search, History, Comment)
2. Export your config:

  ```
  drush cex -y
  ```
3. Update the install profile in your default build properties (`conf/build.default.properties`):

  ```
  drupal.install_profile=config_installer
  ```
4. You should have a ton of new `*.yml` files in `conf/drupal/config`. Add them, and this config change, to git:

  ```
  git add conf/
  git ci -m "Initial Drupal configuration."
  git push
  ```
5. Reinstall your site and verify that your config is ready to go:

  ```
  vendor/bin/phing build install
  ```

## More information

* Site build and install process: [palantirnet/the-build](https://github.com/palantirnet/the-build)
* Development environment setup: [palantirnet/the-vagrant](https://github.com/palantirnet/the-vagrant)

----
Copyright 2016, 2017, 2018 Palantir.net, Inc.
