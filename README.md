# Drupal Skeleton

This is a template for starting Drupal 8 projects using the `composer create-project` command.

## Quick start

This "quick start" section will show you how to set up a local server accessible at `http://example.local` with Drupal ready to install.

### Preface

You should have the development dependencies installed on your Mac before you begin. These dependencies are not project-specific, and you may have some or all of them already installed. If you don't, find a location with good internet and set aside at least an hour to complete this step.

The development dependencies are:

* PHP 7
  * Check your PHP version from the command line using `php --version`
* [XCode](https://itunes.apple.com/us/app/xcode/id497799835?mt=12)
* [Composer](https://getcomposer.org/download/)
* [Ansible](http://docs.ansible.com/ansible/latest/installation_guide/intro_installation.html)
  * We recommend installing with [homebrew](https://brew.sh/): `brew install ansible`
* [Vagrant](https://www.vagrantup.com/downloads.html)
* [VirtualBox](https://www.virtualbox.org/wiki/Downloads)
* Vagrant plugins: [hostmanager](https://github.com/devopsgroup-io/vagrant-hostmanager) and [auto_network](https://github.com/oscar-stack/vagrant-auto_network)
  * Install both with this command: `vagrant plugin install vagrant-hostmanager vagrant-auto_network`

Once you have your dependencies installed, setting up this skeleton will take at least another hour, depending on your internet connection.

Some of the commands below will prompt you for a response, with the default answer in brackets. For this quick start, hit return to accept each default answer:

```
Enter a short name for your project [example] :
```

### Steps

1. Create a new project called "example" based on this template:

  ```
  composer create-project palantirnet/drupal-skeleton example dev-develop --no-interaction
  ```
2. Go into your new project directory and run the script from `palantirnet/the-vagrant` to set up a Vagrant environment:

  ```
  cd example
  vendor/bin/the-vagrant-installer
  ```
  
3. From inside the VM, run the script from `palantirnet/the-build` to set up the default Drupal variables and install Drupal:

  ```
  vagrant up
  vagrant ssh
  vendor/bin/the-build-installer
  ```

5. In your web browser, visit [http://example.local](http://example.local) -- if you type in this URL, you will need to include the `http://` portion for your browser find the site.
6. _Optional:_ While you are logged into the Vagrant environment, you can run Drush commands like `drush status`.

### Extra Credit

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
composer create-project palantirnet/drupal-skeleton PROJECTNAME dev-develop --no-interaction
```

### Update your documentation

Update the `README`:

  * Remove the `README.md`
  * Rename the `README.dist.md` to `README.md`
  * Edit as you like

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

### Run the installers

Go into your new project directory and run the script from `palantirnet/the-vagrant` to set up a Vagrant environment:

  ```
  cd PROJECTNAME
  vendor/bin/the-vagrant-installer
  ```

From inside the VM, run the script from `palantirnet/the-build` to set up a base Drupal installation:

  ```
  vagrant up
  vagrant ssh
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

If you've run `vendor/bin/the-build-installer` from within the VM, Drupal will be installed and the initial config exported to `config/sites/default/`.

You can use the phing scripts provided by `palantirnet/the-build` to reinstall the site from config at any time:

```
vendor/bin/phing install
```

Or, you can use drush directly:

```
drush site-install --existing-config
```

### Manage your configuration in code

In Drupal 8 development, all (or most) Drupal configuration should be exported and treated as part of the codebase. On top of this core process, we use the [config_installer profile](https://www.drupal.org/project/config_installer) to allow us to use the exported configuration as the basis for a repeatable, automation-friendly build and install process. We also use [config_split](https://www.drupal.org/project/config_split) to manage environment-specific configuration.

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

3. You should have a ton of new `*.yml` files in `config/sites/default/`. Add them, and this config change, to git:

  ```
  git add config/
  git ci -m "Initial Drupal configuration."
  git push
  ```
5. Reinstall your site and verify that your config is ready to go:

  ```
  vendor/bin/phing install
  ```

## More information

* Site build and install process: [palantirnet/the-build](https://github.com/palantirnet/the-build)
* Development environment setup: [palantirnet/the-vagrant](https://github.com/palantirnet/the-vagrant)

----
Copyright 2016, 2017, 2018, 2019 Palantir.net, Inc.
