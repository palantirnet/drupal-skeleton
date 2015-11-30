# { Your Project Here. }
## { Some lengthier description of the project. }

## Requirements

* [virtualBox](https://www.virtualbox.org/wiki/Downloads) >= 4.3.x
* [vagrant](http://downloads.vagrantup.com/) >= 1.7.x
* [vagrant-hostmanager](https://github.com/smdahlen/vagrant-hostmanager) `vagrant plugin install vagrant-hostmanager`
* [vagrant-auto_network](https://github.com/oscar-stack/vagrant-auto_network) `vagrant plugin install vagrant-auto_network`

If you have been running a previous version of Vagrant you may need to do: `vagrant plugin update` to ensure that you can install the plugins.

* Make sure your `cnf` directory is writable.

## Getting Started

1. From inside the project root, run `vagrant up`
2. You will be prompted for the administration password on your host machine. Obey.
3. Visit [skeleton.local](http://skeleton.local) in your browser of choice.

## How do I work on this?

1. From inside the project root, type `vagrant ssh`
2. Navigate to `/var/www/sites/skeleton.local`

This is your project directory; run `composer` commands from here, and `drush` commands from the `www` directory. If you want to use git from here, make sure you configure your name and email for proper attribution:

```
git config --global user.email 'me@palantir.net'
git config --global user.name 'My Name'
```

## How do I Drupal?

### The Drupal root

This project uses [Rootcanal](https://github.com/craychee/rootcanal) to assemble our Drupal root in `www`. On your development environment, Rootcanal will link modules and themes from `modules/custom/*`, `themes/*`, and `vendor/*`, and seed module files from the project root.

For production, `bin/rootcanal --prod` copies modules, themes, etc. into `www`, creating an artifact that we can check in to our host's git repository.

### Using drush

* Run `drush` commands from the `www` directory.

### Installing and reinstalling Drupal

Run `composer install --prefer-dist && bin/project/install.sh`

### Adding modules

* Download the module with `composer require drupal/bad_judgement`
* Add the module as a dependency in `skeleton.info`
* To enable the module, run `bin/project/update.sh` (or `cd www; drush en bad_judgement`)
* Commit the changes to `composer.json`, `composer.lock`, and `skeleton.info`

### Patching modules

Sometimes we need to apply patches from the Drupal.org issue queues. These patches should be applied using composer using the [Composer Patches](https://github.com/cweagans/composer-patches) composer plugin.

* Add the Composer Patches package to the project: `composer require cweagans/composer-patches`
* Add the patch to the `composer.json` file per the [Composer Patches instructions](https://github.com/cweagans/composer-patches); include a link to the source of the patch (ie, the Drupal.org issue) if applicable
* To apply the patch, run `composer install --prefer-dist`
* Commit your changes to `composer.json` and `composer.lock`

### Configuring Drupal

Sometimes it is appropriate to configure specific Drupal variables in Drupal's `settings.php` file. Our `settings.php` file is built from `cnf/config.yml` using the [Drupal Settings Compile](https://github.com/winmillwill/settings_compile) package.

* Add your local values to `cnf/config.yml`
* Run `bin/settings_compile cnf/config.yml cnf/settings.php`
* Test the settings
* Add the variable's default, vagrant-appropriate value to `cnf/config.dist.yml`
* Add the variable's Circle value to `cnf/config.circle.yml`
* Commit your changes to `cnf/config.dist.yml` and `cnf/config.circle.yml`

## How do I run tests?

### Behat

To run all of the tests:

```
bin/behat
```

You may also run scenarios from a single `.feature` file:

```
bin/behat features/my-special-tests.feature
```

Or run scenarios with a specific tag:

```
bin/behat --tags=@testme
```

## Troubleshooting

* If you get the error `Command: ["hostonlyif", "create"]`, you need to restart VirtualBox.

```
sudo /Library/Application\ Support/VirtualBox/LaunchDaemons/VirtualBoxStartup.sh restart
```

* If you vagrant up and note that there are no files in your /var/www/sites/SITE, it is very likely that your NFS did not mount correctly. The solution is to halt the box, remove your exports file, and reboot the box. If this was not the problem, no harm will be done.

```
vagrant halt;
sudo rm /etc/exports;
vagrant up;
```
