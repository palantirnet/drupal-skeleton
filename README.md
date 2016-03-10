# Drupal Skeleton

A template for Drupal 8 projects.

## Starting a project

1. Download the `packages.json` file from this repository, or get the raw url to it on github (`PACKAGES_JSON`)
2. Decide on a name for your new project (`PROJECTNAME`)
1. Run `composer create-project palantirnet/drupal-skeleton PROJECTNAME dev-drupal8 --repository=PACKAGES_JSON`.
 * Say 'yes' to removing the existing VCS files.
2. `cd` in to your new directory
3. Remove the `packages.json` file
3. Run `composer drupal-scaffold`
4. Run `git init`
5. Add everything to your new git repository, commit, and push to GitHub
