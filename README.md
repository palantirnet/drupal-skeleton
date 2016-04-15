# Drupal Skeleton

A template for Drupal 8 projects.

## Starting a project

Use the `composer create-project` command to create a project based on this repository, then use the install scripts from `the-vagrant` and `the-build` to add our vagrant configuration and build scripts--if you want them! Check the whole pile into your new project repository.

Step-by-step:

1. Run `composer create-project palantirnet/drupal-skeleton PROJECTNAME dev-drupal8 --no-interaction --repository=https://palantirnet.github.io/drupal-skeleton`.
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

After installing Drupal, you might want to export the default config with `drush config-export` (in our case using the verbose, project-specific version of this command: `vendor/bin/drush -c conf/drushrc.vagrant.php config-export`). This will create a bunch of YAML files in `conf/drupal/sync`. Check these in to the repository in a separate commit so that your project starts with an explicit default config. This will make it easier for developers to make granular config updates during the first development tickets of the project. See [the d8-lab](https://github.com/palantirnet/d8-lab/blob/master/managing-config.md) for more info about managing config.

## Other stuff

* You can remove or re-install the vagrant config any time, if you change your mind about customizing the Ansible provisioning for your project.
* You can add build configuration for more environments with `vendor/bin/phing -f vendor/palantirnet/the-build/tasks/install.xml configure` (or just by copying the `conf/build.*.properties file`)
* Try `vendor/bin/drush -c conf/drushrc.php status` (sorry, that's long)
* If the PHP date.timezone is not set on your *host* machine, when you run `composer create-project`, there will be a big red message at the end of the output, `ERROR: date_default_timezone_get(): It is not safe to rely on the system's [...] in phar:///usr/local/bin/composer/src/Composer/Util/Silencer.php:67`. To avoid this error, set the php date.timezone in `/etc/php.ini`:
```
[Date]
; Defines the default timezone used by the date functions
; http://php.net/date.timezone
date.timezone = "America/Chicago"
```

## See also

* [the-build](https://github.com/palantirnet/the-build)
* [the-vagrant](https://github.com/palantirnet/the-vagrant)
