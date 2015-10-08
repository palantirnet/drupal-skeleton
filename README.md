# Drupal Skeleton

## Requirements

------------
* [virtualBox](https://www.virtualbox.org/wiki/Downloads) >= 4.3.x
* [vagrant](http://downloads.vagrantup.com/) >= 1.7.x
* [vagrant-hostmanager](https://github.com/smdahlen/vagrant-hostmanager)
  * Install with: `vagrant plugin install vagrant-hostmanager`
* [vagrant-auto_network](https://github.com/oscar-stack/vagrant-auto_network) `vagrant plugin install vagrant-auto_network`

If you have been running a previous version of Vagrant you may need to do: `vagrant plugin update` to ensure that you can install the plugins.

## Getting Started Using the Skeleton

------------------

1. Clone this repo and blow away its `.git`.
2. Name your project in the `Vagrantfile` (line 6).
3. Make `README.dist.md` your own project's `README.md`.
4. Run `vagrant up` and if all went well, you can visit `YOURPROJECT.local` in your brower of choice.
5. Rename all the things (see some bash hints below, which can be run inside your project root in vagrant).

Rename things the bash way:

````````````
rename "s/skeleton/YOURPROJECT/" *.*
sed -i 's/skeleton/YOURPROJECT/g' *.*
sed -i -- 's/skeleton/YOURPROJECT/g' **/*

````````````````

## Default Environment Information

------------------

### Virtual Machine

* Base Memory: ``2048``

### SSH

* Port: ``22``
* Username: ``vagrant``
* Password: ``vagrant``
* Private Key: *The default insecure private key that ships with Vagrant*

### MySQL

* Port: ``3306``
* Root Username: ``root``
* Root Password: ``pass``

## Anatomy of Drupal Skeleton

------------------

### Vagrant

Managed with one file: ``Vagrantfile``
*customize* by naming your project.

### Testing

**Managed with**
* ``behat.yml``
* ``circle.yml``
* ``features``

**customize by**
* Editing ``behat.yml``
* make your build explicit in ``circle.yml``
* add your acceptance testing inside ``features``

### Composer

**Manage with**
* ``composer.json``

**customize by**
* Adding project dependencies.

NOTE:
After successful update/install, `bin/wrapper` is called to create `www`.

### Managing Dependencies

**Manage with**
* ``skeleton.info``
* ``skeleton.module`` (only necessary because Drupal says it is)
* ``env.dist``

**customize by**
* specifying your project's dependencies inside the ``.info``
* distinguish dev requirements and prod by adding a separate module for just dev
* use the `env.dist` to specify which of these modules should be enabled

NOTE:
In the `Vagrantfile`, the `env.dist` is copied to `.env`. It is this file that is sourced on the build.
`.env` should not be on version control. Your dev environment will be different than prod.

### Build

**Manage with**
* ``install.sh``
* ``update.sh``
* ``common.sh``
* ``scripts``

**customize by**
* Drupal is initialized with `install.sh` using the minimal profile.
* After the install, a series of commands are run to actually configure Drupal.
* Variables are set by sourcing scripts inside `scripts`.
