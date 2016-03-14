# Drupal Skeleton

A template for Drupal 8 projects.

## Starting a project

1. Run `composer create-project palantirnet/drupal-skeleton PROJECTNAME dev-drupal8 --repository=https://palantirnet.github.io/the-build/packages.json`.
 * Say 'yes' to removing the existing VCS files.
1. `cd` in to your new PROJECTNAME directory
1. Run `composer drupal-scaffold`
1. To add vagrant, run `vendor/bin/phing -f vendor/palantirnet/the-vagrant/tasks/vagrant.xml -Dprojectname=PROJECTNAME -Dcopy=n`
1. Run `git init`
1. Add everything to your new git repository, commit, and push to GitHub


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
