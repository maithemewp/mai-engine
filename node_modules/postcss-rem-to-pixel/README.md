# postcss-rem-to-pixel [![NPM version](https://badge.fury.io/js/postcss-rem-to-pixel.svg)](http://badge.fury.io/js/postcss-rem-to-pixel)

A plugin for [PostCSS](https://github.com/ai/postcss) that generates px units from rem units.

## Install

```shell
$ npm install postcss-rem-to-pixel --save-dev
```

## Usage

Sometimes you need to include a third-party css file that uses rems.  Great pracitice!  Unless you can't afford to change your body font-size just for some vendor.  This script converts every rem value to a px value from the properties you choose using a default font size of 16px.


### Input/Output

*With the default settings, only font related properties are targeted.*

```css
// input
h1 {
    margin: 0 0 20px;
    font-size: 2rem;
    line-height: 1.2;
    letter-spacing: 0.0625rem;
}

// output
h1 {
    margin: 0 0 20px;
    font-size: 32px;
    line-height: 1.2;
    letter-spacing: 1px;
}
```

### Example

```js
var fs = require('fs');
var postcss = require('postcss');
var remToPx = require('postcss-rem-to-pixel');
var css = fs.readFileSync('main.css', 'utf8');
var options = {
    replace: false
};
var processedCss = postcss(remToPx(options)).process(css).css;

fs.writeFile('main-px.css', processedCss, function (err) {
  if (err) {
    throw err;
  }
  console.log('Rem file written.');
});
```

### options

Type: `Object | Null`  
Default:
```js
{
    rootValue: 16,
    unitPrecision: 5,
    propList: ['font', 'font-size', 'line-height', 'letter-spacing'],
    selectorBlackList: [],
    replace: true,
    mediaQuery: false,
    minRemValue: 0
}
```

- `rootValue` (Number) The root element font size.
- `unitPrecision` (Number) The decimal precision px units are allowed to use, floored (rounding down on half).
- `propList` (Array) The properties that can change from rem to px.
    - Values need to be exact matches.
    - Use wildcard `*` to enable all properties. Example: `['*']`
    - Use `*` at the start or end of a word. (`['*position*']` will match `background-position-y`)
    - Use `!` to not match a property. Example: `['*', '!letter-spacing']`
    - Combine the "not" prefix with the other prefixes. Example: `['*', '!font*']` 
- `selectorBlackList` (Array) The selectors to ignore and leave as rem.
    - If value is string, it checks to see if selector contains the string.
        - `['body']` will match `.body-class`
    - If value is regexp, it checks to see if the selector matches the regexp.
        - `[/^body$/]` will match `body` but not `.body`
- `replace` (Boolean) replaces rules containing rems instead of adding fallbacks.
- `mediaQuery` (Boolean) Allow rem to be converted in media queries.
- `minRemValue` (Number) Set the minimum rem value to replace.


### Use with gulp-postcss and autoprefixer

```js
var gulp = require('gulp');
var postcss = require('gulp-postcss');
var autoprefixer = require('autoprefixer');
var remToPx = require('postcss-rem-to-pixel');

gulp.task('css', function () {

    var processors = [
        autoprefixer({
            browsers: 'last 1 version'
        }),
        remToPx({
            replace: false
        })
    ];

    return gulp.src(['build/css/**/*.css'])
        .pipe(postcss(processors))
        .pipe(gulp.dest('build/css'));
});
```

### A message about ignoring properties
Currently, the easiest way to have a single property ignored is to use a capital in the rem unit declaration.

```css
// `rem` is converted to `px`
.convert {
    font-size: 1rem; // converted to 16px
}

// `Rem` or `REM` is ignored by `postcss-rem-to-pixel` but still accepted by browsers
.ignore {
    border: 1Rem solid; // ignored
    border-width: 2REM; // ignored
}
```
