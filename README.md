# Drupal Skeleton

A template for Drupal 8 projects.

## Starting a project

Use the `composer create-project` command to create a project based on this repository, then use the install scripts from `the-vagrant` and `the-build` to add our vagrant configuration and build scripts--if you want them! Check the whole pile into your new project repository.

Step-by-step:

1. Run `composer create-project palantirnet/drupal-skeleton PROJECTNAME dev-drupal8 --no-interaction --repository=https://palantirnet.github.io/the-build/packages.json`.
1. `cd` in to your new PROJECTNAME directory
1. To add vagrant, run `vendor/bin/phing -f vendor/palantirnet/the-vagrant/tasks/vagrant.xml`
1. To add the build, run `vendor/bin/phing -f vendor/palantirnet/the-build/tasks/install.xml`
1. Run `git init`
1. Add your github origin with `git remote add origin git@github.com:palantirnet/your-project.git`
1. Remove the `README.md` file
1. Pull the current master branch down with `git pull -u origin master`
1. Add everything and commit with `git add --all` and `git commit -m "Skeleton commit."`
1. `git push -u origin master`

Now you should have a fleshy skeleton. Your environment will spring to life with `vagrant up` and your Drupal will be ready to run with `vendor/bin/phing build drupal-install -Dbuild.env=vagrant`.

## Other stuff

* You can remove or re-install the vagrant config any time, if you change your mind about customizing the Ansible provisioning for your project.
* You can add build configuration for more environments with `vendor/bin/phing -f vendor/palantirnet/the-build/tasks/install.xml configure` (or just by copying the `conf/build.*.properties file`)
* Try `vendor/bin/drush -c conf/drushrc.php status` (sorry, that's long)

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
