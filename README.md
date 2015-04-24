# Drupal Skeleton

## Requirements

------------
* [virtualBox](https://www.virtualbox.org/wiki/Downloads) >= 4.3.x
* [vagrant](http://downloads.vagrantup.com/) >= 1.6.x
* [vagrant-hostmanager](https://github.com/smdahlen/vagrant-hostmanager)
  * Install with: `vagrant plugin install vagrant-hostmanager`

  Note: On OSX, if installing vagrant-hostmanager fails with an error like:
  ```
  Bundler, the underlying system Vagrant uses to install plugins,
  reported an error. The error is shown below. These errors are usually
  caused by misconfigured plugin installations or transient network
  issues. The error from Bundler is:

  An error occurred while installing nokogiri (1.6.6.2), and Bundler cannot continue.
  Make sure that `gem install nokogiri -v '1.6.6.2'` succeeds before bundling.
  ```

  ...and `gem install nokogiri -v '1.6.6.2'` does not resolve the problem, try `gem install nokogiri -v '1.6.6.2' -- --use-system-libraries --with-iconv-dir=/usr/local/Cellar/libiconv/1.14 --with-xml2-include=/usr/include/libxml2 --with-xml2-lib=/usr/lib/`

  @see https://github.com/sparklemotion/nokogiri/issues/1166 for details.

## Getting Started Using the Skeleton

------------------

1. Clone this repo and blow away its `.git`.
2. Name your project in the `Vagrantfile` (line 6).
3. Update the IP Address from the [next available](https://github.com/palantirnet/palantir-maker-box/wiki/Vagrant-IP-Address) and update the wiki page
4. Make `README.dist.md` your own project's `README.md`.
5. Run `vagrant up` and if all went well, you can visit `YOURPROJECT.dev` in your brower of choice.
6. Rename all the things (see some bash hints below, which can be run inside your project root in vagrant).

Rename things the bash way:

````````````
rename "s/skeleton/YOURPROJECT/" *.*
sed -i 's/skeleton/YOURPROJECT/g' *.*
sed -i -- 's/skeleton/YOURPROJECT/g' **/*

````````````````

## Default Environment Information

------------------

### Virtual Machine

* IP Address: ``10.33.36.12``
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
* ``.travis.yml``
* ``features``

**customize by**
* Editing ``behat.yml``
* make your build explicit in ``.travis.yml``
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

