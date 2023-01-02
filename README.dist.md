# { Your Project Name }

This is the development repository for { your project's } Drupal website. It contains the codebase and an environment to run the site for development.

## Table of Contents

* [Development Environment](#development-environment)
* [Getting Started](#getting-started)
* [Drupal Development](#drupal-development)
* [Styleguide Development](#styleguide-development)
* [Deployment](#Deployment)
* [Additional Documentation](#additional-documentation)

## Development Environment

This project uses [ddev](https://ddev.com/ddev-local/) for its development environment. To run the environment, you will need:

* [Composer](https://getcomposer.org/download/)
* [Docker](https://www.docker.com/)
* [DDev Local](https://ddev.com/ddev-local/)

Docker and DDev can be installed with [homebrew](https://brew.sh/):

```
brew install docker --cask
brew install ddev
```

## Getting Started

1. Clone the project from github: `git clone https://github.com/palantirnet/your-project.git`
2. From inside the project root, run:

  ```
    composer install
    ddev start
  ```
3. Install the Drupal site: `vendor/bin/phing install` (this can be run inside or outside of ddev)
4. Visit your site at [your-project.ddev.site](http://your-project.ddev.site)

## Drupal Development

You can refresh/reset your local Drupal site at any time. SSH into your ddev environment and then:

1. Download the most current dependencies: `composer install`
2. Rebuild your local CSS and Drupal settings file: `phing build`
3. Reinstall Drupal: `phing install` (this will run `build` implicitly)
4. Run your migrations: `phing migrate`
5. ... OR run all three phing targets at once: `phing install migrate` (again, `install` runs `build` for you)

* Shared development environment settings are committed to git in `docroot/sites/default/settings.ddev-overrides.php`
* To customize local development settings, copy:
  * `docroot/sites/default/default.settings.local.php` to `settings.local.php`
  * `docroot/sites/default/default.services.local.yml` to `services.local.yml`
* Config is exported to the `config/` directory; `config_split` module is used to manage environment-specific config
* The `artifacts/` directory can be used to store files and database dumps that should not be checked in to git

Additional information on developing for Drupal within this environment is in [docs/general/drupal_development.md](docs/general/drupal_development.md).

## Styleguide Development

@todo This section needs to be customized per-project.

* `cd styleguide` on your host machine
* `yarn install`
* `yarn serve`
* control+c to stop

For additional documentation, refer to [styleguide/README.md](styleguide/README.md) and [styleguide/docs/*](styleguide/docs/*).

## Deployment

@todo This section needs to be customized per-project.

## Additional Documentation

Project-specific:

* [Technical Approach](docs/technical_approach.md)

General:

* [Drupal Development](docs/general/drupal_development.md)

----
Copyright 2022 Palantir.net, Inc.
