# { Your Project Name }

This is the development repository for { your project's } Drupal website. It contains the codebase and an environment to run the site for development.

## Table of Contents

* [Development Environment](#development-environment)
* [Getting Started](#getting-started)
* [How do I work on this?](#how-do-i-work-on-this)
* [Drupal Development](#drupal-development)
* [Styleguide Development](#styleguide-development)
* [Deployment](#Deployment)
* [Additional Documentation](#additional-documentation)

## Development Environment

The development environment is based on [palantirnet/the-vagrant](https://github.com/palantirnet/the-vagrant). To run the environment, you will need:

* Mac OS X >= 10.13. _This stack may run under other host operating systems, but is not regularly tested. For details on installing these dependencies on your Mac, see our [Mac setup doc [internal]](https://github.com/palantirnet/documentation/wiki/Mac-Setup)._
* [Composer](https://getcomposer.org)
* [virtualBox](https://www.virtualbox.org/wiki/Downloads) >= 5.0
* [ansible](https://github.com/ansible/ansible) `brew install ansible`
* [vagrant](https://www.vagrantup.com/) >= 2.1.0
* Vagrant plugins:
  * [vagrant-hostmanager](https://github.com/smdahlen/vagrant-hostmanager) `vagrant plugin install vagrant-hostmanager`
  * [vagrant-auto_network](https://github.com/oscar-stack/vagrant-auto_network) `vagrant plugin install vagrant-auto_network`

If you update Vagrant, you may need to update your vagrant plugins with `vagrant plugin update`.

## Getting Started

1. Clone the project from github: `git clone https://github.com/palantirnet/your-project.git`
1. From inside the project root, run:

  ```
    composer install
    vagrant up
  ```
3. You will be prompted for the administration password on your host machine
4. Log in to the virtual machine (the VM): `vagrant ssh`
5. From within the VM, build and install the Drupal site: `phing install migrate`
1. Visit your site at [your-project.local](http://your-project.local)

## How do I work on this?

You can edit code, update documentation, and run git commands by opening files directly from your host machine.

To run project-related commands other than `vagrant up` and `vagrant ssh`:

* SSH into the VM with `vagrant ssh`
* You'll be in your project root, at the path `/var/www/your-project.local/`
* You can run `composer`, `drush`, and `phing` commands from here

To work on the styleguide:

* Go to the styleguide directory: `cd styleguide`; you'll be at the path `/your-project/styleguide`
* You can run `yarn install` & `yarn serve` from here, then view the styleguide in your browser at [your-project.local:3000](http://your-project.local:3000)

## Drupal Development

You can refresh/reset your local Drupal site at any time. SSH into your VM and then:

1. Download the most current dependencies: `composer install`
2. Rebuild your local CSS and Drupal settings file: `phing build`
3. Reinstall Drupal: `phing install` (this will run `build` implicitly)
4. Run your migrations: `phing migrate`
5. ... OR run all three phing targets at once: `phing install migrate` (again, `install` runs `build` for you)

Additional information on developing for Drupal within this environment is in [docs/general/drupal_development.md](docs/general/drupal_development.md).

## Styleguide Development

* `cd styleguide` on your host machine
* `yarn install`
* `yarn serve`
* control+c to stop

For additional documentation, refer to [styleguide/README.md](styleguide/README.md) and [styleguide/docs/*](styleguide/docs/*).

## Deployment

@todo This section needs to be customized per-project.

## Additional Documentation

Project-specific:

* [Technical Approach](docs/technical_approach.md)

General:

* [Drupal Development](docs/general/drupal_development.md)

----
Copyright 2020 Palantir.net, Inc.
