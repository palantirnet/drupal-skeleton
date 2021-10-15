# { Your Project Name }

This is the development repository for { your project's } Drupal website. It contains the codebase and an environment to run the site for development.

## Table of Contents

* [Development Environment](#development-environment)
* [Getting Started](#getting-started)
* [How do I work on this?](#how-do-i-work-on-this)
* [Drupal Development](#drupal-development)
* [Styleguide Development](#styleguide-development)
* [Deployment](#Deployment)
* [Additional Documentation](#additional-documentation)

## Development Environment

This project uses [ddev](https://ddev.com/ddev-local/) for its development environment. To run the environment, you will need:

* [Composer](https://getcomposer.org/download/)
* [Docker](https://www.docker.com/)
  * Docker can be installed with [homebrew](https://brew.sh/): `brew install docker --cask`
* [DDev Local](https://ddev.com/ddev-local/)
  * ddev can be installed with [homebrew](https://brew.sh/): `brew install ddev`

## Getting Started

1. Clone the project from github: `git clone https://github.com/palantirnet/your-project.git`
1. From inside the project root, run:

  ```
    composer install
    ddev start
  ```
3. Log in to the ddev environment: `ddev ssh`
4. From within ddev, build and install the Drupal site: `vendor/bin/phing install migrate`
5. Visit your site at [your-project.ddev.site](http://your-project.ddev.site)

## How do I work on this?

You can edit code, update documentation, and run git commands by opening files directly from your machine.

To run site management commands like `drush status` or `phing install`:

* Start the development environment with `ddev start`
* SSH into the development environment with `ddev ssh`
* You'll be in your project root, at the path `/var/www/html/`
* You can run `composer`, `drush`, and `phing` commands from here

To work on the styleguide:

* Go to the styleguide directory: `cd styleguide`; you'll be at the path `/your-project/styleguide`
* You can run `yarn install` & `yarn serve` from here, then view the styleguide in your browser at [your-project.local:3000](http://your-project.local:3000)

## Drupal Development

You can refresh/reset your local Drupal site at any time. SSH into your ddev environment and then:

1. Download the most current dependencies: `composer install`
2. Rebuild your local CSS and Drupal settings file: `phing build`
3. Reinstall Drupal: `phing install` (this will run `build` implicitly)
4. Run your migrations: `phing migrate`
5. ... OR run all three phing targets at once: `phing install migrate` (again, `install` runs `build` for you)

Additional information on developing for Drupal within this environment is in [docs/general/drupal_development.md](docs/general/drupal_development.md).

## Styleguide Development

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
Copyright 2021 Palantir.net, Inc.
