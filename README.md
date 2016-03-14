# Drupal Skeleton

A template for Drupal 8 projects.

## Starting a project

1. Run `composer create-project palantirnet/drupal-skeleton PROJECTNAME dev-drupal8 --repository=https://palantirnet.github.io/the-build/packages.json`.
 * Say 'yes' to removing the existing VCS files.
1. `cd` in to your new PROJECTNAME directory
1. To add vagrant, run `vendor/bin/phing -f vendor/palantirnet/the-vagrant/tasks/vagrant.xml`
1. To add the build, run `vendor/bin/phing -f vendor/palantirnet/the-build/tasks/install.xml`

Now you should have a fleshy skeleton. Your environment will spring to life with `vagrant up` and your Drupal will be ready to run with `vendor/bin/phing`.

To finish up:

1. Run `git init`
1. Add everything to your new git repository, commit, and push to GitHub

## Configuration

* You can remove or re-install the vagrant config any time, if you change your mind about customizing the Ansible provisioning for your project.
* You can add build configuration for more environments with `vendor/bin/phing -f vendor/palantirnet/the-build/tasks/install.xml configure` (or just by copying the `conf/build.*.properties file`)

## Known issues

If while running `composer create-project` you get the following error:

```
 [Composer\Downloader\TransportException]
  The "file:/packages.json" file could not be downloaded: failed to open stream: No such file or directory
```

Then you will need to download the packages.json file manually:

```
wget https://palantirnet.github.io/the-build/packages.json
composer create-project palantirnet/drupal-skeleton PROJECTNAME dev-drupal8 --repository=packages.json
```
