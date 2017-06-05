# Drupal Skeleton

This is a template for Drupal 8 projects using the `composer create-project` command.

## Starting a project

1. Use composer to create a new project based on this skeleton:

  ```
  composer create-project palantirnet/drupal-skeleton PROJECTNAME dev-drupal8 --no-interaction
  ```
2. Go into the project:

  ```
  cd PROJECTNAME
  ```
3. Update the `README`:
  * Remove the `README.md`
  * Rename the `README.dist.md` to `README.md`
  * Edit as you like
4. Update the `composer.json`:
  * Change the `name` from `palantirnet/drupal-skeleton` to `palantirnet/PROJECTNAME`
  * Update the `description` with a brief description of your project.
  * Update the lock file so composer doesn't complain:

    ```
    composer update --lock
    ```
5. Add our Vagrant development environment:

  ```
  vendor/bin/the-vagrant-installer
  ```
6. Add our build scripts:

  ```
  vendor/bin/the-build-installer
  ```
7. Initialize git and commit your work to the `develop` branch:

  ```
  git init
  git checkout -b develop
  git commit --allow-empty -m "Initial commit."
  git add --all
  git commit -m "Add the skeleton."
  ```
8. Push your work up to an empty repository on GitHub

  ```
  git remote add origin git@github.com:palantirnet/PROJECTNAME.git
  git push -u origin develop
  ```

Now you should be ready to follow the instructions in YOUR `README.md` to start up the project. You'll probably want to do the initial Drupal installation at this point to generate a set of Drupal config files.

9. Start up your Vagrant VM:

  ```
  vagrant up
  vagrant ssh
  ```
10. Install Drupal:

  ```
  vendor/bin/phing build install
  ```
11. Log into Drupal in your browser and do some basic config customizations:

  * Set the site timezone
  * Disable per-user timezones
  * Disable user account creation
  * Remove unnecessary content types
  * Set the admin email address (your VM will trap all emails)
  * Turn the Cron interval down to "never"
  * Uninstall unnecessary modules (e.g. Search, History, Comment)
12. Export your config:

  ```
  drush cex -y
  ```
13. Update the install profile in your default build properties (`conf/build.default.properties`):

  ```
  drupal.install_profile=config_installer
  ```
14. You should have a ton of new `*.yml` files in `conf/drupal/config`. Add them, and this config change, to git:

  ```
  git add conf/
  git ci -m "Initial Drupal configuration."
  git push
  ```
15. Reinstall your site and verify that your config is ready to go:

  ```
  vendor/bin/phing build install
  ```

## More information

* Site build and install process: [the-build](https://github.com/palantirnet/the-build)
* Development environment setup: [the-vagrant](https://github.com/palantirnet/the-vagrant)
* Managing config: [the d8-lab](https://github.com/palantirnet/d8-lab/blob/master/managing-config.md)
