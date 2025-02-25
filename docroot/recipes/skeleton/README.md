# Recipe: Drupal Skeleton

This is a replacement for the core 'Standard' recipe. The goal is to install and configure "just enough" that it feels like a complete Drupal site, while not installing things that will need to be removed or reconfigured later.

This recipe can be used on a fresh install:

```
drush site:install recipes/skeleton
```

... or applied to an existing site:

```
drush recipe recipes/skeleton
```

## What it does

* Installs basic modules
* Sets up administration UI
* Configures text formats
* Configures a "Basic page" content type
* Configures user and date settings
* Adds an initial shortcut set
