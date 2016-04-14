# { Your Project Here. }
## { Some lengthier description of the project. }

## Requirements

* VMWare, or [virtualBox](https://www.virtualbox.org/wiki/Downloads) >= 5.0
* [vagrant](http://downloads.vagrantup.com/) >= 1.8
* [ansible](https://github.com/ansible/ansible) `brew install ansible`
* [vagrant-hostmanager](https://github.com/smdahlen/vagrant-hostmanager) `vagrant plugin install vagrant-hostmanager`
* [vagrant-auto_network](https://github.com/oscar-stack/vagrant-auto_network) `vagrant plugin install vagrant-auto_network`

If you have been running a previous version of Vagrant you may need to do: `vagrant plugin update` to ensure that you can install the plugins.

## Getting Started

1. From inside the project root, run:
 * `composer install`
 * `vagrant up`
2. You will be prompted for the administration password on your host machine. Obey.
3. Visit [skeleton.local](http://skeleton.local) in your browser of choice. (You will probably get an error. SSH in and build the site now)

## How do I work on this?

1. From inside the project root, type `vagrant ssh`
2. Navigate to `/var/www/skeleton.local`
3. Build and install: `vendor/bin/phing build drupal-install -Dbuild.env=vagrant`

This is your project directory; run `composer` commands from here, and use drush with `vendor/bin/drush -c conf/drushrc.php`. Avoid using git from here, but if you must, make sure you configure your name and email for proper attribution, and [configure your global .gitignore](https://github.com/palantirnet/development_documentation/blob/master/guidelines/git/gitignore.md):

```
git config --global user.email 'me@palantir.net'
git config --global user.name 'My Name'
```

## How do I Drupal?

### The Drupal root

This project uses [Composer Installers](https://github.com/composer/installers), [DrupalScaffold](https://github.com/drupal-composer/drupal-scaffold), and [the-build](https://github.com/palantirnet/the-build) to assemble our Drupal root in `web`. Dig into `web` to find the both contrib Drupal code (installed by composer) and custom Drupal code (included in the git repository).

### Using drush

* Run `drush` commands using `vendor/bin/drush -c conf/drushrc.php`

### Installing and reinstalling Drupal

Run `composer install && vendor/bin/phing build drupal-install -Dbuild.env=vagrant`

### Adding modules

* Download the module with `composer require drupal/bad_judgement`
* Add the module as a dependency somewhere
* Commit the changes to `composer.json`, `composer.lock`, and wherever else. The module code itself should be excluded by the project's `.gitignore`.

### Patching modules

Sometimes we need to apply patches from the Drupal.org issue queues. These patches should be applied using composer using the [Composer Patches](https://github.com/cweagans/composer-patches) composer plugin.

### Configuring Drupal

Sometimes it is appropriate to configure specific Drupal variables in Drupal's `settings.php` file. Our `settings.php` file is built from a template found at `conf/drupal/settings.php` during the phing build.

* Add your appropriately named values to `conf/build.default.properties` (like `drupal.my_setting=example`)
* Update `conf/drupal/settings.php` to use your new variable (like `$conf['my_setting'] = '@drupal.my_setting@';`)
* Run `vendor/bin/phing build`
* Test
* If the variable requires different values in different environments, add those to the appropriate properties files (`conf/build.vagrant.properties`, `conf/build.circle.properties`, `conf/build.acquia.properties`). Note that you may reference environment variables with `drupal.my_setting=${env.DRUPAL_MY_SETTING}`.
* Finally, commit your changes.

## How do I run tests?

### Behat

Well, there's no behat yet. If there were, you could run all of the tests with:

```
vendor/bin/behat
```

## Troubleshooting

If, on browsing to `http://drupal-skeleton.local`, you get the following error:
> mcor.local’s server DNS address could not be found.

Then `vagrant up` may have failed half way through. When this happens, the `vagrant-hostmanager` plugin does not add the hostname to `/etc/hosts`. Try halting and re-upping the machine: `vagrant halt && vagrant up`. Reload is not sufficient to trigger updating the hosts file.
