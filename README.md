# Mai Engine

The required plugin to power Mai themes.

## Development

Mai Engine makes use of two package managers, NPM for JavaScript and Composer for PHP packages.

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

4. Install Node packages:

    ```shell
    npm install
    ```
    
    *Please note that this step requires that you have Node installed globally on your machine. We recommend using Homebrew to install Node: `brew install node`*
    

### Using the Gulp workflow

Mai Engine utilizes Gulp and Sass to automate tedious tasks, such as automatically generating the many stylesheets required by the child themes.

To get started using Gulp and Sass, first ensure that you have the Gulp CLI installed globally on your machine. To install Gulp CLI simply run the following command from the terminal:

```shell
 sudo npm install gulp-cli -g
 ```

Once the Gulp CLI and Node packages have been installed, you are ready to begin using the following Gulp tasks to automate development:

**CSS** 

```shell
gulp build:css
``` 

**JS**

```shell
gulp build:js
```

### Using the CSS system

Goals of the CSS system: Keep it DRY. Prioritize performance.

Please note: files in the `assets/css/` directory should never be edited directly as any changes will be overridden when running the gulp build task. All changes should be made to the SCSS files in the `assets/scss/` directory and then compiled using the `gulp build:css` command.

**Organization**

This project follows the ITCSS principal to organize the CSS files in such a way that they can better deal with CSS specificity. One of the key principles of ITCSS is that it separates your CSS codebase to several sections (called layers), which take the form of the inverted triangle:

- **Settings** – used with preprocessors and contain font, colors definitions, etc.
- **Tools** – globally used mixins and functions. It’s important not to output any CSS in the first 2 layers.
- **Generic** – reset and/or normalize styles, box-sizing definition, etc. This is the first layer which generates actual CSS.
- **Elements** – styling for bare HTML elements (like H1, A, etc.). These come with default styling from the browser so we can redefine them here.
- **Components** – specific UI components. This is where the majority of our work takes place and our UI components are often composed of Objects and Components
- **Blocks** – similar to components but separated into their own directory because of the importance.
- **Plugins** - also similar to components except separated into their own directory.
- **Utilities** – utilities and helper classes with ability to override anything which goes before in the triangle, eg. hide helper class

More information about ITCSS can be found [here](https://www.xfive.co/blog/itcss-scalable-maintainable-css-architecture/).

**Progressive Loading of CSS**

Instead of loading one large stylesheet in the `<head>` of the website, Mai Engine splits up it's CSS into separate, componentized stylesheets that are only loaded when needed. Below is a list of the different components and the hooks at which they are loaded:

- **Editor**
    purpose: Provides theme styling to the block editor in the admin.
    hook: `enqueue_block_editor_assets`
    
- **Critical**
    purpose: Provides critical styles that are required before the header.
    hook: `wp_enqueue_scripts`
    
- **Header**
    purpose: Adds styling for the site header component.
    hook: `genesis_before_header`
    
- **Desktop**
    purpose: Adds desktop styles for the navigation menu.
    hook: `mai_after_title_area`
    
- **Hero**
    purpose: Adds styling for the hero section.
    hook: `genesis_before_content_sidebar_wrap`
    
- **Content**
    purpose: Adds styling for the content area.
    hook: `genesis_before_content`
    
- **Comments**
    purpose: Adds styling for the comments area.
    hook: `genesis_before_comments`
    
- **Sidebar**
    purpose: Adds styling for the sidebar and widgets.
    hook: `genesis_before_sidebar_widget_area`
    
- **Footer**
    purpose: Adds styling for the site footer.
    hook: `genesis_before_footer`
    
### Variables

Mai Engine has 3 types of variables, all of which have different purposes:

#### JSON variables
    
JSON files can be read by both Gulp and PHP, which means they can be written in one place but accessible in all areas of the project.
    
Theme JSON variables are defined in the `config/theme-name/config.json` file.

##### JSON variables in SCSS

Any variables defined in the JSON file are usable in SCSS files and will be processed by Gulp during compilation. For example, define some colors:

```json
{
  "color-primary": "#fb2056",
  "color-heading": "#232c39"
}
```

These can be used in SCSS files in the following way:

```scss
body {
    color: $color-primary;
}

h1 {
    color: $color-heading;
}
```

##### JSON variables in PHP

To access the JSON variables with PHP, use the following helper function:

```php
mai_get_variable( 'color-primary' );
```

This will check the currently active child themes config for the variable, and if it's not found will use the default config variable. 
    
#### SCSS variables

While JSON variables can be used in SCSS, not all SCSS variables need to be defined in the JSON config. The majority of variables used throughout the SCSS framework are defined in the `assets/scss/settings` directory.
    
#### CSS Custom Properties
    
CSS Custom Properties are different to SCSS variables in that they can be changed at runtime, unlike SCSS variables which are compiled prior to deployment. That being said, Custom Properties work with SCSS, as seen in this project. 

Mai Engine only uses CSS Custom Properties where absolutely necessary as to not hinder performance. Our goal is to use no more than 100 if possible.

All CSS Custom Properties are defined in one place in the plugin, the `assets/scss/generic/_custom-properties.scss` file. You may be wondering why they are not also defined in the `assets/scss/settings` directory along with the other SCSS variables. The reason for this is that CSS Custom Properties output CSS code on the front end, whereas the `assets/scss/settings` directory does not output any actual CSS. 
