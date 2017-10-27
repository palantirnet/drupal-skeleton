# { Your Project Name }

This is the development repository for { your project's } Drupal 8 website. It contains the codebase and an environment to run the site for development.

## Table of Contents

* [Development Environment](#development-environment)
* [Getting Started](#getting-started)
* [How do I work on this?](#how-do-i-work-on-this)
* [Drupal Development](#drupal-development)
* [Deployment](#Deployment)
* [Styleguide Development](#styleguide-development)
* [Additional Documentation](#additional-documentation)

## Development Environment

The development environment is based on [palantirnet/the-vagrant](https://github.com/palantirnet/the-vagrant). To run the environment, you will need:

* Mac OS X >= 10.10. _This stack may run under other host operating systems, but is not regularly tested. For details on installing these dependencies on your Mac, see our [Mac setup doc [internal]](https://github.com/palantirnet/documentation/wiki/Mac-Setup)._
* [Composer](https://getcomposer.org)
* VMWare, or [virtualBox](https://www.virtualbox.org/wiki/Downloads) >= 5.0
* [ansible](https://github.com/ansible/ansible) `brew install ansible`
* [vagrant](https://www.vagrantup.com/) >= 1.8
* Vagrant plugins:
  * [vagrant-hostmanager](https://github.com/smdahlen/vagrant-hostmanager) `vagrant plugin install vagrant-hostmanager`
  * [vagrant-auto_network](https://github.com/oscar-stack/vagrant-auto_network) `vagrant plugin install vagrant-auto_network`
  * [vagrant-triggers](https://github.com/emyl/vagrant-triggers) `vagrant plugin install vagrant-triggers`

If you update Vagrant, you may need to update your vagrant plugins with `vagrant plugin update`.

## Getting Started

1. Clone the project from github: `git clone https://github.com/palantirnet/your-project.git`
1. From inside the project root, run:

  ```
    composer install --ignore-platform-reqs
    vagrant up
  ```
3. You will be prompted for the administration password on your host machine
4. Log in to the virtual machine (the VM): `vagrant ssh`
5. From within the VM, build and install the Drupal site: `phing build install migrate`
1. Visit your site at [drupal-skeleton.local](http://drupal-skeleton.local)

## How do I work on this?

You can edit code, update documentation, and run git commands by opening files directly from your host machine.

To run project-related commands other than `vagrant up` and `vagrant ssh`:

* SSH into the VM with `vagrant ssh`
* You'll be in your project root, at the path `/var/www/uw-stout.local/`
* You can run `composer`, `drush`, and `phing` commands from here

To work on the styleguide:

* SSH in to the VM with `vagrant ssh`
* Go to the styleguide directory: `cd styleguide`; you'll be at the path `/var/www/your-project.local/styleguide`
* You can run butler from here with `npm run butler`, then view the styleguide in your browser at [your-project.local:4000](http://your-project.local:4000)

Avoid committing to git from within your VM, because your commits won't be properly attributed to you. If you must, make sure you [create a global .gitignore [internal]](https://github.com/palantirnet/documentation/wiki/Using-the-gitignore-File) within your VM at `/home/vagrant/.gitignore`, and configure your name and email for proper attribution:

```
git config --global user.email 'me@palantir.net'
git config --global user.name 'My Name'
```

## Drupal Development

You can refresh/reset your local Drupal site at any time. SSH into your VM and then:

1. Download the most current dependencies: `composer install`
2. Rebuild your local CSS and Drupal settings file: `phing build`
3. Reinstall Drupal: `phing install`
4. Run your migrations: `phing migrate`
5. ... OR run all three phing targets at once: `phing build install migrate`

Additional information on developing for Drupal within this environment is in [docs/general/drupal_development.md](docs/general/drupal_development.md).

## Deployment

@todo This section needs to be customized per-project.

## Styleguide Development

* Serve the styleguide and watch for changes:
  * From your VM: `cd styleguide && npm run butler`
  * Visit [your-site.local:4000](http://your-site.local:4000)
  * Hit control+c to stop

Complete Butler usage is documented in [docs/general/styleguide_development.md](docs/general/styleguide_development.md).

## Additional Documentation

Project-specific:

* [Technical Approach](docs/technical_approach.md)

General:

* [Drupal Development](docs/general/drupal_development.md)
* [Styleguide Development](docs/general/styleguide_development.md)

----
Copyright 2017 Palantir.net, Inc.
