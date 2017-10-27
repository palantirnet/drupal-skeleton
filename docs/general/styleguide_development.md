# Styleguide Development

The styleguide uses Palantir's [Butler](https://github.com/palantirnet/butler) to automate front-end development tasks and streamline prototyping. Butler is a set of Gulp commands that manages Sass and Spress to compile and serve the styleguide on your local environment.

## Butler Commands

From within the VM, go to the `styleguide` directory with `cd styleguide`. Then run:

* `npm run butler`

  This is the default task. This will watch your sass/spress files for changes and compile/build accordingly. It will also flag any sass linting errors before compiling. It will output CSS that has been been minified and optimized. While butler is running, the styleguide is available at [your-site.local:4000](http://your-site.local:4000).

* `npm run butler -- sass`

  Just compile the sass. You can also use this syntax to run any task from the Gulpfile.

* `npm run tests`

  Run sass, HTML, and WCAG 2.0AA compliance linters separately. To learn more about configuring and customizing the linters for Butler check the [linters documentation](https://github.com/palantirnet/butler/blob/master/docs/LINTERS.md). WCAG 2.0AA compliance uses the [gulp-accessibility](https://github.com/yargalot/gulp-accessibility) plugin.

* `npm run deploy`

  Deploy the static styleguide to GitHub pages.

  Butler will build a Spress production artifact to `styleguide/output_prod` and deploy the production artifact to `gh-pages` branch of the repo defined in the `conf/butler.defaults.js`. Each commit for this process will default to the message: "Updated with Butler - [timestamp]".
  
  *Note: Make sure that all of your changes are committed to git and pushed to GitHub before running this command, so that you don't deploy styleguide changes that are not available to other developers.*

  *Note: When you are deploying, Butler will ask you for your GitHub credentials at least once, possibly multiple times. Enter your own GitHub credentials as prompted.*

----
Copyright 2017 Palantir.net, Inc.
