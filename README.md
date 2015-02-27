# Skeleton

##Requirements

------------
* [virtualBox](https://www.virtualbox.org/wiki/Downloads) >= 4.3.x
* [vagrant](http://downloads.vagrantup.com/) >= 1.6.x
* [vagrant-hostsupdater](https://github.com/cogitatio/vagrant-hostsupdater) `vagrant plugin install vagrant-hostsupdater`

## Getting Started

------------------

1. From inside the project root, run `vagrant up`
2. You will be prompted for the administration password on your host machine. Obey.
3. Visit `default.dev` in your browser of choice.

##How do I work on this?

------------------

1. From inside the project root `vagrant ssh`
2. Navigate to `/var/www/sites/default.dev`

There is your project. Run `drush` commands from the `www` directory just like you would without a VB.
