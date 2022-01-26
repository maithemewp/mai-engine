# Mai Engine

The required plugin to power Mai themes.

## Development

Mai Engine makes use of two package managers, NPM for JavaScript and Composer for PHP packages.

### Handy CLI commands

**Important:** Remember to specify the `--url` parameter if on multisite. E.g `--url=demo.bizbudding.com/success-business`.

- `wp mai generate` - Generates starter content for a site (Home page etc).
- `wp option delete 'mai-engine'` - Deletes all setting options.

### Setup the development environment

1. Clone this repository into your WordPress site's `plugins` directory.

    ```shell
    git clone https://github.com/maithemewp/mai-engine.git
    ```

2. Change directories into the plugin folder from the command line:

    ```shell
    cd mai-engine
    ```

3. Install Composer and any PHP dependencies with the following command:

    ```shell
    composer install
    ```

    *Please note that this step requires that you have Composer installed globally on your machine. We recommend using Homebrew to install Composer: `brew install composer`*

    ```shell
    export PATH="$HOME/.composer/vendor/bin:$PATH"
    ```

4. Install Node packages:

    ```shell
    npm install
    ```

    *Please note that this step requires that you have Node installed globally on your machine. We recommend using Homebrew to install Node: `brew install node`*


### Composer scripts

Mai Engine uses PHP Code Sniffer for linting and fixing coding standards. To lint all PHP files against WordPress coding standards run the following command:

```shell
composer phpcs
```

To have PHP Code Sniffer attempt to automatically fix any warnings run the following:

```shell
composer phpcbf
```

### NPM scripts

Mai Engine utilizes Gulp and Sass to automate tedious tasks, such as automatically generating the many stylesheets required by the child themes.

First you will need to install NPM on your machine:

```shell
brew install npm
```

It is also recommended to install NVM (Node Version Manager) to allow easy switching of Node versions:

```shell
brew install nvm
```

Next, install the Gulp CLI globally on your machine. To install Gulp CLI run the following command from the terminal:

```shell
 sudo npm install gulp-cli -g
```

Now that all of the global packages are installed, navigate to the root directory of this plugin, e.g:

```shell
cd Sites/my-project/wp-content/plugins/mai-engine
```

From there, make sure that Node is running the correct version (11.15.0). To do this, you will first need to run some commands to configure nvm correctly. A simple composer script is provided:

```shell
composer setup-nvm
```

#### Gulp

Once the Gulp CLI and Node packages have been installed, you are ready to begin using the following Gulp tasks to automate development:

**Default**

Running the default gulp task will kick of development and Gulp will watch files for changes. When a change to a file is detected Gulp will run the build tasks and recompile assets.

```shell
gulp
```

**CSS**

```shell
gulp build:css
```

**JS**

```shell
gulp build:js
```

**To create a new engine theme**
```shell
gulp create --name=themename --composer
```

### Using the CSS system

Goals of the CSS system: Keep it DRY. Prioritize performance.

Please note: files in the `assets/css/` directory should never be edited directly as any changes will be overridden when running the gulp build task. All changes should be made to the SCSS files in the `assets/scss/` directory and then compiled using the `gulp build:css` command.

**Organization**

This project follows the ITCSS principal to organize the CSS files in such a way that they can better deal with CSS specificity. One of the key principles of ITCSS is that it separates your CSS codebase to several sections (called layers), which take the form of the inverted triangle. The structure is also based on the [Sass Guidelines](https://sass-guidelin.es/). More information about ITCSS can be found [here](https://www.xfive.co/blog/itcss-scalable-maintainable-css-architecture/).

- **Abstracts** – used with preprocessors and contain font, colors definitions, globally used mixins and functions. It’s important not to output any CSS in this layer.
- **Base** – reset and/or normalize styles, box-sizing definition, styling for bare HTML elements (like H1, A, etc.). These come with default styling from the browser so we can redefine them here. This is the first layer which generates actual CSS.
- **Layout** – the layout/ folder contains everything that takes part in laying out the site or application. These elements are usually only in one place and contain multiple components.
- **Components** – specific UI components. This is where the majority of our work takes place and our UI components are often composed of Objects and Components
- **Utilities** – utilities and helper classes with ability to override anything which goes before in the triangle, eg. hide helper class
- **Plugins** - styling for third party plugins. Not imported in the main stylesheet.
- **Themes** - theme specific styling. Should only contain custom property overrides if possible. Should be thought of as a config file.

### Color variables

Mai Engine uses both `element-color` and `color-element` naming convention for color variables, here's an explanation on how to use them:

#### var(--color-element)

These custom properties are the ones automatically generated by the theme config and Customizer settings. They should be thought of as the "color palette" and never be changed directly via CSS.

In the theme or engine CSS we use them in places like this:

```css
/* Correct way to set a dark background for the site footer */
.site-footer {
    background-color: var(--color-heading);
}
```

We would never want to change the values like this, as it could affect the parent element if it is using a `.has-heading-` utility class:

```css
/* Wrong way to make all headings in the site footer white */
.site-footer {
    --color-heading: var(--color-white);
}
```

#### var(--element-color)

These are the element custom properties that can be changed depending on their context, for example we can do this:

```css
/* Right way to make all headings in the site footer white */
.site-footer {
    --heading-color: var(--color-white);
}
```

But we shouldn't use them like this, because the custom property may not be set:

```css
/* Wrong way to set a dark background for the site footer */
.site-footer {
    background-color: var(--heading-color);
}
```

To summarise, we have the "color palette", which are globals and should never be changed in the CSS. They can only be changed from the theme config or the Customizer settings. Then we also have the "element properties" which should be used to change an elements styles depending on the context.
